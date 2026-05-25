# Frontend JS Fix Report

## Summary
Comprehensive audit and fix of monitoring page JavaScript functions, Blade button bindings, and modal behavior.

## Broken Buttons Found & Fixed

| Button | Issue | Fix |
|--------|-------|-----|
| **Setujui Order** (`approveOrder`) | Duplicate definition in inline Blade script shadowed `.js` version | Removed inline duplicate; now unified in `resources/js/monitoring.js` |
| **Setujui SPK & Invoice** (`approveSpkInvoice`) | Same duplicate issue | Removed inline duplicate |
| **Tugaskan Teknisi** (`openAssignTech`) | JS guard checked `payment_verified` but Blade shows button at `spk_invoice_approved` — all clicks silently failed | Changed guard to `spk_invoice_approved` |
| **Ganti Order** (`confirmReplaceOrder`) | Stub — did nothing | Implemented full delete-old-order + create-new-order via API |
| **Batal Ganti** (`cancelReplaceOrder`) | Stub — did nothing | Implemented reset `pendingReplaceData`, close popup, show toast |
| **Konfirmasi Modal** (`executeConfirmAction`) | Stub — did nothing | Implemented pending action execution with fallback |
| **Refresh Data** (`refreshMonitoringData`) | Missing — `refreshMonitoringSurface` referenced undefined function | Added global debounced `window.refreshMonitoringData` |

## Missing JS Functions Found & Added
- `window.refreshMonitoringData` — debounced page reload fallback
- `window.confirmReplaceOrder` — full delete+create flow
- `window.cancelReplaceOrder` — proper cleanup
- `window.executeConfirmAction` — pending action execution

## Files Changed

| File | Changes |
|------|---------|
| `resources/js/monitoring.js` | Fixed `confirmReplaceOrder`, `cancelReplaceOrder`, `executeConfirmAction` stubs. Added `refreshMonitoringData`. Added defensive DOM null checks in `showOrderDetail`, `openAssignTech`, `submitAssignTech`. Fixed `openAssignTech` status guard (`payment_verified` → `spk_invoice_approved`). |
| `resources/views/monitoring.blade.php` | Removed duplicate `confirmModal` structure (duplicated close buttons + body broke DOM). Removed duplicate inline `approveOrder`/`approveSpkInvoice` definitions (now centralized in `.js` file). |

## Exact Fixes Applied

### resources/js/monitoring.js
1. **`confirmReplaceOrder`** — now deletes old order via `apiFetch(deleteUrl, 'DELETE')`, then creates new order via `apiFetch(storeUrl, 'POST', newOrderPayload)`, with proper toast feedback.
2. **`cancelReplaceOrder`** — resets `pendingReplaceData = null`, closes popup, shows info toast.
3. **`executeConfirmAction`** — executes `pendingBladeConfirmAction()` if it's a function, otherwise shows warning and closes modal.
4. **`refreshMonitoringData`** — debounced `window.location.reload()` with 300ms timeout, assigned to `window`.
5. **`openAssignTech`** — guard changed from `status !== 'payment_verified'` to `status !== 'spk_invoice_approved'` to match Blade gate.
6. **Defensive DOM checks** — added null guards in `showOrderDetail` (modal, body), `openAssignTech` (orderIdField, notesField, technicianSelect), `submitAssignTech` (orderIdEl, techSelectEl).

### resources/views/monitoring.blade.php
1. **Duplicate `confirmModal`** — removed duplicated modal structure that had extra close buttons and orphaned body section, causing broken DOM.
2. **Duplicate inline script** — removed IIFE that redefined `approveOrder` and `approveSpkInvoice` with a local `fallbackFetch`. These functions now come only from `resources/js/monitoring.js` which uses the standard `apiFetch` helper.

## Manual Test Checklist
- [ ] **Setujui Order** button — should open confirm modal, then call `/service-order/{id}/approve`, then refresh
- [ ] **Setujui SPK & Invoice** button — should open confirm modal, call `/workflow/{id}/approve-spk-invoice`, refresh
- [ ] **Tugaskan Teknisi** — should open assign tech popup at `spk_invoice_approved` status
- [ ] **Ganti Order** — should delete old order and create new order
- [ ] **Batal Ganti** — should close popup without action
- [ ] **Konfirmasi Modal** confirm button — should execute the pending action
- [ ] **Detail** button — should open order detail modal with timeline
- [ ] **Refresh** button — should call `manualRefreshMonitoring()` which triggers debounced reload
- [ ] **Field Report** — submit should send report via API
- [ ] **Dual Confirmation** — frontdesk and manager should each confirm separately

## Remaining UI Risks
1. **`showMasjidSideDetail`** fetches data but doesn't display it in a modal — it's essentially a silent fetch. May need a popup or sidebar to render the data.
2. **`pendingBladeConfirmAction`** is set externally (in other parts of the codebase) — if the setter is missing, `executeConfirmAction` gracefully shows "Tidak ada aksi tertunda" toast.
3. **Mobile list** still uses inline Blade gates (e.g., `$canApproveOrder($order)`) instead of the centralized `$can*` variables — these should be kept in sync manually.
4. **`@push('scripts')` ROUTES_MON block** defines route templates in JavaScript — if backend routes change, these must be updated in sync.
5. **Pricing config** (`window.HARGA_CONFIG`) is hardcoded in Blade — consider moving to config or database for maintainability.
