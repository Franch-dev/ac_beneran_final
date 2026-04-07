import { describe, expect, it } from 'vitest';
import fs from 'node:fs';
import path from 'node:path';

const rootDir = path.resolve(process.cwd());

function read(relativePath) {
  return fs.readFileSync(path.join(rootDir, relativePath), 'utf8');
}

describe('Operations UI overhaul regression', () => {
  it('loads the operations stylesheet from the shared layout shell', () => {
    const layout = read('resources/views/layouts/app.blade.php');
    const operationsCss = read('public/css/operations-ui-overhaul.css');

    expect(layout).toContain("asset('css/operations-ui-overhaul.css')");
    expect(operationsCss).toContain('.monitoring-mobile-list');
    expect(operationsCss).toContain('.pagination-shell');
    expect(operationsCss).toContain('[data-stagger-item]');
  });

  it('keeps dashboard and monitoring actions on safe inline bindings', () => {
    const dashboardBlade = read('resources/views/dashboard.blade.php');
    const monitoringBlade = read('resources/views/monitoring.blade.php');

    expect(dashboardBlade).toContain('@js($masjid->name)');
    expect(dashboardBlade).toContain('pagination-shell');
    expect(monitoringBlade).toContain('@js($order->order_number)');
    expect(monitoringBlade).toContain('@js($order->masjid->name)');
    expect(monitoringBlade).toContain('pagination-shell');
  });

  it('shares escaped client rendering and global monitoring badge refresh', () => {
    const appJs = read('public/js/app.js');
    const monitoringJs = read('public/js/monitoring.js');
    const workflowJs = read('public/js/workflow.js');

    expect(appJs).toContain('window.escapeHtml = escapeHtml;');
    expect(appJs).toContain("refreshStatusBadges()");
    expect(monitoringJs).toContain('const monitoringSafeText');
    expect(monitoringJs).toContain('function legacyShowOrderDetail');
    expect(workflowJs).toContain('const workflowSafeText');
  });
});
