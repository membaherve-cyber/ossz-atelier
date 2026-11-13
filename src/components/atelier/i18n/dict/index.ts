// OSSZ ATELIER — merges every bilingual dictionary module into one lookup map.
// Each screen owns its own file so entries can be added independently.

import type { DictEntries } from "../core";
import common from "./common";
import shell from "./shell";
import login from "./login";
import dashboard from "./dashboard";
import notifications from "./notifications";
import tasks from "./tasks";
import taskCreate from "./task-create";
import taskDetail from "./task-detail";
import orders from "./orders";
import customers from "./customers";
import performance from "./performance";
import incentives from "./incentives";
import reports from "./reports";
import users from "./users";
import stages from "./stages";
import audit from "./audit";

const merged: DictEntries = Object.assign(
  {},
  common,
  shell,
  login,
  dashboard,
  notifications,
  tasks,
  taskCreate,
  taskDetail,
  orders,
  customers,
  performance,
  incentives,
  reports,
  users,
  stages,
  audit
);

export { merged };
