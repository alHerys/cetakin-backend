# CLAUDE.md — On-Demand Print Service Backend

Laravel 11 API for an on-demand print and stationery (ATK) ordering platform. Partners (print shops) register, get approved by admin, then serve customers who place print or ATK orders through a Flutter mobile app.

---

## Stack

| Layer | Technology |
|---|---|
| Language | PHP 8.2 |
| Framework | Laravel 13 |
| Auth | tymon/jwt-auth |
| Database | PostgreSQL 16 |
| File Storage | Cloudinary |
| Containerization | Docker + Docker Compose |
| CI/CD | Railway (auto-deploy on push to `main`) |
| Testing | Pest |

---

## Project Status

**Greenfield.** Nothing is built yet. Follow the phase order in `plan.md` exactly.

---

## Domain Glossary

- **ATK** — Alat Tulis Kantor (Indonesian: stationery/office supplies). ATK orders are product orders from a shop's catalog, distinct from print orders.
- **Partner** — a print shop owner. Must register and be approved by admin before they can operate.
- **User** — a customer placing print or ATK orders.
- **Admin** — a superuser who approves or rejects partner registrations.

---

## Architecture Rules

### Service Layer
Every business operation goes through a Service class. Controllers are thin — they validate input (via FormRequest), call a Service method, and return a response. Never put business logic in controllers or models.

```
Request → FormRequest (validation) → Controller → Service → Model → Response
```

### API Response Envelope
All responses use the `ApiResponse` trait. The JSON shape is always:

```json
{ "status": "success|error", "message": "...", "data": {} }
```

Paginated responses use `paginated()` which adds `meta.pagination`.

### Migrations
Convert `schema.sql` into Laravel migration files — do **not** run schema.sql directly. Migration order must match the FK dependency chain defined in `plan.md` (Phase 1.1).

### No Local File Storage
All uploads go to Cloudinary via `CloudinaryService`. Never write files to disk.

### DB Transactions
Any operation that writes to more than one table must be wrapped in `DB::transaction()`. This includes: order creation (print + ATK), stock decrement, shop creation at partner registration.

---

## Roles & Middleware

Three roles: `user`, `partner`, `admin`. Stored in `users.role`.

| Middleware | Alias | Purpose |
|---|---|---|
| `RoleMiddleware` | `role:X` | Checks `auth()->user()->role` matches allowed value(s) |
| `PartnerApprovedMiddleware` | `partner.approved` | Ensures `shop->status === 'approved'`; returns 403 otherwise |

`partner.approved` must gate **all** partner operational routes (shop management, order management). A pending or rejected partner must never be able to accept orders.

---

## Order Status Transitions

### Print Orders & ATK Orders (same flow)

Valid partner-driven transitions (linear, no skipping):
```
pending → confirmed → processing → ready_for_pickup → completed
```

**Cancellation:** A user can cancel their own order, but only from `pending`. Add `cancelled` to the status enum. No other cancel path exists.

Enforce these transitions server-side even if the client enforces them too. Invalid transitions return `422`.

Status history is auto-logged by DB triggers — do not duplicate this logic in PHP.

---

## File Uploads (Print Orders)

- Accepted type: PDF only (`application/pdf`)
- Max size: 10MB
- Validate at the `FormRequest` level using Laravel's `mimes:pdf|max:10240`
- `total_pages` is submitted by the user in the request payload — do not attempt server-side PDF parsing

---

## Email

Email is **out of scope**. Admin approve/reject actions update `shops.status` (and `rejection_reason` on reject) in the DB only. Do not implement Mailable classes or mail configuration.

---

## Testing (Pest)

Write feature tests for every endpoint. Use `RefreshDatabase`. Test:
- Happy path
- Auth guard (unauthenticated → 401)
- Role guard (wrong role → 403)
- Validation errors (→ 422)
- Business rule violations (invalid status transition, insufficient stock, duplicate review)

Do not mock the database in feature tests — use a real test DB connection.

---

## Key Constraints & Non-Obvious Decisions

- **UUIDs everywhere.** All PKs are `UUID` using `gen_random_uuid()`. Use `HasUuids` trait on all models.
- **Prices are integers (cents/smallest unit).** Never use floats for money. `final_price`, `unit_price`, `subtotal` are all `INTEGER`.
- **ATK stock is decremented on order creation**, inside the same DB transaction. If any item has insufficient stock, the entire order must fail.
- **One review per order.** Enforced by DB unique constraints (`unique_print_order_review`, `unique_atk_order_review`). Check at service level too and return `422` on duplicate.
- **Reviews only on completed orders.** A review submitted against a non-completed order returns `422`.
- **Shop rating is updated by a DB trigger** (`trg_update_shop_rating`) on every `INSERT` into `reviews`. Do not recalculate in PHP.
- **Discovery is role-gated to `user`.** Partners and admins do not use the discovery endpoints.
- **`partner.approved` middleware is separate from `role:partner`.** Both must be applied to partner operational routes.
- **JWT guard is `auth:api`** — every protected route uses this guard, not `auth`.
- **`cancelled` status** must be added to both `print_orders.status` and `atk_orders.status` CHECK constraints in migrations (not in schema.sql, which does not include it).

---

## Environment Variables Required

```
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=
JWT_SECRET=
JWT_TTL=1440
JWT_REFRESH_TTL=20160
CLOUDINARY_URL=cloudinary://api_key:api_secret@cloud_name
```

---

## Commits

Never run `git commit` yourself. At the end of each logical commit point, provide the exact command for the user to run and wait. Example:

```bash
git add <files>
git commit -m "feat: initialize Laravel 13 project"
```

The user will cross-check the work before committing.

---

## Development Phase Order

Follow `plan.md`. Do not skip phases or implement features ahead of their phase. Each phase has a checklist — treat it as the acceptance criteria before moving on.

| Phase | Scope |
|---|---|
| 0 | Docker, Laravel init, JWT, Cloudinary, Railway |
| 1 | Migrations + models + `ApiResponse` trait |
| 2 | Auth (register user, register partner, login, logout, me) |
| 3 | Admin approval (list partners, approve, reject — DB only, no email) |
| 4 | Partner shop management + ATK catalog |
| 5 | Discovery (Haversine search, filters, shop detail) |
| 6 | Print orders (user place + partner manage) |
| 7 | ATK orders (user place + partner manage) |
| 8 | Reviews + rating trigger verification |
| 9 | Global exception handler, rate limiting, final checks |
