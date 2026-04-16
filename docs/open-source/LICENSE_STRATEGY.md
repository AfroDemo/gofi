# License Strategy for Go-Fi

## Recommended Model

Use **AGPL-3.0-only** for the open-source core.

That is the right default for a SaaS-oriented platform because the main value of Go-Fi is not just running the code locally. The value is in running it as a network service, operating it for customers, and extending it into a commercial product.

## Why AGPL Is Better Than MIT Here

MIT is too permissive for this business model. A competitor could take the code, improve it privately, run it as a hosted service, and never contribute those improvements back.

AGPL changes that equation. If someone modifies Go-Fi and uses it to provide a network service, they must make the source of the modified version available to users of that service.

That does not stop all commercial use. It does stop private SaaS capture of the public core.

## How AGPL Prevents SaaS Abuse

AGPL closes the usual SaaS loophole found in permissive licensing.

With AGPL:

* hosted operators cannot quietly modify the platform and keep the changes closed if those changes are part of the network service
* competitors cannot take the public core, run it as a paid service, and keep their operational improvements secret
* community improvements remain visible when the platform is used over a network

## Practical Result

The public repo becomes a community foundation.

The business remains protected because:

* the hosted service is still the easiest experience to buy
* commercial users can buy a separate license
* premium modules can stay outside the open-source core
* the Go-Fi brand stays controlled through trademark policy

## Recommended Release Rule

Before public release, ensure the repository has:

* an AGPL notice in the root license setup
* a clear commercial licensing path
* a trademark policy
* contributor and security documentation

This file is a strategy note, not legal advice.
