/**
 * OSSZ Atelier — database seed.
 * Creates staff accounts, products with garment components, customers, and a
 * set of orders with realistic production-task history at various workflow stages.
 *
 * Run: bun prisma/seed.ts
 */
import { PrismaClient } from "@prisma/client";
import { randomBytes, scryptSync } from "crypto";

const db = new PrismaClient();

function hashPassword(password: string): string {
  const salt = randomBytes(16).toString("hex");
  const hash = scryptSync(password, salt, 64).toString("hex");
  return `${salt}:${hash}`;
}

const now = () => new Date();
const daysAgo = (n: number, h = 10) => {
  const d = new Date();
  d.setDate(d.getDate() - n);
  d.setHours(h, 0, 0, 0);
  return d;
};
const daysFromNow = (n: number, h = 17) => {
  const d = new Date();
  d.setDate(d.getDate() + n);
  d.setHours(h, 0, 0, 0);
  return d;
};

// ---------------------------------------------------------------------------
// Production-task state machine used to build realistic history in the seed.
// It mirrors the server-side workflow logic.
// ---------------------------------------------------------------------------

type Action =
  | { kind: "assign"; at: Date; staffId: string; byId: string; dueAt?: Date; reason?: string }
  | { kind: "progress"; at: Date; byId: string; to: number; note?: string; issueReason?: string }
  | { kind: "submit"; at: Date; byId: string; note?: string }
  | {
      kind: "inspect";
      at: Date;
      byId: string;
      result: "APPROVED" | "CHANGES_REQUESTED";
      notes?: string;
      correctionInstructions?: string;
    }
  | { kind: "reopen"; at: Date; byId: string; reason: string }
  | { kind: "block"; at: Date; byId: string; reason: string }
  | { kind: "resolve"; at: Date; byId: string; note?: string };

async function seedTask(opts: {
  orderId: string;
  orderItemId: string;
  pieceName: string;
  instructions?: string | null;
  createdBy: string;
  createdAt: Date;
  actions: Action[];
}): Promise<string> {
  const task = await db.productionTask.create({
    data: {
      orderId: opts.orderId,
      orderItemId: opts.orderItemId,
      pieceName: opts.pieceName,
      productionInstructions: opts.instructions ?? null,
      status: "UNASSIGNED",
      progressPercentage: 0,
      createdAt: opts.createdAt,
      updatedAt: opts.createdAt,
    },
  });

  await db.productionEvent.create({
    data: {
      taskId: task.id,
      orderId: opts.orderId,
      eventType: "TASK_CREATED",
      actorId: opts.createdBy,
      newValue: opts.pieceName,
      createdAt: opts.createdAt,
    },
  });

  let status = "UNASSIGNED";
  let progress = 0;
  let assignedStaffId: string | null = null;

  for (const a of opts.actions) {
    switch (a.kind) {
      case "assign": {
        const prevStaff = assignedStaffId;
        assignedStaffId = a.staffId;
        const newStatus = status === "UNASSIGNED" ? "ASSIGNED" : status;
        await db.productionTask.update({
          where: { id: task.id },
          data: {
            assignedStaffId: a.staffId,
            assignedById: a.byId,
            assignedAt: a.at,
            dueAt: a.dueAt ?? undefined,
            status: newStatus,
            updatedAt: a.at,
          },
        });
        await db.assignmentRecord.create({
          data: {
            taskId: task.id,
            previousStaffId: prevStaff,
            newStaffId: a.staffId,
            assignedById: a.byId,
            reason: a.reason ?? null,
            createdAt: a.at,
          },
        });
        await db.productionEvent.create({
          data: {
            taskId: task.id,
            orderId: opts.orderId,
            eventType: prevStaff ? "TASK_REASSIGNED" : "PIECE_ASSIGNED",
            actorId: a.byId,
            previousValue: prevStaff,
            newValue: a.staffId,
            reason: a.reason ?? null,
            createdAt: a.at,
          },
        });
        await db.notification.create({
          data: {
            userId: a.staffId,
            title: prevStaff
              ? `Task reassigned to you: ${opts.pieceName}`
              : `New task assigned: ${opts.pieceName}`,
            body: a.reason ?? null,
            taskId: task.id,
            orderId: opts.orderId,
            type: prevStaff ? "TASK_REASSIGNED" : "TASK_ASSIGNED",
            createdAt: a.at,
          },
        });
        status = newStatus;
        break;
      }
      case "progress": {
        const prevStatus = status;
        let newStatus = prevStatus;
        if (status === "ASSIGNED" && a.to > 0) newStatus = "IN_PROGRESS";
        if (status === "CHANGES_REQUESTED") newStatus = "IN_PROGRESS";
        await db.productionTask.update({
          where: { id: task.id },
          data: { progressPercentage: a.to, status: newStatus, updatedAt: a.at },
        });
        if (prevStatus === "ASSIGNED" && newStatus === "IN_PROGRESS") {
          await db.productionEvent.create({
            data: {
              taskId: task.id,
              orderId: opts.orderId,
              eventType: "PRODUCTION_STARTED",
              actorId: a.byId,
              createdAt: a.at,
            },
          });
        }
        await db.progressUpdate.create({
          data: {
            taskId: task.id,
            staffId: a.byId,
            previousProgress: progress,
            newProgress: a.to,
            previousStatus: prevStatus,
            newStatus,
            note: a.note ?? null,
            issueReason: a.issueReason ?? null,
            createdAt: a.at,
          },
        });
        await db.productionEvent.create({
          data: {
            taskId: task.id,
            orderId: opts.orderId,
            eventType: "PROGRESS_UPDATED",
            actorId: a.byId,
            previousValue: `${progress}%`,
            newValue: `${a.to}%`,
            reason: a.note ?? null,
            createdAt: a.at,
          },
        });
        if (a.to === 100) {
          await db.notification.create({
            data: {
              userId: opts.createdBy,
              title: `${opts.pieceName} reported at 100%`,
              taskId: task.id,
              orderId: opts.orderId,
              type: "PROGRESS_COMPLETE",
              createdAt: a.at,
            },
          });
        }
        status = newStatus;
        progress = a.to;
        break;
      }
      case "submit": {
        await db.productionTask.update({
          where: { id: task.id },
          data: { status: "AWAITING_INSPECTION", updatedAt: a.at },
        });
        await db.productionEvent.create({
          data: {
            taskId: task.id,
            orderId: opts.orderId,
            eventType: "SUBMITTED_FOR_INSPECTION",
            actorId: a.byId,
            reason: a.note ?? null,
            createdAt: a.at,
          },
        });
        await db.notification.create({
          data: {
            userId: opts.createdBy,
            title: `${opts.pieceName} submitted for inspection`,
            taskId: task.id,
            orderId: opts.orderId,
            type: "SUBMITTED_FOR_INSPECTION",
            createdAt: a.at,
          },
        });
        status = "AWAITING_INSPECTION";
        break;
      }
      case "inspect": {
        const approved = a.result === "APPROVED";
        await db.inspectionRecord.create({
          data: {
            taskId: task.id,
            inspectorId: a.byId,
            result: a.result,
            inspectionNotes: a.notes ?? null,
            correctionInstructions: a.correctionInstructions ?? null,
            inspectedAt: a.at,
          },
        });
        await db.productionTask.update({
          where: { id: task.id },
          data: { status: a.result, updatedAt: a.at },
        });
        await db.productionEvent.create({
          data: {
            taskId: task.id,
            orderId: opts.orderId,
            eventType: "INSPECTION_COMPLETED",
            actorId: a.byId,
            previousValue: "AWAITING_INSPECTION",
            newValue: a.result,
            reason: a.notes ?? null,
            createdAt: a.at,
          },
        });
        await db.productionEvent.create({
          data: {
            taskId: task.id,
            orderId: opts.orderId,
            eventType: approved ? "PIECE_APPROVED" : "CORRECTIONS_REQUESTED",
            actorId: a.byId,
            reason: approved ? a.notes ?? null : a.correctionInstructions ?? null,
            createdAt: a.at,
          },
        });
        if (assignedStaffId) {
          await db.notification.create({
            data: {
              userId: assignedStaffId,
              title: approved
                ? `${opts.pieceName} approved — great work`
                : `Corrections requested on ${opts.pieceName}`,
              body: approved ? a.notes ?? null : a.correctionInstructions ?? null,
              taskId: task.id,
              orderId: opts.orderId,
              type: approved ? "PIECE_APPROVED" : "CORRECTIONS_REQUESTED",
              createdAt: a.at,
            },
          });
        }
        status = a.result;
        break;
      }
      case "reopen": {
        await db.productionTask.update({
          where: { id: task.id },
          data: { status: "IN_PROGRESS", updatedAt: a.at },
        });
        await db.productionEvent.create({
          data: {
            taskId: task.id,
            orderId: opts.orderId,
            eventType: "TASK_REOPENED",
            actorId: a.byId,
            previousValue: "APPROVED",
            newValue: "IN_PROGRESS",
            reason: a.reason,
            createdAt: a.at,
          },
        });
        if (assignedStaffId) {
          await db.notification.create({
            data: {
              userId: assignedStaffId,
              title: `${opts.pieceName} reopened for corrections`,
              body: a.reason,
              taskId: task.id,
              orderId: opts.orderId,
              type: "TASK_REOPENED",
              createdAt: a.at,
            },
          });
        }
        status = "IN_PROGRESS";
        break;
      }
      case "block": {
        await db.productionTask.update({
          where: { id: task.id },
          data: {
            status: "BLOCKED",
            blockedReason: a.reason,
            blockedById: a.byId,
            blockedAt: a.at,
            updatedAt: a.at,
          },
        });
        await db.productionEvent.create({
          data: {
            taskId: task.id,
            orderId: opts.orderId,
            eventType: "PRODUCTION_BLOCKED",
            actorId: a.byId,
            reason: a.reason,
            createdAt: a.at,
          },
        });
        await db.notification.create({
          data: {
            userId: opts.createdBy,
            title: `${opts.pieceName} is blocked`,
            body: a.reason,
            taskId: task.id,
            orderId: opts.orderId,
            type: "TASK_BLOCKED",
            createdAt: a.at,
          },
        });
        status = "BLOCKED";
        break;
      }
      case "resolve": {
        const newStatus = progress > 0 ? "IN_PROGRESS" : "ASSIGNED";
        await db.productionTask.update({
          where: { id: task.id },
          data: {
            status: newStatus,
            blockedReason: null,
            blockedById: null,
            blockedAt: null,
            updatedAt: a.at,
          },
        });
        await db.productionEvent.create({
          data: {
            taskId: task.id,
            orderId: opts.orderId,
            eventType: "BLOCKER_RESOLVED",
            actorId: a.byId,
            previousValue: "BLOCKED",
            newValue: newStatus,
            reason: a.note ?? null,
            createdAt: a.at,
          },
        });
        if (assignedStaffId) {
          await db.notification.create({
            data: {
              userId: assignedStaffId,
              title: `Blocker cleared on ${opts.pieceName}`,
              body: a.note ?? null,
              taskId: task.id,
              orderId: opts.orderId,
              type: "BLOCKER_RESOLVED",
              createdAt: a.at,
            },
          });
        }
        status = newStatus;
        break;
      }
    }
  }
  return task.id;
}

// ---------------------------------------------------------------------------
// Main
// ---------------------------------------------------------------------------

async function main() {
  console.log("Seeding OSSZ Atelier…");

  // --- Staff accounts -------------------------------------------------------
  const staffSpec = [
    { email: "admin@ossz.com", name: "Aïcha Ngo", role: "ADMIN", password: "ossz-admin1", isActive: true },
    { email: "manager@ossz.com", name: "Marie Ebogo", role: "MANAGER", password: "ossz-manager1", isActive: true },
    { email: "inspector@ossz.com", name: "Paul Etoundi", role: "INSPECTOR", password: "ossz-inspect1", isActive: true },
    { email: "hamidou@ossz.com", name: "Hamidou Bello", role: "TAILOR", password: "ossz-tailor1", isActive: true },
    { email: "lawal@ossz.com", name: "Lawal Okafor", role: "TAILOR", password: "ossz-tailor1", isActive: true },
    { email: "grace@ossz.com", name: "Grace Fon", role: "TAILOR", password: "ossz-tailor1", isActive: true },
    { email: "idrissu@ossz.com", name: "Idrissu Alfa", role: "TAILOR", password: "ossz-tailor1", isActive: false },
  ];
  const userIds: Record<string, string> = {};
  for (const s of staffSpec) {
    const user = await db.user.upsert({
      where: { email: s.email },
      update: { name: s.name, role: s.role, isActive: s.isActive },
      create: {
        email: s.email,
        name: s.name,
        role: s.role,
        passwordHash: hashPassword(s.password),
        isActive: s.isActive,
      },
    });
    userIds[s.email.split("@")[0]] = user.id;
  }
  const { admin, manager, inspector, hamidou, lawal, grace } = userIds;

  // --- Products & garment components ----------------------------------------
  const productSpec = [
    {
      name: "Three-Piece Suit",
      category: "Suits",
      basePrice: 85000,
      description: "Jacket, trousers and waistcoat set in premium wool.",
      components: [
        { name: "Jacket", defaultInstructions: "Full canvas construction, notch lapel, two-button front." },
        { name: "Trousers", defaultInstructions: "Flat front, side adjusters, cuffed hem." },
        { name: "Vest", defaultInstructions: "Five-button front, back adjuster belt." },
      ],
    },
    {
      name: "Two-Piece Suit",
      category: "Suits",
      basePrice: 65000,
      description: "Classic jacket and trousers business suit.",
      components: [
        { name: "Jacket", defaultInstructions: "Half canvas construction, notch lapel." },
        { name: "Trousers", defaultInstructions: "Flat front, plain hem." },
      ],
    },
    {
      name: "Agbada Set",
      category: "Traditional",
      basePrice: 120000,
      description: "Flowing embroidered three-piece ceremonial attire.",
      components: [
        { name: "Outer Robe", defaultInstructions: "Embroidered neckline and chest panel." },
        { name: "Inner Tunic", defaultInstructions: "Mandarin collar, matching embroidery." },
        { name: "Trousers", defaultInstructions: "Drawstring waist, tapered leg." },
      ],
    },
    {
      name: "Grand Boubou",
      category: "Traditional",
      basePrice: 75000,
      description: "Elegant wide-sleeved boubou with trousers.",
      components: [
        { name: "Top Boubou", defaultInstructions: "Wide sleeves, reinforced shoulder line." },
        { name: "Trousers", defaultInstructions: "Drawstring waist." },
      ],
    },
    {
      name: "Dress Shirt",
      category: "Shirts",
      basePrice: 18000,
      description: "Tailored cotton dress shirt.",
      components: [],
    },
    {
      name: "Kaftan",
      category: "Traditional",
      basePrice: 35000,
      description: "Classic knee-length kaftan tunic.",
      components: [],
    },
    {
      name: "Women's Wrapper Set",
      category: "Traditional",
      basePrice: 42000,
      description: "Blouse and wrapper in matching wax fabric.",
      components: [
        { name: "Blouse", defaultInstructions: "Fitted bodice, covered buttons." },
        { name: "Wrapper", defaultInstructions: "Full-length wrap skirt." },
      ],
    },
  ];
  const productIds: Record<string, string> = {};
  for (const p of productSpec) {
    const existing = await db.product.findFirst({ where: { name: p.name } });
    const product = existing
      ? await db.product.update({
          where: { id: existing.id },
          data: {
            category: p.category,
            basePrice: p.basePrice,
            description: p.description,
            isActive: true,
          },
        })
      : await db.product.create({
          data: {
            name: p.name,
            category: p.category,
            basePrice: p.basePrice,
            description: p.description,
            isActive: true,
          },
        });
    // Replace components to keep the seed deterministic.
    await db.productComponent.deleteMany({ where: { productId: product.id } });
    if (p.components.length > 0) {
      await db.productComponent.createMany({
        data: p.components.map((c, i) => ({
          productId: product.id,
          name: c.name,
          defaultInstructions: c.defaultInstructions,
          sortOrder: i,
        })),
      });
    }
    productIds[p.name] = product.id;
  }

  // --- Customers --------------------------------------------------------------
  const customerSpec = [
    { name: "Emmanuel Nkoulou", email: "emmanuel.nkoulou@example.cm", phone: "+237 6 77 12 34 56" },
    { name: "Sarah Mboga", email: "sarah.mboga@example.cm", phone: "+237 6 99 88 77 66" },
    { name: "Ibrahim Diallo", email: "ibrahim.diallo@example.cm", phone: "+237 6 55 44 33 22" },
    { name: "Claire Tchoumi", email: "claire.tchoumi@example.cm", phone: "+237 6 90 11 22 33" },
    { name: "Musa Bello", email: "musa.bello@example.cm", phone: "+237 6 71 45 67 89" },
  ];
  const customerIds: Record<string, string> = {};
  for (const c of customerSpec) {
    const existing = await db.customer.findFirst({ where: { name: c.name } });
    const customer = existing
      ? await db.customer.update({ where: { id: existing.id }, data: c })
      : await db.customer.create({ data: c });
    customerIds[c.name] = customer.id;
  }

  // --- Orders & production tasks ----------------------------------------------
  if ((await db.order.count()) > 0) {
    console.log("Orders already present — skipping order/task seeding.");
    console.log("Seed complete.");
    return;
  }

  const suitMeasurements = JSON.stringify({
    Chest: "102 cm",
    Waist: "88 cm",
    Shoulder: "46 cm",
    Sleeve: "64 cm",
    Inseam: "82 cm",
  });
  const agbadaMeasurements = JSON.stringify({
    Chest: "108 cm",
    Shoulder: "50 cm",
    Length: "140 cm",
    Sleeve: "66 cm",
    Waist: "94 cm",
  });
  const shirtMeasurements = JSON.stringify({
    Neck: "40 cm",
    Chest: "100 cm",
    Sleeve: "64 cm",
  });
  const wrapperMeasurements = JSON.stringify({
    Bust: "92 cm",
    Waist: "76 cm",
    Hip: "100 cm",
    BlouseLength: "65 cm",
    WrapperLength: "110 cm",
  });

  async function createOrder(opts: {
    reference: string;
    customerId: string;
    status: string;
    paymentStatus: string;
    createdAt: Date;
    expectedDeliveryDate?: Date;
    notes?: string;
    items: Array<{
      productId: string;
      productName: string;
      quantity: number;
      unitPrice: number;
      measurements?: string;
      instructions?: string;
    }>;
  }) {
    const order = await db.order.create({
      data: {
        reference: opts.reference,
        customerId: opts.customerId,
        status: opts.status,
        paymentStatus: opts.paymentStatus,
        totalAmount: opts.items.reduce((s, i) => s + i.unitPrice * i.quantity, 0),
        expectedDeliveryDate: opts.expectedDeliveryDate ?? null,
        notes: opts.notes ?? null,
        createdAt: opts.createdAt,
        updatedAt: opts.createdAt,
        items: {
          create: opts.items.map((i) => ({
            productId: i.productId,
            quantity: i.quantity,
            unitPrice: i.unitPrice,
            measurements: i.measurements ?? null,
            instructions: i.instructions ?? null,
          })),
        },
      },
      include: { items: true },
    });
    return order;
  }

  // OSSZ-ORD-001 — Three-piece suit in active production (acceptance scenario data:
  // Jacket 75% Hamidou, Trousers 100% awaiting inspection, Vest 60% Lawal → 78.33% overall).
  {
    const order = await createOrder({
      reference: "OSSZ-ORD-001",
      customerId: customerIds["Emmanuel Nkoulou"],
      status: "IN_PRODUCTION",
      paymentStatus: "DEPOSIT_PAID",
      createdAt: daysAgo(6),
      expectedDeliveryDate: daysFromNow(4),
      notes: "Wedding attire — customer prefers midnight navy wool.",
      items: [
        {
          productId: productIds["Three-Piece Suit"],
          productName: "Three-Piece Suit",
          quantity: 1,
          unitPrice: 85000,
          measurements: suitMeasurements,
          instructions: "Midnight navy wool, notch lapel, slim fit.",
        },
      ],
    });
    const item = order.items[0]!;
    await seedTask({
      orderId: order.id,
      orderItemId: item.id,
      pieceName: "Jacket",
      instructions: "Full canvas construction, notch lapel, two-button front.",
      createdBy: manager,
      createdAt: daysAgo(6, 9),
      actions: [
        { kind: "assign", at: daysAgo(6, 9), staffId: hamidou, byId: manager, dueAt: daysFromNow(2) },
        { kind: "progress", at: daysAgo(5, 14), byId: hamidou, to: 30, note: "Cutting completed." },
        { kind: "progress", at: daysAgo(2, 11), byId: hamidou, to: 75, note: "Body assembled, sleeves set." },
      ],
    });
    await seedTask({
      orderId: order.id,
      orderItemId: item.id,
      pieceName: "Trousers",
      instructions: "Flat front, side adjusters, cuffed hem.",
      createdBy: manager,
      createdAt: daysAgo(6, 9),
      actions: [
        { kind: "assign", at: daysAgo(6, 9), staffId: hamidou, byId: manager, dueAt: daysFromNow(2) },
        { kind: "progress", at: daysAgo(5, 15), byId: hamidou, to: 50, note: "Cutting and initial stitching completed." },
        { kind: "progress", at: daysAgo(3, 10), byId: hamidou, to: 100, note: "Trousers completed." },
        { kind: "submit", at: daysAgo(3, 10), byId: hamidou, note: "Ready for inspection." },
      ],
    });
    await seedTask({
      orderId: order.id,
      orderItemId: item.id,
      pieceName: "Vest",
      instructions: "Five-button front, back adjuster belt.",
      createdBy: manager,
      createdAt: daysAgo(6, 9),
      actions: [
        { kind: "assign", at: daysAgo(6, 10), staffId: lawal, byId: manager, dueAt: daysFromNow(3) },
        { kind: "progress", at: daysAgo(4, 9), byId: lawal, to: 20, note: "Cutting started." },
        { kind: "progress", at: daysAgo(1, 16), byId: lawal, to: 60, note: "Front panels assembled." },
      ],
    });
  }

  // OSSZ-ORD-002 — Two-piece suit, breakdown created but unassigned (allocation demo).
  {
    const order = await createOrder({
      reference: "OSSZ-ORD-002",
      customerId: customerIds["Sarah Mboga"],
      status: "CONFIRMED",
      paymentStatus: "UNPAID",
      createdAt: daysAgo(3),
      expectedDeliveryDate: daysFromNow(10),
      items: [
        {
          productId: productIds["Two-Piece Suit"],
          productName: "Two-Piece Suit",
          quantity: 1,
          unitPrice: 65000,
          measurements: suitMeasurements,
          instructions: "Charcoal grey, classic fit.",
        },
      ],
    });
    const item = order.items[0]!;
    await seedTask({
      orderId: order.id,
      orderItemId: item.id,
      pieceName: "Jacket",
      createdBy: manager,
      createdAt: daysAgo(2, 9),
      actions: [],
    });
    await seedTask({
      orderId: order.id,
      orderItemId: item.id,
      pieceName: "Trousers",
      createdBy: manager,
      createdAt: daysAgo(2, 9),
      actions: [],
    });
  }

  // OSSZ-ORD-003 — Agbada set: one approved, one changes-requested, one overdue in progress.
  {
    const order = await createOrder({
      reference: "OSSZ-ORD-003",
      customerId: customerIds["Ibrahim Diallo"],
      status: "IN_PRODUCTION",
      paymentStatus: "PAID",
      createdAt: daysAgo(12),
      expectedDeliveryDate: daysFromNow(2),
      notes: "Ceremonial agbada, gold embroidery on ivory fabric.",
      items: [
        {
          productId: productIds["Agbada Set"],
          productName: "Agbada Set",
          quantity: 1,
          unitPrice: 120000,
          measurements: agbadaMeasurements,
          instructions: "Ivory brocade, gold embroidery.",
        },
      ],
    });
    const item = order.items[0]!;
    await seedTask({
      orderId: order.id,
      orderItemId: item.id,
      pieceName: "Outer Robe",
      instructions: "Embroidered neckline and chest panel.",
      createdBy: manager,
      createdAt: daysAgo(12, 9),
      actions: [
        { kind: "assign", at: daysAgo(12, 9), staffId: lawal, byId: manager, dueAt: daysFromNow(1) },
        { kind: "progress", at: daysAgo(9, 10), byId: lawal, to: 45, note: "Fabric marked and cut." },
        { kind: "progress", at: daysAgo(6, 15), byId: lawal, to: 100, note: "Embroidery and assembly complete." },
        { kind: "submit", at: daysAgo(5, 9), byId: lawal },
        {
          kind: "inspect",
          at: daysAgo(4, 11),
          byId: inspector,
          result: "APPROVED",
          notes: "Excellent embroidery alignment. Approved.",
        },
      ],
    });
    await seedTask({
      orderId: order.id,
      orderItemId: item.id,
      pieceName: "Inner Tunic",
      instructions: "Mandarin collar, matching embroidery.",
      createdBy: manager,
      createdAt: daysAgo(12, 9),
      actions: [
        { kind: "assign", at: daysAgo(12, 10), staffId: grace, byId: manager, dueAt: daysFromNow(1) },
        { kind: "progress", at: daysAgo(8, 11), byId: grace, to: 60 },
        { kind: "progress", at: daysAgo(4, 14), byId: grace, to: 100, note: "Tunic assembled." },
        { kind: "submit", at: daysAgo(3, 9), byId: grace },
        {
          kind: "inspect",
          at: daysAgo(2, 10),
          byId: inspector,
          result: "CHANGES_REQUESTED",
          notes: "Neckline embroidery is off-centre.",
          correctionInstructions: "Realign the neckline embroidery 1 cm to the left and re-press.",
        },
      ],
    });
    await seedTask({
      orderId: order.id,
      orderItemId: item.id,
      pieceName: "Trousers",
      instructions: "Drawstring waist, tapered leg.",
      createdBy: manager,
      createdAt: daysAgo(12, 9),
      actions: [
        { kind: "assign", at: daysAgo(11, 9), staffId: grace, byId: manager, dueAt: daysAgo(1) },
        { kind: "progress", at: daysAgo(7, 10), byId: grace, to: 40, note: "Legs cut." },
      ],
    });
  }

  // OSSZ-ORD-004 — Two dress shirts (quantity 2 → two tasks), one blocked.
  {
    const order = await createOrder({
      reference: "OSSZ-ORD-004",
      customerId: customerIds["Musa Bello"],
      status: "IN_PRODUCTION",
      paymentStatus: "UNPAID",
      createdAt: daysAgo(8),
      expectedDeliveryDate: daysFromNow(5),
      items: [
        {
          productId: productIds["Dress Shirt"],
          productName: "Dress Shirt",
          quantity: 2,
          unitPrice: 18000,
          measurements: shirtMeasurements,
          instructions: "One white, one sky-blue. Collar stays.",
        },
      ],
    });
    const item = order.items[0]!;
    await seedTask({
      orderId: order.id,
      orderItemId: item.id,
      pieceName: "Dress Shirt #1",
      instructions: "White, classic collar.",
      createdBy: manager,
      createdAt: daysAgo(7, 9),
      actions: [
        { kind: "assign", at: daysAgo(7, 9), staffId: grace, byId: manager, dueAt: daysFromNow(5) },
        { kind: "progress", at: daysAgo(4, 10), byId: grace, to: 55, note: "Body and collar attached." },
      ],
    });
    await seedTask({
      orderId: order.id,
      orderItemId: item.id,
      pieceName: "Dress Shirt #2",
      instructions: "Sky blue, classic collar.",
      createdBy: manager,
      createdAt: daysAgo(7, 9),
      actions: [
        { kind: "assign", at: daysAgo(7, 9), staffId: grace, byId: manager, dueAt: daysFromNow(5) },
        { kind: "progress", at: daysAgo(5, 11), byId: grace, to: 25 },
        {
          kind: "block",
          at: daysAgo(1, 12),
          byId: grace,
          reason: "Waiting for replacement mother-of-pearl buttons — supplier delivery delayed.",
        },
      ],
    });
  }

  // OSSZ-ORD-005 — Kaftan, no production breakdown yet (breakdown creation demo).
  {
    await createOrder({
      reference: "OSSZ-ORD-005",
      customerId: customerIds["Claire Tchoumi"],
      status: "PENDING",
      paymentStatus: "UNPAID",
      createdAt: daysAgo(2),
      expectedDeliveryDate: daysFromNow(12),
      items: [
        {
          productId: productIds["Kaftan"],
          productName: "Kaftan",
          quantity: 1,
          unitPrice: 35000,
          measurements: JSON.stringify({ Chest: "96 cm", Length: "110 cm", Shoulder: "44 cm" }),
          instructions: "Teal cotton with tonal chest embroidery.",
        },
      ],
    });
  }

  // OSSZ-ORD-006 — Wrapper set, fully approved and dispatched.
  {
    const order = await createOrder({
      reference: "OSSZ-ORD-006",
      customerId: customerIds["Sarah Mboga"],
      status: "DISPATCHED",
      paymentStatus: "PAID",
      createdAt: daysAgo(20),
      expectedDeliveryDate: daysAgo(4),
      items: [
        {
          productId: productIds["Women's Wrapper Set"],
          productName: "Women's Wrapper Set",
          quantity: 1,
          unitPrice: 42000,
          measurements: wrapperMeasurements,
          instructions: "Wax print, matching headwrap.",
        },
      ],
    });
    const item = order.items[0]!;
    await seedTask({
      orderId: order.id,
      orderItemId: item.id,
      pieceName: "Blouse",
      createdBy: manager,
      createdAt: daysAgo(20, 9),
      actions: [
        { kind: "assign", at: daysAgo(20, 10), staffId: hamidou, byId: manager, dueAt: daysAgo(8) },
        { kind: "progress", at: daysAgo(16, 11), byId: hamidou, to: 70 },
        { kind: "progress", at: daysAgo(12, 9), byId: hamidou, to: 100, note: "Blouse complete." },
        { kind: "submit", at: daysAgo(12, 9), byId: hamidou },
        { kind: "inspect", at: daysAgo(10, 14), byId: inspector, result: "APPROVED", notes: "Clean finishing." },
      ],
    });
    await seedTask({
      orderId: order.id,
      orderItemId: item.id,
      pieceName: "Wrapper",
      createdBy: manager,
      createdAt: daysAgo(20, 9),
      actions: [
        { kind: "assign", at: daysAgo(20, 10), staffId: lawal, byId: manager, dueAt: daysAgo(8) },
        { kind: "progress", at: daysAgo(15, 10), byId: lawal, to: 80 },
        { kind: "progress", at: daysAgo(11, 15), byId: lawal, to: 100, note: "Wrapper complete." },
        { kind: "submit", at: daysAgo(11, 15), byId: lawal },
        { kind: "inspect", at: daysAgo(9, 10), byId: inspector, result: "APPROVED" },
      ],
    });
  }

  // OSSZ-ORD-007 — Two-piece suit completed, with one rework cycle (reports data).
  {
    const order = await createOrder({
      reference: "OSSZ-ORD-007",
      customerId: customerIds["Emmanuel Nkoulou"],
      status: "COMPLETED",
      paymentStatus: "PAID",
      createdAt: daysAgo(25),
      expectedDeliveryDate: daysAgo(10),
      items: [
        {
          productId: productIds["Two-Piece Suit"],
          productName: "Two-Piece Suit",
          quantity: 1,
          unitPrice: 65000,
          measurements: suitMeasurements,
          instructions: "Deep green tweed.",
        },
      ],
    });
    const item = order.items[0]!;
    await seedTask({
      orderId: order.id,
      orderItemId: item.id,
      pieceName: "Jacket",
      createdBy: manager,
      createdAt: daysAgo(25, 9),
      actions: [
        { kind: "assign", at: daysAgo(25, 9), staffId: hamidou, byId: manager, dueAt: daysAgo(12) },
        { kind: "progress", at: daysAgo(21, 10), byId: hamidou, to: 50 },
        { kind: "progress", at: daysAgo(17, 9), byId: hamidou, to: 100, note: "Jacket complete." },
        { kind: "submit", at: daysAgo(17, 9), byId: hamidou },
        { kind: "inspect", at: daysAgo(15, 10), byId: inspector, result: "APPROVED", notes: "Beautiful lapel roll." },
      ],
    });
    await seedTask({
      orderId: order.id,
      orderItemId: item.id,
      pieceName: "Trousers",
      createdBy: manager,
      createdAt: daysAgo(25, 9),
      actions: [
        { kind: "assign", at: daysAgo(25, 9), staffId: hamidou, byId: manager, dueAt: daysAgo(12) },
        { kind: "progress", at: daysAgo(20, 11), byId: hamidou, to: 100, note: "Trousers complete." },
        { kind: "submit", at: daysAgo(20, 11), byId: hamidou },
        {
          kind: "inspect",
          at: daysAgo(18, 9),
          byId: inspector,
          result: "CHANGES_REQUESTED",
          notes: "Inseam 2 cm too short.",
          correctionInstructions: "Let the hem down by 2 cm and re-finish.",
        },
        { kind: "progress", at: daysAgo(16, 10), byId: hamidou, to: 100, note: "Hem corrected and re-pressed." },
        { kind: "submit", at: daysAgo(16, 10), byId: hamidou },
        { kind: "inspect", at: daysAgo(14, 11), byId: inspector, result: "APPROVED" },
      ],
    });
  }

  console.log("Seed complete.");
  console.log("Demo accounts (password shown):");
  console.log("  admin@ossz.com / ossz-admin1      (Administrator)");
  console.log("  manager@ossz.com / ossz-manager1  (Production Manager)");
  console.log("  inspector@ossz.com / ossz-inspect1 (Inspector)");
  console.log("  hamidou@ossz.com / ossz-tailor1   (Production Staff)");
  console.log("  lawal@ossz.com / ossz-tailor1     (Production Staff)");
  console.log("  grace@ossz.com / ossz-tailor1     (Production Staff)");
}

main()
  .catch((e) => {
    console.error(e);
    process.exitCode = 1;
  })
  .finally(() => db.$disconnect());
