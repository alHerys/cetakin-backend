# Cetakin Backend

REST API for **Cetakin** — an on-demand print and stationery (ATK) ordering platform. Print shop partners register and are approved by an admin, then customers can discover nearby shops, place print orders (PDF upload), and buy stationery products through a mobile app.

---

## Table of Contents

- [Stack](#stack)
- [Architecture](#architecture)
- [Getting Started](#getting-started)
- [Environment Variables](#environment-variables)
- [API Overview](#api-overview)
- [Authentication](#authentication)
- [Order Status Flow](#order-status-flow)
- [Running Tests](#running-tests)
- [API Documentation](#api-documentation)

---

## Stack

| Layer | Technology |
|---|---|
| Language | PHP 8.3 |
| Framework | Laravel 13 |
| Authentication | `tymon/jwt-auth` |
| Database | PostgreSQL 16 |
| File Storage | Cloudinary (`cloudinary/cloudinary_php`) |
| Containerization | Docker + Docker Compose |
| Testing | Pest |
| API Docs | Scramble (OpenAPI 3.1) |

---

## Architecture

```
Request → FormRequest (validation) → Controller → Service → Model → Response
```

- **Controllers** are thin — they validate input and delegate to a Service.
- **Services** contain all business logic.
- **DB triggers** handle status history logging and shop rating recalculation automatically.
- All responses follow a consistent JSON envelope:

```json
{
  "status": "success | error",
  "message": "Human-readable message.",
  "data": {}
}
```

Paginated responses include a `meta.pagination` object with `total`, `per_page`, `current_page`, and `last_page`.

---

## Getting Started

### Prerequisites

- Docker & Docker Compose
- A [Cloudinary](https://cloudinary.com) account (free tier is sufficient)

### 1. Clone and configure

```bash
git clone https://github.com/crtal7/cetakin-backend.git
cd cetakin-backend
cp .env.example .env   # then fill in the required values (see below)
```

### 2. Start the containers

```bash
docker compose up -d --build
```

This starts two containers:
- `app` — Laravel on port `8000`
- `db` — PostgreSQL 16

### 3. Run migrations and seed

```bash
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed   # creates the admin account
```

### 4. Verify

Check that the API and database are up:

```bash
curl http://localhost:8000/api/v1/health
```

Expected response:

```json
{
  "status": "ok",
  "services": {
    "database": "ok"
  }
}
```

Then verify authentication:

```bash
curl http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"admin@printapp.com","password":"admin123"}'
```

---

## Environment Variables

| Variable | Description |
|---|---|
| `APP_KEY` | Laravel application key — generate with `php artisan key:generate` |
| `DB_DATABASE` | PostgreSQL database name |
| `DB_USERNAME` | PostgreSQL username |
| `DB_PASSWORD` | PostgreSQL password |
| `JWT_SECRET` | JWT signing secret — generate with `php artisan jwt:secret` |
| `JWT_TTL` | Access token lifetime in minutes (default: `1440`) |
| `JWT_REFRESH_TTL` | Refresh token lifetime in minutes (default: `20160`) |
| `CLOUDINARY_URL` | Full Cloudinary URL: `cloudinary://api_key:api_secret@cloud_name` |

---

## API Overview

All endpoints are prefixed with `/api/v1`. Protected endpoints require a `Bearer` token in the `Authorization` header and `Accept: application/json`.

### Authentication

| Method | Endpoint | Access | Description |
|---|---|---|---|
| `POST` | `/auth/register` | Public | Register a customer account |
| `POST` | `/auth/register/partner` | Public | Register a partner (print shop) account |
| `POST` | `/auth/login` | Public | Log in and receive a JWT |
| `POST` | `/auth/logout` | Auth | Invalidate the current token |
| `GET` | `/auth/me` | Auth | Get the authenticated user's profile |

### Admin

> Requires role: `admin`

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/admin/partners` | List all partner registrations (filterable by `?status=`) |
| `PATCH` | `/admin/partners/{id}/approve` | Approve a pending partner |
| `PATCH` | `/admin/partners/{id}/reject` | Reject a pending partner (requires `reason`) |

### Shop Discovery

> Requires role: `user`

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/shops` | Discover nearby shops via Haversine search (`lat`, `lng`, `radius`, `min_rating`) |
| `GET` | `/shops/{id}` | Get shop details |
| `GET` | `/shops/{id}/atk` | Browse a shop's stationery catalog |
| `GET` | `/shops/{id}/reviews` | Get reviews for a shop |

### Partner — Shop Management

> Requires role: `partner` + approved status

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/shops/me` | Get own shop profile |
| `PUT` | `/shops/me` | Update shop profile |
| `PUT` | `/shops/me/services` | Update offered services |
| `PUT` | `/shops/me/pricing` | Update print pricing |
| `GET` | `/shops/me/atk` | List own ATK products |
| `POST` | `/shops/me/atk` | Add an ATK product |
| `GET` | `/shops/me/atk/{id}` | Get a single ATK product |
| `PUT` | `/shops/me/atk/{id}` | Update an ATK product |
| `DELETE` | `/shops/me/atk/{id}` | Delete an ATK product |

### Print Orders — Customer

> Requires role: `user`

| Method | Endpoint | Description |
|---|---|---|
| `POST` | `/orders/print` | Place a print order (`multipart/form-data` with PDF) |
| `GET` | `/orders/print` | List own print orders (filterable by `?status=`) |
| `GET` | `/orders/print/{id}` | Get print order detail with status history |
| `POST` | `/orders/print/{id}/cancel` | Cancel a pending order |
| `POST` | `/orders/print/{id}/review` | Submit a review (completed orders only) |

### Print Orders — Partner

> Requires role: `partner` + approved status

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/partner/orders/print` | List shop's print orders (filterable by `?status=`) |
| `PATCH` | `/partner/orders/print/{id}/status` | Advance order status |

### ATK Orders — Customer

> Requires role: `user`

| Method | Endpoint | Description |
|---|---|---|
| `POST` | `/orders/atk` | Place an ATK order |
| `GET` | `/orders/atk` | List own ATK orders (filterable by `?status=`) |
| `GET` | `/orders/atk/{id}` | Get ATK order detail with items and status history |
| `POST` | `/orders/atk/{id}/review` | Submit a review (completed orders only) |

### ATK Orders — Partner

> Requires role: `partner` + approved status

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/partner/orders/atk` | List shop's ATK orders (filterable by `?status=`) |
| `PATCH` | `/partner/orders/atk/{id}/status` | Advance order status |

### Reviews

> Requires role: `user`

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/reviews/me` | List all reviews submitted by the authenticated user |
| `GET` | `/reviews/me/{id}` | Get a specific review |

---

## Authentication

This API uses **JWT (JSON Web Tokens)**. After logging in, include the token in every protected request:

```
Authorization: Bearer <your_token>
Accept: application/json
```

> **Important:** Always include `Accept: application/json`. Without it, Laravel returns HTML on authentication failures instead of JSON.

Tokens expire after `JWT_TTL` minutes (default 24 hours).

---

## Order Status Flow

Both print and ATK orders follow the same linear status progression:

```
pending → confirmed → processing → ready_for_pickup → completed
                                                           ↑
                                                       review unlocked
```

| Rule | Detail |
|---|---|
| Status advancement | Partners only, one step at a time — skipping returns `422` |
| Cancellation | Customers only, from `pending` state only |
| Reviews | Customers only, on `completed` orders only, one per order |
| Rating update | `shops.average_rating` and `total_reviews` update automatically via DB trigger |

---

## Running Tests

Tests run against a separate PostgreSQL database (`cetakin_test`). Create it once:

```bash
docker compose exec db createdb -U <DB_USERNAME> cetakin_test
docker compose exec -e DB_DATABASE=cetakin_test app php artisan migrate --force
```

Run the full test suite:

```bash
docker compose exec app ./vendor/bin/pest
```

**45 tests across 5 feature files:**

| File | Coverage |
|---|---|
| `AuthTest` | Registration, login, logout, profile |
| `AdminTest` | Partner listing, approval, rejection |
| `PrintOrderTest` | Place order, list, show, cancel, status transitions, role guards |
| `AtkOrderTest` | Place order, stock validation, item snapshot, status transitions |
| `ReviewTest` | Submit review, duplicate guard, non-completed guard, rating trigger |

---

## API Documentation

Interactive API documentation powered by [Scramble](https://scramble.dedoc.co) (Stoplight Elements UI):

```
http://localhost:8000/docs/api
```

Raw OpenAPI 3.1 specification:

```
http://localhost:8000/docs/api.json
```

To authenticate in the UI, click the **lock icon** on any endpoint and enter your JWT token.

Export the spec as a static file:

```bash
docker compose exec app php artisan scramble:export
# outputs → api.json
```
