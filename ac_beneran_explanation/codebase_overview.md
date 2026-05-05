# Codebase Overview

## Project Name
AC Beneran Platform

## Description
Comprehensive platform for managing AC (Air Conditioning) service operations for mosques and musholla in Indonesia. Handles service orders, technician assignments, invoicing, and member management across multiple module-based applications.

## Tech Stack
| Layer | Technology |
|-------|-------------|
| Backend | Laravel 12.x, PHP 8.2+ |
| Frontend | Vite 7, Tailwind CSS 4, Blade Templates |
| Frontend Libraries | Axios, AnimeJS 4, AOS 2, GSAP 3 |
| Database | MySQL (Multi-database: 3 databases) |
| Authentication | Custom Laravel Breeze |
| Build Tools | Vite, Laravel Vite Plugin |
| Scheduling | Laravel Task Scheduling |

## Directory Structure
```
ac_beneran_final/
├── app/                      # Core Laravel application
│   ├── Console/              # Artisan commands (DB setup, order cleanup)
│   ├── Http/                 # Controllers, Middleware
│   │   ├── Controllers/      # Web & API controllers
│   │   └── Middleware/      # Custom middleware (role checks)
│   ├── Models/               # Eloquent models (Masjid, ServiceOrder, User, etc.)
│   ├── Services/              # Business logic services
│   ├── Support/              # Helper classes (navigation, redirects)
│   └── helpers.php           # Global helper functions (pricing, terbilang)
├── bootstrap/                # Laravel bootstrap files
├── config/                   # Laravel configuration files
├── database/                 # Migrations, seeders, factories
│   ├── migrations/           # Multi-database migrations
│   ├── seeders/             # Database seeders
│   └── factories/           # Model factories
├── Modules/                  # Modular application (5 modules)
│   ├── AcService/           # Core AC service operations
│   ├── AcAnggota/           # Member portal
│   ├── AcMasjidMusholla/    # Public service info
│   ├── Inventory/           # Asset tracking (MVP)
│   └── FutureModule/        # Template module
├── public/                   # Public assets (index.php, built assets)
├── resources/                # Frontend source (CSS, JS, views)
│   ├── css/                 # Tailwind CSS entry
│   ├── js/                  # App.js, monitoring.js
│   └── views/               # Blade templates
├── routes/                   # Route definitions (web, api)
└── storage/                 # Logs, cache, sessions
```

## Key Layers & Responsibilities
### Frontend Layer
- Renders Blade templates with Tailwind CSS 4 styling
- Uses Vite for asset bundling (app.css, app.js, monitoring.js)
- Animation libraries: AnimeJS (complex animations), AOS (scroll animations), GSAP (advanced animations)
- Axios for HTTP requests to backend

### Backend Layer
- Laravel 12 MVC structure
- Modular architecture: 5 independent modules with separate routes, views, controllers
- Multi-database support: 3 MySQL databases (main_platform, ac_service_db, inventory_db)
- Role-based access control (Admin, Manager, Frontdesk, Technician, Viewer)
- Scheduled tasks: Daily cleanup of orphaned service orders

### Database Layer
1. **main_platform**: Users, sessions, auth tokens
2. **ac_service_db**: Masjids, AC units, service orders, invoices, workflow steps, members
3. **inventory_db**: Inventory tracking (planned)

### API Layer
- Web routes for module pages, auth, dashboard
- API routes for reactive data (monitoring snapshots, service order updates)
- Role-protected endpoints for CRUD operations

## Dependencies
### PHP (composer.json)
- laravel/framework: ^12.0
- laravel/tinker: ^2.10.1
- Dev: fakerphp/faker, laravel/pail, laravel/pint, laravel/sail

### Node.js (package.json)
- Dependencies: animejs, aos, gsap, axios
- Dev Dependencies: @tailwindcss/vite, vite, laravel-vite-plugin

## Key Files
- `helpers.php`: Global service pricing and number-to-Indonesian-word conversion
- `vite.config.js`: Configures Vite with Laravel plugin and Tailwind
- `.env.example`: Multi-database and subdomain configuration
- `README.md`: Full project documentation, feature list, setup instructions
