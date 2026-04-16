# Monetization Strategy

This document explains how Go-Fi can remain open while still being economically favorable to the founder.

## Core model: open core

The recommended model is:

- **Open core** for community adoption and trust
- **Commercial path** for organizations needing proprietary or enterprise use
- **Brand control** to protect the official product identity

## Main revenue paths

### 1. Hosted SaaS
Offer an official hosted version of Go-Fi.

Best for:
- hotspot operators who do not want self-hosting
- resellers who want updates and support
- operators who want quick onboarding

Advantages:
- recurring revenue
- stronger customer lock-in through convenience and support
- faster product feedback loop

### 2. Managed deployment
Provide setup and deployment services for:
- hotels
- cafés
- local ISPs
- hotspot operators
- community WiFi providers

Typical revenue:
- setup fee
- customization fee
- maintenance fee

### 3. Commercial licensing
Sell commercial licenses for:
- closed-source deployments
- white-label rights
- private forks
- enterprise rollout

### 4. Support and consulting
Offer:
- architecture support
- integration help
- payment gateway setup
- hotspot/router onboarding
- performance tuning

### 5. Premium modules
Keep selected advanced modules outside the public core repo.
Examples are listed in `PREMIUM_MODULES.md`.

## Why this model is favorable

This approach helps the founder because:

- the public repo attracts contributors and trust
- AGPL reduces silent proprietary SaaS competition around the core
- brand restrictions keep the official identity valuable
- premium modules preserve future upsell paths
- managed hosting/support creates recurring income

## What not to give away too early

Avoid open-sourcing every strategic advantage from day one.
In particular, be careful with:

- advanced analytics
- enterprise reporting
- white-label tooling
- premium integrations
- operational automation that customers will pay for

## Founder advantage checklist

To stay in a strong commercial position:

- keep the public core useful but not exhaustive
- own the official hosted experience
- own the official brand
- keep commercial contracts simple
- document boundaries between open and premium clearly
