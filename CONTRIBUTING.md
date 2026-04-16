# Contributing to Go-Fi

Thanks for your interest in contributing.

## Ground Rules

- Be respectful and constructive.
- Keep pull requests focused.
- Open an issue first for major changes.
- Do not submit code you do not have the right to contribute.

## How to Contribute

### Reporting Bugs
Please include:
- clear title and summary
- steps to reproduce
- expected behavior
- actual behavior
- screenshots/logs where helpful
- environment details

### Suggesting Features
Please include:
- problem being solved
- proposed solution
- alternatives considered
- any possible impact on security, tenancy, or finance

### Pull Requests
Before opening a PR:
- run tests
- keep coding style consistent with the project
- document behavior changes
- avoid mixing unrelated changes

## Coding Standards

- Keep controllers thin.
- Prefer readable code over clever code.
- Respect tenant boundaries and authorization rules.
- Financial logic must remain auditable.
- Do not introduce breaking schema changes casually.
- Add tests for business-critical paths.

## Commit Guidance

Good commits:
- `feat: add voucher redemption flow`
- `fix: prevent duplicate payment callback allocations`
- `docs: clarify AGPL and trademark policy`

## Areas That Need Extra Care

- payment callback processing
- revenue allocation logic
- tenant scoping
- session activation and expiry
- authorization

## License of Contributions

By contributing, you agree that your contributions may be distributed under the repository license
and, where applicable, included in future dual-licensed or commercially licensed versions of the project.
