# Service Order Workflow Refactor TODO

## Current Status: [ ] Planning → [ ] Implementation → [ ] Testing

## Step 1: Extend WorkflowStep model [x]
- Add new steps: `spk_invoice_created`, `spk_invoice_approved`, `technician_reported`, `invoice_edited`, `payment_received`, `printed`
- Update stepLabel, stepIcon, stepColor methods

## Step 2: Update ServiceOrder model [x]
- Extend STATUS_LABELS if needed
- Add methods: `needsSpkInvoiceCreation()`, `needsSpkInvoiceApproval()`, etc.

## Step 3: Update WorkflowController [ ]
- Modify `assign()`: remove invoice requirement (frontdesk creates together)
- Add `createSpkInvoice()` for frontdesk (one action)
- Add `approveSpkInvoice()` for manager (one action)
- Add `submitTechnicianReport()`, `editInvoice()`, `recordPayment()`, `printDocuments()`

## Step 4: ServiceOrderController updates [ ]
- Update `approve()` to handle guest vs frontdesk paths
- Add `createSpkAndInvoice()` endpoint

## Step 5: Routes [Modules/AcService/routes/web.php] [ ]
- Add routes: create-spk-invoice, approve-spk-invoice, technician-report, etc.

## Step 6: Update seeders/views [ ]
- ServiceOrderSeeder.php: new step examples
- Monitoring views: new step support

## Step 7: Test complete flow [ ]
- Guest order → frontdesk create SPK+Invoice → manager approve → assign → technician report → edit → payment → print
- Frontdesk direct order → same flow

**Next Action:** Implement Step 1 (WorkflowStep.php)
