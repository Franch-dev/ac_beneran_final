import { describe, expect, it } from 'vitest';
import fs from 'node:fs';
import path from 'node:path';

const rootDir = path.resolve(process.cwd());
const navigationJs = fs.readFileSync(path.join(rootDir, 'resources/js/navigation.js'), 'utf8');
const animationsJs = fs.readFileSync(path.join(rootDir, 'resources/js/animations.js'), 'utf8');
const legacyAppJs = fs.readFileSync(path.join(rootDir, 'public/js/app.js'), 'utf8');
const homeBlade = fs.readFileSync(path.join(rootDir, 'resources/views/home.blade.php'), 'utf8');

describe('Guest navigation ownership', () => {
  it('keeps anchor ownership in navigation.js only', () => {
    expect(navigationJs).toContain("document.querySelectorAll('.nav-link[href^=\"#\"]')");
    expect(navigationJs).toContain('window.closeGuestNavbar?.();');
    expect(animationsJs).not.toContain('a[href^="#"]');
    expect(legacyAppJs).not.toContain("history.scrollRestoration = 'manual';");
    expect(legacyAppJs).not.toContain('window.scrollTo(0, 0);');
  });

  it('keeps public pricing cards in ascending PK order', () => {
    const onePkIndex = homeBlade.indexOf('<span class="pricing-pk">1 PK</span>');
    const twoPkIndex = homeBlade.indexOf('<span class="pricing-pk">2 PK</span>');
    const fivePkIndex = homeBlade.indexOf('<span class="pricing-pk">5 PK</span>');

    expect(onePkIndex).toBeGreaterThan(-1);
    expect(twoPkIndex).toBeGreaterThan(onePkIndex);
    expect(fivePkIndex).toBeGreaterThan(twoPkIndex);
    expect(homeBlade).toContain('<div class="pricing-badge">Terpopuler</div>');
  });
});
