# Detailed Technical Audit Findings

**Audit date:** 2026-10-24

## 1. Repository and architecture overview

CodeIgniter 4.7.4 routes server-rendered PHP views and AJAX endpoints. MySQL migrations define catalog, cart, coupon, order, payment, shipment, content, and user data. `CartService` produces server-priced cart snapshots; `OrderService` creates order snapshots and attempts inventory/coupon changes inside a database transaction. Checkout selects a provider through `PaymentManager`; the current manager exposes Razorpay only when configured. Account and admin route groups use custom session filters.

## 2. Methodology, scope, and exclusions

Reviewed implementation and schema paths from HTTP route through controller, service, model, and view; searched for enforcement points before drawing conclusions; inspected framework configuration and test/dependency artifacts. Commands and results are recorded in [TEST_RESULTS.md](TEST_RESULTS.md). No production system, payment gateway, database, or customer data was accessed. Findings based on code are labelled Confirmed; deployment-dependent conditions are labelled High or Medium confidence.

## 3. Findings

### F-001 — Published seed administrator credentials can enable full administrative takeover

- **Category:** Security
- **Severity:** HIGH
- **Priority:** P0
- **Confidence:** Confirmed
- **Affected component:** Production provisioning / seeded users / `/admin`
- **Evidence:** [README.md](../README.md) lines 42-55 directs `php spark db:seed DatabaseSeeder` and publishes the administrator account. [app/Database/Seeds/DatabaseSeeder.php](../app/Database/Seeds/DatabaseSeeder.php) lines 9-14 invokes `ContentSeeder`; [app/Database/Seeds/ContentSeeder.php](../app/Database/Seeds/ContentSeeder.php) lines 16-31 creates an active admin with the documented password. [app/Config/Routes.php](../app/Config/Routes.php) lines 89-118 makes that role administrative.
- **Issue:** The production setup path includes publicly known, active credentials for an administrator.
- **Impact:** An unauthenticated internet user can obtain full catalog, order, customer, payment-setting, and content administration if the standard seeding procedure was used.
- **Reproduction or failure scenario:** Deploy using the README sequence, run `DatabaseSeeder`, then sign in with the published administrator email and password and open `/admin`.
- **Root cause:** Demo identities are mixed into the general production seeder and setup documentation.
- **Recommended remediation:** Separate non-production fixtures from production seeders; make administrator bootstrap an explicit one-time, secret-driven deployment step; delete/rotate all seeded credentials in every non-disposable environment; add a deployment guard that refuses demo accounts in production.
- **Verification criteria:** Production migration/seeding creates no predictable account; attempts with the documented credentials fail; an administrator is provisioned only through the approved bootstrap process.
- **Dependencies:** None.
- **Attack prerequisites:** Network access to a deployment seeded through the documented path.
- **Exploitability:** Direct and realistic when the documented setup was used.
- **Affected data or assets:** All admin-managed data and settings, including customer/order information and Razorpay configuration.
- **Potential scope:** System-wide.

### F-002 — Payment confirmation is not bound to the persisted Razorpay order or independently reconciled

- **Category:** Security / Business Logic
- **Severity:** HIGH
- **Priority:** P1
- **Confidence:** Confirmed
- **Affected component:** `POST /checkout/payment/verify`, Razorpay payment flow
- **Evidence:** [app/Controllers/Checkout/Checkout.php](../app/Controllers/Checkout/Checkout.php) lines 124-142 selects an application order from session or request, then accepts `$method->verify($payload)` and calls `markPaid`. [app/Libraries/Payment/RazorpayPaymentService.php](../app/Libraries/Payment/RazorpayPaymentService.php) lines 106-120 verifies only HMAC over submitted `razorpay_order_id|razorpay_payment_id`. [app/Services/OrderService.php](../app/Services/OrderService.php) lines 123-127 creates a payment row but does not store `provider_order`; lines 137-143 mark paid with no pending-state, amount, currency, or unique-provider-reference condition. The schema explicitly has `provider_order` and `provider_ref` fields at [CreateOrderTables.php](../app/Database/Migrations/2025-01-01-000006_CreateOrderTables.php) lines 79-98, but the former is unused.
- **Issue:** A signature proves a gateway response is signed for some merchant Razorpay order, not that it belongs to this application's selected order or that the payment was captured for its expected amount/currency. Replays are also accepted.
- **Impact:** Orders can be falsely marked paid, the same payment can be associated with multiple orders, and financial reconciliation is unreliable.
- **Reproduction or failure scenario:** Obtain a valid Razorpay checkout response for any merchant order, submit its three Razorpay fields to the callback while the session/request resolves another pending application order. The code has no equality check between the submitted and application gateway-order IDs, nor a remote capture/amount check. Reposting the same payload repeats `markPaid` and history creation.
- **Root cause:** Gateway-order creation is detached from payment persistence, and the browser callback is treated as authoritative.
- **Recommended remediation:** Persist the gateway order ID atomically with the local payment before rendering checkout; require equality with that ID; fetch/verify payment capture status, amount, currency, and merchant order server-to-server or process authenticated provider webhooks; enforce a unique provider payment reference; update only a pending payment in one transaction; store a redacted, minimal event record.
- **Verification criteria:** A valid signature for a different gateway order, wrong amount/currency, pending/failed payment, duplicate payment ID, replay, and mismatched session order all fail without changing local status. A valid captured payment changes one matching pending order exactly once.
- **Dependencies:** F-006.
- **Attack prerequisites:** A valid gateway response under the merchant account, or a replayable callback payload.
- **Exploitability:** Realistic for payment replay/misassociation; exact fraud path should be tested with Razorpay test mode after remediation design is approved.
- **Affected data or assets:** Order payment state, fulfillment decisions, revenue records.
- **Potential scope:** Multiple orders.

### F-003 — Inventory conditional decrements can fail silently, enabling oversell

- **Category:** Business Logic / Database
- **Severity:** HIGH
- **Priority:** P1
- **Confidence:** Confirmed
- **Affected component:** Checkout stock reservation
- **Evidence:** [app/Services/OrderService.php](../app/Services/OrderService.php) lines 41-57 reads stock before writing. Lines 104-109 issue conditional `stock >= quantity` decrements but never inspect affected rows. Lines 129-134 commit when the transaction has no SQL error. Lines 51-53 also treat zero stock as allowable for every non-`made_to_order` item because the rejection condition includes `$stock > 0`. [app/Database/Migrations/2025-01-01-000003_CreateProductsTable.php](../app/Database/Migrations/2025-01-01-000003_CreateProductsTable.php) lines 22-24 defines `stock=0` with a distinct `stock_status`; no unlimited-inventory flag exists.
- **Issue:** Two checkouts can read the same stock. One conditional update then affects zero rows after another succeeds, but its order/items/payment record still commit. A zero-stock `in_stock` product can also be ordered because the precheck bypasses it.
- **Impact:** Oversold items, order records without matching inventory reduction, and manual fulfillment disputes.
- **Reproduction or failure scenario:** Start concurrent orders for the last unit. Both pass the initial read; only one decrement matches `stock >= quantity`; the other still inserts/commits the order. Separately, set an `in_stock` non-made-to-order SKU to stock zero and add it to cart: quantity is not rejected by the shown checks.
- **Root cause:** Read-then-write inventory logic lacks a checked atomic reservation outcome and mixes zero-stock semantics with made-to-order semantics.
- **Recommended remediation:** Define stock semantics explicitly. Use a single conditional update/reservation per line inside a transaction and abort/roll back on affected-row count other than one; lock or reserve inventory before order creation; test product and variant paths concurrently.
- **Verification criteria:** Parallel attempts for final inventory yield at most one accepted order/reservation; zero inventory is rejected unless an explicit unlimited/made-to-order policy applies; failed reservations leave no order, coupon use, or payment row.
- **Dependencies:** F-006.

### F-004 — Coupon dates, per-user limits, global limits, and concurrency are not enforced at checkout

- **Category:** Business Logic / Database
- **Severity:** HIGH
- **Priority:** P1
- **Confidence:** Confirmed
- **Affected component:** Cart and order coupon handling
- **Evidence:** [app/Services/CartService.php](../app/Services/CartService.php) lines 296-315 checks dates and global count only when applying a code. [app/Services/OrderService.php](../app/Services/OrderService.php) lines 168-188 re-fetches by active status and minimum subtotal but does not check `starts_at`, `expires_at`, `usage_limit`, `used_count`, or `usage_limit_user`; lines 112-118 increment `used_count` unconditionally and insert usage. [CouponModel.php](../app/Models/CouponModel.php) lines 19-24 filters only code/status. The schema stores these limits at [CreateCommerceTables.php](../app/Database/Migrations/2025-01-01-000005_CreateCommerceTables.php) lines 68-104.
- **Issue:** Coupon qualification can change between cart and checkout without revalidation, per-user usage is never checked, and the counter increment has no limit predicate or affected-row check.
- **Impact:** Expired/not-yet-valid, exhausted, or single-use promotions may be applied; simultaneous checkouts can exceed campaign limits.
- **Reproduction or failure scenario:** Apply a valid code, then let it expire or exhaust it before `createFromCart`; the checkout verification shown still returns its discount. Place two checkouts at the final usage slot; both can increment after an unchecked prior read.
- **Root cause:** Cart validation is incorrectly relied upon as a final authorization, and coupon consumption is not a conditional atomic operation.
- **Recommended remediation:** Revalidate every eligibility condition at checkout; for signed-in users count/enforce `coupon_usages` against `usage_limit_user`; use `UPDATE ... WHERE used_count < usage_limit` (or a locked reservation) and require one affected row; decide how guest per-customer limits are identified and preserve reconciliation records.
- **Verification criteria:** Invalid date, exhausted global/per-user limit, cancellation/retry, and concurrent last-slot tests fail safely without discount or counter drift.
- **Dependencies:** F-006.

### F-005 — Order lifecycle permits arbitrary transitions and has no cancellation/refund/inventory-recovery implementation

- **Category:** Business Logic / Missing Feature
- **Severity:** HIGH
- **Priority:** P1
- **Confidence:** Confirmed
- **Affected component:** Admin order management and returns/refunds
- **Evidence:** [app/Controllers/Admin/Orders.php](../app/Controllers/Admin/Orders.php) lines 70-80 accepts any listed status for any existing order. [app/Services/OrderService.php](../app/Services/OrderService.php) lines 152-162 simply updates status and records history. No controller/service invokes a payment refund, validates a refund amount, restores inventory, or prevents invalid terminal transitions. The customer-facing policy promises cancellation/refunds at [app/Views/frontend/return_and_refunds.php](../app/Views/frontend/return_and_refunds.php) lines 19-38, but routes expose neither a customer cancellation nor return request.
- **Issue:** Status is a free-form administrative label rather than an enforced state machine. Financial and inventory side effects are absent.
- **Impact:** Shipped/delivered orders can be marked cancelled, refunded orders can lack a payment refund, and inventory can remain decremented or be restored inconsistently if future code adds ad-hoc handling.
- **Reproduction or failure scenario:** An administrator can submit `cancelled` for a shipped order or move any order backwards/forwards because only membership in a list is checked. No code then changes payment or stock.
- **Root cause:** Lifecycle rules and side effects are not modeled centrally.
- **Recommended remediation:** Create explicit allowed transitions keyed by current order/payment/fulfillment state; implement authorized customer cancellation/return request flows; execute one-time stock restoration and provider refund records in audited transactions; separate return/refund status from fulfillment status.
- **Verification criteria:** Invalid transitions are rejected; cancellation/refund eligibility and amounts are enforced; each successful cancellation/refund produces one auditable external/payment action and exactly one applicable stock adjustment.
- **Dependencies:** F-002, F-003.

### F-006 — Checkout submissions have no server-side idempotency

- **Category:** Business Logic / Reliability
- **Severity:** HIGH
- **Priority:** P1
- **Confidence:** Confirmed
- **Affected component:** `POST /checkout/place`
- **Evidence:** [app/Controllers/Checkout/Checkout.php](../app/Controllers/Checkout/Checkout.php) lines 46-118 creates an order for every accepted POST. [app/Services/OrderService.php](../app/Services/OrderService.php) lines 65-127 generates a new random order number, order, items, stock decrement, coupon usage, and payment record. No idempotency key, request fingerprint, session order lock, or unique business key is stored. The UI only disables the button in [app/Views/checkout/index.php](../app/Views/checkout/index.php) lines 132-136.
- **Issue:** The client-side disabled button is bypassable by refresh, retry, duplicate tabs, network ambiguity, or crafted parallel requests.
- **Impact:** Duplicate orders, stock decrements, coupon consumption, and gateway intents; the customer may be charged or contacted inconsistently.
- **Reproduction or failure scenario:** Send two valid POSTs with the same session/cart before cart clearing. Each call can create a separate order because the server has no duplicate detection.
- **Root cause:** There is no persisted idempotency contract around order creation.
- **Recommended remediation:** Require a cryptographically random idempotency key generated for the checkout attempt and persist it under a unique constraint with request/order state; atomically return the existing result on retries; lock/reserve the cart during creation; define recovery for an order created before gateway intent creation fails.
- **Verification criteria:** Repeated/parallel identical checkout requests return the same order and create one inventory/coupon/payment effect; a changed cart/address/payment method requires an explicit new key.
- **Dependencies:** F-003, F-004.

### F-007 — Password recovery is a nonfunctional stub and login has no visible brute-force control

- **Category:** Security / Missing Feature
- **Severity:** MEDIUM
- **Priority:** P1
- **Confidence:** Confirmed
- **Affected component:** Authentication
- **Evidence:** [app/Controllers/Auth/Auth.php](../app/Controllers/Auth/Auth.php) lines 94-105 always presents a neutral success message but never creates a reset token or sends mail. Routes at [Routes.php](../app/Config/Routes.php) lines 65-71 expose no reset/confirm route. The authentication flow at Auth lines 21-48 performs password verification with no rate limiter, delay, lockout, or MFA. Repository search found no rate-limit/throttle implementation in `app`.
- **Issue:** Users cannot recover accounts, while password guessing can be attempted repeatedly.
- **Impact:** Account lockout/support burden and elevated credential-stuffing/brute-force risk, especially combined with F-001.
- **Reproduction or failure scenario:** Submit a valid registered email to forgot-password; the response claims instructions were sent but no state or delivery code exists. Repeated login attempts follow the same code path without a throttle.
- **Root cause:** Recovery and abuse controls were left as placeholders.
- **Recommended remediation:** Implement expiring, single-use, hashed reset tokens and configured email delivery; add per-account and per-IP adaptive throttling with monitoring; optionally add MFA for administrators; regenerate session ID at successful login explicitly.
- **Verification criteria:** A reset is deliverable and usable once within expiry without account enumeration; rapid failed attempts are throttled; successful login resets relevant counters and rotates session state.
- **Dependencies:** F-001.

### F-008 — Checkout has no usable payment method unless Razorpay is configured, contrary to the documented COD fallback

- **Category:** Functional Bug
- **Severity:** HIGH
- **Priority:** P1
- **Confidence:** Confirmed
- **Affected component:** Payment availability / checkout
- **Evidence:** [app/Libraries/Payment/PaymentManager.php](../app/Libraries/Payment/PaymentManager.php) lines 14-28 adds Razorpay only when configured and comments out the COD service. [Checkout.php](../app/Controllers/Checkout/Checkout.php) lines 62-65 rejects a posted method not present in this manager. [README.md](../README.md) lines 81-84 and 93-95 state that COD works out of the box.
- **Issue:** A default install with no Razorpay credentials offers an empty method set and rejects checkout placement.
- **Impact:** The primary purchase flow is unavailable in the documented setup.
- **Reproduction or failure scenario:** Follow the README without payment keys, open checkout, and submit any payment method; `PaymentManager::get()` returns null and placement fails.
- **Root cause:** COD was disabled in source without changing the product contract or introducing another fallback.
- **Recommended remediation:** Enable and test COD with explicit eligibility rules, or block checkout clearly until an online gateway is configured and correct the deployment documentation. Add a startup/readiness check for at least one enabled method.
- **Verification criteria:** A clean configured environment exposes at least one supported method and completes an authorized end-to-end test order without bypassing payment rules.
- **Dependencies:** F-002.

### F-009 — Production hardening depends on unset environment overrides; HTTPS and CSP are off by default

- **Category:** Operations / Security
- **Severity:** MEDIUM
- **Priority:** P1
- **Confidence:** High
- **Affected component:** Global web configuration
- **Evidence:** [app/Config/App.php](../app/Config/App.php) lines 19, 136, 160, and 201 set a localhost HTTP base URL, UTC timezone, `forceGlobalSecureRequests=false`, and `CSPEnabled=false`. [app/Config/Filters.php](../app/Config/Filters.php) lines 54-63 installs `forcehttps`, page cache, performance metrics, and debug toolbar filters globally; the first only enforces HTTPS when the App setting is true. [app/Config/Database.php](../app/Config/Database.php) lines 27-52 has `DBDebug=true` and `encrypt=false` by default. `env` is a commented template, not evidence of production overrides.
- **Issue:** Secure transport and CSP rely entirely on undocumented deployment correctness, while defaults are development-oriented. No deployment/IaC/reverse-proxy configuration or monitoring integration is present to verify compensating controls.
- **Impact:** A misconfigured deployment can expose login/checkout sessions over HTTP, weaken XSS containment, leak database errors, or use unencrypted remote DB transport.
- **Reproduction or failure scenario:** Deploy using defaults or omit the listed environment overrides; the application does not force HTTPS or emit a CSP.
- **Root cause:** Production controls are documented as manual steps rather than enforced/validated configuration.
- **Recommended remediation:** Provide production configuration templates and deployment validation that require HTTPS, correct proxy trust, production environment, debug off, secure cookies, and an intentional CSP compatible with Razorpay; encrypt database transport where applicable; add health/monitoring and backup/restore procedures.
- **Verification criteria:** A staging deployment redirects HTTP to HTTPS, sets HSTS after TLS validation, omits debug output/tooling, emits tested security headers/CSP, and rejects unsafe production startup configuration.
- **Dependencies:** None.

### F-010 — Razorpay secret is stored in plaintext application settings

- **Category:** Security / Architecture
- **Severity:** MEDIUM
- **Priority:** P2
- **Confidence:** Confirmed
- **Affected component:** Razorpay configuration
- **Evidence:** [app/Controllers/Admin/Settings.php](../app/Controllers/Admin/Settings.php) lines 10-18 includes `razorpay_key_secret` in editable fields and lines 28-35 persists submitted values through `SettingModel`. [CreateContentTables.php](../app/Database/Migrations/2025-01-01-000007_CreateContentTables.php) lines 131-143 defines the `settings.value` text column; [RazorpayPaymentService.php](../app/Libraries/Payment/RazorpayPaymentService.php) lines 17-21 reads it as the gateway secret.
- **Issue:** The payment secret is treated as ordinary CMS configuration in the application database instead of a deployment secret.
- **Impact:** Database backups, SQL-console access, or broad administrative access can disclose the secret and permit forged gateway signatures.
- **Reproduction or failure scenario:** An authorized database reader can retrieve the settings record; no encryption-at-rest application layer or external secret reference is used.
- **Root cause:** Secret management is coupled to editable settings.
- **Recommended remediation:** Store payment secrets only in a managed secret store/environment injection; remove the editable secret UI or make it write a secure external reference; rotate current keys; never log full gateway bodies.
- **Verification criteria:** No database/settings export contains a live gateway secret; gateway credentials rotate without source/database migration; authorized runtime can use the secret while the admin UI cannot display it.
- **Dependencies:** F-002.

### F-011 — Admin video uploads lack content-signature, dimension/duration, and processing safeguards

- **Category:** Security / Operations
- **Severity:** LOW
- **Priority:** P3
- **Confidence:** High
- **Affected component:** Product customer-media upload
- **Evidence:** [app/Controllers/Admin/Products.php](../app/Controllers/Admin/Products.php) lines 388-432 routes any `video/*` MIME to `storeUserVideo`; lines 480-504 only apply a 64 MB size, MIME, and extension allowlist before placing the original file in public uploads. Unlike image uploads at [ImageUploadService.php](../app/Services/ImageUploadService.php) lines 30-82, videos are not signature-inspected, transcoded, duration-limited, scanned, or given a safe poster.
- **Issue:** Large or malformed media reaches public storage without robust validation or resource controls.
- **Impact:** Storage/bandwidth pressure and browser/media-parser reliability risk; impact increases if the admin account is compromised (F-001).
- **Reproduction or failure scenario:** An administrator uploads a 64 MB file labeled as an allowed video type; the original is publicly served with no codec/duration policy.
- **Root cause:** Video handling bypasses the hardened image pipeline.
- **Recommended remediation:** Use a dedicated private upload/quarantine pipeline, inspect container signatures with a media parser, constrain duration/codecs/resolution, transcode asynchronously, generate poster images, and serve approved assets through controlled delivery.
- **Verification criteria:** Malformed/oversized/disallowed-codec samples are rejected; accepted videos are processed before public availability and cannot exhaust synchronous web workers.
- **Dependencies:** F-001.

### F-012 — Cart drawer interpolates server data into HTML without escaping

- **Category:** Security / Frontend
- **Severity:** MEDIUM
- **Priority:** P2
- **Confidence:** Confirmed
- **Affected component:** Cart drawer JavaScript
- **Evidence:** [public/assets/js/cart.js](../public/assets/js/cart.js) lines 5-24 interpolates `it.url`, `it.image`, `it.name`, and `it.variant` into an HTML template; line 45 assigns the combined result with `innerHTML`. These values originate from product/variant records in [app/Services/CartService.php](../app/Services/CartService.php) lines 236-250. In contrast, server views generally use `esc()`.
- **Issue:** A product/variant value containing HTML can execute/render in every shopper's cart drawer.
- **Impact:** Stored XSS against shoppers if an administrator account, database import, or future seller/CMS pathway inserts malicious catalog content.
- **Reproduction or failure scenario:** Store a product name containing an image tag with an event handler, add that product, and open the AJAX drawer; `innerHTML` parses the supplied string.
- **Root cause:** DOM construction uses string interpolation for untrusted data.
- **Recommended remediation:** Create DOM nodes and assign `textContent`/properties, or rigorously encode all interpolated attribute/text values with context-specific escaping; validate catalog fields; enable a tested CSP as defense in depth.
- **Verification criteria:** Payloads in every product/variant/media field render literally and do not create elements, event handlers, or JavaScript URLs.
- **Dependencies:** F-001, F-009.

### F-013 — Automated test coverage does not cover the commerce and security-critical flows

- **Category:** Testing / Architecture
- **Severity:** MEDIUM
- **Priority:** P2
- **Confidence:** Confirmed
- **Affected component:** Test suite
- **Evidence:** The three committed tests are framework examples: [tests/unit/HealthTest.php](../tests/unit/HealthTest.php), [tests/session/ExampleSessionTest.php](../tests/session/ExampleSessionTest.php), and [tests/database/ExampleDatabaseTest.php](../tests/database/ExampleDatabaseTest.php). No tests target cart, checkout, authorization, payment, coupons, stock, uploads, or order transitions. [phpunit.dist.xml](../phpunit.dist.xml) line 4 requires PHPUnit under vendor, but `vendor/bin/phpunit` was absent during this audit.
- **Issue:** Release-critical behavior lacks regression protection and the suite is currently not executable from the supplied dependency tree.
- **Impact:** The identified financial/security defects can recur unnoticed; safe change confidence is low.
- **Reproduction or failure scenario:** `php vendor/bin/phpunit --testdox` exits with “Could not open input file: vendor/bin/phpunit.”
- **Root cause:** Starter tests were retained without application test implementation and dev dependencies are incomplete locally.
- **Recommended remediation:** Restore locked dev dependencies in CI; add isolated MySQL-compatible integration tests and test-mode gateway fakes for all F-002–F-008 cases; add concurrency and authorization tests; make test execution a release gate.
- **Verification criteria:** CI installs dependencies reproducibly and executes tests for duplicate checkout, stock/coupon races, payment mismatch/replay, status transitions, guest/account ownership, uploads, and auth recovery/throttling.
- **Dependencies:** None.

### F-014 — Customers receive no implemented transactional notifications and cannot submit return/cancellation requests

- **Category:** Missing Feature
- **Severity:** LOW
- **Priority:** P2
- **Confidence:** Confirmed
- **Affected component:** Post-purchase customer service
- **Evidence:** Repository search found no use of CodeIgniter email sending, notification service, queue, scheduler, webhook consumer, customer cancellation route, or return-request route. The only password-reset implementation is a stub (F-007). Existing account routes in [Routes.php](../app/Config/Routes.php) lines 76-84 cover display/profile/addresses only; checkout routes lines 54-60 have no cancellation/return endpoint.
- **Issue:** Orders, payments, shipment updates, cancellations, and returns have no customer notification or self-service/request channel despite displayed policy content.
- **Impact:** Customers lack receipts/status updates and staff must handle disputes manually; failed or pending payments are harder to reconcile.
- **Reproduction or failure scenario:** Place/order-status-update paths contain no email/notification call, and account UI offers no cancellation/return operation.
- **Root cause:** Operational customer-service workflows are not implemented.
- **Recommended remediation:** Define notification events and reliable outbox/queue delivery, provide an authenticated cancellation/return request process with staff review, and include delivery-failure observability. Do not send production messages until templates and consent rules are approved.
- **Verification criteria:** Test-mode orders and state changes produce idempotent, auditable notifications; eligible customers can submit requests and ineligible requests are explained without changing order state.
- **Dependencies:** F-005, F-013.

## 4. Confirmed design controls and non-findings

- Explicit routes have auto-routing disabled ([Routing.php](../app/Config/Routing.php) line 97), reducing unintended controller exposure.
- Account order detail filters by both order number and current user ([Account.php](../app/Controllers/Account/Account.php) lines 43-57); address update/delete verifies ownership (lines 69-116).
- Cart/order price totals are server-derived ([CartService.php](../app/Services/CartService.php) lines 193-265; [OrderService.php](../app/Services/OrderService.php) lines 38-63).
- Product image uploads use size, MIME, extension, image parsing, random names, and re-encoding checks ([ImageUploadService.php](../app/Services/ImageUploadService.php) lines 30-82).
- CSRF is global except for the payment verification endpoint ([Filters.php](../app/Config/Filters.php) lines 75-83); that exemption is only safe after F-002 is addressed.

## 5. Performance, operations, and maintainability observations

No measured latency claims are made. The app uses file sessions/cache ([Session.php](../app/Config/Session.php) lines 25-61; [Cache.php](../app/Config/Cache.php) lines 25-37), which needs shared storage before multi-instance deployment. No queue, worker, scheduled job, health endpoint, backup/recovery procedure, deployment rollback specification, or external monitoring is present in the repository. Several admin lists load all records (`Messages`, `Subscribers`, and `Pages` controllers) rather than paginate; this is a P3 scalability concern, not a launch blocker. The schema uses foreign keys and important unique keys, but `coupon_usages.order_id` has no foreign key and payment provider references are not unique, which amplifies F-002/F-004 reconciliation risk.

## 6. Missing-feature/product-gap classification

Launch blockers: usable payment configuration (F-008), lifecycle/refund rules (F-005), reliable payment/inventory/coupon correctness (F-002–F-006), and auth recovery/throttling (F-007). Important operational capabilities: notifications/return requests (F-014), monitoring/backups/shared state, and a working test gate (F-013). Growth improvements: CMS editing, paginated admin tooling, analytics, richer carrier integration, and reviewed video processing.

## 7. Unresolved questions

1. Has any deployed environment run `DatabaseSeeder`? If yes, F-001 requires immediate credential rotation and access-log review.
2. What is the intended `stock=0` meaning for `in_stock` products versus `made_to_order` items?
3. Is Razorpay configured in any environment, and is there an existing provider webhook/reconciliation service outside this repository?
4. Which system owns HTTPS termination, DB TLS, backups, WAF/rate limits, monitoring, and secret management?
5. Is COD intentionally unavailable? The implementation and README conflict.
6. What are the approved cancellation, return, tax, invoice, and notification policies?

## 8. Detailed remediation recommendations

Follow the risk-ordered plan in [REMEDIATION_ROADMAP.md](REMEDIATION_ROADMAP.md). Implement F-001 first, then take payment/order creation offline or behind a maintenance flag until F-002–F-006 are verified in an isolated test environment. Do not apply `npm audit fix --force` blindly: it proposes a Tailwind 4 breaking change. Treat the npm findings as build-pipeline dependency work, separate from the live checkout fixes.
