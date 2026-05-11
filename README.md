# AC Beneran Platform

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-12.x-blue?style=flat&logo=laravel" alt="Laravel">
  <img src="https://img.shields.io/badge/PHP-8.2+-777BB4?style=flat&logo=php" alt="PHP">
  <img src="https://img.shields.io/badge/TailwindCSS-4.x-06B6D4?style=flat&logo=tailwind-css" alt="Tailwind">
  <img src="https://img.shields.io/badge/MySQL-4_Databases-4479A1?style=flat&logo=mysql" alt="MySQL">
  <img src="https://img.shields.io/badge/License-MIT-green?style=flat" alt="License">
</p>

## Overview

AC Beneran is a comprehensive platform for managing AC (Air Conditioning) service operations specifically tailored for mosques and musholla in Indonesia. Built with a robust modular architecture and multi-database design, it manages the entire service lifecycle—from initial order and technician assignment to automated invoicing and dual-confirmation completion.

## Tech Stack

| Layer | Technology |
|-------|-----------|
| **Backend** | Laravel 12.x |
| **Frontend** | Vite + Tailwind CSS 4.x |
| **Database** | MySQL 8.x (Multi-database: Main, AC Service, Anggota, Inventory) |
| **Animations** | GSAP, Anime.js, AOS |
| **Auth** | Customized Laravel Breeze / Role-based Access |

## Architecture

### Multi-Database Design
The platform intelligently partitions data across four distinct databases to ensure scalability and isolation. The system is designed to **automatically create** these databases during migration if they do not exist.

1. `main_platform` → Global users, auth, sessions, and core configurations.
2. `ac_masjid_db` → Core operations: Masjids, AC Units, Service Orders, and Invoices.
3. `ac_anggota_db` → Member portal data and history.
4. `inventory_db` → Asset and stock management (MVP).

### Modular System
The application follows a modular pattern located in the `Modules/` directory:

| Module | Purpose | Status |
|--------|---------|--------|
| **AcService** | Core operational hub for masjid services | Live |
| **AcMasjidMusholla** | Public monitoring and information | Live |
| **AcAnggota** | Member/Donator portal | Live |
| **Inventory** | Asset and equipment tracking | MVP |
| **FutureModule** | Template for new feature expansion | MVP |

## Getting Started

### Prerequisites
- PHP 8.2+
- Composer 2.x
- Node.js 20+
- MySQL 8.x

### Installation

```bash
# 1. Install PHP dependencies
composer install

# 2. Install Node.js dependencies
npm install

# 3. Setup environment
copy .env.example .env

# 4. Generate keys and migrate
# Note: Migrations will attempt to auto-create the 4 required databases
php artisan key:generate
php artisan migrate

# 5. Build frontend assets
npm run build
```

### Development
The platform includes a pre-configured development stack that handles the server, queue, and vite simultaneously:

```bash
# Recommended: Run the full stack (Serve, Queue, Pail, Vite)
composer dev

# Manual alternative
php artisan serve
npm run dev
```

## Core Workflow & Statuses

### Service Order Lifecycle
AC Beneran uses a strict status-driven workflow to ensure operational integrity:

1. `spk_invoice_created` → Order initiated, SPK & Invoice generated.
2. `approved` → Documents reviewed and published for technician.
3. `in_progress` → Technician assigned and work is ongoing.
4. `waiting_review` → Triggered if technician reports additional costs/materials.
5. `waiting_invoice` → Work complete, pending payment verification.
6. `completed` → Payment verified; requires **Dual Confirmation** (Frontdesk & Manager).

### Dual Confirmation Feature
To ensure service quality and financial accuracy, an order can only be truly finalized after both a **Frontdesk** staff and a **Manager** have confirmed the completion in the system.

## Project Structure

```
ac_beneran_final/
├── app/
│   ├── Http/Controllers/    # Core business logic
│   ├── Models/              # Multi-connection Eloquent models
│   └── Support/             # Platform-wide helpers and navigation
├── Modules/                 # Encapsulated feature modules
│   ├── AcService/           # Main service logic
│   ├── AcAnggota/           # Member portal
│   └── ...                  # Other modules
├── database/
│   ├── migrations/          # Structured for multi-db support
│   └── seeders/             # Core and module data seeders
├── public/                  # Themed assets (Liquid Glass UI)
└── resources/views/         # Blade templates & UI components
```

## Security & Reliability
- **Subdomain Routing:** Modules are isolated via subdomains (e.g., `ac.domain.test`).
- **Real-time Sync:** Uses `SyncEvent` models for UI reactivity.
- **Role Middleware:** Strict RBAC (Admin, Manager, Frontdesk, Technician, Viewer).
- **Security Headers:** Built-in CSP and security header middleware.

## Documentation

Comprehensive guides are available in the `project-docs/` directory:

- [Architecture Guide](project-docs/Architecture.md)
- [Database Schema](project-docs/Database.md)
- [API Specification](project-docs/API.md)
- [Product Requirements (PRD)](project-docs/PRD.md)
- [Deployment Guide](project-docs/Deployment.md)
- [UI/UX Design System](project-docs/UIUX.md)

## License
This project is licensed under the MIT License.

---
<p align="center">Built for better transparency in Mosque AC Maintenance 🕌✨</p>
