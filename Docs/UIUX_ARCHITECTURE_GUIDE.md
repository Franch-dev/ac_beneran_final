# 🧠 Forkis Platform UI/UX Architecture Guide (Laravel Modular Monolith)
**Version 1.0 | Aligned: Laravel 11+, Blade, Vite, Tailwind | Date: Current**

## 🎯 Objective
Elevate **existing Forkis landing/catalog** to **Apple-inspired premium UI/UX** with **glassmorphism + advanced animations**. Fix bugs, preserve Laravel data-binding (e.g., `$catalogModules`, counters), prepare for subdomain modules (AC Service/Inventory). Ensure Vite-optimized, 60fps, WCAG AA.

## 📦 Project Context (From Structure)
- **Core**: Modular monolith (landing → catalog → modules via shared auth).
- **UI Files**: `home.blade.php` (hero/catalog/pricing/contact), `layouts/header.blade.php` (navbar/sidebar), `public/css/style.css`+`visual-enhancements.css` (Google-inspired vars/shadows/dark mode).
- **JS**: `public/js/app.js`/etc., Vite (`vite.config.js` w/ Tailwind).
- **Existing**: Catalog cards implemented, CSS counters/orbits/scrollspy, responsive sidebar, dark mode toggle.
- **No**: GSAP/AOS/anime.js yet – add via npm/Vite.

## 🎨 Design System (Glassmorphism Integrated)
```
CSS Vars (extend style.css):
--glass-bg: rgba(255,255,255,0.25);
--glass-blur: blur(20px);
--glass-border: 1px solid rgba(255,255,255,0.18);
--shadow-glass: 0 8px 32px rgba(0,0,0,0.1);
```
- **Typography**: System fonts (DM Sans exists – enhance Inter/SF Pro stack).
- **Palette**: Extend Google-inspired (primary #1a73e8 → glass gradients).
- **Glass Components**: Navbar/cards/hero (backdrop-filter: var(--glass-blur); background: var(--glass-bg)).
- **Dark Mode**: Preserve/enhance existing.

## 🚀 Animation Stack (Vite-Native)
1. **GSAP 3.x** (npm i gsap): Hero timelines, scroll-triggers (parallax/pin catalog), morphs.
2. **AOS** (npm i aos): Scroll reveals (fade-up stagger on catalog/pricing).
3. **anime.js** (CDN/light): SVG icons/micro-interactions (thumb hovers).
4. **Lenis** (npm i @studio-freight/lenis): Smooth scroll.
5. **Swiper** (npm i swiper): Future carousels (testimonials?).

**Integration**: `resources/js/app.js` → import/register → expose to Blade.

## 🔧 Laravel-Specific Requirements
- **Preserve Blade**: `@forelse($catalogModules)`, `@php($catalogCount)`, counters (`data-target`).
- **Fix Bugs**: Audit responsive (mobile sidebar/navbar), overflows (hero orbits), accessibility (focus/skip-links exist – enhance).
- **Mobile-First**: Breakpoints match existing (480/768/1024px).

## 📋 Step-by-Step Plan
### 1. **Audit (No Code Changes)**
   - Lighthouse: Perf 95+, Acc 100%.
   - Bugs: Layout shifts (hero), hover consistency.

### 2. **Glassmorphism (edit public/css/style.css + visual-enhancements.css)**
   ```
   .glass-hero { background: var(--glass-bg); backdrop-filter: var(--glass-blur); }
   .catalog-card { /* enhance existing */ }
   ```

### 3. **Animations (resources/js/app.js + Vite)**
   ```
   import { gsap } from 'gsap';
   gsap.timeline().to('.hero', {opacity:1, duration:1});
   // ScrollTrigger for catalog stagger.
   ```

### 4. **Blade Updates**
   - `home.blade.php`: Add glass/animation classes to hero/catalog.
   - `header.blade.php`: Glass navbar.

### 5. **Test/Deploy**
   - `npm run build` → `php artisan view:clear`.
   - Cross-device (iOS/Android/Win), Lighthouse 95+.

## 📂 Deliverables
- Updated `public/css/*.css` (glass vars/classes).
- `resources/js/animations.js` (GSAP/AOS/Lenis bundle).
- New `Docs/JS_DESIGN_INSTRUCTION.md` (customize guide).
- `npm run dev` command.

## ✅ Success Metrics
- **Visual**: Premium glass/animations (Apple/Linear vibe).
- **Perf**: 60fps, Lighthouse 95+.
- **Laravel**: Dynamic data intact, Vite builds clean.
- **Future**: Subdomain-ready modules.

**Proceed via plan confirmation → code edits.**

