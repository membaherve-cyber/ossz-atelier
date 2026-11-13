/**
 * OSSZ ATELIER — standalone database seed.
 * Creates the three account roles, configurable production stages, performance
 * configuration, incentive schemes and a realistic demo dataset matching the
 * specification scenario (John Doe / OSSZ-2026-001 three-piece suit, allocated
 * to Hamidou and Lawal) plus historical completed work for the performance and
 * incentive modules.
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

const DEMO_PASSWORD = "Atelier@2026";

async function main() {
  console.log("Seeding OSSZ ATELIER standalone database...");

  // ---------------------------------------------------------------------
  // 1. Accounts (Management / Admin / Staff)
  // ---------------------------------------------------------------------
  const aminata = await db.user.create({
    data: {
      code: "USR-001",
      name: "Aminata Sow",
      email: "management@atelier.com",
      phone: "+237 677 000 001",
      jobTitle: "Atelier Director",
      passwordHash: hashPassword(DEMO_PASSWORD),
      role: "MANAGEMENT",
    },
  });
  const david = await db.user.create({
    data: {
      code: "USR-002",
      name: "David Nkeng",
      email: "admin@atelier.com",
      phone: "+237 677 000 002",
      jobTitle: "Production Administrator",
      passwordHash: hashPassword(DEMO_PASSWORD),
      role: "ADMIN",
    },
  });
  const hamidou = await db.user.create({
    data: {
      code: "USR-003",
      name: "Hamidou Abdoul",
      email: "hamidou@atelier.com",
      phone: "+237 677 000 003",
      jobTitle: "Master Tailor",
      passwordHash: hashPassword(DEMO_PASSWORD),
      role: "STAFF",
    },
  });
  const lawal = await db.user.create({
    data: {
      code: "USR-004",
      name: "Lawal Sani",
      email: "lawal@atelier.com",
      phone: "+237 677 000 004",
      jobTitle: "Tailor",
      passwordHash: hashPassword(DEMO_PASSWORD),
      role: "STAFF",
    },
  });
  await db.user.create({
    data: {
      code: "USR-005",
      name: "Ngozi Okafor",
      email: "ngozi@atelier.com",
      phone: "+237 677 000 005",
      jobTitle: "Finisher & Ironing",
      passwordHash: hashPassword(DEMO_PASSWORD),
      role: "STAFF",
    },
  });

  // ---------------------------------------------------------------------
  // 2. Configurable production stages (specification checklist)
  // ---------------------------------------------------------------------
  const stageDefs: Array<[string, string, number, string]> = [
    ["assigned", "Assigned", 1, "ALL"],
    ["ongoing", "Ongoing", 2, "ALL"],
    ["cutting", "Cutting", 3, "FULL,CUTTING"],
    ["sewing", "Sewing/Stitching", 4, "FULL,SEWING"],
    ["fitting", "Fitting", 5, "FULL,SEWING"],
    ["finishing", "Finishing", 6, "FULL,FINISHING"],
    ["ironing", "Ironing", 7, "FULL,IRONING,FINISHING"],
    ["quality-check", "Quality Check", 8, "ALL"],
    ["ready-for-pickup", "Ready for Pickup", 9, "ALL"],
    ["delivered", "Delivered/Collected", 10, "ALL"],
    ["blocked", "Blocked", 11, "ALL"],
    ["other", "Other", 12, "ALL"],
  ];
  const stages: Record<string, string> = {};
  for (const [code, name, sortOrder, appliesTo] of stageDefs) {
    const s = await db.productionStage.create({
      data: { code, name, sortOrder, appliesTo, weight: 1 },
    });
    stages[code] = s.id;
  }

  // ---------------------------------------------------------------------
  // 3. Performance configuration & incentive schemes
  // ---------------------------------------------------------------------
  await db.performanceConfig.create({
    data: {
      id: "main",
      productivityWeight: 30,
      timelyWeight: 25,
      qualityWeight: 30,
      reliabilityWeight: 10,
      managementWeight: 5,
      pieceTarget: 20,
    },
  });

  const monthlyScheme = await db.incentiveScheme.create({
    data: {
      name: "Monthly Excellence Bonus",
      periodType: "MONTHLY",
      minScore: 75,
      amount: 25000,
      description:
        "Awarded to staff whose overall monthly performance score reaches 75/100 or above.",
      createdById: aminata.id,
      createdByName: aminata.name,
    },
  });
  await db.incentiveScheme.create({
    data: {
      name: "Annual Excellence Award",
      periodType: "ANNUAL",
      minScore: 80,
      amount: 250000,
      description: "Annual recognition of sustained excellence across the year.",
      createdById: aminata.id,
      createdByName: aminata.name,
    },
  });
  await db.incentiveScheme.create({
    data: {
      name: "Quality Champion",
      periodType: "QUALITY",
      minScore: 90,
      amount: 15000,
      description: "For a monthly quality approval rate of 90% or above with at least 3 completions.",
      createdById: aminata.id,
      createdByName: aminata.name,
    },
  });

  // ---------------------------------------------------------------------
  // 4. Customers & manually registered orders
  // ---------------------------------------------------------------------
  const john = await db.customer.create({
    data: {
      code: "CUST-0001",
      name: "John Doe",
      phone: "+237 677 123 456",
      email: "johndoe@example.com",
      address: "Bonapriso, Douala",
      notes: "Prefers slim-fit cuts. Returning customer.",
      createdById: aminata.id,
      createdAt: daysAgo(21),
    },
  });
  const amina = await db.customer.create({
    data: {
      code: "CUST-0002",
      name: "Amina Cissé",
      phone: "+237 699 551 122",
      email: "amina.cisse@example.com",
      address: "Bastos, Yaoundé",
      notes: "Measures on file from January fitting.",
      createdById: david.id,
      createdAt: daysAgo(46),
    },
  });

  const orderSuit = await db.order.create({
    data: {
      orderNumber: "OSSZ-2026-001",
      customerId: john.id,
      orderDate: daysAgo(20),
      expectedDeliveryDate: daysFromNow(10),
      description: "Three-piece suit",
      garmentCategory: "Suit",
      numberOfPieces: 3,
      instructions: "Slim fit, midnight blue fabric, gold buttons.",
      priority: "HIGH",
      notes: "Customer expects a fitting before finishing.",
      createdById: aminata.id,
      createdAt: daysAgo(20),
    },
  });
  const orderAgbada = await db.order.create({
    data: {
      orderNumber: "OSSZ-2026-002",
      customerId: amina.id,
      orderDate: daysAgo(6),
      expectedDeliveryDate: daysFromNow(24),
      description: "Agbada two-piece set",
      garmentCategory: "Agbada",
      numberOfPieces: 2,
      instructions: "Embroidered neckline, matching trousers.",
      priority: "NORMAL",
      createdById: david.id,
      createdAt: daysAgo(6),
    },
  });
  const orderSenegal = await db.order.create({
    data: {
      orderNumber: "OSSZ-2025-014",
      customerId: amina.id,
      orderDate: daysAgo(45),
      expectedDeliveryDate: daysAgo(12),
      description: "Senegal two-piece with headwrap",
      garmentCategory: "Senegal",
      numberOfPieces: 3,
      priority: "NORMAL",
      createdById: aminata.id,
      createdAt: daysAgo(45),
    },
  });
  const orderKaftan = await db.order.create({
    data: {
      orderNumber: "OSSZ-2025-009",
      customerId: john.id,
      orderDate: daysAgo(75),
      expectedDeliveryDate: daysAgo(55),
      description: "Grand kaftan",
      garmentCategory: "Kaftan",
      numberOfPieces: 1,
      priority: "NORMAL",
      createdById: aminata.id,
      createdAt: daysAgo(75),
    },
  });

  // ---------------------------------------------------------------------
  // 5. Production tasks — current live production
  // ---------------------------------------------------------------------
  async function createTask(opts: {
    code: string;
    orderId: string;
    garmentPiece: string;
    category?: string;
    instructions?: string;
    deadline: Date;
    priority?: string;
    status: string;
    progress: number;
    assignedStaffId?: string;
    createdAt: Date;
    stageStates: Array<[string, string]>; // [stageCode, state]
    stageUpdates?: Array<{ stageCode: string; from: string; to: string; by: string; at: Date }>;
    statusChain?: Array<{ to: string; by: string; at: Date; reason?: string }>;
    progressHistory?: Array<{ from: number; to: number; by: string; at: Date; note?: string }>;
  }) {
    const task = await db.productionTask.create({
      data: {
        code: opts.code,
        orderId: opts.orderId,
        garmentPiece: opts.garmentPiece,
        category: opts.category ?? "FULL",
        instructions: opts.instructions ?? null,
        deadline: opts.deadline,
        priority: opts.priority ?? "NORMAL",
        status: opts.status,
        progress: opts.progress,
        assignedStaffId: opts.assignedStaffId ?? null,
        createdById: aminata.id,
        createdAt: opts.createdAt,
        updatedAt: now(),
      },
    });

    // Stage states
    const category = opts.category ?? "FULL";
    const applies = (code: string) => {
      const def = stageDefs.find((s) => s[0] === code)!;
      return def[3] === "ALL" || def[3].split(",").includes(category);
    };
    const names: Record<string, string> = {};
    const users: Record<string, string> = {
      hamidou: hamidou.name,
      lawal: lawal.name,
      aminata: aminata.name,
      david: david.name,
    };
    for (const [code, name] of stageDefs) names[code] = name;
    for (const [stageCode, state] of opts.stageStates) {
      if (!applies(stageCode)) continue;
      await db.taskStageState.create({
        data: {
          taskId: task.id,
          stageId: stages[stageCode],
          state,
          updatedById: opts.assignedStaffId ?? null,
          updatedByName: opts.assignedStaffId === hamidou.id ? hamidou.name : lawal.name,
          updatedAt: opts.createdAt,
        },
      });
    }
    if (opts.stageUpdates) {
      for (const u of opts.stageUpdates) {
        if (!applies(u.stageCode)) continue;
        await db.taskStageUpdate.create({
          data: {
            taskId: task.id,
            stageId: stages[u.stageCode],
            stageName: names[u.stageCode],
            userId: u.by === "hamidou" ? hamidou.id : u.by === "lawal" ? lawal.id : aminata.id,
            userName: users[u.by],
            previousState: u.from,
            newState: u.to,
            createdAt: u.at,
          },
        });
      }
    }
    if (opts.statusChain) {
      let prev = "UNASSIGNED";
      for (const c of opts.statusChain) {
        await db.taskStatusChange.create({
          data: {
            taskId: task.id,
            userId: c.by === "aminata" ? aminata.id : c.by === "david" ? david.id : c.by === "hamidou" ? hamidou.id : lawal.id,
            userName: users[c.by] ?? aminata.name,
            fromStatus: prev,
            toStatus: c.to,
            reason: c.reason ?? null,
            createdAt: c.at,
          },
        });
        prev = c.to;
      }
    }
    if (opts.progressHistory) {
      for (const p of opts.progressHistory) {
        await db.taskProgressHistory.create({
          data: {
            taskId: task.id,
            userId: p.by === "hamidou" ? hamidou.id : lawal.id,
            userName: users[p.by],
            previousProgress: p.from,
            newProgress: p.to,
            source: "MANUAL",
            note: p.note ?? null,
            createdAt: p.at,
          },
        });
      }
    }
    if (opts.assignedStaffId) {
      await db.taskAssignment.create({
        data: {
          taskId: task.id,
          staffId: opts.assignedStaffId,
          staffName:
            opts.assignedStaffId === hamidou.id ? hamidou.name : opts.assignedStaffId === lawal.id ? lawal.name : "",
          assignedById: aminata.id,
          assignedByName: aminata.name,
          type: "ASSIGN",
          createdAt: opts.createdAt,
        },
      });
    }
    return task;
  }

  const t1 = await createTask({
    code: "TASK-0001",
    orderId: orderSuit.id,
    garmentPiece: "Jacket",
    instructions: "Attach the customer-provided gold buttons. Single-breasted, two vents.",
    deadline: daysFromNow(12),
    priority: "HIGH",
    status: "ONGOING",
    progress: 60,
    assignedStaffId: hamidou.id,
    createdAt: daysAgo(15),
    stageStates: [
      ["assigned", "COMPLETED"],
      ["ongoing", "COMPLETED"],
      ["cutting", "COMPLETED"],
      ["sewing", "IN_PROGRESS"],
      ["fitting", "NOT_STARTED"],
      ["finishing", "NOT_STARTED"],
      ["ironing", "NOT_STARTED"],
      ["quality-check", "NOT_STARTED"],
      ["ready-for-pickup", "NOT_STARTED"],
      ["delivered", "NOT_STARTED"],
      ["blocked", "NOT_APPLICABLE"],
      ["other", "NOT_APPLICABLE"],
    ],
    stageUpdates: [
      { stageCode: "assigned", from: "NOT_STARTED", to: "COMPLETED", by: "hamidou", at: daysAgo(15) },
      { stageCode: "ongoing", from: "NOT_STARTED", to: "COMPLETED", by: "hamidou", at: daysAgo(14) },
      { stageCode: "cutting", from: "NOT_STARTED", to: "IN_PROGRESS", by: "hamidou", at: daysAgo(12) },
      { stageCode: "cutting", from: "IN_PROGRESS", to: "COMPLETED", by: "hamidou", at: daysAgo(10) },
      { stageCode: "sewing", from: "NOT_STARTED", to: "IN_PROGRESS", by: "hamidou", at: daysAgo(8) },
    ],
    statusChain: [
      { to: "ASSIGNED", by: "aminata", at: daysAgo(15) },
      { to: "ONGOING", by: "hamidou", at: daysAgo(14) },
    ],
    progressHistory: [
      { from: 0, to: 25, by: "hamidou", at: daysAgo(14), note: "Fabric cut, assembly started." },
      { from: 25, to: 45, by: "hamidou", at: daysAgo(8) },
      { from: 45, to: 60, by: "hamidou", at: daysAgo(2), note: "Body assembled, sleeves set." },
    ],
  });

  const t2 = await createTask({
    code: "TASK-0002",
    orderId: orderSuit.id,
    garmentPiece: "Trousers",
    instructions: "Slim fit, side adjusters instead of belt loops.",
    deadline: daysFromNow(12),
    priority: "HIGH",
    status: "ONGOING",
    progress: 80,
    assignedStaffId: hamidou.id,
    createdAt: daysAgo(15),
    stageStates: [
      ["assigned", "COMPLETED"],
      ["ongoing", "COMPLETED"],
      ["cutting", "COMPLETED"],
      ["sewing", "COMPLETED"],
      ["fitting", "COMPLETED"],
      ["finishing", "IN_PROGRESS"],
      ["ironing", "NOT_STARTED"],
      ["quality-check", "NOT_STARTED"],
      ["ready-for-pickup", "NOT_STARTED"],
      ["delivered", "NOT_STARTED"],
      ["blocked", "NOT_APPLICABLE"],
      ["other", "NOT_APPLICABLE"],
    ],
    stageUpdates: [
      { stageCode: "assigned", from: "NOT_STARTED", to: "COMPLETED", by: "hamidou", at: daysAgo(15) },
      { stageCode: "ongoing", from: "NOT_STARTED", to: "COMPLETED", by: "hamidou", at: daysAgo(15, 12) },
      { stageCode: "cutting", from: "NOT_STARTED", to: "COMPLETED", by: "hamidou", at: daysAgo(11) },
      { stageCode: "sewing", from: "NOT_STARTED", to: "COMPLETED", by: "hamidou", at: daysAgo(7) },
      { stageCode: "fitting", from: "NOT_STARTED", to: "COMPLETED", by: "hamidou", at: daysAgo(4) },
      { stageCode: "finishing", from: "NOT_STARTED", to: "IN_PROGRESS", by: "hamidou", at: daysAgo(2) },
    ],
    statusChain: [
      { to: "ASSIGNED", by: "aminata", at: daysAgo(15) },
      { to: "ONGOING", by: "hamidou", at: daysAgo(15, 12) },
    ],
    progressHistory: [
      { from: 0, to: 30, by: "hamidou", at: daysAgo(14) },
      { from: 30, to: 60, by: "hamidou", at: daysAgo(7) },
      { from: 60, to: 80, by: "hamidou", at: daysAgo(3), note: "Fitting done, finishing hem." },
    ],
  });

  const t3 = await createTask({
    code: "TASK-0003",
    orderId: orderSuit.id,
    garmentPiece: "Vest",
    deadline: daysAgo(2), // overdue
    priority: "HIGH",
    status: "ONGOING",
    progress: 30,
    assignedStaffId: lawal.id,
    createdAt: daysAgo(14),
    stageStates: [
      ["assigned", "COMPLETED"],
      ["ongoing", "COMPLETED"],
      ["cutting", "IN_PROGRESS"],
      ["sewing", "NOT_STARTED"],
      ["fitting", "NOT_STARTED"],
      ["finishing", "NOT_STARTED"],
      ["ironing", "NOT_STARTED"],
      ["quality-check", "NOT_STARTED"],
      ["ready-for-pickup", "NOT_STARTED"],
      ["delivered", "NOT_STARTED"],
      ["blocked", "NOT_APPLICABLE"],
      ["other", "NOT_APPLICABLE"],
    ],
    stageUpdates: [
      { stageCode: "assigned", from: "NOT_STARTED", to: "COMPLETED", by: "lawal", at: daysAgo(14) },
      { stageCode: "ongoing", from: "NOT_STARTED", to: "COMPLETED", by: "lawal", at: daysAgo(13) },
      { stageCode: "cutting", from: "NOT_STARTED", to: "IN_PROGRESS", by: "lawal", at: daysAgo(9) },
    ],
    statusChain: [
      { to: "ASSIGNED", by: "aminata", at: daysAgo(14) },
      { to: "ONGOING", by: "lawal", at: daysAgo(13) },
    ],
    progressHistory: [
      { from: 0, to: 15, by: "lawal", at: daysAgo(13) },
      { from: 15, to: 30, by: "lawal", at: daysAgo(4) },
    ],
  });

  await createTask({
    code: "TASK-0004",
    orderId: orderAgbada.id,
    garmentPiece: "Agbada Gown",
    instructions: "Embroidered neckline as per chosen pattern A2.",
    deadline: daysFromNow(24),
    status: "UNASSIGNED",
    progress: 0,
    createdAt: daysAgo(5),
    stageStates: [],
  });
  await createTask({
    code: "TASK-0005",
    orderId: orderAgbada.id,
    garmentPiece: "Matching Trousers",
    deadline: daysFromNow(24),
    status: "UNASSIGNED",
    progress: 0,
    createdAt: daysAgo(5),
    stageStates: [],
  });

  // ---------------------------------------------------------------------
  // 6. Historical completed work (performance & incentive data)
  // ---------------------------------------------------------------------
  const doneStates = (category: string): Array<[string, string]> => {
    const list: Array<[string, string]> = [];
    for (const [code] of stageDefs) {
      const def = stageDefs.find((s) => s[0] === code)!;
      const appliesToCat = def[3] === "ALL" || def[3].split(",").includes(category);
      if (!appliesToCat) continue;
      if (code === "blocked" || code === "other") list.push([code, "NOT_APPLICABLE"]);
      else if (code === "ready-for-pickup" || code === "delivered") list.push([code, "COMPLETED"]);
      else list.push([code, "COMPLETED"]);
    }
    return list;
  };

  // OSSZ-2025-014 — Senegal two-piece (delivered)
  const t6 = await db.productionTask.create({
    data: {
      code: "TASK-0006",
      orderId: orderSenegal.id,
      garmentPiece: "Senegal Top",
      category: "FULL",
      deadline: daysAgo(13),
      priority: "NORMAL",
      status: "DELIVERED",
      progress: 100,
      assignedStaffId: hamidou.id,
      createdById: aminata.id,
      completedAt: daysAgo(14),
      deliveredAt: daysAgo(12),
      createdAt: daysAgo(30),
      updatedAt: daysAgo(12),
    },
  });
  await db.taskAssignment.create({
    data: {
      taskId: t6.id, staffId: hamidou.id, staffName: hamidou.name,
      assignedById: aminata.id, assignedByName: aminata.name, type: "ASSIGN", createdAt: daysAgo(30),
    },
  });
  for (const [code, state] of doneStates("FULL")) {
    await db.taskStageState.create({
      data: {
        taskId: t6.id, stageId: stages[code], state,
        updatedById: hamidou.id, updatedByName: hamidou.name, updatedAt: daysAgo(14),
      },
    });
  }
  for (const [from, to, at] of [
    ["UNASSIGNED", "ASSIGNED", daysAgo(30)],
    ["ASSIGNED", "ONGOING", daysAgo(29)],
    ["ONGOING", "BLOCKED", daysAgo(20)],
    ["BLOCKED", "ONGOING", daysAgo(18)],
    ["ONGOING", "AWAITING_INSPECTION", daysAgo(15)],
    ["AWAITING_INSPECTION", "APPROVED", daysAgo(14)],
    ["APPROVED", "READY_FOR_PICKUP", daysAgo(13)],
    ["READY_FOR_PICKUP", "DELIVERED", daysAgo(12)],
  ] as const) {
    await db.taskStatusChange.create({
      data: {
        taskId: t6.id, userId: aminata.id, userName: aminata.name,
        fromStatus: from, toStatus: to,
        reason: to === "BLOCKED" ? "Fabric shipment delayed at the market." : null,
        createdAt: at,
      },
    });
  }
  await db.taskProgressHistory.createMany({
    data: [
      { taskId: t6.id, userId: hamidou.id, userName: hamidou.name, previousProgress: 0, newProgress: 40, source: "MANUAL", createdAt: daysAgo(25) },
      { taskId: t6.id, userId: hamidou.id, userName: hamidou.name, previousProgress: 40, newProgress: 100, source: "MANUAL", note: "Top fully assembled.", createdAt: daysAgo(15) },
    ],
  });
  await db.inspectionRecord.create({
    data: {
      taskId: t6.id, inspectorId: aminata.id, inspectorName: aminata.name,
      decision: "APPROVED", comments: "Clean seams, excellent finish.", createdAt: daysAgo(14),
    },
  });

  const t7 = await db.productionTask.create({
    data: {
      code: "TASK-0007",
      orderId: orderSenegal.id,
      garmentPiece: "Wrap Skirt",
      category: "FULL",
      deadline: daysAgo(6),
      priority: "NORMAL",
      status: "DELIVERED",
      progress: 100,
      assignedStaffId: hamidou.id,
      createdById: david.id,
      completedAt: daysAgo(8),
      deliveredAt: daysAgo(7),
      createdAt: daysAgo(20),
      updatedAt: daysAgo(7),
    },
  });
  await db.taskAssignment.create({
    data: {
      taskId: t7.id, staffId: hamidou.id, staffName: hamidou.name,
      assignedById: david.id, assignedByName: david.name, type: "ASSIGN", createdAt: daysAgo(20),
    },
  });
  for (const [code, state] of doneStates("FULL")) {
    await db.taskStageState.create({
      data: { taskId: t7.id, stageId: stages[code], state, updatedById: hamidou.id, updatedByName: hamidou.name, updatedAt: daysAgo(8) },
    });
  }
  for (const [from, to, at] of [
    ["UNASSIGNED", "ASSIGNED", daysAgo(20)],
    ["ASSIGNED", "ONGOING", daysAgo(19)],
    ["ONGOING", "AWAITING_INSPECTION", daysAgo(9)],
    ["AWAITING_INSPECTION", "APPROVED", daysAgo(8)],
    ["APPROVED", "READY_FOR_PICKUP", daysAgo(8, 14)],
    ["READY_FOR_PICKUP", "DELIVERED", daysAgo(7)],
  ] as const) {
    await db.taskStatusChange.create({
      data: { taskId: t7.id, userId: david.id, userName: david.name, fromStatus: from, toStatus: to, createdAt: at },
    });
  }
  await db.inspectionRecord.create({
    data: {
      taskId: t7.id, inspectorId: david.id, inspectorName: david.name,
      decision: "APPROVED", comments: "Good pleat alignment.", createdAt: daysAgo(8),
    },
  });

  const t8 = await db.productionTask.create({
    data: {
      code: "TASK-0008",
      orderId: orderSenegal.id,
      garmentPiece: "Headwrap",
      category: "IRONING",
      deadline: daysAgo(10),
      priority: "NORMAL",
      status: "DELIVERED",
      progress: 100,
      assignedStaffId: lawal.id,
      createdById: aminata.id,
      completedAt: daysAgo(9),
      deliveredAt: daysAgo(7),
      createdAt: daysAgo(20),
      updatedAt: daysAgo(7),
    },
  });
  await db.taskAssignment.create({
    data: {
      taskId: t8.id, staffId: lawal.id, staffName: lawal.name,
      assignedById: aminata.id, assignedByName: aminata.name, type: "ASSIGN", createdAt: daysAgo(20),
    },
  });
  for (const [code, state] of doneStates("IRONING")) {
    await db.taskStageState.create({
      data: { taskId: t8.id, stageId: stages[code], state, updatedById: lawal.id, updatedByName: lawal.name, updatedAt: daysAgo(9) },
    });
  }
  for (const [from, to, at, reason] of [
    ["UNASSIGNED", "ASSIGNED", daysAgo(20), null],
    ["ASSIGNED", "ONGOING", daysAgo(19), null],
    ["ONGOING", "AWAITING_INSPECTION", daysAgo(16), null],
    ["AWAITING_INSPECTION", "CHANGES_REQUESTED", daysAgo(16, 15), null],
    ["CHANGES_REQUESTED", "ONGOING", daysAgo(15), null],
    ["ONGOING", "AWAITING_INSPECTION", daysAgo(9, 9), null],
    ["AWAITING_INSPECTION", "APPROVED", daysAgo(9), null],
    ["APPROVED", "READY_FOR_PICKUP", daysAgo(8), null],
    ["READY_FOR_PICKUP", "DELIVERED", daysAgo(7), null],
  ] as const) {
    await db.taskStatusChange.create({
      data: {
        taskId: t8.id, userId: aminata.id, userName: aminata.name,
        fromStatus: from, toStatus: to, reason, createdAt: at,
      },
    });
  }
  await db.inspectionRecord.createMany({
    data: [
      {
        taskId: t8.id, inspectorId: aminata.id, inspectorName: aminata.name,
        decision: "CHANGES_REQUESTED",
        comments: "Pressing marks still visible on the edges. Redo the ironing.",
        createdAt: daysAgo(16, 15),
      },
      {
        taskId: t8.id, inspectorId: aminata.id, inspectorName: aminata.name,
        decision: "APPROVED", comments: "Corrected. Crisp finish.", createdAt: daysAgo(9),
      },
    ],
  });

  // OSSZ-2025-009 — Kaftan (delivered)
  const t9 = await db.productionTask.create({
    data: {
      code: "TASK-0009",
      orderId: orderKaftan.id,
      garmentPiece: "Kaftan Gown",
      category: "FULL",
      deadline: daysAgo(56),
      priority: "NORMAL",
      status: "DELIVERED",
      progress: 100,
      assignedStaffId: hamidou.id,
      createdById: aminata.id,
      completedAt: daysAgo(58),
      deliveredAt: daysAgo(55),
      createdAt: daysAgo(70),
      updatedAt: daysAgo(55),
    },
  });
  await db.taskAssignment.create({
    data: {
      taskId: t9.id, staffId: hamidou.id, staffName: hamidou.name,
      assignedById: aminata.id, assignedByName: aminata.name, type: "ASSIGN", createdAt: daysAgo(70),
    },
  });
  for (const [code, state] of doneStates("FULL")) {
    await db.taskStageState.create({
      data: { taskId: t9.id, stageId: stages[code], state, updatedById: hamidou.id, updatedByName: hamidou.name, updatedAt: daysAgo(58) },
    });
  }
  await db.inspectionRecord.create({
    data: {
      taskId: t9.id, inspectorId: aminata.id, inspectorName: aminata.name,
      decision: "APPROVED", comments: "Beautiful mandarin collar work.", createdAt: daysAgo(58),
    },
  });

  // ---------------------------------------------------------------------
  // 7. Comments & reported issues
  // ---------------------------------------------------------------------
  await db.taskComment.createMany({
    data: [
      {
        taskId: t1.id, userId: hamidou.id, userName: hamidou.name,
        message: "Jacket body assembled. Will set the sleeves tomorrow.",
        createdAt: daysAgo(5),
      },
      {
        taskId: t3.id, userId: lawal.id, userName: lawal.name,
        message: "Waiting for the vest lining fabric promised by the customer. Production is stalled.",
        isIssue: true,
        createdAt: daysAgo(3, 11),
      },
    ],
  });

  // ---------------------------------------------------------------------
  // 8. Notifications (internal)
  // ---------------------------------------------------------------------
  await db.notification.createMany({
    data: [
      {
        userId: hamidou.id, type: "TASK_ASSIGNED",
        title: "New task assigned",
        message: `You were assigned ${t1.code} — Jacket (OSSZ-2026-001).`,
        taskId: t1.id, createdAt: daysAgo(15),
      },
      {
        userId: hamidou.id, type: "TASK_ASSIGNED",
        title: "New task assigned",
        message: `You were assigned ${t2.code} — Trousers (OSSZ-2026-001).`,
        taskId: t2.id, createdAt: daysAgo(15, 10, ),
      },
      {
        userId: lawal.id, type: "TASK_ASSIGNED",
        title: "New task assigned",
        message: `You were assigned ${t3.code} — Vest (OSSZ-2026-001).`,
        taskId: t3.id, createdAt: daysAgo(14),
      },
      {
        userId: aminata.id, type: "ISSUE_REPORTED",
        title: "Production issue reported",
        message: `Lawal Sani reported an issue on ${t3.code} — Vest: missing lining fabric.`,
        taskId: t3.id, createdAt: daysAgo(3, 11),
      },
      {
        userId: david.id, type: "ISSUE_REPORTED",
        title: "Production issue reported",
        message: `Lawal Sani reported an issue on ${t3.code} — Vest: missing lining fabric.`,
        taskId: t3.id, createdAt: daysAgo(3, 11),
      },
      {
        userId: aminata.id, type: "TASK_OVERDUE",
        title: "Task overdue",
        message: `${t3.code} — Vest (OSSZ-2026-001) has passed its deadline.`,
        taskId: t3.id, createdAt: daysAgo(1),
      },
      {
        userId: david.id, type: "TASK_OVERDUE",
        title: "Task overdue",
        message: `${t3.code} — Vest (OSSZ-2026-001) has passed its deadline.`,
        taskId: t3.id, createdAt: daysAgo(1),
      },
    ],
  });

  // ---------------------------------------------------------------------
  // 9. Management assessments & incentive history
  // ---------------------------------------------------------------------
  const lastMonth = new Date(now().getFullYear(), now().getMonth() - 1, 1);
  const lastMonthPeriod = `${lastMonth.getFullYear()}-${String(lastMonth.getMonth() + 1).padStart(2, "0")}`;
  await db.staffAssessment.createMany({
    data: [
      {
        staffId: hamidou.id, staffName: hamidou.name, period: lastMonthPeriod,
        score: 88, comments: "Excellent craftsmanship and autonomy.", updatedAt: daysAgo(5),
      },
      {
        staffId: lawal.id, staffName: lawal.name, period: lastMonthPeriod,
        score: 65, comments: "Good effort; watch deadlines and pressing quality.", updatedAt: daysAgo(5),
      },
    ],
  });

  await db.incentiveRecord.createMany({
    data: [
      {
        schemeId: monthlyScheme.id, schemeName: monthlyScheme.name,
        staffId: hamidou.id, staffName: hamidou.name,
        period: lastMonthPeriod, performanceScore: 82.4, eligible: true, proposedAmount: 25000,
        status: "APPROVED", approvedById: aminata.id, approvedByName: aminata.name,
        approvalComments: "Consistent excellence all month.",
        paymentStatus: "PAID", paymentDate: daysAgo(4),
        createdAt: daysAgo(5), updatedAt: daysAgo(4),
      },
      {
        schemeId: monthlyScheme.id, schemeName: monthlyScheme.name,
        staffId: lawal.id, staffName: lawal.name,
        period: lastMonthPeriod, performanceScore: 48.6, eligible: false, proposedAmount: 0,
        status: "REJECTED", approvedById: aminata.id, approvedByName: aminata.name,
        approvalComments: "Below the 75/100 threshold for this month.",
        paymentStatus: "UNPAID",
        createdAt: daysAgo(5), updatedAt: daysAgo(5),
      },
    ],
  });

  // ---------------------------------------------------------------------
  // 10. Audit trail
  // ---------------------------------------------------------------------
  await db.auditLog.createMany({
    data: [
      { userId: aminata.id, userName: aminata.name, action: "USER_CREATED", entityType: "USER", entityId: david.id, details: JSON.stringify({ role: "ADMIN" }), createdAt: daysAgo(60) },
      { userId: aminata.id, userName: aminata.name, action: "CUSTOMER_CREATED", entityType: "CUSTOMER", entityId: john.id, details: JSON.stringify({ code: "CUST-0001" }), createdAt: daysAgo(21) },
      { userId: aminata.id, userName: aminata.name, action: "ORDER_CREATED", entityType: "ORDER", entityId: orderSuit.id, details: JSON.stringify({ orderNumber: "OSSZ-2026-001" }), createdAt: daysAgo(20) },
      { userId: aminata.id, userName: aminata.name, action: "TASKS_CREATED", entityType: "ORDER", entityId: orderSuit.id, details: JSON.stringify({ tasks: [t1.code, t2.code, t3.code] }), createdAt: daysAgo(15) },
      { userId: aminata.id, userName: aminata.name, action: "TASK_ASSIGNED", entityType: "TASK", entityId: t1.id, details: JSON.stringify({ staff: hamidou.name }), createdAt: daysAgo(15) },
      { userId: aminata.id, userName: aminata.name, action: "TASK_ASSIGNED", entityType: "TASK", entityId: t2.id, details: JSON.stringify({ staff: hamidou.name }), createdAt: daysAgo(15) },
      { userId: aminata.id, userName: aminata.name, action: "TASK_ASSIGNED", entityType: "TASK", entityId: t3.id, details: JSON.stringify({ staff: lawal.name }), createdAt: daysAgo(14) },
      { userId: hamidou.id, userName: hamidou.name, action: "PROGRESS_UPDATED", entityType: "TASK", entityId: t1.id, details: JSON.stringify({ from: 45, to: 60 }), createdAt: daysAgo(2) },
      { userId: lawal.id, userName: lawal.name, action: "ISSUE_REPORTED", entityType: "TASK", entityId: t3.id, details: JSON.stringify({ issue: "Missing lining fabric" }), createdAt: daysAgo(3, 11) },
      { userId: aminata.id, userName: aminata.name, action: "INCENTIVE_APPROVED", entityType: "INCENTIVE", entityId: hamidou.id, details: JSON.stringify({ period: lastMonthPeriod, amount: 25000 }), createdAt: daysAgo(4) },
    ],
  });

  console.log("Seed complete.");
  console.log("Demo accounts (password: Atelier@2026):");
  console.log("  MANAGEMENT  management@atelier.com");
  console.log("  ADMIN       admin@atelier.com");
  console.log("  STAFF       hamidou@atelier.com");
  console.log("  STAFF       lawal@atelier.com");
  console.log("  STAFF       ngozi@atelier.com");
}

main()
  .catch((e) => {
    console.error(e);
    process.exit(1);
  })
  .finally(() => db.$disconnect());
