# Cutting QMS — QA Test Flows (SKW-QMS-17)

Admin module for the cutting floor workflow: **Lay Inspection → Cut Panel Inspection → Bundling Inspection**, with sign-offs, special approvals, revisions, bundle release and reporting.

This document pairs with the dummy data seeder `Database\Seeders\CuttingQmsDummySeeder`, which creates one audit per scenario so QA can jump straight into any branch.

---

## 1. Setup

### 1.1 Load the dummy data

```bash
php artisan db:seed --class=CuttingQmsDummySeeder
```

Add `--force` if `APP_ENV=production`.

> **Warning — this resets cutting data.** The seeder deletes every row in the cutting tables (audits, stages, checkpoint results, sign-offs, special approvals, bundles, size patterns, checkpoint photos) before recreating the demo set. It does **not** touch buyers, suppliers, fabric records or non-cutting users. Safe to re-run for a clean slate.
>
> It is **not** registered in `DatabaseSeeder`, so a plain `php artisan db:seed` will not run it.
>
> It also calls `RoleSeeder`, `CuttingQmsSeeder` and `AdminUserSeeder`, and runs `DemoDataSeeder` only when no buyers exist.

### 1.2 Logins

All cutting accounts use the pattern `<Role>@12345`.

| Role | Email | Password | Purpose |
|------|-------|----------|---------|
| Admin | `admin@fabricsourcing.in` | `Admin@12345` | Full access, config screens |
| Manager | `manager@fabricsourcing.in` | `Manager@12345` | Full cutting workflow incl. release |
| Cutting QC | `qc@fabricsourcing.in` | `Qc@12345` | Submits stages (auto-signs QC) |
| Cutting Incharge | `incharge@fabricsourcing.in` | `Incharge@12345` | Sign-offs + release |
| QAM | `qam@fabricsourcing.in` | `Qam@12345` | Sign-offs, special approvals, release |
| Management | `management@fabricsourcing.in` | `Management@12345` | Read-only |
| Merchandiser | `merch@fabricsourcing.in` | `Merch@12345` | Read-only, **only assigned buyers** |

### 1.3 Key routes

| Screen | URL |
|--------|-----|
| Dashboard | `/admin/cutting` |
| Audit register | `/admin/cutting/audits` |
| Create audit | `/admin/cutting/audits/create` |
| Audit detail | `/admin/cutting/audits/{id}` |
| Stage form | `/admin/cutting/audits/{id}/stages/{lay\|cut_panel\|bundling}` |
| Reports | `/admin/cutting/reports` |
| Checklists (admin) | `/admin/cutting/checklists` |
| Cutting tables (admin) | `/admin/cutting/tables` |

### 1.4 Notifications

`CuttingStageReadyNotification` and `CuttingSpecialApprovalRequested` write to the database channel **immediately** (they do not implement `ShouldQueue`). The bell icon updates without a queue worker. Mail goes through the configured mailer.

---

## 2. Role & permission matrix

| Capability | admin | manager | cutting_qc | cutting_incharge | qam | management | merchandiser |
|------------|:-----:|:-------:|:----------:|:----------------:|:---:|:----------:|:------------:|
| View dashboard / register | Y | Y | Y | Y | Y | Y | Y (assigned buyers) |
| Create / edit audit header | Y | Y | Y | Y | – | – | – |
| Submit stage | Y | Y | Y | Y | – | – | – |
| Sign off **lay / cut_panel / bundling** | Y | Y | – | Y | Y | – | – |
| Approve / reject special approval | Y | – | – | – | Y | – | – |
| Release bundles | Y | Y | – | Y | Y | – | – |
| Export cutting reports | Y | Y | – | Y | Y | Y | – |
| Manage checklists / tables / numbering | Y | – | – | – | – | – | – |

Notes:
- **QC cannot sign off directly** — QC *submits* a stage and is auto-recorded as the QC sign-off (QC holds a required sign-off role). The Incharge then signs.
- **Admin bypasses role checks** but only records a sign-off if the admin actually holds a required role, so an admin submit does not auto-complete a stage.
- **A user may hold several roles** (e.g. `cutting_qc` + `cutting_incharge`). All roles are shown on the user's profile and sidebar, all role permissions apply, and submitting a stage records a sign-off for *every* required role that user holds (so one submit can complete both the QC and Incharge sign-offs). A user who already signed one role can still sign another required role they hold.
- The `viewer` role has no cutting access → expect **403** on `/admin/cutting/*`.

---

## 3. End-to-end happy path

Do this on a freshly created audit, or inspect the pre-seeded ready states.

1. **QC** logs in → `/admin/cutting/audits/create` → fill header (table, date, shift, buyer, style, PO, lot, fabric, colour, dia, lay/cut numbers, sizes, marking type, required/actual length, width, plies, tolerance %). Save → redirected to the **Lay** stage form. *(Pre-seeded equivalent: PO-9001 = draft.)*
2. **QC** on Lay → mark all checkpoints **OK**, choose result **"Approved to CUT"** → **Submit**. Stage locks, QC auto-signed, audit `in_progress`, awaiting Cutting Incharge. *(Pre-seeded: PO-9002.)*
3. **Incharge** opens the audit → clicks **Sign off** on Lay. Lay = `approved`, current stage advances to **Cut Panel**. *(Pre-seeded: PO-9003.)*
4. **QC** on Cut Panel → checkpoints + size patterns → result **"Approved for bundling"** → Submit. Awaiting Incharge. *(Pre-seeded: PO-9004.)*
5. **Incharge** signs off Cut Panel → advances to **Bundling**. *(Pre-seeded: PO-9005.)*
6. **QC** on Bundling → checkpoints + bundles → result **"Approved to issue for sewing"** → Submit. Awaiting Incharge, then QAM. *(Pre-seeded: PO-9006.)*
7. **Incharge** signs → awaiting **QAM**.
8. **QAM** signs → Bundling `approved`. Audit ready to release. *(Pre-seeded: PO-9007.)*
9. **Incharge/QAM/Manager** → **Release bundles**. Bundles → `released`, audit `status = closed`, `closed_at` set. *(Pre-seeded: PO-9008.)*

**Expected throughout:** dashboard KPIs, pending-sign-off counters, timeline and audit report (PDF) reflect the new state; each sign-off records the acting user, role and timestamp.

---

## 4. Branch flows

### Flow B — Not approved + revision (PO-9009)
1. Open **PO-9009** (parent **CQA-1009**, Lay = `not_approved`).
2. Confirm the not-OK checkpoint(s) and remarks are shown, and the audit offers **Request revision**.
3. Click **Request revision** → a new audit is created as **revision 2** (**CQA-1010**), same PO, Lay stage reset to `draft`, no stages copied from the parent.
4. Confirm the revision is linked to the parent/root and the register shows the revision chain.
5. Complete the reinspection on CQA-1010 and confirm it can proceed normally.

### Flow C — Special approval (PO-9010 / 9011 / 9012)
- **Pending** — PO-9010: Lay submitted with result *special_approval* → stage `special_pending`, one **pending** approval awaiting QAM.
- **Approve** — open PO-9011: Cut Panel approval already **approved** → stage `special_approved`, advanced to Bundling.
- **Reject** — open PO-9012: Cut Panel approval **rejected** → stage returns to `not_approved`.
- **To exercise it live:** pick a stage, submit with result *special_approval*, then log in as **QAM** → approve or reject from the approval panel. A `special_approved` stage only advances once the approval is approved **and** all sign-offs are complete.
- Check the **Special Approval Log** report (should list 3 rows: pending / approved / rejected).

### Flow D — Blocking checkpoint (PO-9013)
1. The seeder flags **"Stripe/check matching"** as a *blocking* checkpoint (global template setting).
2. Open PO-9013 Lay, mark Stripe/check **not OK**, and try to submit with result **Approved to CUT**.
3. **Expected:** submission is rejected — an approved result is not allowed while a blocking checkpoint is not OK (server-side enforced, not just UI).
4. Submitting with result **Not approved to CUT** succeeds.

### Flow E — Mandatory remarks (PO-9013)
1. The seeder flags **"Tension"** as *mandatory remarks on not-OK*.
2. On any stage, mark Tension **not OK** and submit **without** a remark → validation error.
3. Add a remark → submission succeeds. (PO-9013 already has a Tension remark + checkpoint photo.)

### Flow F — Tolerance & plies mismatch (PO-9014)
1. Open PO-9014: actual lay length/width exceed the required values beyond the tolerance %, and actual plies ≠ required plies.
2. **Expected:** the stage/detail view shows out-of-tolerance badges for length and width and a plies-mismatch indicator.
3. Confirm an approved result is still possible if the checkpoints are OK (tolerance is informational unless a checkpoint blocks), and that the mismatch is visible on the report.

### Flow G — Release & close (PO-9007 → PO-9008)
1. PO-9007: all three stages approved, bundles `created`, not yet released.
2. As Incharge/QAM/Manager, **Release bundles** → bundles become `released`, audit `closed`.
3. PO-9008 demonstrates the finished state (bundles released, audit `closed`).
4. Confirm a closed audit cannot be edited/submitted further, and bundle print is available (`/admin/cutting/bundles/{bundle}/print`).

### Flow H — Merchandiser visibility (PO-9015)
1. Log in as **merch@fabricsourcing.in**.
2. The register/dashboard show **only audits for the buyer assigned to this merchandiser** (the seeder assigns the merchandiser to the first buyer only).
3. **PO-9015** belongs to an *unassigned* buyer → it must **not** appear. Opening it directly must 403/404.
4. Non-merchandiser roles see all audits (no restriction).

### Flow I — Reports & exports
From `/admin/cutting/reports` verify each report renders and exports:
- **Defect Analysis** — not-OK checkpoints grouped (expect ~6 rows).
- **Special Approval Log** — 3 rows (pending / approved / rejected).
- **Sign-off Turnaround** — per sign-off elapsed time (expect ~43 rows).
- **Table-wise Performance** — per cutting table (expect 5 tables).
- **Audit PDF report** (`/admin/cutting/audits/{id}/report`) and **register Excel export** (`/admin/cutting/audits/export`).

### Flow J — Admin configuration
- **Checklists** (`/admin/cutting/checklists`): edit template header, add/edit/delete checkpoints, reorder (move), create a **new version**, and adjust numbering (prefix/padding/next number).
- **Cutting tables** (`/admin/cutting/tables`): create, edit, deactivate.
- **Numbering**: confirm new audits continue the `CQA-####` sequence after changing `next_number`.

### Flow K — Negative / permission tests
- **management** and **merchandiser**: no create/edit/submit/sign-off/release controls; direct POST to those endpoints → 403.
- **viewer**: 403 on all `/admin/cutting/*`.
- **cutting_qc**: can submit but has no sign-off button; sign-off POST → 403.
- Unauthenticated access redirects to login.

---

## 5. Seeded scenario reference

`audit_no` is generated at runtime, so map scenarios by **PO number**.

| PO | Scenario | Expected state |
|----|----------|----------------|
| PO-9001 | Draft, unsaved lay stage | audit `in_progress`, lay `draft` |
| PO-9002 | Lay submitted | lay `submitted`, awaiting Incharge |
| PO-9003 | Lay approved, cut panel draft | lay `approved`, cut_panel `draft` |
| PO-9004 | Cut panel submitted | awaiting Incharge |
| PO-9005 | Cut panel approved, bundling draft | bundling `draft` |
| PO-9006 | Bundling submitted | awaiting Incharge/QAM |
| PO-9007 | All stages approved | ready to release bundles |
| PO-9008 | Bundles released | audit `closed` |
| PO-9009 | Lay not approved + revision | parent + revision 2 (PO-9009) |
| PO-9010 | Lay special approval pending | `special_pending`, 1 pending approval |
| PO-9011 | Cut panel special approval approved | advanced to bundling |
| PO-9012 | Cut panel special approval rejected | cut_panel `not_approved` |
| PO-9013 | Blocking checkpoint + mandatory remarks | lay `not_approved` |
| PO-9014 | Out of tolerance + plies mismatch | lay awaiting Incharge |
| PO-9015 | Buyer not assigned to merchandiser | visibility test |

Coverage produced by the seeder: 16 audits, 27 stages (all statuses present), 180 checkpoint results, 43 sign-offs, 3 special approvals (pending/approved/rejected), 11 bundles (8 created / 3 released), 21 size patterns, 1 checkpoint photo, 1 revision.

---

## 6. Reset

Re-run the seeder for a clean slate:

```bash
php artisan db:seed --class=CuttingQmsDummySeeder
```

To wipe all cutting data without reseeding, run the seeder's reset alone (or delete rows via the app). To rebuild the whole database in a non-production environment: `php artisan migrate:fresh --seed`.
