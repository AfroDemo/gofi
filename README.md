# 🌐 Go-Fi (WRMS v2)

**Multi-Tenant Hotspot Billing & Reseller Management Platform**

> A modern platform for managing WiFi hotspots, selling internet access via mobile money, and enabling revenue sharing between platform owners and resellers.

## Overview

Go-Fi is a **multi-tenant hotspot billing system** designed for real-world deployments in mobile-money-first markets.

It allows:
- internet resellers to sell access easily
- users to pay via mobile money
- operators to use vouchers as fallback
- platform owners to manage multiple partners and share revenue automatically

This is not just a hotspot system — it is a **full business platform for internet distribution**.

## Key Features

### Multi-Tenant Architecture
- Multiple resellers (tenants)
- Each tenant manages their own branches and hotspots
- Platform-level admin with full visibility

### Mobile Payment Integration
- Pay for internet using mobile money
- Payment verification via callbacks/webhooks
- Automatic session activation after successful payment

### Voucher System
- Bulk voucher generation
- Voucher profiles (time/data-based)
- Manual sales support
- Secure redemption and tracking

### Session Management
- MAC-based user sessions
- Automatic expiry enforcement
- Ready for router integration (MikroTik / OpenWrt / RADIUS)

### Revenue Sharing Engine
- Split revenue between platform and tenants
- Supports percentage-based, fixed-fee, and hybrid models
- Allocation stored per transaction

## Tech Stack

- Laravel
- Inertia.js + React
- MySQL / PostgreSQL
- Laravel Queue
- Tailwind CSS

## Open Source Licensing

This repository uses the **GNU Affero General Public License v3.0 (AGPL-3.0)** for the open-source core.

Why AGPL:
- If someone modifies the software and runs it as a hosted service, they must also provide source code for those modifications under AGPL.
- This makes it harder for third parties to take the public code, run a competing hosted version, and keep changes private.
- It preserves community access to improvements while supporting an open-core business model.

See:
- `LICENSE`
- `COMMERCIAL-LICENSE.md`
- `TRADEMARK_POLICY.md`

## Commercial Usage

Commercial use is allowed under AGPL **only if the user fully complies with AGPL obligations**.

A separate commercial license is required for cases such as:
- closed-source deployments
- white-label usage
- proprietary SaaS offerings
- OEM/enterprise embedding
- use of protected branding or premium modules outside the AGPL terms

For commercial licensing, managed deployment, support, or partnership inquiries, contact the project owner.

## Core Concept

When a user connects to a hotspot:

1. They are redirected to a captive portal
2. They choose an internet package
3. They either:
   - pay via mobile money, or
   - enter a voucher code
4. The system:
   - verifies payment or voucher
   - creates a session
   - grants internet access via router integration

## Roles

### Platform Admin
- manages tenants
- controls revenue rules
- views global reports

### Tenant / Reseller
- manages branches and hotspots
- creates packages and vouchers
- tracks revenue and usage

### Branch Operator
- handles local operations
- sells vouchers
- monitors sessions

## Development Setup

```bash
git clone https://github.com/YOUR_USERNAME/go-fi.git
cd go-fi
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
npm run dev
```

## Repository Files

- `LICENSE` — AGPL-3.0 notice for the project
- `COMMERCIAL-LICENSE.md` — commercial licensing overview
- `TRADEMARK_POLICY.md` — brand usage restrictions
- `CONTRIBUTING.md` — contribution guide
- `SECURITY.md` — vulnerability reporting process
- `DISCLAIMER.md` — no-warranty / liability notice
- `MONETIZATION.md` — business model and economic strategy
- `PREMIUM_MODULES.md` — planned commercial modules
- `OPEN_SOURCE_GUARDRAILS.md` — what to avoid and how to keep monetization healthy

## Roadmap

### MVP
- [x] multi-tenant structure
- [x] packages and vouchers
- [x] transactions
- [x] session control
- [x] revenue sharing

### Next
- [x] dual payment gateway fallback orchestration (Palmpesa <-> Snippe)
- [ ] real payment gateway integration
- [ ] router integration
- [ ] advanced reporting
- [ ] premium add-on modules
- [ ] hosted SaaS offering

## Contributing

Contributions are welcome. Please read `CONTRIBUTING.md` and `CODE_OF_CONDUCT.md` before opening issues or pull requests.

## License

Open-source core: **AGPL-3.0**  
Commercial licensing available separately.

## Author

**Hasani Mkindi**  
Founder, AfroDemoz  
Tanzania 🇹🇿
