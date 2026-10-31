# Feature Completeness Matrix

| Feature | Status | Evidence | Gap or Risk | Priority |
|---|---|---|---|---|
| Customer registration and login | Implemented but incomplete | `Auth` register/login and session filters | No throttling; reset flow is a stub (F-007); seeded admin risk (F-001) | P0/P1 |
| Product discovery | Implemented and verified | Shop/category/search routes and `ProductModel` active queries | Search result cap is fixed; no search indexing | P3 |
| Product detail pages | Implemented and verified | `Frontend\\Product::show`, images, variants, reviews | Variant availability is client-presented; checkout remains authority | P2 |
| Search and filtering | Implemented and verified | `Shop::query`, whitelisted sort, parameterized builder usage | No full-text/search-service scalability plan | P3 |
| Product variants | Implemented but incomplete | Variant schema/admin editor/PDP resolver | Editing rebuilds variants; inventory and cart transition tests absent | P2 |
| Cart | Implemented but incomplete | `CartService`, cart routes, guest/account support | Client drawer unsafe interpolation (F-012); zero stock semantics unclear | P2 |
| Checkout | Implemented but incomplete | `Checkout::place`, server pricing | No idempotency (F-006) | P1 |
| Payment processing | Implemented but incomplete | Razorpay service and checkout handoff | Verification/reconciliation flaw (F-002); COD disabled despite docs (F-008) | P1 |
| Order confirmation | Implemented but incomplete | Session/account ownership checks and confirmation view | Pending payment can be viewed; no customer notification | P2 |
| Order history | Implemented and verified | Account order queries filter by current user | Guest orders only session-visible until account claim | P2 |
| Shipping and tracking | Implemented but incomplete | Admin shipment entry, account tracking link | Manual status/links only; no carrier synchronization or lifecycle guard | P2 |
| Cancellation | Missing | Policy page only; no customer route/action | No eligibility enforcement or stock/payment workflow (F-005) | P1 |
| Returns and refunds | Missing | Policy page only; no return/refund provider code | No request, approval, refund amount, or inventory restoration workflow (F-005) | P1 |
| Coupons | Implemented but incomplete | Cart/apply and order coupon paths | Final eligibility/per-user/concurrency checks absent (F-004) | P1 |
| Reviews and ratings | Implemented but incomplete | Approved review display/admin moderation | No customer review-submission route found | P3 |
| Wishlist | Implemented and verified | Guest session and user-backed `WishlistModel` | Product existence is not rechecked on toggle | P3 |
| Customer notifications | Missing | No mail/queue/notification sender usage found | No receipts, payment, shipment, or support notifications (F-014) | P2 |
| Admin product management | Implemented but incomplete | Admin product/category/media routes | Variant rebuild and media ingestion need hardening/testing | P2/P3 |
| Admin inventory management | Implemented but incomplete | Product/variant stock editing | No reservation/audit/multi-location controls; oversell flaw (F-003) | P1 |
| Admin order management | Implemented but incomplete | Admin order/shipment/status controllers | Arbitrary transition updates (F-005) | P1 |
| Customer support | Implemented but incomplete | Contact messages and help/policy pages | No request workflow, replies, or notifications | P2 |
| Privacy and account deletion | Missing | Account routes cover profile/addresses only | No data export/deletion or retention workflow located | P3 |
| Accessibility | Implemented but incomplete | Skip link, labels, aria-live, accordions in views | No automated/manual a11y test evidence | P3 |
| SEO | Implemented and verified | Metadata, JSON-LD, sitemap/robots routes | Production canonical/base URL must be configured | P2 |
| Analytics | Missing | No analytics integration found | Optional until measurement requirements are defined | P4 |
| Monitoring and operations | Unable to verify | Local file logger/performance filter only | No external monitoring, health, backup, or runbook configuration | P2 |
