# Open Source Guardrails

This file captures practical mistakes to avoid when open-sourcing Go-Fi.

## Things to avoid

### 1. Using a permissive license too early
If you use MIT or similarly permissive licensing for a project like this, others may clone it, host it, monetize it, and out-market you without giving back.

### 2. Giving away every premium advantage
If the repo includes every enterprise feature, every integration, every analytics tool, and white-label support, you remove too many reasons to pay you.

### 3. Ignoring brand protection
Code licensing does not protect the product name. If you do not protect branding, competitors can look official and confuse customers.

### 4. Mixing premium and public code carelessly
Keep boundaries clear. If premium features are planned, structure the repo so the core can stay clean and the add-ons can stay separate.

### 5. Poor contributor rules
Without clear contribution rules, you may receive code that increases maintenance cost or muddies licensing expectations.

### 6. Weak payment and finance boundaries
Because this product handles money, access, and partner revenue, sloppy financial data handling can damage both trust and operations.

## Monetization mistakes that kill leverage

- publishing white-label rights by default
- allowing unrestricted commercial trademark reuse
- open-sourcing every competitive integration immediately
- failing to offer an easier hosted experience than self-hosting
- failing to document when a commercial license is required
- building a public core that is too messy for community trust and too complete for commercial differentiation

## Practical strategy

The best balance is usually:

- strong public core
- AGPL protection on the core
- separate commercial rights
- protected branding
- premium modules later
- hosted convenience as the easiest path for most customers
