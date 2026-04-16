# 🌐 Go-Fi (WRMS v2)

### Multi-Tenant Hotspot Billing & Reseller Management Platform

> A modern platform for managing WiFi hotspots, selling internet access via mobile money, and enabling revenue sharing between platform owners and resellers.

---

## 🚀 Overview

**Go-Fi** is a **multi-tenant hotspot billing system** designed for real-world deployments in mobile-money-first markets.

It allows:

* Internet resellers to **sell access easily**
* Users to **pay via mobile money**
* Operators to **use vouchers as fallback**
* Platform owners to **manage multiple partners and share revenue automatically**

This is not just a hotspot system — it’s a **full business platform for internet distribution**.

---

## Open Source Philosophy

Go-Fi is intended to be public, useful, and contributor-friendly without giving away the business model. The source code should help operators, developers, and learners understand how a modern hotspot billing platform works in practice.

The project is open for community benefit, transparency, and learning. Contributors can improve the core platform, fix bugs, add quality-of-life features, and help make the base product stronger for everyone.

The commercial strategy remains separate from the public core. That means the source is open, but the brand, hosted service, premium modules, and enterprise usage rights are controlled deliberately so the project can stay sustainable.

---

## Commercial Usage

Go-Fi is planned as an AGPL-licensed open-source core with a commercial licensing path for companies that want to use it without open-sourcing their own modified deployments.

If you run Go-Fi as a SaaS, modify it and expose it over a network, or use it in a white-label enterprise deployment, you must comply with the AGPL terms or obtain a commercial license.

For commercial licensing, white-label usage, enterprise deployment, or support inquiries, contact the project owner at: `hasanimkindi@gmail.com`

Reference documents:

* [Repository Structure](docs/open-source/REPO_STRUCTURE.md)
* [License Strategy](docs/open-source/LICENSE_STRATEGY.md)
* [Dual License Plan](docs/open-source/DUAL_LICENSE_PLAN.md)
* [Trademark Policy](docs/open-source/TRADEMARK_POLICY.md)
* [Monetization Strategy](docs/open-source/MONETIZATION_STRATEGY.md)
* [Future Premium Strategy](docs/open-source/FUTURE_PREMIUM_STRATEGY.md)
* [Contributing](CONTRIBUTING.md)
* [Code of Conduct](CODE_OF_CONDUCT.md)
* [Security Policy](SECURITY.md)
* [Disclaimer](DISCLAIMER.md)

---

## ✨ Key Features

### 🧩 Multi-Tenant Architecture

* Multiple resellers (tenants)
* Each tenant manages their own branches and hotspots
* Platform-level admin with full visibility

### 💳 Mobile Payment Integration

* Pay for internet using mobile money
* Payment verification via callbacks/webhooks
* Automatic session activation after successful payment

### 🎟 Voucher System (Fallback)

* Bulk voucher generation
* Voucher profiles (time/data-based)
* Manual sales support
* Secure redemption & tracking

### 📡 Session Management

* MAC-based user sessions
* Automatic expiry enforcement
* Ready for router integration (MikroTik / OpenWrt / RADIUS)

### 💰 Revenue Sharing Engine

* Split revenue between platform and tenants
* Supports:

  * percentage-based
  * fixed fee
  * hybrid models
* Allocation stored per transaction

### 📊 Dashboard & Reporting

* Revenue tracking
* Active sessions
* Voucher usage
* Branch-level performance

---

## 🏗 Tech Stack

* **Backend:** Laravel
* **Frontend:** Inertia.js + React
* **Database:** MySQL / PostgreSQL
* **Queue:** Laravel Queue (Redis/DB)
* **Styling:** Tailwind CSS

---

## 🧠 Core Concept

When a user connects to a hotspot:

1. They are redirected to a **captive portal**
2. They choose an internet package
3. They:

   * pay via mobile money **OR**
   * enter a voucher code
4. The system:

   * verifies payment/voucher
   * creates a session
   * grants internet access via router integration

---

## 🏢 System Roles

### Platform Admin

* Manages tenants
* Controls revenue rules
* Views global reports

### Tenant (Reseller)

* Manages branches/hotspots
* Creates packages and vouchers
* Tracks revenue and usage

### Branch Operator

* Handles local operations
* Sells vouchers
* Monitors sessions

---

## 📦 Core Modules

* Tenancy & Roles
* Branch / Hotspot Management
* Packages
* Vouchers
* Transactions
* Sessions
* Revenue Allocation
* Payout Tracking

---

## 🔁 Key Flows

### 💳 Mobile Payment Flow

* Select package → Pay → Confirm → Session activated

### 🎟 Voucher Flow

* Enter code → Validate → Session activated

### ⏱ Session Lifecycle

* Start → Active → Expired → Revoked

---

## 📁 Project Structure (High-Level)

```
core/
 ├── app/
 ├── resources/
 ├── routes/
 ├── database/
modules/
 ├── premium/
 ├── integrations/
 ├── enterprise/
docs/
 ├── open-source/
 ├── product/
 ├── policies/
```

### How the split works later

* `core/` holds the open-source AGPL platform that remains usable by the community.
* `modules/` holds optional add-ons, commercial packs, or future enterprise-only features.
* `docs/` holds policies, strategy, product notes, and contributor guidance.
* Keep premium code isolated so the public core remains valuable, but not the full monetization package.

---

## ⚙️ Installation

```bash
git clone https://github.com/YOUR_USERNAME/go-fi.git
cd go-fi

composer install
npm install

cp .env.example .env
php artisan key:generate
```

### Configure database

```bash
php artisan migrate --seed
```

### Run app

```bash
php artisan serve
npm run dev
```

---

## 🧪 Demo Seed Data

Seeder includes:

* Platform admin
* Sample tenants
* Branches
* Packages
* Vouchers
* Transactions

> Update credentials in seeders or `.env` for local testing.

---

## 🔌 Integration Notes

### Payment Gateway

* Abstracted (plug-and-play)
* Supports mobile money APIs
* Webhook-based confirmation

### Router Integration (Planned/Stub)

* MikroTik API
* OpenWrt scripts
* RADIUS support

---

## 📌 Roadmap

### MVP

* [x] Multi-tenant structure
* [x] Packages & vouchers
* [x] Transactions
* [x] Session control
* [x] Revenue sharing

### Next

* [ ] Real payment gateway integration
* [ ] Router integration
* [ ] Advanced reporting
* [ ] Mobile app
* [ ] White-label support

Public roadmap details are tracked in [docs/open-source/ROADMAP.md](docs/open-source/ROADMAP.md), and release readiness is covered in [docs/open-source/RELEASE_CHECKLIST.md](docs/open-source/RELEASE_CHECKLIST.md).

---

## 🌍 Target Market

* Internet cafés
* Hotels & lodges
* Restaurants
* Co-working spaces
* Small ISPs
* Community WiFi providers

---

## 💡 Vision

The goal is to build the strongest practical open-core hotspot billing platform in the market: one that earns through hosting, support, and premium modules while still giving the community a useful foundation to build on.

To become the **default platform for internet reselling in emerging markets**, enabling:

* affordable connectivity
* flexible monetization
* decentralized internet distribution

---

## 🤝 Contributing

Contributions are welcome.

* Fork the repo
* Create feature branch
* Submit PR

---

## 📜 License

AGPL-3.0-only for the open-source core. See [LICENSE](LICENSE) and [docs/open-source/LICENSE_STRATEGY.md](docs/open-source/LICENSE_STRATEGY.md).

---

## 👨‍💻 Author

**Hasani Mkindi**
Founder of AfroDemoz
Tanzania 🇹🇿

---

## ⭐ Support

If you like this project:

* ⭐ Star the repo
* 🍴 Fork it
* 📢 Share it

