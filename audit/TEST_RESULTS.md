# Audit Testing Results

**Date:** 2026-10-24

## Commands executed

| Command | Result | Relevant outcome / limitation |
|---|---|---|
| `rg --files ...`; targeted `nl`, `rg`, and `sed` reviews | Passed | Repository reconnaissance and static evidence collection completed. |
| `php spark routes` | Passed | Explicit route table generated. Account/admin filters are present; `checkout/payment/verify` is CSRF-exempt as designed. |
| `php vendor/bin/phpunit --testdox` | Not runnable | Failed: `Could not open input file: vendor/bin/phpunit`. The supplied vendor tree has autoload/framework files but no PHPUnit binary. |
| `find app -name '*.php' -print0 \| xargs -0 -n1 php -l` | Passed | All application PHP files reported no syntax errors. |
| `composer audit --locked --no-interaction` | Passed | No PHP dependency security advisories reported. Network access was required for this read-only advisory query. |
| `npm audit --omit=dev --audit-level=low` | Passed | Reported zero production dependency vulnerabilities. |
| `npm audit --audit-level=low` | Passed | Reported 4 dev/build-chain vulnerabilities: 1 high (`source-map-js`) and 3 moderate through Tailwind/PostCSS `postcss-selector-parser`. Do not run `npm audit fix --force` blindly; it proposes Tailwind 4. |

## Static-analysis observations

- SQL use was primarily through CodeIgniter query builder/model APIs. No confirmed SQL injection was identified in reviewed application code.
- Explicit route definitions and `autoRoute=false` were confirmed.
- Ownership checks were confirmed for account orders and addresses.
- Image upload handling includes multiple validation and re-encoding controls; video handling does not have equivalent safeguards.
- No tests were found for checkout, payment, coupon enforcement, stock concurrency, order transitions, authorization abuse, auth recovery, or upload adversarial cases.

## Existing test coverage

Only three starter/example tests exist: framework health, session storage, and example database model behavior. They do not exercise application business logic. `phpunit.dist.xml` defines an isolated SQLite test configuration, but the live application migrations target MySQL behavior and the PHPUnit executable was unavailable locally.

## Verification limitations

- No MySQL database or seeded application instance was started.
- No live/test Razorpay transaction, webhook, refund, email, or upload was executed.
- No production endpoint, production data, database, secret, TLS proxy, CDN, monitoring, backup, or payment account was accessed.
- Dependency advisory results are point-in-time and do not establish exploitability in deployed runtime configuration.

## Recommended additional tests

1. MySQL integration tests for stock/coupon conditional updates under parallel checkout.
2. Razorpay test-mode tests for callback/webhook signature, gateway-order ownership, amount/currency/capture validation, replay, and duplicate payment IDs.
3. Idempotency tests for duplicate checkout POSTs and intent-creation failure recovery.
4. Order transition, cancellation, partial refund, and one-time inventory restoration tests.
5. Account/order/address ownership, rate-limit, password-reset, and session-rotation tests.
6. Adversarial image/video validation and cart DOM-XSS regression tests.
7. Staging tests for HTTPS, CSP/security headers, secure cookies, error display, backups, restore, and health/monitoring behavior.
