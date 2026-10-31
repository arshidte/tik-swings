# Code Review — Vulnerabilities, Bugs & Improvements

Investigation of the CodeIgniter 4 e-commerce codebase. An `audit/` directory already exists from a 2026-10-24 audit; this review independently verified those findings and adds new ones.

## 🔴 Security Vulnerabilities (High)

1. **Hard-coded published admin credentials** — [`ContentSeeder.php:20`](app/Database/Seeds/ContentSeeder.php:20) seeds `admin@swingandgrain.in` / `admin1234` (and `customer@example.com` / `password`). If seeded in production, full admin takeover. Rotate/remove before deploy.

2. **Payment verification not bound to the gateway order or amount** — [`Checkout.php:124-143`](app/Controllers/Checkout/Checkout.php:124) `verifyPayment()` only checks the HMAC signature of `order_id|payment_id`. It never verifies that the returned `razorpay_order_id` matches the gateway order created for THIS order, that the amount/currency match, or that the payment id is unused. Any valid Razorpay callback (even for a different purchase) can mark an arbitrary session-pending order as paid. Also CSRF-exempt ([`Filters.php:77`](app/Config/Filters.php:77)) with no webhook fallback.

3. **CSRF-exempt endpoint trusts client-supplied order number** — `verifyPayment()` falls back to `$payload['order_number']` when no session pending order exists; combined with #2 this widens the attack surface.

4. **No checkout idempotency** — `Checkout::place()` creates a new order on every submission; retries/double-clicks create duplicate orders and reserve stock repeatedly. `pending_order` session key is only removed on success/failure paths in one branch.

5. **Non-atomic stock & coupon decrement** — [`OrderService.php:105-118`](app/Services/OrderService.php:105) decrements with conditional `stock >= qty` updates but never checks affected row counts; the order is still inserted, coupon `used_count` incremented unconditionally, and `verifyCoupon()`/`applyCoupon()` check limits outside a transaction. Concurrent checkouts can oversell and exceed coupon usage limits. Rows affected = 0 should roll back the transaction.

6. **No rate limiting / brute-force protection** — [`Auth.php:21-48`](app/Controllers/Auth/Auth.php:21) login has no throttling or lockout. `sendReset()` ([`Auth.php:99`](app/Controllers/Auth/Auth.php:99)) is a stub — no actual reset flow exists, meaning users cannot reset passwords at all (functional bug too).

7. **No session regeneration on login** — `establishSession()` sets session data without `session()->regenerate()`, leaving session-fixation risk. Logout does `destroy()` correctly, though.

8. **Video upload MIME spoofing unverified** — [`Products.php:481-505`](app/Controllers/Admin/Products.php:481) accepts client-reported `getMimeType()` and extension only; no content inspection (e.g., `finfo` on actual bytes). Admin-only, so lower severity, but a compromised admin session could plant a polyglot file. Uploaded media also stored under web-served `public/uploads` — consider serving with `Content-Disposition` / separate origin.

## 🟠 Bugs

9. **Stock allowed to go negative for `made_to_order`/zero-stock logic is inconsistent** — [`OrderService.php:51`](app/Services/OrderService.php:51): if `$stock === 0` and stock_status is `in_stock`, the guard `$stock > 0 && $qty > $stock` skips validation entirely — a 0-stock in-stock product can be ordered with any quantity. Similarly [`CartService.php:84`](app/Services/CartService.php:84) `if ($stock > 0 ...)` means zero-stock items are addable without limit.

10. **Guest cart merge can exceed stock** — [`CartService.php:355-373`](app/Services/CartService.php:355) `mergeGuestIntoUser()` sums quantities without a stock cap (unlike `add()`), and `$l['variant_id']` null vs `0` matching can double-count lines.

11. **Variant rebuild destroys history** — [`Products.php:312-319`](app/Controllers/Admin/Products.php:312) deletes all variants on each save; `order_items.variant_id` then dangles (no FK cleanup or soft-delete), breaking historical order display.

12. **`markPaid()` runs outside a transaction and unconditionally overwrites** — [`OrderService.php:137-144`](app/Services/OrderService.php:137) updates the PaymentModel row for the order without checking current status — replayed callbacks overwrite `provider_ref`/`payload`; also no transition state machine (any admin status change is accepted, no refund/cancellation/restock flow).

13. **`updateStatus()` accepts arbitrary status strings** — [`OrderService.php:152`](app/Services/OrderService.php:152) no whitelist; admin form input goes straight to the DB and history.

14. **Auth redirect open-redirect risk** — [`Auth.php:44`](app/Controllers/Auth/Auth.php:44) uses `session()->get('redirect_url')` (set from `current_url()` in filters) without validating it's a relative/local URL. Low risk since set server-side, but worth hardening.

15. **`uid()` on guest returns 0, used in queries** — [`Checkout.php:39`](app/Controllers/Checkout/Checkout.php:39) passes `$this->uid()` (0) to `AddressModel->forUser()` and `UserModel->find()` instead of `uidOrNull()` — harmless but sloppy; `index()` also leaks a `user` array of `[]`.

16. **Slug uniqueness race** — [`Products.php:130-136`](app/Controllers/Admin/Products.php:130) check-then-insert without a DB unique constraint handling; two concurrent saves produce duplicate slugs (unless the DB enforces it and insert fails ungracefully).

17. **`transStart`/`transComplete` with exceptions** — [`OrderService.php:33-132`](app/Services/OrderService.php:33) throws inside the transaction; CI4 `transStart` does handle rollback on failure status, but exceptions thrown mid-transaction skip `transComplete()` unless using `transException`/try-finally — verify rollback actually occurs on the thrown `RuntimeException` paths.

18. **Search `like()` on raw input** — `Admin/Products.php:32` uses `like('name', $q)` — parameterized (safe from injection) but leading wildcard `%` not escaped, so `%`/`_` in user input distort results (minor).

## 🟡 Improvements

19. **Testing is effectively absent** — `tests/` contains only CI4 scaffold examples. No tests for CartService, OrderService, payment verification — the most business-critical, money-handling code.

20. **Password policy weak** — min 8 chars, no strength checks; consider `min_length[10]` or password strength rules; also no email verification flow despite `email_verified_at` column.

21. **Error handling** — controllers catch only `\RuntimeException`; a TypeError/DB exception in checkout shows a generic 500 with cart intact but order possibly half-created. Prefer try/catch around the whole transaction with logging.

22. **`verifyCoupon()` duplicated** — logic exists in both [`CartService.php:328`](app/Services/CartService.php:328) and [`OrderService.php:168`](app/Services/OrderService.php:168); extract to a shared CouponService to avoid drift.

23. **N+1 / repeated model instantiation** — `CartService::variantLabel()` calls `forProduct()` per line; `OrderService`/`Checkout` instantiate many models per call. Consider DI or caching.

24. **Settings via `setting()` in hot paths** — `shippingFor()` queries settings on every snapshot; cache per-request.

25. **`logout` via GET** — [`Routes.php:71`](app/Config/Routes.php:71) logout on GET is CSRF/logout-CSRF prone (email/img tag can log a user out); move to POST with CSRF token.

26. **No security headers tuning / CSP** — `ContentSecurityPolicy.php` exists but `CSPEnabled` defaults false; secureheaders filter is on (good), but add CSP for defense-in-depth.

27. **Missing pagination/limit hardening on storefront search & filters** — verify `Shop::filter` caps page size to prevent large result-set DoS.

28. **Logging** — Razorpay service logs full response bodies; ensure secrets/PII (emails, phone) aren't logged in `payload` blobs (`markPaid` stores raw callback payload).

## Priority remediation order

1. Remove/rotate seeded admin credentials (#1)
2. Bind payment verification to gateway order + amount + one-time payment id; add webhook (#2, #3, #12)
3. Transactional stock/coupon enforcement with affected-row checks (#5, #9)
4. Checkout idempotency key (#4)
5. Session regeneration on login + login rate limiting (#6, #7)
6. Real password-reset flow (#6)
7. Status whitelist + refund/cancel state machine (#12, #13)
8. Tests for Order/Cart/payment paths (#19)
