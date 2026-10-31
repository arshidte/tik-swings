# Prioritized Remediation Roadmap

This roadmap is ordered by risk reduction. It intentionally provides no unsupported duration estimates.

## Phase 1: Immediate containment and P0 issues

### Remove predictable seeded administration access — F-001

- **Task:** Stop using `DatabaseSeeder` for production identities; disable/delete/rotate every known seeded account; remove public credentials from production-facing documentation.
- **Why it matters:** Prevents immediate full-store compromise where the standard setup was used.
- **Dependencies:** None.
- **Approach:** Split demo fixtures into a development-only seeder; use a secret-driven one-time administrator bootstrap; add a deployment assertion that rejects known demo emails/password hashes in production.
- **Verification:** Fresh production provisioning has no demo accounts; documented credentials cannot authenticate; access logs are reviewed if any production instance was exposed.
- **Migration/downtime/coordination:** Requires operational coordination and credential rotation; consider short maintenance mode while auditing existing accounts.

## Phase 2: Production blockers and P1 issues

### Rebuild payment confirmation around persisted gateway state — F-002

- **Task:** Persist Razorpay order ID, expected paise amount/currency, and local payment state before client checkout; verify a callback/webhook against that exact record and provider capture state.
- **Why it matters:** Prevents false paid status and duplicate payment association.
- **Dependencies:** F-006 idempotency design; secret migration in F-010.
- **Approach:** Add unique provider-order/provider-payment constraints where data permits; use provider webhooks plus server-to-server retrieval; atomically transition only a pending payment; retain redacted audit events.
- **Verification:** Test gateway cases for mismatch, changed amount/currency, failed/pending/captured state, replay, and duplicate payment IDs.
- **Migration/downtime/coordination:** Schema migration and Razorpay test-mode coordination required; pause online payment confirmation until validation passes.

### Make checkout and stock/coupon reservation atomic and idempotent — F-003, F-004, F-006

- **Task:** Introduce one checkout idempotency record and transactional reservations for all stock and coupon effects.
- **Why it matters:** Prevents duplicate orders, oversell, and promotion leakage.
- **Dependencies:** Product stock policy decision; cancellation/refund rules in F-005.
- **Approach:** Use conditional updates with checked affected rows or row locks; roll back entire order creation on failure; persist/return the result for the same idempotency key; enforce coupon date/global/per-user limits in the same transaction.
- **Verification:** Concurrent final-stock/final-coupon tests accept at most one operation; duplicate POSTs create one order/effect; zero-stock rules behave according to documented policy.
- **Migration/downtime/coordination:** Schema migration likely for idempotency/reservation fields and constraints; test against MySQL, not only SQLite.

### Implement an enforced order/payment/refund state machine — F-005

- **Task:** Define permitted transitions and all required financial/inventory side effects.
- **Why it matters:** Stops inconsistent fulfillment/refund status and financial loss.
- **Dependencies:** F-002 and F-003.
- **Approach:** Centralize transition validation; separate order, fulfillment, return, and refund entities/states; use one-time compensating inventory/refund operations and audit history.
- **Verification:** Invalid transitions are rejected; valid cancellation/refund transitions create exactly one matched provider and inventory result.
- **Migration/downtime/coordination:** Schema changes and customer-service/payment-provider policy approval required.

### Provide a real usable payment method and configuration readiness check — F-008

- **Task:** Enable/test COD or provide a clearly configured online method; update the README to match.
- **Why it matters:** Checkout is otherwise unusable on a default installation.
- **Dependencies:** F-002 for online payment; F-005 for COD policy.
- **Approach:** Make payment methods feature-configured, validate at startup/deploy, and display an actionable checkout message only when no method is available.
- **Verification:** A clean staging environment exposes and completes at least one approved payment path.
- **Migration/downtime/coordination:** Payment/operations coordination; no migration necessarily required.

### Add account recovery and login abuse protection — F-007

- **Task:** Implement reset tokens/email delivery and adaptive rate limiting; protect admins with MFA where feasible.
- **Why it matters:** Prevents support dead ends and reduces account takeover risk.
- **Dependencies:** Mail provider and rate-limit storage/edge-control selection.
- **Approach:** Store hashed, expiring single-use tokens; apply per-IP/account throttle and audit events; rotate session on login.
- **Verification:** Reset success/failure/expiry/reuse and throttle tests pass without account enumeration.
- **Migration/downtime/coordination:** Token storage and email provider configuration; coordinate user-facing templates.

### Enforce production-safe deployment configuration — F-009

- **Task:** Supply validated production configuration and operational runbooks.
- **Why it matters:** Reduces accidental HTTP/debug/weak-header deployments.
- **Dependencies:** Hosting and reverse-proxy design.
- **Approach:** Require production environment, HTTPS/HSTS, trusted proxy settings, secure cookies, debug off, intentional CSP, DB TLS when remote, health/monitoring, backup restore, and migration/rollback procedures.
- **Verification:** Staging security-header and startup-config checks pass; backup restore is rehearsed.
- **Migration/downtime/coordination:** Infrastructure/operations coordination required.

## Phase 3: Reliability, functional correctness, and P2 issues

### Move Razorpay secrets out of application settings — F-010

- **Task:** Migrate secrets to managed deployment secrets and rotate existing keys.
- **Why it matters:** Limits blast radius from database/settings access.
- **Dependencies:** Secret manager and F-002 payment redesign.
- **Approach:** Remove secret editing/display from settings; inject at runtime; redact logs and payload retention.
- **Verification:** DB exports/settings UI contain no usable payment secret; rotated secret works in staging.
- **Migration/downtime/coordination:** Secret rotation coordination; no customer downtime if deployed safely.

### Remove cart HTML interpolation — F-012

- **Task:** Render cart drawer with safe DOM APIs or context-aware encoding.
- **Why it matters:** Prevents stored catalog XSS in shopper browsers.
- **Dependencies:** F-009 CSP hardening is defense in depth.
- **Approach:** Assign text via `textContent`, URL/image attributes only after protocol/path validation, and add XSS regression fixtures.
- **Verification:** HTML/script/URL payloads display as text and no code runs.
- **Migration/downtime/coordination:** No migration; standard frontend rollout.

### Establish a meaningful, executable test gate — F-013

- **Task:** Restore test dependencies and build focused integration/concurrency/security coverage.
- **Why it matters:** Prevents recurrence of every commerce defect above.
- **Dependencies:** F-002–F-007 designs.
- **Approach:** Pin/install dev tools in CI; use isolated MySQL and Razorpay fakes/test mode; add request/authorization and concurrency suites.
- **Verification:** CI blocks release on failing commerce/security tests and reports coverage for critical services.
- **Migration/downtime/coordination:** CI environment and test database needed.

### Add transaction notifications and return/cancellation requests — F-014

- **Task:** Implement an outbox-backed notification pipeline and governed customer service requests.
- **Why it matters:** Enables receipts, reliable customer communication, and policy execution.
- **Dependencies:** F-005 state model and a mail/SMS provider.
- **Approach:** Emit idempotent domain events, queue delivery, observe failures, and expose authenticated request forms with staff resolution.
- **Verification:** Test-mode events deliver once and customer eligibility is enforced.
- **Migration/downtime/coordination:** Provider, privacy/consent, and support-process coordination.

## Phase 4: Improvements and P3/P4 issues

### Harden media ingestion and scale administration — F-011 and operational observations

- **Task:** Process video asynchronously in a controlled pipeline; paginate growing admin datasets; add centralized monitoring and worker runbooks.
- **Why it matters:** Reduces storage/parser risk and avoids slow operational interfaces as data grows.
- **Dependencies:** Object storage/media processing and monitoring choices.
- **Approach:** Quarantine, inspect/transcode, constrain media; add pagers/indexes where profiling demonstrates need; centralize logs/metrics/alerts.
- **Verification:** Malformed videos are rejected, large supported files are processed without web-request exhaustion, and large lists remain bounded.
- **Migration/downtime/coordination:** May require storage/worker infrastructure; plan data migration before changing media URLs.
