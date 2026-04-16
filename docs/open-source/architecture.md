# Architecture Overview

## Go-Fi (WRMS v2)
**Multi-Tenant Hotspot Billing & Reseller Management Platform**

---

## 1. Introduction

Go-Fi is a multi-tenant hotspot billing and reseller management platform designed for mobile-money-first markets. It enables platform owners, resellers, and branch operators to manage internet access sales, voucher fallback, session control, and revenue sharing from a single system.

The platform is built with:

- **Laravel** for backend logic, APIs, queues, and business workflows
- **Inertia.js + React** for the web dashboard and captive portal UI
- **MySQL or PostgreSQL** for persistent storage
- **Queue workers** for async tasks such as payment callback processing and session expiry handling

Go-Fi is designed as a **real business platform**, not just a basic hotspot tool.

---

## 2. Core Architectural Goals

The system architecture is designed around the following goals:

- **Multi-tenancy from day one**
- **Strong tenant data isolation**
- **Financial traceability**
- **Extensible payment integrations**
- **Voucher fallback support**
- **Router/network integration readiness**
- **Clear role-based access control**
- **Scalable SaaS foundation**

---

## 3. High-Level Architecture

The platform consists of five major layers:

1. **Presentation Layer**
2. **Application Layer**
3. **Domain / Business Logic Layer**
4. **Infrastructure / Integration Layer**
5. **Persistence Layer**

### High-Level View

```text
Users / Customers / Operators
        |
        v
Frontend UI (Inertia + React)
        |
        v
Laravel Web Layer (Routes, Controllers, Middleware, Requests)
        |
        v
Application Services / Actions / Policies
        |
        +----------------------+
        |                      |
        v                      v
Domain Logic             Integration Services
(Payments, Vouchers,     (Payment gateways,
Sessions, Revenue)       Router/network hooks)
        |                      |
        +----------+-----------+
                   |
                   v
            Database / Storage
````

---

## 4. Main System Context

The platform serves several types of actors:

### 4.1 Platform Owner

The super admin who operates the main system and may also own some hotspot points directly.

### 4.2 Tenant / Reseller

A business or individual operating one or more hotspot points under the platform.

### 4.3 Branch / Point

A physical location such as a café, hotel, office, station, or hotspot area.

### 4.4 Operator / Staff

A user assigned to manage local operations such as voucher sales or branch monitoring.

### 4.5 End User / Customer

A person connecting to a hotspot and purchasing or redeeming internet access.

---

## 5. Multi-Tenant Architecture

Go-Fi follows a **shared application, shared database, row-scoped multi-tenant model**.

### 5.1 Tenancy Model

All tenant-owned business records are linked to a tenant directly or indirectly through branch ownership.

Examples:

* packages belong to a tenant
* branches belong to a tenant
* hotspot devices belong to a tenant and branch
* vouchers belong to a tenant
* transactions belong to a tenant
* sessions belong to a tenant

### 5.2 Tenant Isolation Strategy

Tenant isolation is enforced through:

* route protection
* authorization policies
* scoped queries
* explicit foreign keys
* careful UI filtering

Platform admins can see all data. Tenant users only see their allowed tenant data.

### 5.3 Why This Model

This approach is preferred because:

* it is simpler for MVP and early growth
* it supports central reporting
* it fits Laravel naturally
* it is easier to manage than per-tenant databases at this stage

---

## 6. Core Business Domains

The system is organized around several business domains.

### 6.1 Tenancy & Identity

Handles:

* users
* roles
* tenant memberships
* branch assignments
* access control

### 6.2 Commerce & Access Sales

Handles:

* packages
* voucher profiles
* vouchers
* customer purchases
* access activation

### 6.3 Payments

Handles:

* payment initiation
* transaction lifecycle
* payment callback/webhook processing
* payment verification

### 6.4 Sessions & Network Access

Handles:

* active session creation
* expiry logic
* MAC/IP-based session tracking
* network authorization hooks

### 6.5 Revenue Sharing & Finance

Handles:

* revenue split rules
* transaction allocations
* balances
* payouts
* financial reports

---

## 7. Main Architectural Components

## 7.1 Frontend Layer

The frontend is built with **Inertia.js + React**.

### Responsibilities

* dashboards for platform and tenant users
* captive portal for hotspot customers
* forms for package management, vouchers, transactions, and payouts
* role-aware navigation
* mobile-friendly layouts for customer flows

### Main UI Areas

* platform admin dashboard
* tenant dashboard
* branch/hotspot management
* package management
* voucher management
* transaction monitoring
* payout visibility
* captive portal purchase and redemption flow

---

## 7.2 Laravel Web Layer

This layer contains:

* routes
* controllers
* middleware
* form requests
* policies

### Responsibilities

* request validation
* authentication and authorization
* request-to-service orchestration
* rendering Inertia pages
* exposing API/webhook endpoints where needed

Controllers should remain thin and delegate business logic to services or actions.

---

## 7.3 Application / Service Layer

This layer coordinates important workflows.

Examples:

* initiate payment for a package
* process payment callback
* redeem voucher
* activate session
* allocate revenue
* calculate payout summaries

### Benefits

* keeps controllers clean
* makes workflows testable
* reduces duplicated business rules

---

## 7.4 Domain Layer

This layer models the business entities and their rules.

Examples:

* Tenant
* Branch
* Package
* Voucher
* Transaction
* Session
* RevenueShareRule
* RevenueAllocation
* Payout

This is where the core business meaning lives.

---

## 7.5 Integration Layer

This layer abstracts external systems.

### Payment Integration

A payment gateway abstraction allows future support for multiple providers.

Example interface responsibilities:

* initiate payment
* verify payment
* process callback payload
* map provider response to internal transaction updates

### Router / Network Integration

A network access abstraction allows future integration with:

* MikroTik API
* OpenWrt scripts
* RADIUS-based systems

Example interface responsibilities:

* grant access
* revoke access
* check session status
* receive device heartbeat

This keeps the core app independent of specific network hardware.

---

## 7.6 Persistence Layer

The persistence layer stores all platform data in a relational database.

Main storage areas:

* tenant structure
* users and roles
* packages and vouchers
* transactions and payment callbacks
* sessions
* revenue rules and allocations
* payouts and balances
* audit information

---

## 8. Data Model Overview

Below is the conceptual structure of the main entities.

### 8.1 Identity and Tenancy

* `users`
* `roles`
* `tenant_user` or membership mapping
* `tenants`
* `branches`
* `hotspot_devices`

### 8.2 Products and Access

* `packages`
* `voucher_profiles`
* `vouchers`

### 8.3 Financial Transactions

* `transactions`
* `payment_callbacks`

### 8.4 Network Sessions

* `sessions`

### 8.5 Revenue Sharing

* `revenue_share_rules`
* `revenue_allocations`
* `payouts`
* optional ledger/balance tables

---

## 9. Core System Flows

## 9.1 Mobile Payment Purchase Flow

This is the main commercial flow.

### Steps

1. Customer connects to hotspot
2. Customer is redirected to captive portal
3. Customer selects a package
4. Customer enters a phone number
5. System initiates payment through payment gateway
6. A pending transaction is created
7. Payment provider sends callback/webhook
8. Callback is verified and processed
9. Transaction is marked paid
10. Revenue allocations are created
11. Session is created
12. Router/network access provider is called to grant access
13. Customer gets internet access

### Important Rules

* no access before confirmed payment
* callbacks must be idempotent
* transaction state changes must be auditable

---

## 9.2 Voucher Redemption Flow

This is the fallback path.

### Steps

1. Customer connects to hotspot
2. Customer opens captive portal
3. Customer selects voucher option
4. Customer enters voucher code
5. System validates voucher
6. Voucher status is updated safely
7. Transaction is recorded
8. Revenue allocation is created if applicable
9. Session is created
10. Router/network access provider grants access

### Important Rules

* voucher must not be reused incorrectly
* voucher may be locked to first MAC address
* redemption must be traceable

---

## 9.3 Session Lifecycle Flow

### States

* pending
* active
* expired
* terminated

### Flow

1. Session is created after a valid activation event
2. Session becomes active
3. Periodic checks determine whether it is still valid
4. When duration or data limit is exhausted, the session expires
5. Network access is revoked

---

## 9.4 Revenue Allocation Flow

This is critical to the business model.

### Steps

1. Paid or valid transaction is confirmed
2. Matching revenue share rule is selected
3. Gross, fees, and net amounts are computed
4. Revenue allocations are created and stored
5. Tenant balance / payout summary becomes visible

### Key Principle

**Revenue allocations are stored at transaction time.**
They are not treated as temporary calculations only.

---

## 10. Revenue Sharing Architecture

Revenue sharing is a first-class part of the platform.

### Supported Rule Types

* percentage split
* fixed platform fee
* hybrid split

### Possible Scope Levels

* platform default
* tenant-specific
* branch-specific
* package-specific

### Why This Matters

The system must support:

* hotspots owned directly by the platform owner
* hotspots owned by partners
* different agreements per tenant or point

### Example

For a transaction of TZS 2,000:

* gateway fee = TZS 100
* net = TZS 1,900
* platform allocation = TZS 600
* tenant allocation = TZS 1,300

These allocations should be written permanently to the database.

---

## 11. Access Control Architecture

Authorization is hierarchical.

### 11.1 Platform Role

* `super_admin`

### 11.2 Tenant Roles

* `tenant_owner`
* `branch_manager`
* `operator`
* `accountant` (optional)

### Enforcement Mechanisms

* authentication
* middleware
* policies
* scoped queries
* UI restrictions

### Principle

Frontend hiding is not enough.
All sensitive access must be protected on the server.

---

## 12. Queue and Background Job Architecture

Some processes should be asynchronous.

### Good Queue Candidates

* payment callback post-processing
* voucher batch generation
* session expiry checks
* payout summary updates
* router sync tasks
* notifications

### Why Queues Matter

* keeps the app responsive
* prevents long-running requests
* improves reliability for retryable work

---

## 13. Router / Network Integration Design

Go-Fi is designed to support network integration without tightly coupling the core app to one vendor.

### Planned Integration Targets

* MikroTik
* OpenWrt
* RADIUS-compatible systems

### Network Abstraction Principles

* the core app decides business validity
* the network layer enforces access
* device-specific logic stays in integration classes
* failures are logged and recoverable

### Example Actions

* grant access to MAC address
* revoke expired session
* poll session state
* sync branch device status

---

## 14. Auditability and Traceability

Because the platform handles money and access control, auditability is essential.

### Must Be Traceable

* payment initiation
* payment confirmation
* voucher generation
* voucher redemption
* session activation
* session termination
* revenue allocation
* payout approval

### Basic Measures

* timestamps
* actor/user reference
* transaction reference
* callback payload retention
* optional activity logs

---

## 15. Error Handling Principles

The architecture should treat failures explicitly.

### Examples

* failed payment initiation
* unmatched payment callback
* duplicate callback
* invalid voucher code
* expired voucher
* router access grant failure
* session creation failure

### Design Rule

A financial or access-control failure must never silently succeed.

---

## 16. Security Considerations

Security requirements include:

* strong authentication for dashboard users
* strict authorization boundaries
* tenant isolation
* webhook verification
* secure secret storage
* no credential exposure in code
* safe logging practices
* rate limiting for public endpoints where needed

Sensitive areas:

* payments
* voucher redemption
* admin actions
* session activation
* network device secrets

---

## 17. Scalability Considerations

The architecture is designed to scale in stages.

### Early Stage

* single application instance
* single database
* queue worker
* one or few hotspots

### Growth Stage

* multiple queue workers
* optimized reporting
* caching where useful
* more robust router integrations
* hosted SaaS operations

### Long-Term Evolution

Possible future evolution:

* modular premium packages
* more payment providers
* more network adapters
* advanced analytics
* white-label capabilities

---

## 18. Why Laravel + Inertia + React

This stack was selected because it supports both speed and structure.

### Benefits

* one main backend framework
* simplified deployment compared to separate SPA/API stacks
* rich dashboard UI using React
* strong Laravel ecosystem for auth, queues, jobs, policies, and testing
* easier founder-led development and maintenance

This is a strong fit for an MVP that still needs real business architecture.

---

## 19. Architectural Boundaries for MVP

The MVP should focus on these core capabilities:

### In Scope

* multi-tenancy
* branches and hotspot devices
* packages
* vouchers
* payments abstraction
* transactions
* sessions
* revenue share rules
* allocations
* payout visibility
* dashboards

### Out of Scope for Initial MVP

* ads-based free access
* AI analytics
* advanced white-labeling
* mobile app
* complex BI dashboards
* full router firmware management

---

## 20. Summary

Go-Fi is architected as a **multi-tenant, finance-aware hotspot platform** rather than a simple voucher tool.

Its architecture is built around:

* tenant-safe SaaS foundations
* real commercial workflows
* payment-first access activation
* voucher fallback
* session lifecycle control
* stored revenue allocation
* future-ready integration with hotspot/network devices

This gives the project a strong base for both self-hosted deployments and future managed SaaS growth.

---
