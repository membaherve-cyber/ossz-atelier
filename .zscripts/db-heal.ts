// OSSZ ATELIER — database self-heal check (run at dev-server startup).
//
// Guards against the "empty database after environment reset" failure mode:
// an external reset can leave the SQLite file present but with all tables
// emptied (or dropped). Instead of failing with cryptic 500s on every API
// call, this script detects the state and restores the schema + demo data.
//
// Outcomes:
//   - schema + users present  -> no-op (data is NEVER touched)
//   - schema missing          -> prisma db push, then seed
//   - tables present but empty-> seed demo data
//
// It never drops or resets a populated database.

import { PrismaClient } from "@prisma/client";
import { execSync } from "child_process";

const db = new PrismaClient();

function run(cmd: string): void {
  execSync(cmd, { stdio: "inherit" });
}

async function main(): Promise<void> {
  let userCount: number;
  try {
    userCount = await db.user.count();
  } catch {
    // Table missing (or unreadable schema) — recreate it.
    console.log("[db-heal] Schema missing — running prisma db push");
    run("bunx prisma db push --skip-generate");
    console.log("[db-heal] Seeding demo data");
    run("bun prisma/seed.ts");
    return;
  }

  if (userCount > 0) {
    console.log(`[db-heal] Database OK (${userCount} users) — data preserved`);
    return;
  }

  console.log("[db-heal] Database is empty — restoring demo data");
  run("bun prisma/seed.ts");
}

main()
  .catch((e) => {
    console.error("[db-heal] failed:", e instanceof Error ? e.message : e);
    // Never block server startup on heal failure.
    process.exit(0);
  })
  .finally(() => db.$disconnect());
