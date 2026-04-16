# Go-Fi

Multi-tenant hotspot billing and reseller management platform built with Laravel, Inertia.js, and React.

## Open source model

Go-Fi uses an **open-core** strategy.

- The **core platform** is released under **AGPL-3.0**.
- Commercial users who do not want to comply with AGPL obligations must obtain a **commercial license** from the project owner.
- The **Go-Fi** name, logo, brand assets, and official hosted service are **not** open for unrestricted reuse.

This model keeps the code visible and community-friendly while protecting the long-term business behind the platform.

## What Go-Fi does

- Multi-tenant hotspot and branch management
- Mobile-payment-first internet package sales
- Voucher fallback for manual/off-credit customers
- Session creation and lifecycle tracking
- Revenue-sharing and payout foundations
- Operator, manager, tenant-owner, and platform-admin workflows

## Why AGPL-3.0

AGPL-3.0 is used because this project is intended for public collaboration **without allowing competitors to quietly run a closed hosted version**.

If someone modifies the AGPL-covered core and offers it to users over a network, they must also make the corresponding source code available under the same license.

## Commercial usage

A commercial license is required for use cases such as:

- closed-source deployments
- white-label resale
- proprietary hosted/SaaS offerings that do not want AGPL obligations
- redistribution using the Go-Fi brand or official design assets
- enterprise contracts needing separate warranties, support, or indemnity

See `COMMERCIAL-LICENSE.md` and `TRADEMARK_POLICY.md`.

## Repository documents

- `LICENSE` — AGPL-3.0 notice file for the core
- `COMMERCIAL-LICENSE.md` — commercial licensing terms summary
- `CONTRIBUTING.md` — contribution rules
- `CODE_OF_CONDUCT.md` — community behavior expectations
- `SECURITY.md` — vulnerability reporting process
- `DISCLAIMER.md` — warranty and liability disclaimer
- `TRADEMARK_POLICY.md` — brand and naming restrictions
- `MONETIZATION.md` — business model guidance
- `PREMIUM_MODULES.md` — future premium/closed offerings plan
- `OPEN_SOURCE_GUARDRAILS.md` — mistakes to avoid

## Local development

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
npm run dev
```

## Contributing

Please read `CONTRIBUTING.md` before opening an issue or pull request.

## Brand note

Forks are welcome, but they must not present themselves as the official Go-Fi project unless written permission is granted.

## Contact

For commercial licensing, support, managed deployment, or partnership inquiries, contact the project owner through the repo contact details or business channels.
