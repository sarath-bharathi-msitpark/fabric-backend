# Cutting QMS — Executable Test Cases (SKW-QMS-17)

Companion to `docs/cutting-qms-qa-flows.md`. This sheet gives **click-level user flows** for every case, in the order QA should run them. Each test case (TC) lists the role, precondition, steps and expected result so it can be logged as Pass/Fail.

- Use **Search by PO#** in the register to locate a scenario row (the `Audit ID` like `CQA-####` is generated at runtime and will differ).
- Precondition references the seeded data from `php artisan db:seed --class=CuttingQmsDummySeeder`.

## Logins

| Role | Email | Password |
|------|-------|----------|
| Admin | `admin@fabricsourcing.in` | `Admin@12345` |
| Manager | `manager@fabricsourcing.in` | `Manager@12345` |
| Cutting QC | `qc@fabricsourcing.in` | `Qc@12345` |
| Cutting Incharge | `incharge@fabricsourcing.in` | `Incharge@12345` |
| QAM | `qam@fabricsourcing.in` | `Qam@12345` |
| Management | `management@fabricsourcing.in` | `Management@12345` |
| Merchandiser | `merch@fabricsourcing.in` | `Merch@12345` |

---

## A. Access, navigation & dashboard

### TC-001 — Cutting section is reachable and guarded
- **Role:** unauthenticated, then QC
- **Steps:**
  1. Open `/admin/cutting` while logged out.
  2. Log in as QC and open `/admin/cutting`.
- **Expected:** step 1 redirects to login; step 2 shows the **Cutting QMS Dashboard**.

### TC-002 — Dashboard renders with data
- **Role:** QC
- **Steps:** Open `/admin/cutting`.
- **Expected:** KPI tiles show **Audits Today, Lay Approval %, Cut Panel Approval %, Bundles Issued, Total Audits, Pending Sign-offs** with non-zero values; the three stage cards (Lay/Cut Panel/Bundling) show Approved / Not Approved / Special / Pending counts; **Top Not OK Checkpoints (7 days)**, **Pending Sign-offs** and **Cutting Table Status** tables are populated.

### TC-003 — Dashboard filters
- **Role:** QC
- **Steps:** On the dashboard set a **From/To** date range and a **Buyer** / **Cutting Table** / **Shift**, click **Apply**, then **Reset**.
- **Expected:** KPIs, stage cards and tables update to the filtered scope; **Reset** restores all data.

### TC-004 — Audit register loads
- **Role:** QC
- **Steps:** Dashboard → **Audit Register** (or `/admin/cutting/audits`).
- **Expected:** Table lists audits with columns **Audit ID, Date, Table, Buyer, Style# / PO#, Lay, Cut Panel, Bundling, Rev** and row actions **View / Edit / Delete**.

### TC-005 — Register search & filters
- **Role:** QC
- **Steps:** Search `PO-9008`; then filter by **All stages = Bundling**, **All results**, a **Buyer**, a **Shift**, and click **Apply** / **Reset**.
- **Expected:** Search finds PO-9008; each filter narrows the list; **Reset** clears all.

### TC-006 — Register export
- **Role:** Manager (has export), then QC (no export)
- **Steps:** As Manager click **Export Excel**; repeat as QC.
- **Expected:** Manager downloads an `.xlsx` matching the current filters; QC sees **no** Export button.

---

## B. Create audit & header

### TC-007 — Create a new audit
- **Role:** QC
- **Steps:** Register → **+ New Audit** → fill **Audit Date**, **Cutting Table**, Shift, Buyer, Style#, Style Name, PO#, LOT#, Fabric, Colour, DIA; in **Lay Details** fill Lay/Cut numbers, **Number of Sizes**, **Lay Method**, marking type, required/actual length & width, plies, height, total parts, tolerance; click **Create audit**.
- **Expected:** Audit is saved, `Audit ID` assigned (`CQA-####`), redirected to the audit detail with the **Lay Inspection** stage available.

### TC-008 — Required field validation
- **Role:** QC
- **Steps:** Click **+ New Audit**, leave **Audit Date**, **Number of Sizes** and **Lay Method** empty, click **Create audit**.
- **Expected:** Form re-displays with validation errors; audit not created.

### TC-009 — Edit header
- **Role:** QC
- **Steps:** Open any audit → **Edit header** → change Shift/PO# → **Save changes**.
- **Expected:** Changes persist; **Cancel** returns without saving.

### TC-010 — Header locking after submit
- **Role:** QC
- **Steps:** Open a stage that is already **submitted/approved** (e.g. PO-9002 Lay) and check whether **Edit header** is available.
- **Expected:** Header edit is blocked/not offered once a stage is locked (or edits do not affect locked stage data).

---

## C. Lay inspection — happy path

### TC-011 — Save lay draft
- **Role:** QC
- **Precondition:** PO-9001 (Lay draft)
- **Steps:** Open PO-9001 → **Inspect** on **Lay Inspection** → tick some **OK** radios → **Save draft**.
- **Expected:** "Progress: X of Y checked" updates; audit status becomes **in_progress**; stage stays **draft**; no lock.

### TC-012 — Submit lay as approved
- **Role:** QC
- **Precondition:** PO-9002 is submitted; for a fresh run use TC-011 draft
- **Steps:** On Lay, set every checkpoint to **OK**, choose **Overall result = "Approved to CUT"**, click **Submit stage** → confirm.
- **Expected:** Stage locks (`submitted`), QC auto-recorded in **Digital Sign-offs**, message says **Awaiting sign-off: Cutting Incharge**; stage fields become read-only.

### TC-013 — Incharge signs off lay
- **Role:** Cutting Incharge
- **Precondition:** PO-9002
- **Steps:** Open PO-9002 → Lay Inspection → **Sign off** (add a comment).
- **Expected:** Lay = **approved**; audit advances to **Cut Panel Inspection**; sign-off row shows Incharge name/time; Cut Panel unlocks.

### TC-014 — Wrong signer blocked
- **Role:** QC
- **Precondition:** PO-9002 (awaiting Incharge)
- **Steps:** Open PO-9002 Lay as QC.
- **Expected:** No **Sign off** button; message "You are not the next authorized signer for this stage." (or 403 on direct POST).

### TC-015 — Sign-off order enforced
- **Role:** QAM
- **Precondition:** PO-9002 (awaiting Cutting Incharge, not QAM)
- **Steps:** Open Lay as QAM.
- **Expected:** QAM cannot sign before Incharge — no sign-off form / message shown.

---

## D. Cut panel inspection

### TC-016 — Cut panel draft (PO-9003)
- **Role:** QC
- **Precondition:** PO-9003 (Lay approved, Cut Panel draft)
- **Steps:** Open PO-9003 → **Cut Panel Inspection**.
- **Expected:** Stage is unlocked and editable; **Size-wise Pattern Matching** section visible with **Size / Top / Middle / Bottom / Remarks** and **Match / No match** selects.

### TC-017 — Submit cut panel with pattern matching
- **Role:** QC
- **Steps:** Fill all checkpoints **OK**, fill the size rows (Match/No match), set **Overall result = "Approved for bundling"**, **Submit stage**.
- **Expected:** Stage locks, awaiting **Cutting Incharge**; size patterns saved and shown when reopened.

### TC-018 — Incharge signs cut panel
- **Role:** Cutting Incharge
- **Precondition:** PO-9004
- **Steps:** Open PO-9004 → Cut Panel → **Sign off**.
- **Expected:** Cut Panel = **approved**, advances to **Bundling Inspection**; Bundling unlocks.

---

## E. Bundling inspection

### TC-019 — Bundling draft with bundles (PO-9005)
- **Role:** QC
- **Precondition:** PO-9005 (Cut Panel approved, Bundling draft)
- **Steps:** Open PO-9005 → **Bundling Inspection**.
- **Expected:** **Bundles** table with columns **Bundle No / Size / Panel Numbers / Pieces / Print**, a **+ Add bundle** button, and the **Overall result** select.

### TC-020 — Add / edit bundles
- **Role:** QC
- **Steps:** Click **+ Add bundle**, enter Bundle No, Size, Panel Numbers, Pieces; **Save draft**; reopen.
- **Expected:** Bundle row persists; **Print** card link appears for saved bundles.

### TC-021 — Submit bundling
- **Role:** QC
- **Precondition:** PO-9006
- **Steps:** Set all checkpoints **OK**, set **Overall result = "Approved to issue for sewing"**, **Submit stage**.
- **Expected:** Awaiting **Cutting Incharge**, then **QAM**; QC auto-signed.

### TC-022 — Incharge then QAM sign bundling
- **Role:** Cutting Incharge, then QAM
- **Precondition:** PO-9006
- **Steps:** As Incharge sign off; then as QAM sign off.
- **Expected:** After Incharge → awaiting QAM; after QAM → Bundling = **approved**; audit ready to release.

---

## F. Release & close

### TC-023 — Release to sewing
- **Role:** Cutting Incharge (or QAM/Manager)
- **Precondition:** PO-9007 (all stages approved, bundles created)
- **Steps:** Open PO-9007 → **Bundles** panel → **Release to sewing** → confirm.
- **Expected:** All bundles → **released**, audit **status = closed**, `closed_at` set; the Release button disappears.

### TC-024 — Closed audit is read-only
- **Role:** Cutting Incharge
- **Precondition:** PO-9008 (closed)
- **Steps:** Open PO-9008 and try to open a stage / edit.
- **Expected:** Stages are view-only (locked); no submit/sign-off/release actions.

### TC-025 — Bundle print card
- **Role:** QC
- **Precondition:** PO-9008
- **Steps:** In the Bundles table click **Print**.
- **Expected:** Opens a printable bundle card (`/admin/cutting/bundles/{id}/print`) in a new tab.

---

## G. Not approved & revision

### TC-026 — Not approved lay (PO-9009)
- **Role:** Cutting Incharge
- **Precondition:** PO-9009 (Lay not_approved)
- **Steps:** Open PO-9009 → Lay.
- **Expected:** Stage shows **Not approved**; the not-OK checkpoint(s) and remarks are visible.

### TC-027 — Create revision
- **Role:** Cutting Incharge
- **Precondition:** PO-9009
- **Steps:** On the audit detail, in **Re-inspection** enter a reason → **Create revision**.
- **Expected:** A new audit is created as **Revision 2** (same PO), Lay reset to **draft**, **no stages copied**; the **Revision History** table links both revisions.

### TC-028 — Revision proceeds independently
- **Role:** QC
- **Steps:** Open the revision, complete Lay, submit.
- **Expected:** Revision can be worked and signed off independently of the parent.

---

## H. Special approval

### TC-029 — Submit a special approval request
- **Role:** QC
- **Precondition:** any unlocked stage (e.g. a fresh audit)
- **Steps:** Set checkpoints as needed, choose **Overall result = "Cut on special approval"** (Lay) / "On special approval" (Cut Panel/Bundling), fill **Special approval reason**, **Submit stage**.
- **Expected:** Stage = **special_pending**; a **Special Approvals** box appears with status **Pending**; QAM gets a notification (bell).

### TC-030 — Pending approval visible (PO-9010)
- **Role:** QAM
- **Precondition:** PO-9010
- **Steps:** Open PO-9010 → Lay.
- **Expected:** **Special Approvals** shows **Pending** with reason, requester and timestamp; **Approve / Reject** buttons visible to QAM.

### TC-031 — Approve special approval
- **Role:** QAM
- **Precondition:** PO-9010 (pending)
- **Steps:** Enter **Decision reason** → **Approve**.
- **Expected:** Approval = **approved**; once all sign-offs are complete the stage becomes **special_approved** and advances (compare with PO-9011).

### TC-032 — Reject special approval
- **Role:** QAM
- **Precondition:** PO-9012 (rejected) or a pending one
- **Steps:** **Reject** with a reason.
- **Expected:** Approval = **rejected**; stage becomes **not_approved** (PO-9012 shows this state).

### TC-033 — Non-QAM cannot decide
- **Role:** Cutting QC
- **Precondition:** PO-9010 (pending)
- **Steps:** Open PO-9010 → Special Approvals.
- **Expected:** No **Approve/Reject** buttons for QC.

### TC-034 — Special approval log
- **Role:** QAM
- **Steps:** Reports → **Special Approval Log**.
- **Expected:** 3 rows covering pending / approved / rejected.

---

## I. Blocking checkpoint & mandatory remarks

### TC-035 — Blocking checkpoint prevents approval
- **Role:** QC
- **Precondition:** PO-9013 (blocking checkpoint "Stripe/check matching")
- **Steps:** Open PO-9013 Lay, ensure the blocking checkpoint is **Not OK**, choose **Overall result = "Approved to CUT"**, **Submit stage**.
- **Expected:** Submission is **rejected** (server-side): an approved result is not allowed while a blocking checkpoint is Not OK.

### TC-036 — Blocking checkpoint allows Not approved
- **Role:** QC
- **Precondition:** PO-9013
- **Steps:** Same as TC-035 but choose **"Not approved to CUT"** → **Submit stage**.
- **Expected:** Submission succeeds; stage = **not_approved**. The checkpoint shows a **Blocks approval** tag.

### TC-037 — Mandatory remarks on Not OK
- **Role:** QC
- **Steps:** On any stage, mark the checkpoint tagged **Remarks required on Not OK** (e.g. Tension) as **Not OK** and **Submit** without a remark; then retry with a remark.
- **Expected:** First attempt fails validation; with a remark it succeeds.

### TC-038 — Checkpoint photo on Not OK
- **Role:** QC
- **Precondition:** PO-9013
- **Steps:** On a Not OK checkpoint use **Photo (Not OK)** to attach an image; **Save draft**; reopen.
- **Expected:** Uploaded photo(s) shown as **photo** links and openable.

---

## J. Tolerance & plies

### TC-039 — Tolerance flags display
- **Role:** QC
- **Precondition:** PO-9014 (length/width outside tolerance, plies mismatch)
- **Steps:** Open PO-9014 → Lay.
- **Expected:** Yellow **Tolerance flags** banner lists **Length outside tolerance**, **Width outside tolerance**, **Actual plies differ from required** as applicable.

### TC-040 — No flags when within tolerance
- **Role:** QC
- **Precondition:** PO-9003 (within tolerance)
- **Steps:** Open PO-9003 → Cut Panel.
- **Expected:** No tolerance banner.

---

## K. Merchandiser visibility

### TC-041 — Merchandiser sees only assigned buyer
- **Role:** Merchandiser
- **Steps:** Log in as `merch@` → open **Audit Register** and Dashboard.
- **Expected:** Only audits for the assigned buyer are listed.

### TC-042 — Unassigned buyer audit hidden
- **Role:** Merchandiser
- **Precondition:** PO-9015 (unassigned buyer)
- **Steps:** Search `PO-9015` in the register; then open its audit URL directly.
- **Expected:** PO-9015 does not appear; direct access returns 403/404.

### TC-043 — Other roles unrestricted
- **Role:** QC
- **Steps:** Search `PO-9015`.
- **Expected:** QC sees all audits regardless of buyer.

---

## L. Reports & exports

### TC-044 — Defect analysis
- **Role:** QAM
- **Steps:** Reports → **Defect Analysis**.
- **Expected:** Not-OK checkpoints grouped with counts (~6 rows); filters and Excel/PDF export work.

### TC-045 — Sign-off turnaround
- **Role:** Manager
- **Steps:** Reports → **Sign-off Turnaround**.
- **Expected:** Per-sign-off elapsed time listed (~43 rows); export works.

### TC-046 — Table-wise performance
- **Role:** Manager
- **Steps:** Reports → **Table-wise Performance**.
- **Expected:** Per-table audits, lay/cut-panel approval rates and rework counts (~5 tables); export works.

### TC-047 — Audit PDF report
- **Role:** QC
- **Steps:** Open any audit → **PDF**.
- **Expected:** A generated PDF with header, lay details, checkpoints, sign-offs and result.

---

## M. Admin configuration

### TC-048 — Checklist template & checkpoints
- **Role:** Admin
- **Steps:** `/admin/cutting/checklists` → edit template header; add a checkpoint; edit it; reorder with move; delete a test checkpoint.
- **Expected:** Changes persist and appear on new stage forms.

### TC-049 — Checklist new version
- **Role:** Admin
- **Steps:** Create a **new version** of a template; then open a stage.
- **Expected:** New version becomes current; existing audits keep their historical results.

### TC-050 — Checkpoint flags
- **Role:** Admin
- **Steps:** Toggle **Remarks required on Not OK** and **Blocks approval** on a checkpoint; open a stage.
- **Expected:** The corresponding tags and enforcement apply on the stage form.

### TC-051 — Numbering
- **Role:** Admin
- **Steps:** Change numbering (prefix/padding/next number) → create a new audit.
- **Expected:** New `Audit ID` follows the updated sequence.

### TC-052 — Cutting tables CRUD
- **Role:** Admin
- **Steps:** `/admin/cutting/tables` → create, edit, deactivate a table.
- **Expected:** Table appears in the audit form selectors when active; deactivated table is not selectable.

---

## N. Permissions / negative tests

### TC-053 — Management read-only
- **Role:** Management
- **Steps:** Open the register and an audit.
- **Expected:** No **+ New Audit**, Edit, Submit, Sign off or Release actions; export available; direct POSTs → 403.

### TC-054 — QC cannot sign off
- **Role:** Cutting QC
- **Precondition:** PO-9002
- **Steps:** Open the awaiting stage.
- **Expected:** No **Sign off** button; direct sign-off POST → 403.

### TC-055 — Viewer has no cutting access
- **Role:** viewer (or any non-cutting role)
- **Steps:** Open `/admin/cutting`.
- **Expected:** **403 Forbidden**.

### TC-056 — Admin override behaviour
- **Role:** Admin
- **Precondition:** PO-9002 (awaiting Incharge)
- **Steps:** Open Lay as Admin and submit/sign as applicable.
- **Expected:** Admin can access everything; an admin **submit** does not auto-complete a stage (no sign-off recorded for admin unless the admin holds the required role).

---

## Summary log

| TC | Title | Role | Pass/Fail | Notes |
|----|-------|------|-----------|-------|
| TC-001 | Cutting section guarded | QC | | |
| TC-002 | Dashboard renders | QC | | |
| TC-003 | Dashboard filters | QC | | |
| TC-004 | Register loads | QC | | |
| TC-005 | Register search & filters | QC | | |
| TC-006 | Register export | Manager/QC | | |
| TC-007 | Create audit | QC | | |
| TC-008 | Required validation | QC | | |
| TC-009 | Edit header | QC | | |
| TC-010 | Header locking | QC | | |
| TC-011 | Save lay draft | QC | | |
| TC-012 | Submit lay approved | QC | | |
| TC-013 | Incharge signs lay | Incharge | | |
| TC-014 | Wrong signer blocked | QC | | |
| TC-015 | Sign-off order enforced | QAM | | |
| TC-016 | Cut panel draft | QC | | |
| TC-017 | Submit cut panel | QC | | |
| TC-018 | Incharge signs cut panel | Incharge | | |
| TC-019 | Bundling draft | QC | | |
| TC-020 | Add/edit bundles | QC | | |
| TC-021 | Submit bundling | QC | | |
| TC-022 | Incharge + QAM sign | Incharge/QAM | | |
| TC-023 | Release to sewing | Incharge | | |
| TC-024 | Closed audit read-only | Incharge | | |
| TC-025 | Bundle print | QC | | |
| TC-026 | Not approved lay | Incharge | | |
| TC-027 | Create revision | Incharge | | |
| TC-028 | Revision proceeds | QC | | |
| TC-029 | Submit special approval | QC | | |
| TC-030 | Pending approval visible | QAM | | |
| TC-031 | Approve special | QAM | | |
| TC-032 | Reject special | QAM | | |
| TC-033 | Non-QAM cannot decide | QC | | |
| TC-034 | Special approval log | QAM | | |
| TC-035 | Blocking blocks approval | QC | | |
| TC-036 | Blocking allows Not approved | QC | | |
| TC-037 | Mandatory remarks | QC | | |
| TC-038 | Checkpoint photo | QC | | |
| TC-039 | Tolerance flags | QC | | |
| TC-040 | No flags in tolerance | QC | | |
| TC-041 | Merchandiser scoped | Merchandiser | | |
| TC-042 | Unassigned hidden | Merchandiser | | |
| TC-043 | Others unrestricted | QC | | |
| TC-044 | Defect analysis | QAM | | |
| TC-045 | Sign-off turnaround | Manager | | |
| TC-046 | Table performance | Manager | | |
| TC-047 | Audit PDF | QC | | |
| TC-048 | Checklist template | Admin | | |
| TC-049 | New version | Admin | | |
| TC-050 | Checkpoint flags | Admin | | |
| TC-051 | Numbering | Admin | | |
| TC-052 | Tables CRUD | Admin | | |
| TC-053 | Management read-only | Management | | |
| TC-054 | QC cannot sign off | QC | | |
| TC-055 | Viewer 403 | viewer | | |
| TC-056 | Admin override | Admin | | |
