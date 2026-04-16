# Future Premium Strategy

## Goal

Keep the open-source core strong enough to attract contributors, but reserve premium capabilities for paid offerings so the business can grow.

## Features to Keep Out of Open Source Later

The following should be treated as premium or enterprise-only features when they become real product lines:

* advanced analytics
* AI-generated operational insights
* multi-country payment integrations
* enterprise dashboard and executive reporting
* white-label system and branding controls
* advanced SLA monitoring and alerting
* centralized fleet management for large operator groups
* premium automation for payouts, settlements, and compliance

## Separation Rule

If a feature directly increases revenue, lowers support cost dramatically, or makes white-label usage easier, consider keeping it out of the public core.

## Packaging Model

Use a layered structure:

* **core/** for the AGPL open-source base
* **modules/** for optional add-ons
* **enterprise/** or a private package registry for paid capabilities

## Release Discipline

Do not ship premium features into the public repo by default.

If a feature must be public for technical reasons, publish only the minimum integration surface and keep the actual value layer private.

## Positioning

The public version should be good enough to learn from, contribute to, and self-host.

The paid version should be the best choice for businesses that want convenience, branding flexibility, and lower operational risk.
