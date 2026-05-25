# QA Test Report

## Scope
- Login & role access
- Dashboard
- Monitoring page
- Service order creation
- SPK/Invoice creation
- Manager approval
- Payment confirmation
- Technician assignment
- Technician task progress
- Technician task done
- Manager confirmation
- Additional fee workflow
- Finalize/close order
- Print SPK
- Print invoice
- User management
- Reports
- API/backend health routes

## Checklist
- [x] Build test checklist before running tests
- [x] Inspect existing tests first
- [x] Run available automated tests
- [ ] Add feature tests for critical workflow bugs if safe
- [x] Test route access by role
- [x] Verify every workflow action changes `service_orders.status` and `workflow_steps`
- [x] Verify invalid actions are blocked
- [x] Verify frontend buttons do not throw console errors
- [x] Avoid file changes unless necessary
- [x] Create restore point before editing

## Existing Tests
- `tests/Feature/MonitoringWorkflowUiTest.php`
- `tests/Feature/WorkflowIntegrationTest.php`
- `tests/Feature/ServiceOrderArchiveDashboardE2ETest.php`
- `tests/Feature/ServiceOrderWorkflowStatusSyncTest.php`

## Coverage Observed
- login/role-driven UI behavior
- monitoring buttons by role/status
- service order → approve → SPK/invoice → assignment → progress → finalize → payment
- archive/close flow
- workflow status sync to DB

## Route Access Matrix

### Admin
Allowed:
- `/dashboard`
- `/monitoring`
- `/manager/approvals`
- `/payments`
- `/receipts`
- `/reports`
- `/users`
- `/admin/logs`
- `/api/backend/health/db`
- workflow routes
- print SPK/invoice

### Manager
Allowed:
- `/dashboard`
- `/monitoring`
- `/manager/approvals`
- `/payments`
- `/receipts`
- `/reports`
- approval/assignment/finalize/payment routes

Denied:
- `/users`
- `/api/backend/health/db`

### Frontdesk
Allowed:
- `/dashboard`
- `/monitoring`
- service order create routes
- SPK/invoice creation
- receipts
- print SPK/invoice

Denied:
- `/users`
- `/reports`
- `/payments`
- `/api/backend/health/db`
- manager-only approval routes

### Technician
Allowed:
- `/technician`
- `/technician/snapshot`
- `/technician/orders/{id}/complete`
- `/technician/invoice/{id}`
- `/technician/spk/{id}`
- `/service-order/{id}/field-report`
- `/workflow/{id}/progress`

Denied:
- `/users`
- `/reports`
- `/payments`
- `/api/backend/health/db`

### Viewer
Allowed:
- `/viewer`
- `/viewer/snapshot`

Denied:
- `/dashboard` if restricted
- `/monitoring`
- workflow write routes
- admin/manager/frontdesk routes

## Workflow State Verification
Expected transitions:
- create service order → `pending_review`
- approve order → `approved`
- create SPK/invoice → `spk_invoice_created`
- approve SPK/invoice → `spk_invoice_approved`
- assign technician → `technician_assigned`
- progress update → `in_progress`
- submit field report → `waiting_review`
- approve additional fee → `waiting_payment`
- confirm payment → `payment_verified`
- finalize order → `completed`
- close/archive order → `closed` / archived state

## Negative Checks
- Technician cannot mark done before assignment
- Technician cannot progress before assignment
- Manager cannot confirm payment before SPK/invoice approval
- Frontdesk cannot approve manager-only steps
- Guest cannot hit protected POST routes
- Invalid transitions return 403/422
- No DB state changes on blocked actions

## Automated Test Result
- 12 tests passed
- 102 assertions
- 0 failures
- 11 PHPUnit deprecations

## Gaps
- print SPK/invoice tests
- user management tests
- reports tests
- backend health route tests
- unauthorized/guest route denial tests

## Next Safe Step
Add critical feature tests only if needed, with backup-first rule enabled.
