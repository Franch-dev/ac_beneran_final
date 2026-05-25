# API Documentation — AC Beneran System

> **Version:** 1.0  
> **Base URL:** `http://ac-beneran.local`  
> **Response Format:** JSON (standardized via `App\Support\ApiResponse`)  
> **Auth:** Session-based (Laravel Sanctum/Session)

---

## 📐 Response Standard

All JSON endpoints follow a standardized response envelope:

### Success
```json
{
  "success": true,
  "...": "...additional data keys..."
}
```

### Error
```json
{
  "success": false,
  "message": "Error description"
}
```

### Snapshot (AJAX partial refresh)
```json
{
  "html": "<rendered HTML>"
}
```

### Raw
Direct data dump — no envelope wrapper. Used for collection endpoints.

---

## 🔓 Public / Guest Endpoints

### `GET /`
Home page — returns Blade view.

### `GET /login`
Login form.

### `POST /login`
Authenticate user.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `email` | string | ✅ | User email |
| `password` | string | ✅ | User password |
| `remember` | boolean | ❌ | Remember session |
| `redirect` | string | ❌ | Post-login redirect path |

**Response:** Redirect (302).

### `GET /sitemap`
Returns sitemap view.

### `GET /sitemap.json`
Returns sitemap as `JsonResponse`.

### `GET /modules/ac-service`
Guest-facing landing page.

### `GET /modules/ac-service/guest-order`
Guest order form view.

### `POST /modules/ac-service/guest-order`
Submit service order as guest.

**Validation Rules:**
| Field | Rules |
|-------|-------|
| `masjid_id` | Required, exists in `ac_service.masjids` |
| `meeting_person` | Required, in: `dkm,marbot` |
| `phone` | Required, string, max:20 |
| `service_date` | Required, date, after_or_equal:today |
| `notes` | Nullable, string, max:1000 |
| `details` | Required, array, min:1 |
| `details.*.pk_type` | Required, in: `1PK,2PK,5PK` |
| `details.*.brand` | Required, string, max:100 |
| `details.*.quantity` | Required, integer, min:1, max:100 |

**Response:** Redirect with `success` or `errors` flash messages.

### `GET /modules/ac-anggota`
### `GET /modules/ac-anggota/card`
Anggota card view.

### `GET /modules/ac-masjid-musholla`
### `GET /modules/ac-masjid-musholla/card`
Masjid/Musholla card view.

### `GET /modules/inventory`
### `GET /modules/future-module`

---

## 🔐 Authenticated Endpoints

### Auth

| Method | Endpoint | Controller | Description |
|--------|----------|------------|-------------|
| POST | `/logout` | `AuthController::logout` | Logout user |

---

## 📊 Dashboard & Monitoring

### `GET /dashboard`
Dashboard view.

### `GET /dashboard/snapshot`
**Response:**
```json
{
  "html": "<rendered dashboard HTML>"
}
```

### `GET /dashboard/status-counts`
**Response:**
```json
{
  "success": true,
  "status_counts": { "...": "..." }
}
```

### `GET /dashboard/masjid/metrics`
**Response:**
```json
{
  "success": true,
  "total_masjid": 0,
  "total_units": 0,
  "total_services": 0
}
```

### `GET /dashboard/masjid/history/{masjid}`
**Response:**
```json
{
  "success": true,
  "history": []
}
```

---

## 🕌 Masjid Management

### `GET /api/masjids`
List all masjids.

### `POST /api/masjids`
Create masjid.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `name` | string | ✅ | Masjid name |
| `address` | string | ✅ | Address |
| `type` | string | ✅ | `masjid` or `musholla` |
| `phone_numbers` | array | ✅ | Contact numbers |
| `dkm_name` | string | ❌ | DKM contact person |
| `marbot_name` | string | ❌ | Marbot contact person |

**Response (201):**
```json
{
  "success": true,
  "masjid": { "...": "..." }
}
```

### `PUT /api/masjids/{masjid}`
Update masjid.

**Response:**
```json
{
  "success": true,
  "masjid": { "...": "..." }
}
```

### `DELETE /api/masjids/{masjid}`
Delete masjid.

**Response:**
```json
{
  "success": true,
  "masjid_id": 1
}
```

### `GET /api/masjids/{masjid}/detail`
Get masjid with AC units and orders.

### `GET /api/masjids/{masjid}/history`
Get service order history for masjid.

---

## ❄️ AC Unit Management

### `POST /api/ac-units`
Create AC unit.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `masjid_id` | integer | ✅ | Masjid ID |
| `pk_type` | string | ✅ | `1PK`, `2PK`, or `5PK` |
| `brand` | string | ✅ | Brand name |
| `quantity` | integer | ✅ | Unit quantity |
| `merk` | string | ❌ | Specific brand/merk |

### `PUT /api/ac-units/{acUnit}`
Update AC unit.

### `DELETE /api/ac-units/{acUnit}`
Delete AC unit.

### `POST /api/ac-units/bulk`
Bulk create AC units for a masjid.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `masjid_id` | integer | ✅ | Masjid ID |
| `units` | array | ✅ | Array of unit objects |
| `units.*.pk_type` | string | ✅ | `1PK`, `2PK`, or `5PK` |
| `units.*.brand` | string | ✅ | Brand name |
| `units.*.quantity` | integer | ✅ | Quantity |

**Response:**
```json
{
  "success": true,
  "masjid": { "...": "..." }
}
```

---

## 👥 Anggota Management

### `GET /api/anggotas`
List all anggota.

### `POST /api/anggotas`
Create anggota.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `name` | string | ✅ | Anggota name |
| `address` | string | ✅ | Address |
| `phone_numbers` | array | ✅ | Contact numbers |
| `contact_name` | string | ✅ | Contact person |
| `type` | string | ✅ | `individual` or `business` |

### `PUT /api/anggotas/{anggota}`
Update anggota.

### `DELETE /api/anggotas/{anggota}`
Delete anggota.

### `GET /api/anggotas/{anggota}/detail`
Get anggota with AC units and service orders.

---

## 📋 Service Order Workflow

### `POST /api/service-orders`
Create a new service order.

**Validation (same as guest-order):**
- `masjid_id` — required, exists
- `meeting_person` — required, `dkm|marbot`
- `phone` — required, string
- `service_date` — required, date, after_or_equal:today
- `details` — required, array, min:1
- `details.*.pk_type` — required, `1PK|2PK|5PK`
- `details.*.brand` — required, string
- `details.*.quantity` — required, integer, min:1

**Conflict Response (409):**
```json
{
  "success": false,
  "has_existing": true,
  "existing_order": {
    "id": 1,
    "order_number": "SO-2024-0001",
    "status": "spk_invoice_created",
    "status_label": "Order Dibuat",
    "service_date": "15 Jan 2024"
  },
  "message": "Masjid ini sudah memiliki service order aktif..."
}
```

**Success Response:**
```json
{
  "success": true,
  "order": { "...": "..." }
}
```

### `POST /api/service-orders/{serviceOrder}/approve`
Manager approves order → creates invoice, transitions to `waiting_payment`.

**Response:**
```json
{
  "success": true,
  "service_order_id": 1,
  "status": "waiting_payment"
}
```

### `POST /api/service-orders/{serviceOrder}/cancel-approve`
Cancel approval → revert to `spk_invoice_created`.

**Response:**
```json
{
  "success": true
}
```

### `DELETE /api/service-orders/{serviceOrder}`
Delete service order.

**Response:**
```json
{
  "success": true,
  "service_order_id": 1
}
```

### `GET /api/service-orders/{serviceOrder}`
Get order detail with workflow history.

**Response:**
```json
{
  "success": true,
  "order": {
    "id": 1,
    "order_number": "SO-2024-0001",
    "status": "spk_invoice_created",
    "service_date": "2024-01-15",
    "phone": "08123456789",
    "notes": "...",
    "masjid": { "id": 1, "name": "Masjid Al-Falah" },
    "service_details": [
      { "pk_type": "1PK", "brand": "Daikin", "quantity": 2, "service_type": "" }
    ],
    "invoice": { "id": 1, "invoice_number": "INV-2024-0001" }
  },
  "history": [
    {
      "id": 1,
      "label": "Order Dibuat",
      "icon": "fa-file",
      "color": "blue",
      "actor": "Admin",
      "role": "admin",
      "notes": "...",
      "time": "15 Jan 2024, 10:00"
    }
  ]
}
```

### `POST /api/service-orders/{serviceOrder}/spk-invoice`
Create SPK & Invoice (WorkflowController).

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `notes` | string | ❌ | Notes (max:500) |

**Response:**
```json
{
  "success": true,
  "message": "Invoice diterbitkan, menunggu pembayaran."
}
```

---

## 🛠 Technician Workflow

### `GET /api/service-orders/{serviceOrder}/timeline`
Get workflow timeline and technician assignment.

### `POST /workflow/{serviceOrder}/assign`
Assign technician to order.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `technician_id` | integer | ✅ | Technician user ID |
| `notes` | string | ❌ | Assignment notes |

**Response:**
```json
{
  "success": true,
  "message": "Order berhasil ditugaskan ke Teknisi A."
}
```

### `POST /workflow/{serviceOrder}/progress`
Technician updates progress.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `status` | string | ✅ | `in_progress` or `done` |
| `notes` | string | ❌ | Progress notes |

### `POST /api/service-orders/{serviceOrder}/field-report`
Technician submits field report.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `field_report_notes` | string | ✅ | Report (max:2000) |
| `field_report_additional_fee` | number | ❌ | Additional fee |
| `field_report_tools_materials` | array | ❌ | Tools/materials used |

---

## 💰 Payment & Invoicing

### `POST /api/service-orders/{serviceOrder}/confirm-payment`
Manager confirms payment (requires `manager` or `admin` role).

### `POST /api/service-orders/{serviceOrder}/finalize`
Manager finalizes order (from `payment_verified` or `waiting_review`).

### `POST /api/service-orders/{serviceOrder}/approve-additional-fee`
Manager approves additional fee.

### `POST /api/service-orders/{serviceOrder}/frontdesk-confirm-complete`
Frontdesk confirms completion (dual confirmation flow).

### `POST /api/service-orders/{serviceOrder}/manager-confirm-complete`
Manager confirms completion (dual confirmation flow).

### `GET /invoices/{serviceOrder}/print`
Print invoice view.

### `GET /invoices/{serviceOrder}/spk`
Print SPK view.

---

## 👥 User Management

### `GET /users`
User management page.

### `POST /users`
Create user.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `name` | string | ✅ | User name |
| `email` | email | ✅ | Email address |
| `password` | string | ✅ | Password (min:8) |
| `role` | string | ✅ | `frontdesk`, `manager`, `admin`, `technician`, `viewer` |

### `PUT /users/{user}`
Update user.

### `PUT /users/{user}/reset-password`
Reset user password.

### `DELETE /users/{user}`
Delete user.

---

## 📈 Monitoring

### `GET /monitoring`
Monitoring page.

### `GET /monitoring/snapshot`
**Response:**
```json
{
  "html": "<rendered monitoring HTML>"
}
```

### `GET /monitoring/status-counts`
**Response:**
```json
{
  "success": true,
  "total_masjid": 0,
  "total_units": 0,
  "active_orders": 0,
  "overdue_locations": 0,
  "needs_attention_locations": 0
}
```

---

## 🔄 Real-time Sync

### `GET /sync/stream`
Server-Sent Events (SSE) stream. Accepts `Last-Event-ID` header or `last_event_id` query parameter.

**Event format:**
```
id: {event_id}
event: sync
data: {"id":1,"type":"created","resource":"service_order","resource_id":1,...}

```

---

## 📝 Validation Errors

All validation errors follow Laravel's standard format:

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "field_name": ["Error message 1", "Error message 2"]
  }
}
```

**HTTP Status Codes:**
| Code | Meaning |
|------|---------|
| 200 | Success |
| 201 | Created |
| 403 | Forbidden / Access denied |
| 404 | Resource not found |
| 409 | Conflict (existing active order) |
| 422 | Validation error / Business rule violation |
| 503 | Service unavailable |
