# Contributing to Go-Fi

Thanks for your interest in contributing.

Go-Fi is an open-core project. Contributions are welcome, but they should strengthen the public core instead of pushing the repo toward unmaintainable complexity.

## Before you start

Please:

1. Read the README and project docs.
2. Check whether an issue already exists.
3. Open an issue before starting large features.
4. Keep pull requests focused.

## What kinds of contributions are welcome

- bug fixes
- tests
- developer experience improvements
- documentation improvements
- performance improvements with evidence
- accessibility and usability improvements
- maintainable feature additions aligned with the roadmap

## What is less likely to be accepted

- breaking architectural rewrites without justification
- large dependency additions for small problems
- branding changes
- unrelated experimental features
- features that belong in future premium/commercial modules

## Development expectations

- write clear code
- prefer small, reviewable pull requests
- respect existing conventions
- include tests for important behavior changes
- update documentation when behavior changes

## Coding standards

### Backend
- follow Laravel conventions
- keep controllers thin
- use form requests for validation
- keep business logic out of views
- write migrations that are clean and reversible

### Frontend
- keep React components readable and composable
- avoid giant page files when smaller components make sense
- preserve accessibility and responsiveness

### General
- use descriptive naming
- avoid dead code
- avoid introducing silent security risks

## Branch and PR guidance

- create a feature branch from the default branch
- use a descriptive branch name
- open a pull request with:
  - problem summary
  - approach taken
  - screenshots for UI changes
  - test notes

## Commit style

Simple and clear commit messages are preferred, for example:

- `feat: add voucher batch generation`
- `fix: prevent duplicate payment callback allocation`
- `docs: clarify commercial licensing note`

## Reporting issues

When opening an issue, include:

- expected behavior
- actual behavior
- steps to reproduce
- environment details if relevant
- screenshots/logs if helpful

## Security issues

Do not open public issues for security vulnerabilities. Follow `SECURITY.md`.

## Licensing note for contributors

By contributing to this repository, you agree that your contributions may be distributed under the project’s open-source license and may also be used by the project owner in commercially licensed distributions of the project.
