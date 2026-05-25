# Roles and Access Guide

This file explains the system roles used in this project:

- `admin`
- `frontdesk`
- `manager`
- `technician`
- `viewer`

The role values are defined in `app/Support/UserRoles.php` and checked through middleware (`role:...`) plus helper methods in `app/Models/User.php`.

## 1) Admin (`admin`)

### Main purpose
- Full operational control and oversight.

### Typical access
- Can access almost all protected areas used by other roles.
- Can access admin-only routes such as backend ops and sitemap JSON.
- Can perform privileged workflow actions where routes allow `admin` together with other roles.

### Typical responsibilities
- Manage users and role assignments.
- Monitor logs, system health, and operational consistency.
- Act as fallback operator for frontdesk/manager tasks when needed.

## 2) Frontdesk (`frontdesk`)

### Main purpose
- Handle intake and administration for service orders.

### Typical access
- Guest order management (`frontdesk/guest-orders`).
- Invoice editing (`frontdesk/invoices/.../edit`).
- Shared operational pages where frontdesk is explicitly allowed.

### Typical responsibilities
- Review and validate incoming guest requests.
- Approve/reject guest orders with notes.
- Prepare or adjust invoice details before manager approval flow.
- Complete frontdesk-side confirmations for closing flow.

## 3) Manager (`manager`)

### Main purpose
- Approval authority for workflow, financial checks, and finalization.

### Typical access
- Manager approvals page (`manager/approvals`).
- Payment verification area (`payments/...`).
- Receipt listing and detail pages (shared with admin/frontdesk).
- Manager confirmation endpoints in workflow completion.

### Typical responsibilities
- Approve or reject service-related requests.
- Verify transfer/QRIS/cash payment records.
- Approve additional fees when technician reports extra cost.
- Confirm completion and close orders according to workflow rules.

## 4) Technician (`technician`)

### Main purpose
- Execute field work and submit job completion evidence.

### Typical access
- Technician dashboard and snapshots.
- Job completion form for assigned orders only.
- Technician-specific SPK/invoice views for assigned work.

### Typical responsibilities
- Perform service tasks in the field.
- Submit field report notes, proof photos, and completion data.
- Report additional fees/material usage when required by job reality.

## 5) Viewer (`viewer`)

### Main purpose
- Read-only visibility for audit and monitoring.

### Typical access
- Viewer dashboard and snapshot routes.
- Monitoring-style views that are exposed for passive oversight.

### Typical responsibilities
- Observe order progress and status trends.
- Support audit, reporting, and transparency needs.
- No operational mutation actions (create/update/delete/approve).

## Notes

- Final effective permissions are determined by route middleware and controller checks, not role label alone.
- If behavior changes in routes/controllers, update this document to keep role guidance accurate.
