import { describe, expect, it } from 'vitest';
import fs from 'node:fs';
import path from 'node:path';

const rootDir = path.resolve(process.cwd());
const dashboardBlade = fs.readFileSync(path.join(rootDir, 'resources/views/dashboard.blade.php'), 'utf8');
const monitoringBlade = fs.readFileSync(path.join(rootDir, 'resources/views/monitoring.blade.php'), 'utf8');
const appJs = fs.readFileSync(path.join(rootDir, 'public/js/app.js'), 'utf8');
const monitoringJs = fs.readFileSync(path.join(rootDir, 'public/js/monitoring.js'), 'utf8');
const workflowJs = fs.readFileSync(path.join(rootDir, 'public/js/workflow.js'), 'utf8');
const overhaulCss = fs.readFileSync(path.join(rootDir, 'public/css/operations-ui-overhaul.css'), 'utf8');

describe('Operations UI overhaul contract', () => {
  it('keeps dashboard and monitoring on the new operations surface layer', () => {
    expect(dashboardBlade).toContain('page-operations--dashboard');
    expect(dashboardBlade).toContain('ops-hero ops-hero--dashboard');
    expect(dashboardBlade).toContain('ops-kpi-card');

    expect(monitoringBlade).toContain('page-operations--monitoring');
    expect(monitoringBlade).toContain('ops-table-shell');
    expect(monitoringBlade).toContain('monitoring-mobile-list');
  });

  it('uses escaped text and shared popup helpers in dynamic monitoring flows', () => {
    expect(appJs).toContain('function escapeHtml');
    expect(appJs).toContain("document.body.classList.toggle('popup-open'");
    expect(monitoringJs).toContain('monitoringSafeText');
    expect(monitoringJs).toContain("openPopup('confirmModal')");
  });

  it('marks workflow popups as temporary and styles the new design primitives', () => {
    expect(workflowJs).toContain('data-temporary-popup="true"');
    expect(workflowJs).toContain('btn-accent');
    expect(overhaulCss).toContain('.ops-hero');
    expect(overhaulCss).toContain('.ops-masjid-card');
    expect(overhaulCss).toContain('.toast-notification');
  });
});
