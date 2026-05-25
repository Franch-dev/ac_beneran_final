# Backend Workflow Stabilization Report

## Scope
Focused backend workflow hardening for:
- `ServiceOrderController`
- `WorkflowController`
- `TechnicianController`
- `InvoiceController`
- Monitoring workflow button visibility in:
  - `resources/views/monitoring.blade.php`
  - `Modules/AcMasjidMusholla/resources/views/monitoring.blade.php`
- Workflow sync tests in `tests/Feature/*`

## Workflow Button Routes (backend endpoints used by monitoring actions)
- `POST /service-order/{serviceOrder}/approve`
- `POST /service-order/{serviceOrder}/create-spk-invoice`
- `POST /workflow/{serviceOrder}/approve-spk-invoice`
- `POST /service-order/{serviceOrder}/confirm-payment`
- `POST /workflow/{serviceOrder}/assign`
- `POST /workflow/{serviceOrder}/progress`
- `POST /service-order/{serviceOrder}/field-report`
- `POST /service-order/{serviceOrder}/approve-additional-fee`
- `POST /service-order/{serviceOrder}/finalize-order`
- `POST /service-order/{serviceOrder}/frontdesk-confirm-complete`
- `POST /service-order/{serviceOrder}/manager-confirm-complete`

## Bugs Found
1. **Workflow deadlock after SPK approval**: `approveSpkInvoice` left order at `spk_invoice_approved`, but technician assignment UI guard required `payment_verified`.
2. **Assignment sequence bypass risk**: assignment was allowed from `spk_invoice_approved` before payment confirmation.
3. **Technician completion bypass**:
   - `updateProgress` allowed `done` too early.
   - `completeJob` allowed completion from `technician_assigned` and moved status to legacy states (`work_completed` / `pending_fee_approval`) that diverged from main flow.
4. **Additional fee gate weak**: `finalizeOrder` could move to payment without forcing additional fee approval first.
5. **Premature completion risk**: payment-verified orders could be finalized before any completed technician assignment existed.
6. **Dual close step duplication**: frontdesk/manager confirmations each wrote `closed` step independently.
7. **Invoice editor schema mismatch**:
   - Used `description`/`price` fields on `service_details` (actual fields are `pk_type`/`brand`/`price_per_unit`).
   - Could break invoice edit flow and total recalculation.

## Fixes Applied
### Core workflow logic
- `WorkflowController@approveSpkInvoice`
  - now moves status to `waiting_payment`.
  - records both workflow steps: `spk_invoice_approved` and `waiting_payment`.
- `WorkflowController@assign`
  - now enforces assignment only after payment (`payment_verified`), with controlled reassignment only before work starts.
- `WorkflowController@updateProgress`
  - prevents `done` before `in_progress`.
  - syncs `done` transition to `waiting_review` step/status.
  - updates field-report baseline data when `done` is used.
- `WorkflowController@close`
  - no longer bypasses workflow by forcing completion from `payment_verified`.
  - now requires `completed` + both frontdesk/manager confirmations before writing a single `closed` step.

### Service order flow hardening
- `ServiceOrderController@finalizeOrder`
  - blocks finalize if additional fee exists but not approved.
  - blocks `payment_verified -> completed` if no technician assignment is marked `done`.
  - from `waiting_review`:
    - if approved additional fee exists -> `waiting_payment` (and clears prior payment verification metadata for re-payment flow).
    - if no additional fee -> directly `completed`.
- `ServiceOrderController@confirmPayment`
  - now also records verifier identity (`payment_verified_by`, `payment_verified_by_name`).
  - blocks confirmation while additional fee still unapproved.
- `ServiceOrderController@approveAdditionalFee`
  - requires invoice existence.
  - updates invoice total and resets payment verification fields before moving to `waiting_payment`.
- `ServiceOrderController@frontdeskConfirmComplete` and `@managerConfirmComplete`
  - now create `closed` step only once, and only when both confirmations are present.

### Technician completion consistency
- `TechnicianController@jobCompletionForm` and `@completeJob`
  - now require `in_progress` to complete.
  - completion now always transitions to `waiting_review` (main flow), not legacy divergent states.
  - writes field report data on `service_orders` for downstream manager validation.

### Invoice flow stabilization
- `InvoiceController@showEditor`
  - binds `serviceDetails` relation onto invoice for editor rendering compatibility.
- `InvoiceController@editInvoice`
  - corrected to use `brand` + `price_per_unit` columns.
  - fixed total recalculation from `price_per_unit`.
  - removed status mutation to legacy `pending_fee_approval`.

### Status/model sync
- `ServiceOrder` model now casts `field_report_tools_materials` as `array`.

### Monitoring action gate alignment
- Updated monitoring views to match backend sequence:
  - technician assignment action shown at `payment_verified`.
  - final “Selesaikan Order” action from `payment_verified` requires assignment status `done`.

## Files Changed
- `app/Http/Controllers/ServiceOrderController.php`
- `app/Http/Controllers/WorkflowController.php`
- `app/Http/Controllers/TechnicianController.php`
- `app/Http/Controllers/InvoiceController.php`
- `app/Models/ServiceOrder.php`
- `resources/views/monitoring.blade.php`
- `Modules/AcMasjidMusholla/resources/views/monitoring.blade.php`
- `tests/Feature/WorkflowIntegrationTest.php`
- `tests/Feature/ServiceOrderWorkflowStatusSyncTest.php`
- `tests/Feature/MonitoringWorkflowUiTest.php`

## Tests Run
Executed with PHPUnit:
- `vendor\\bin\\phpunit --filter WorkflowIntegrationTest` ?
- `vendor\\bin\\phpunit --filter ServiceOrderWorkflowStatusSyncTest` ?
- `vendor\\bin\\phpunit --filter MonitoringWorkflowUiTest` ?

Notes:
- PHPUnit reports existing deprecations (`PHPUnit Deprecations: 12`) but tests above pass.

## Remaining Risks
1. There are still legacy status handlers in other controllers/modules (e.g., manager fee-approval dashboard flow) that may not fully align with this canonical path.
2. There are duplicate/parallel route surfaces between core and module pages; future refactor should centralize state transition policies into a single workflow service.
3. Existing database enum history is complex; ensure production schema includes all statuses used by this stabilized flow (`waiting_payment`, `payment_verified`, `technician_assigned`, `waiting_review`, `completed`, `closed` workflow step).

## Rollback Path
- Backup restore point created at:
  - `.AI-agents-task-rule-etc/backups/backend-workflow/20260522-005033/`
- To rollback, copy backed-up files from that directory back to project root with same relative paths.
