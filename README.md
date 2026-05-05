# AC Beneran Platform

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-12.x-blue?style=flat&logo=laravel" alt="Laravel">
  <img src="https://img.shields.io/badge/PHP-8.2+-777BB4?style=flat&logo=php" alt="PHP">
  <img src="https://img.shields.io/badge/TailwindCSS-4.x-06B6D4?style=flat&logo=tailwind-css" alt="Tailwind">
  <img src="https://img.shields.io/badge/MySQL-3+Databases-4479A1?style=flat&logo=mysql" alt="MySQL">
  <img src="https://img.shields.io/badge/License-MIT-green?style=flat" alt="License">
</p>

## Overview

AC Beneran is a comprehensive platform for managing AC (Air Conditioning) service operations for mosques and musholla in Indonesia. The system handles service orders, technician assignments, invoicing, and member management across multiple module-based applications.

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | Laravel 12.x |
| Frontend | Vite + Tailwind CSS 4 |
| Database | MySQL (Multi-database) |
| PHP | 8.2+ |
| Authentication | Laravel Breeze (Custom) |

## Architecture

### Multi-Database Design

```
main_platform     → Users, Sessions, Auth
ac_service_db    → Masjid, AC Units, Service Orders, Invoices
inventory_db    → Inventory (Planned)
```

### Module System

The application uses a modular architecture with 5 modules:

| Module | Purpose | Status |
|--------|---------|--------|
| AC Service | Core service operations | Live |
| AC Masjid & Musholla | Public service info | Live |
| AC Anggota | Member portal | Live |
| Inventory | Asset tracking | MVP |
| Future Module | Template | MVP |

### User Roles

| Role | Permissions |
|------|-----------|
| Admin | Full system access |
| Manager | Approvals, Reports, Workflow |
| Frontdesk | CRUD Masjid, AC, Orders |
| Technician | Service execution |
| Viewer | Read-only access |

## Getting Started

### Prerequisites

- PHP 8.2+
- Composer
- Node.js 18+
- MySQL 5.7+

### Installation

```bash
# Install PHP dependencies
composer install

# Install Node.js dependencies
npm install

# Copy environment file
copy .env.example .env

# Generate application key
php artisan key:generate

# Run migrations
php artisan migrate

# Build assets
npm run build
```

### Development

```bash
# Run full development stack
composer dev

# Or separately
php artisan serve
npm run dev
```

## Project Structure

```
ac_beneran_final/
├── app/
│   ├── Http/Controllers/    # Controllers
│   ├── Models/              # Eloquent Models
│   └── Providers/           # Service Providers
├── bootstrap/
│   └── app.php              # Application bootstrap
├── config/                 # Configuration files
├── database/
│   ├── migrations/         # Database migrations
│   ├── seeders/           # Database seeders
│   └── factories/          # Model factories
├── Modules/               # Modular application
│   ├── AcService/         # AC Service module
│   ├── AcAnggota/         # AC Anggota module
│   ├── AcMasjidMusholla/  # AC Masjid module
│   ├── Inventory/        # Inventory module
│   └── FutureModule/     # Future module
├── public/                # Public assets
├── resources/
│   └── views/            # Blade templates
├── routes/               # Route definitions
├── storage/              # Storage (logs, cache)
└── vendor/              # Composer dependencies
```

## Key Features

### Service Management
- Masjid & AC unit registration
- Service order creation and tracking
- Workflow management (create → approve → assign → execute → close)
- Invoice generation
- SPK (Surat Perintah Kerja) generation

### Member Management
- Anggota registration
- Member AC units tracking
- Service history per member

### Monitoring & Reporting
- Real-time dashboard
- Status tracking
- Snapshot APIs for reactivity
- Export capabilities

### User Management
- Role-based access control
- Password reset functionality
- Activity logging

## API Endpoints

### Public
- `GET /` - Home page
- `GET /login` - Login form
- `GET /modules/{module}` - Module pages

### Authenticated
- `GET /dashboard` - Main dashboard
- `GET /monitoring` - Service monitoring
- `GET /profile` - User profile

### Protected (Role-based)
- `POST /masjid` - Create Masjid (frontdesk+)
- `POST /workflow/{order}/assign` - Assign technician (manager+)
- `GET /users` - User management (admin only)

See `sitemap-visual.html` for complete route documentation.

## Database Schema

### Main Database (main_platform)

| Table | Description |
|-------|------------|
| users | User accounts with roles |
| sessions | User sessions |
| password_reset_tokens | Password reset tokens |

### AC Service Database (ac_service_db)

| Table | Description |
|-------|------------|
| masjids | Masjid/Musholla records |
| ac_units | AC units per Masjid |
| service_orders | Service order requests |
| service_details | Order line items |
| invoices | Generated invoices |
| workflow_steps | Order workflow history |
| technician_assignments | Technician assignments |
| anggotas | Member records |
| anggota_ac_units | Member AC units |
| anggota_service_orders | Member service orders |
| sync_events | Real-time sync events |

See `database-schema.html` for detailed schema.

## Environment Variables

```env
# Application
APP_NAME="Forkis Platform"
APP_ENV=local
APP_DEBUG=true

# Database
DB_CONNECTION=main
DB_HOST=127.0.0.1
DB_DATABASE=main_platform

MAIN_DB_CONNECTION=mysql
MAIN_DB_DATABASE=main_platform

AC_SERVICE_DB_CONNECTION=mysql
AC_SERVICE_DB_DATABASE=ac_service_db

# Domains (for subdomain routing)
AC_SERVICE_DOMAIN=ac.ac_beneran_final.test
INVENTORY_DOMAIN=inventory.ac_beneran_final.test
```

## Security

- Laravel CSRF protection
- SQL injection prevention (Eloquent ORM)
- XSS prevention (Blade escaping)
- Role-based access control via middleware
- Session security configuration

## Documentation

| File | Description |
|------|------------|
| `README.md` | This file |
| `Architecture.md` | Architecture details |
| `Database.md` | Database documentation |
| `TechStack.md` | Technology details |
| `Features.md` | Feature list |
| `Security.md` | Security practices |
| `UIUX.md` | Design system |
| `API.md` | API documentation |
| `Deployment.md` | Deployment guide |
| `PRD.md` | Product requirements |
| `sitemap-visual.html` | Visual sitemap |
| `database-schema.html` | Visual database schema |

## License

This project is licensed under the MIT License.

---

<p align="center">Built with ❤️ using Laravel & Tailwind CSS</p>
