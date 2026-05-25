# Database & Data Integrity Report - AC Beneran Platform

## 1. Database Structure Summary
The project utilizes a multi-database architecture with four logical connections:
- **`main`**: Authentication and global platform data (Users, Roles).
- **`ac_service`**: Core operational data (Masjid, Service Orders, Invoices, Workflows).
- **`ac_anggota`**: Intended for member profiles and authentication (currently largely unused).
- **`inventory`**: Inventory and asset tracking (MVP).

### Key Tables in `ac_service`:
- `masjids`: Customer locations.
- `ac_units`: Inventory of units per masjid.
- `service_orders`: Core operational document.
- `service_details`: Line items for service orders.
- `invoices`: Financial document linked to service orders.
- `workflow_steps`: Audit trail and status history.
- `technician_assignments`: Link between technicians and orders.

## 2. Model Relationship Summary
- **ServiceOrder** ↔ **Masjid**: Many-to-one (BelongsTo).
- **ServiceOrder** ↔ **ServiceDetail**: One-to-many (HasMany).
- **ServiceOrder** ↔ **Invoice**: One-to-one (HasOne).
- **ServiceOrder** ↔ **WorkflowStep**: One-to-many (HasMany).
- **ServiceOrder** ↔ **TechnicianAssignment**: One-to-one (HasOne).
- **TechnicianAssignment** ↔ **User** (main DB): Many-to-one (Technician). *Note: Relationship was missing in model.*
- **AcUnit** ↔ **Masjid**: Many-to-one (BelongsTo).

## 3. Enum/Status Consistency Table

| Table | Column | Values | Consistency Note |
|-------|--------|--------|-------------------|
| `service_orders` | `status` | `pending_review`, `approved`, `spk_invoice_created`, `spk_invoice_approved`, `technician_assigned`, `in_progress`, `work_completed`, `pending_fee_approval`, `fee_approved`, `waiting_payment`, `payment_verified`, `waiting_review`, `completed`, `cancelled` | Matches model and latest migrations. |
| `workflow_steps` | `step` | `guest_created`, `frontdesk_created`, `approved`, `spk_invoice_created`, `spk_invoice_approved`, `waiting_payment`, `payment_verified`, `invoice_generated`, `assigned`, `in_progress`, `technician_reported`, `edited_invoice_created`, `edited_invoice_approved`, `invoice_edited`, `payment_received`, `printed`, `waiting_review`, `completed`, `closed`, `cancelled` | Matches model and latest migration (2026-05-21). |
| `technician_assignments` | `status` | `assigned`, `in_progress`, `done` | Uses `done` instead of `completed` (Design choice). |
| `ac_units` | `pk_type` | `1PK`, `2PK`, `5PK` | Consistent across migrations and UI. |

## 4. Issues Found

### High Severity:
- **Duplicate Model Definition:** `app/Models/AirConditionerUnit.php` and `app/Models/AcUnit.php` both define `class AcUnit`. This causes a PHP Fatal Error if both are loaded.
- **Inconsistent Logic:** `GuestOrder::approve()` in the model is inconsistent with `GuestOrderController::approve()`. It misses `ServiceDetail` creation and uses different workflow steps, making it unsafe to call from background jobs or seeders.

### Medium Severity:
- **Missing Relationship:** `TechnicianAssignment` model was missing the `technician()` relationship to the `User` model (cross-database).
- **Redundant Model:** `app/Models/AcAnggota.php` is a redundant subclass of `AnggotaAcUnit` and is unused.
- **Misaligned Status Naming:** `ServiceOrder` uses `technician_assigned` while `WorkflowStep` uses `assigned`. While functional, it increases cognitive load for developers.

### Low Severity:
- **Unused Database:** The `ac_anggota` database and its associated table `ac_anggota` are defined in migrations and config but not utilized by any model or authentication guard.

## 5. Fixes Applied
- [x] Deleted `app/Models/AirConditionerUnit.php` (Duplicate).
- [x] Deleted `app/Models/AcAnggota.php` (Redundant/Unused).
- [x] Added `technician()` relationship to `App\Models\TechnicianAssignment`.
- [x] Stabilized `GuestOrder::approve()` to align with controller logic and ensure data integrity (creating ServiceDetails).

## 6. Migration Recommendations
- **Index Optimization:** Already addressed in recent migrations.
- **Status Alignment:** Consider a future migration to rename `technician_assigned` to `assigned` in `service_orders` for better consistency with `WorkflowStep` and `TechnicianAssignment`.

## 7. Rollback Notes
- If `AirConditionerUnit.php` was intended to be a different class (unlikely given it defined `class AcUnit`), it can be restored from backup.
- `AcAnggota.php` can be restored if specific subclassing for members is needed in the future.
