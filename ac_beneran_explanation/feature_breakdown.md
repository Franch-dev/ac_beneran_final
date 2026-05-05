# Feature Breakdown

## Service Management
| Feature | Frontend | Backend | API | Database |
|---------|-----------|---------|-----|----------|
| Masjid & AC Unit Registration | Blade form (AcService module) | MasjidController, AcUnit model | POST /masjid, POST /ac-unit | masjids, ac_units tables |
| Service Order Creation & Tracking | Guest/Authenticated order form | ServiceOrderController, ServiceOrder model | POST /service-order, GET /service-order/{id} | service_orders, service_details tables |
| Workflow Management (Create → Approve → Assign → Execute → Close) | Workflow UI, step indicators | WorkflowController, WorkflowStep model | POST /workflow/{order}/assign, POST /workflow/{order}/approve | workflow_steps, technician_assignments tables |
| Invoice Generation | Invoice form, PDF preview | InvoiceController, Invoice model | POST /invoice, GET /invoice/{id} | invoices table |
| SPK Generation | SPK form, PDF preview | WorkflowController (createSpkInvoice) | POST /workflow/{order}/spk-invoice | invoices table (linked to service order) |

## Member Management
| Feature | Frontend | Backend | API | Database |
|---------|-----------|---------|-----|----------|
| Anggota Registration | Member registration form | AnggotaController, Anggota model | POST /anggota | anggotas table |
| Member AC Units Tracking | Member dashboard, AC unit list | AnggotaController, AnggotaAcUnit model | GET /anggota/{id}/ac-units | anggota_ac_units table |
| Service History Per Member | Member service history page | AnggotaHistoryController | GET /anggota/{id}/history | anggota_service_orders table |

## Monitoring & Reporting
| Feature | Frontend | Backend | API | Database |
|---------|-----------|---------|-----|----------|
| Real-time Dashboard | Monitoring page, reactive updates | MonitoringController | GET /monitoring, GET /api/monitoring/snapshot | service_orders, workflow_steps tables |
| Status Tracking | Order status badges, filters | ServiceOrder model (status attribute) | GET /api/service-orders?status= | service_orders table |
| Snapshot APIs for Reactivity | Frontend polling/websocket | SyncController, sync_events model | GET /api/monitoring/snapshot | sync_events table |
| Export Capabilities | Export buttons (PDF/Excel) | ReportController | GET /export/orders, GET /export/invoices | service_orders, invoices tables |

## User Management
| Feature | Frontend | Backend | API | Database |
|---------|-----------|---------|-----|----------|
| Role-based Access Control | Middleware checks, UI visibility | Auth middleware, User model (role attribute) | All protected endpoints | users table (role column) |
| Password Reset Functionality | Forgot password form, reset form | AuthController (Laravel Breeze custom) | POST /forgot-password, POST /reset-password | password_reset_tokens table |
| Activity Logging | Admin log page | AdminLogController | GET /admin/logs | (logging to storage/logs or dedicated table) |
