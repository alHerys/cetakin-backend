# Backend Development Plan
### On-Demand Print Service App — Laravel API

---

## Stack Overview

| Layer | Technology |
|-------|-----------|
| Language | PHP 8.2 |
| Framework | Laravel 13 |
| Auth | tymon/jwt-auth |
| Database | PostgreSQL |
| File Storage | Cloudinary |
| Containerization | Docker + Docker Compose |
| CI/CD | Railway (auto-deploy on git push) |
| OS | Ubuntu 24 |

---

## Project Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Auth/
│   │   │   └── AuthController.php
│   │   ├── Admin/
│   │   │   └── PartnerController.php
│   │   ├── Shop/
│   │   │   ├── ShopController.php
│   │   │   ├── ShopServiceController.php
│   │   │   ├── ShopPricingController.php
│   │   │   └── AtkProductController.php
│   │   ├── Discovery/
│   │   │   └── DiscoveryController.php
│   │   ├── Order/
│   │   │   ├── PrintOrderController.php
│   │   │   └── AtkOrderController.php
│   │   ├── Partner/
│   │   │   ├── PartnerPrintOrderController.php
│   │   │   └── PartnerAtkOrderController.php
│   │   └── Review/
│   │       └── ReviewController.php
│   ├── Middleware/
│   │   ├── RoleMiddleware.php
│   │   └── PartnerApprovedMiddleware.php
│   └── Requests/
│       ├── Auth/
│       ├── Shop/
│       ├── Order/
│       └── Review/
├── Services/
│   ├── AuthService.php
│   ├── ProfileService.php
│   ├── OrderPrintingService.php
│   ├── OrderAtkService.php
│   ├── SellingProductService.php
│   ├── TransactionService.php
│   └── CloudinaryService.php
├── Models/
│   ├── User.php
│   ├── Shop.php
│   ├── ShopService.php
│   ├── ShopPricing.php
│   ├── AtkProduct.php
│   ├── PrintOrder.php
│   ├── PrintOrderStatusHistory.php
│   ├── AtkOrder.php
│   ├── AtkOrderItem.php
│   ├── AtkOrderStatusHistory.php
│   └── Review.php
└── Traits/
    └── ApiResponse.php
```

---

## Phase 0 — Project Setup
> Goal: working Laravel app in Docker, connected to PostgreSQL, JWT configured, Railway pipeline live.

### 0.1 Initialize Laravel
```bash
composer create-project laravel/laravel printapp-backend
cd printapp-backend
```

### 0.2 Install Dependencies
```bash
composer require tymon/jwt-auth
composer require cloudinary-labs/cloudinary-laravel
composer require --dev laravel/pint
```

### 0.3 Docker Setup
Create `docker-compose.yml` at the project root:
```yaml
services:
  app:
    build: .
    ports:
      - "8000:8000"
    volumes:
      - .:/var/www/html
    depends_on:
      - db
    environment:
      - DB_CONNECTION=pgsql
      - DB_HOST=db
      - DB_PORT=5432
      - DB_DATABASE=${DB_DATABASE}
      - DB_USERNAME=${DB_USERNAME}
      - DB_PASSWORD=${DB_PASSWORD}

  db:
    image: postgres:16
    environment:
      POSTGRES_DB: ${DB_DATABASE}
      POSTGRES_USER: ${DB_USERNAME}
      POSTGRES_PASSWORD: ${DB_PASSWORD}
    volumes:
      - pgdata:/var/lib/postgresql/data

volumes:
  pgdata:
```

Create `Dockerfile`:
```dockerfile
FROM php:8.2-cli
RUN apt-get update && apt-get install -y libpq-dev zip unzip \
    && docker-php-ext-install pdo pdo_pgsql
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY . .
RUN composer install --no-dev --optimize-autoloader
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
```

### 0.4 Configure JWT
```bash
php artisan vendor:publish --provider="Tymon\JWTAuth\Providers\LaravelServiceProvider"
php artisan jwt:secret
```

Add to `.env`:
```
JWT_SECRET=your_generated_secret
JWT_TTL=1440
JWT_REFRESH_TTL=20160
```

### 0.5 Configure Cloudinary
Add to `.env`:
```
CLOUDINARY_URL=cloudinary://api_key:api_secret@cloud_name
```

### 0.6 Railway Setup
- Connect GitHub repo to Railway
- Add all `.env` variables in Railway dashboard
- Railway auto-deploys on every push to `main`

**Checklist:**
- [ ] `docker compose up` runs without errors
- [ ] Laravel welcome page accessible at `localhost:8000`
- [ ] PostgreSQL connection confirmed via `php artisan db:show`
- [ ] `php artisan jwt:secret` generates secret
- [ ] Railway pipeline triggers on push

---

## Phase 1 — Database & Models
> Goal: all migrations run, all models with relationships defined.

### 1.1 Run Migrations
Run the `schema.sql` against your PostgreSQL instance, or convert each table to a Laravel migration file. Recommended order:

1. `users`
2. `shops`
3. `shop_services`
4. `shop_pricing`
5. `atk_products`
6. `print_orders`
7. `print_order_status_history`
8. `atk_orders`
9. `atk_order_items`
10. `atk_order_status_history`
11. `reviews`

```bash
php artisan migrate
```

### 1.2 Define Models & Relationships

**User.php**
```php
// Implements JWTSubject
public function shop(): HasOne
public function printOrders(): HasMany
public function atkOrders(): HasMany
public function reviews(): HasMany
```

**Shop.php**
```php
public function user(): BelongsTo
public function service(): HasOne      // shop_services
public function pricing(): HasOne      // shop_pricing
public function atkProducts(): HasMany
public function printOrders(): HasMany
public function atkOrders(): HasMany
public function reviews(): HasMany
```

**PrintOrder.php**
```php
public function user(): BelongsTo
public function shop(): BelongsTo
public function statusHistory(): HasMany
public function review(): HasOne
```

**AtkOrder.php**
```php
public function user(): BelongsTo
public function shop(): BelongsTo
public function items(): HasMany       // atk_order_items
public function statusHistory(): HasMany
public function review(): HasOne
```

**Review.php**
```php
public function user(): BelongsTo
public function shop(): BelongsTo
public function printOrder(): BelongsTo
public function atkOrder(): BelongsTo
```

### 1.3 ApiResponse Trait
Create `app/Traits/ApiResponse.php` — a reusable trait for consistent JSON responses:
```php
trait ApiResponse {
    protected function success($data = null, string $message = 'OK', int $code = 200): JsonResponse
    protected function error(string $message, int $code, $errors = null): JsonResponse
    protected function paginated($paginator, string $message = 'OK'): JsonResponse
}
```

**Checklist:**
- [ ] All migrations run cleanly with `php artisan migrate`
- [ ] All model relationships resolve without errors
- [ ] `ApiResponse` trait works in a test controller

---

## Phase 2 — Authentication Service
> Goal: register, login, logout, and `/me` endpoints working with JWT.

### 2.1 RoleMiddleware
```php
// app/Http/Middleware/RoleMiddleware.php
// Checks auth()->user()->role against allowed roles
// Returns 403 if mismatch
```

Register in `bootstrap/app.php`:
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'role' => RoleMiddleware::class,
        'partner.approved' => PartnerApprovedMiddleware::class,
    ]);
})
```

### 2.2 AuthService
```php
// app/Services/AuthService.php
public function registerUser(array $data): array
public function registerPartner(array $data): array  // includes shop creation + Cloudinary upload
public function login(array $credentials): array
public function logout(): void
public function me(): User
```

### 2.3 Routes
```php
// routes/api.php
Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/register/partner', [AuthController::class, 'registerPartner']);
        Route::post('/login', [AuthController::class, 'login']);
        Route::middleware('auth:api')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/me', [AuthController::class, 'me']);
        });
    });
});
```

### 2.4 Form Requests
- `RegisterUserRequest` — validates name, email, password, phone
- `RegisterPartnerRequest` — validates all shop fields + file upload
- `LoginRequest` — validates email, password

**Checklist:**
- [ ] `POST /api/v1/auth/register` returns user + token
- [ ] `POST /api/v1/auth/register/partner` creates user, shop, uploads photo to Cloudinary
- [ ] `POST /api/v1/auth/login` returns token
- [ ] `POST /api/v1/auth/logout` invalidates token
- [ ] `GET /api/v1/auth/me` returns correct user
- [ ] Invalid credentials return `401`
- [ ] Wrong role returns `403`

---

## Phase 3 — Admin Service
> Goal: admin can list, approve, and reject partner registrations.

### 3.1 PartnerApprovedMiddleware
```php
// Checks shop->status === 'approved'
// Returns 403 with message if partner is still pending/rejected
```

### 3.2 Routes
```php
Route::prefix('admin')->middleware(['auth:api', 'role:admin'])->group(function () {
    Route::get('/partners', [Admin\PartnerController::class, 'index']);
    Route::patch('/partners/{id}/approve', [Admin\PartnerController::class, 'approve']);
    Route::patch('/partners/{id}/reject', [Admin\PartnerController::class, 'reject']);
});
```

### 3.3 Approval Logic
On approve:
- Set `shops.status = 'approved'`
- Send approval email via `Mail::to($partner)->send(new PartnerApprovedMail())`

On reject:
- Set `shops.status = 'rejected'`
- Store `rejection_reason`
- Send rejection email via `Mail::to($partner)->send(new PartnerRejectedMail($reason))`

**Checklist:**
- [ ] Non-admin cannot access `/admin/*` routes
- [ ] Approve sets status and sends email
- [ ] Reject sets status, stores reason, sends email
- [ ] Pending list filters correctly by status

---

## Phase 4 — Profile & Shop Management Service
> Goal: partners can fully manage their shop, services, pricing, and ATK catalog.

### 4.1 ProfileService
```php
public function getShop(User $user): Shop
public function updateShop(User $user, array $data): Shop  // handles optional Cloudinary re-upload
public function updateServices(Shop $shop, array $data): ShopService
public function updatePricing(Shop $shop, array $data): ShopPricing
```

### 4.2 SellingProductService
```php
public function listProducts(Shop $shop): Collection
public function addProduct(Shop $shop, array $data): AtkProduct   // optional Cloudinary upload
public function updateProduct(AtkProduct $product, array $data): AtkProduct
public function deleteProduct(AtkProduct $product): void
```

### 4.3 Routes
```php
Route::prefix('shops')->middleware(['auth:api', 'role:partner', 'partner.approved'])->group(function () {
    Route::get('/me', [ShopController::class, 'show']);
    Route::put('/me', [ShopController::class, 'update']);
    Route::put('/me/services', [ShopServiceController::class, 'update']);
    Route::put('/me/pricing', [ShopPricingController::class, 'update']);
    Route::apiResource('/me/atk', AtkProductController::class)
        ->parameters(['atk' => 'id']);
});
```

**Checklist:**
- [ ] Partner can view and update their own shop
- [ ] Services and pricing update independently
- [ ] ATK CRUD works with optional photo upload to Cloudinary
- [ ] Partner cannot access other shops' data

---

## Phase 5 — Discovery Service
> Goal: users can find nearby shops filtered by distance, services, and rating.

### 5.1 Haversine Query
Use a raw PostgreSQL expression in Eloquent to calculate distance:
```php
$shops = Shop::selectRaw("
    *,
    (6371 * acos(
        cos(radians(?)) * cos(radians(latitude)) *
        cos(radians(longitude) - radians(?)) +
        sin(radians(?)) * sin(radians(latitude))
    )) AS distance_km
", [$lat, $lng, $lat])
->where('status', 'approved')
->having('distance_km', '<=', $radius)
->orderBy('distance_km')
->get();
```

### 5.2 Filters
Chain optional filter scopes:
- `services` — filter by `shop_services.paper_sizes`, `color_modes`, etc. using `whereJsonContains` or array overlap
- `min_rating` — `where('average_rating', '>=', $minRating)`

### 5.3 Routes
```php
Route::prefix('shops')->middleware(['auth:api', 'role:user'])->group(function () {
    Route::get('/', [DiscoveryController::class, 'index']);
    Route::get('/{id}', [DiscoveryController::class, 'show']);
    Route::get('/{id}/atk', [DiscoveryController::class, 'atkCatalog']);
    Route::get('/{id}/reviews', [ReviewController::class, 'shopReviews']);
});
```

**Checklist:**
- [ ] Returns shops sorted by distance
- [ ] `radius` param limits results correctly
- [ ] Service filters narrow results correctly
- [ ] `min_rating` filter works
- [ ] Closed shops (outside `open_time`/`close_time`) have `is_open: false`

---

## Phase 6 — Order Printing Service
> Goal: users can place print orders with file upload; partners can manage order status.

### 6.1 CloudinaryService
```php
// app/Services/CloudinaryService.php
public function upload(UploadedFile $file, string $folder): string  // returns secure URL
public function delete(string $publicId): void
```

### 6.2 OrderPrintingService
```php
public function createOrder(User $user, array $data, UploadedFile $file): PrintOrder
// Steps:
// 1. Upload file to Cloudinary via CloudinaryService
// 2. Calculate total_pages (from file metadata or default to copies * 1)
// 3. Calculate final_price based on shop_pricing
// 4. Create print_order record
// (status history auto-logged by DB trigger)

public function listOrders(User $user, ?string $status): LengthAwarePaginator
public function getOrder(User $user, string $id): PrintOrder
public function updateStatus(Shop $shop, string $orderId, string $status): PrintOrder
```

### 6.3 Price Calculation Logic
```php
private function calculatePrice(ShopPricing $pricing, array $specs): int {
    $perPage = $specs['color_mode'] === 'full_color'
        ? $pricing->full_color_per_page
        : $pricing->black_and_white_per_page;

    $base = $perPage * $specs['total_pages'] * $specs['copies'];
    $sideExtra = $specs['sides'] === 'double' ? $pricing->double_side_surcharge * $specs['copies'] : 0;
    $bindingExtra = $pricing->binding_prices[$specs['binding']] ?? 0;

    return $base + $sideExtra + $bindingExtra;
}
```

### 6.4 Routes
```php
// User routes
Route::prefix('orders/print')->middleware(['auth:api', 'role:user'])->group(function () {
    Route::post('/', [PrintOrderController::class, 'store']);
    Route::get('/', [PrintOrderController::class, 'index']);
    Route::get('/{id}', [PrintOrderController::class, 'show']);
    Route::post('/{id}/review', [ReviewController::class, 'storePrintReview']);
});

// Partner routes
Route::prefix('partner/orders/print')->middleware(['auth:api', 'role:partner', 'partner.approved'])->group(function () {
    Route::get('/', [PartnerPrintOrderController::class, 'index']);
    Route::patch('/{id}/status', [PartnerPrintOrderController::class, 'updateStatus']);
});
```

### 6.5 Status Transition Validation
Enforce valid transitions server-side:
```php
const VALID_TRANSITIONS = [
    'pending'          => 'confirmed',
    'confirmed'        => 'processing',
    'processing'       => 'ready_for_pickup',
    'ready_for_pickup' => 'completed',
];
```

**Checklist:**
- [ ] File upload reaches Cloudinary, URL stored in DB
- [ ] Price is correctly calculated from shop pricing
- [ ] Invalid file format/size rejected at `FormRequest` level
- [ ] Status history auto-logged via DB trigger
- [ ] Invalid status transitions return `422`
- [ ] Partner can only update orders belonging to their own shop

---

## Phase 7 — Order ATK Service
> Goal: users can place ATK orders; partners can manage them.

### 7.1 OrderAtkService
```php
public function createOrder(User $user, array $data): AtkOrder
// Steps:
// 1. Validate each atk_id belongs to the given shop_id
// 2. Check stock availability for each item
// 3. Calculate subtotal per item and final_price
// 4. Create atk_order + atk_order_items records
// 5. Decrement stock for each product

public function listOrders(User $user, ?string $status): LengthAwarePaginator
public function getOrder(User $user, string $id): AtkOrder
public function updateStatus(Shop $shop, string $orderId, string $status): AtkOrder
```

### 7.2 Stock Validation
```php
foreach ($items as $item) {
    $product = AtkProduct::findOrFail($item['atk_id']);
    if ($product->shop_id !== $shopId) abort(422, 'Product does not belong to this shop');
    if ($product->stock < $item['quantity']) abort(422, "Insufficient stock for {$product->name}");
}
```

### 7.3 Routes
```php
// User routes
Route::prefix('orders/atk')->middleware(['auth:api', 'role:user'])->group(function () {
    Route::post('/', [AtkOrderController::class, 'store']);
    Route::get('/', [AtkOrderController::class, 'index']);
    Route::get('/{id}', [AtkOrderController::class, 'show']);
    Route::post('/{id}/review', [ReviewController::class, 'storeAtkReview']);
});

// Partner routes
Route::prefix('partner/orders/atk')->middleware(['auth:api', 'role:partner', 'partner.approved'])->group(function () {
    Route::get('/', [PartnerAtkOrderController::class, 'index']);
    Route::patch('/{id}/status', [PartnerAtkOrderController::class, 'updateStatus']);
});
```

**Checklist:**
- [ ] ATK items validated against correct shop
- [ ] Stock checked before order creation
- [ ] Stock decremented after successful order
- [ ] `final_price` correctly sums all item subtotals
- [ ] Partner can only see/update their own shop's orders

---

## Phase 8 — Transaction & Review Service
> Goal: users can submit reviews on completed orders; shop ratings auto-update.

### 8.1 TransactionService (Review Logic)
```php
public function submitPrintReview(User $user, string $orderId, array $data): Review
// Validates: order is completed, belongs to user, no review yet

public function submitAtkReview(User $user, string $orderId, array $data): Review
// Same validations for ATK orders

public function getUserReviews(User $user): LengthAwarePaginator
public function getUserReview(User $user, string $reviewId): Review
public function getShopReviews(string $shopId): LengthAwarePaginator
```

### 8.2 Rating Auto-Update
The `update_shop_rating` DB trigger handles this automatically on every `INSERT` into `reviews`. No extra Laravel code needed — just verify it works after inserting a review.

### 8.3 Routes
```php
Route::middleware(['auth:api', 'role:user'])->group(function () {
    Route::get('/reviews/me', [ReviewController::class, 'myReviews']);
    Route::get('/reviews/me/{id}', [ReviewController::class, 'myReview']);
});
```

**Checklist:**
- [ ] Review only allowed on `completed` orders
- [ ] Duplicate review on same order returns `422`
- [ ] `average_rating` on shop updates after review insert
- [ ] `total_reviews` count increments correctly
- [ ] User can retrieve their own review history

---

## Phase 9 — Final Polish & Deployment

### 9.1 Global Exception Handler
In `bootstrap/app.php`, register a global handler for clean JSON error responses:
- `ModelNotFoundException` → `404`
- `ValidationException` → `422`
- `AuthenticationException` → `401`
- `AuthorizationException` → `403`
- Uncaught `Exception` → `500`

### 9.2 API Rate Limiting
Add in `bootstrap/app.php`:
```php
RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
});
```

### 9.3 Final Checklist
- [ ] All 35 endpoints return correct response envelope
- [ ] All role guards tested (user/partner/admin cannot cross-access)
- [ ] File upload tested with large files (>10MB)
- [ ] Cloudinary URLs are accessible
- [ ] Railway auto-deploy triggers on push to `main`
- [ ] `php artisan migrate` runs cleanly on Railway PostgreSQL instance
- [ ] `.env` variables all set in Railway dashboard

---

## Development Order (Priority)

| Phase | Description | Est. Time |
|-------|-------------|-----------|
| 0 | Project setup, Docker, Railway | 0.5 day |
| 1 | Database migrations + models | 0.5 day |
| 2 | Authentication (JWT) | 1 day |
| 3 | Admin approval flow | 0.5 day |
| 4 | Shop management + ATK catalog | 1 day |
| 5 | Discovery (GPS + filtering) | 1 day |
| 6 | Print orders + file upload | 1.5 days |
| 7 | ATK orders | 1 day |
| 8 | Reviews + ratings | 0.5 day |
| 9 | Polish + deployment | 0.5 day |
| **Total** | | **~8 days** |

---

## Key Reminders

- **Always use `DB::transaction()`** for any operation that writes to multiple tables (order creation, stock decrement).
- **Never store files locally** — all uploads go directly to Cloudinary.
- **JWT token** must be attached via Dio interceptor on the Flutter side — ensure `auth:api` guard is on every protected route.
- **Status transitions** must be validated server-side even if Flutter enforces them client-side.
- **`partner.approved` middleware** must gate all partner operational routes — a pending partner should never be able to accept orders.
