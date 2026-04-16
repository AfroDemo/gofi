# Repository Structure Strategy

## Suggested Layout

```text
core/
  app/
  resources/
  routes/
  database/
  tests/
modules/
  premium/
  integrations/
  enterprise/
docs/
  open-source/
  product/
  policies/
```

## Why This Structure Works

* `core/` holds the community-facing application logic.
* `modules/` gives you a clean place for optional paid extensions.
* `docs/` keeps the strategy, policy, and product decisions visible.
* The split makes it easier to open-source the base while keeping premium value separate.

## How to Separate Open vs Premium Later

Use clear boundaries:

* public interfaces in the core
* private integrations in premium modules
* optional package discovery through configuration
* feature flags or license checks only where needed

## Rule of Thumb

If the code is required for the community to understand and run the platform, keep it in core.

If the code mainly exists to unlock revenue, enterprise convenience, or brand-controlled deployment, keep it outside the public core.
