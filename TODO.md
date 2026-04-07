# Website Update Full Implementation TODO

## Current Status: Starting Phase 1

### Phase 1: Pre-Migration Critical Fixes
- [ ] 1. Copy migration files from Website-Update/ to database/migrations/
- [ ] 2. Fix ->constrained() → ->index() in both workflow migrations
- [ ] 3. Add workflowSteps() & technicianAssignment() relationships to ServiceOrder.php
- [ ] 4. Remove $this->authorize('manager') from WorkflowController@assign()
- [ ] 5a. Patch MonitoringController.php query (+ eager load workflowSteps, technicianAssignment)
- [ ] 5b. Patch monitoring.blade.php (add Progress column, buttons, include workflow_panel, workflow.js script)
- [ ] 5c. Patch header.blade.php (add new sidebar nav links)
- [ ] 5d. Append routes_to_append.php to Modules/AcService/routes/web.php + use statements

### Phase 2: Migrate & Seed
- [ ] Run `php artisan migrate`
- [ ] Edit DatabaseSeeder.php (+ teknisi & viewer users)
- [ ] Run `php artisan db:seed`

### Phase 3: Copy Remaining New Files
- [ ] Copy models: WorkflowStep.php, TechnicianAssignment.php → app/Models/
- [ ] Copy controllers: UserController.php, ProfileController.php, etc. → app/Http/Controllers/
- [ ] Create view directories & copy views (users/, profile/, reports/, etc.)
- [ ] Copy public/js/workflow.js
- [ ] Copy remaining files (DatabaseSeeder_addition.php, etc.)

### Phase 4: Clear Caches & Test
- [ ] Run cache clear commands
- [ ] Test new logins (teknisi@example.com, viewer@example.com)
- [ ] Verify workflow functionality in monitoring

**Next: Complete Phase 1 step-by-step**

