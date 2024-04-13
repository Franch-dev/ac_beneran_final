# AC Servis Masjid

Laravel-based operations platform for managing mosque AC inventory, service orders, workflow approvals, and monitoring.

## Current Focus

The frontend now uses a glass/material operations layer for:

- Masjid data cards on the dashboard
- Sidebar notification badges for monitoring states
- Monitoring queue tables and mobile cards
- Workflow and audit popups
- Toasts and interaction feedback

## Stack

- Laravel
- Blade templates
- Custom CSS in `public/css`
- Page scripts in `public/js`
- Vite for `resources/js` enhancements

## Key UI Files

- `resources/views/dashboard.blade.php`
- `resources/views/monitoring.blade.php`
- `resources/views/layouts/app.blade.php`
- `resources/views/layouts/header.blade.php`
- `public/css/style.css`
- `public/css/operations-ui-overhaul.css`
- `public/css/ui-overhaul.css`
- `public/js/app.js`
- `public/js/dashboard.js`
- `public/js/monitoring.js`
- `public/js/workflow.js`

## Local Setup

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
```

## Useful Commands

```bash
npm run dev
npm run build
npm test
php artisan test
```

## UI Architecture Notes

- `public/css/operations-ui-overhaul.css` and `public/css/ui-overhaul.css` are the isolated override layers for the new dashboard and monitoring design system.
- `public/js/app.js` owns shared popup, toast, escape, sidebar, and badge refresh behavior.
- `public/js/dashboard.js`, `public/js/monitoring.js`, and `public/js/workflow.js` own page-specific workflows only.
- Dynamic HTML rendering must use escaped text before inserting server-provided values.

## Documentation

- Architecture reference: [Docs/ARCHITECTURE.md](Docs/ARCHITECTURE.md)
- UI guidance: [Docs/UIUX_ARCHITECTURE_GUIDE.md](Docs/UIUX_ARCHITECTURE_GUIDE.md)
- ADR: [Docs/ADR-2026-04-06-operations-ui-overhaul.md](Docs/ADR-2026-04-06-operations-ui-overhaul.md)
