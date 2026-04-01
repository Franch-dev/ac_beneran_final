# JS Design Instruction - Forkis Premium Animations

## Run
```bash
npm run dev  # Dev w/ HMR
npm run build  # Production build
```

## Customization
- **GSAP**: Edit `resources/js/animations.js` timelines/triggers.
- **AOS**: Blade `data-aos="fade-up" data-aos-delay="100"`.
- **Lenis**: CDN fallback in `layouts/app.blade.php`.
- **Glass**: CSS `.glass-hero`/`.glass-card` classes.

## View Changes
php artisan serve
Open http://localhost:8000

Lighthouse: 95+ Perf/Acc.

