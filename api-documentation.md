# API Documentation — On-Demand Print Service App

**Base URL:** `/api/v1`  
**Authentication:** Bearer Token (JWT) via `Authorization: Bearer <token>` header  
**Content-Type:** `application/json` (except file upload endpoints which use `multipart/form-data`)

---

## Response Envelope

All responses follow a consistent envelope format:

```json
{
  "success": true,
  "message": "Human-readable message",
  "data": { }
}
```

For paginated responses, `data` includes:

```json
{
  "data": {
    "items": [ ],
    "meta": {
      "current_page": 1,
      "per_page": 15,
      "total": 100,
      "last_page": 7
    }
  }
}
```

For errors:

```json
{
  "success": false,
  "message": "Error description",
  "errors": {
    "field": ["Validation message"]
  }
}
```

---

## HTTP Status Codes

| Code | Meaning |
|------|---------|
| 200 | OK |
| 201 | Created |
| 400 | Bad Request |
| 401 | Unauthenticated |
| 403 | Forbidden (wrong role) |
| 404 | Not Found |
| 422 | Validation Error |
| 500 | Server Error |

---

## 0. Health

### GET `/health`
Check API and database connectivity.

**Access:** Public

**Response `200`:**
```json
{
  "status": "ok",
  "services": {
    "database": "ok"
  }
}
```

**Response `503`** (if database is unreachable):
```json
{
  "status": "degraded",
  "services": {
    "database": "error"
  }
}
```

---

## 1. Authentication

### POST `/auth/register`
Register a new user account.

**Access:** Public

**Request:**
```json
{
  "name": "Budi Santoso",
  "email": "budi@email.com",
  "password": "password123",
  "password_confirmation": "password123",
  "phone": "08123456789"
}
```

**Response `201`:**
```json
{
  "success": true,
  "message": "Registration successful",
  "data": {
    "user": {
      "id": "uuid",
      "name": "Budi Santoso",
      "email": "budi@email.com",
      "phone": "08123456789",
      "role": "user",
      "created_at": "2025-01-01T10:00:00Z"
    },
    "token": "eyJ..."
  }
}
```

---

### POST `/auth/register/partner`
Register a new print shop partner account. Status will be `pending` until approved by admin.

**Access:** Public

**Request:** `multipart/form-data`

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| name | string | Yes | Owner's full name |
| email | string | Yes | Login email |
| password | string | Yes | Min 8 characters |
| password_confirmation | string | Yes | |
| phone | string | Yes | Contact number |
| shop_name | string | Yes | Name of the print shop |
| shop_address | string | Yes | Full address |
| shop_phone | string | Yes | Shop contact number |
| shop_description | string | No | Brief description |
| open_time | string | Yes | Format: `HH:MM` e.g. `08:00` |
| close_time | string | Yes | Format: `HH:MM` e.g. `21:00` |
| operating_days | array | Yes | e.g. `["monday","tuesday","wednesday"]` |
| shop_photo | file | Yes | Image file, max 5MB |

**Response `201`:**
```json
{
  "success": true,
  "message": "Partner registration submitted. Awaiting admin approval.",
  "data": {
    "partner": {
      "id": "uuid",
      "name": "Andi",
      "email": "andi@printshop.com",
      "role": "partner",
      "status": "pending",
      "shop": {
        "id": "uuid",
        "shop_name": "Percetakan Maju Jaya",
        "shop_address": "Jl. Soekarno Hatta No. 12, Malang",
        "shop_phone": "0341123456",
        "shop_photo_url": "https://res.cloudinary.com/...",
        "status": "pending"
      }
    }
  }
}
```

---

### POST `/auth/login`
Login for all roles (user, partner, admin).

**Access:** Public

**Request:**
```json
{
  "email": "budi@email.com",
  "password": "password123"
}
```

**Response `200`:**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": "uuid",
      "name": "Budi Santoso",
      "email": "budi@email.com",
      "role": "user"
    },
    "token": "eyJ..."
  }
}
```

---

### POST `/auth/logout`
Invalidate the current JWT token.

**Access:** Authenticated (all roles)

**Request:** _(no body)_

**Response `200`:**
```json
{
  "success": true,
  "message": "Logged out successfully",
  "data": null
}
```

---

### GET `/auth/me`
Get the currently authenticated user's profile.

**Access:** Authenticated (all roles)

**Response `200`:**
```json
{
  "success": true,
  "message": "OK",
  "data": {
    "id": "uuid",
    "name": "Budi Santoso",
    "email": "budi@email.com",
    "phone": "08123456789",
    "role": "user",
    "created_at": "2025-01-01T10:00:00Z"
  }
}
```

---

### PUT `/auth/me`
Update the authenticated user's profile. All fields are optional.

**Access:** Authenticated (all roles)

**Request:**
```json
{
  "name": "Budi Updated",
  "email": "budi.new@example.com",
  "phone": "08111222333"
}
```

**Response `200`:**
```json
{
  "success": true,
  "message": "Profile updated successfully",
  "data": {
    "id": "uuid",
    "name": "Budi Updated",
    "email": "budi.new@example.com",
    "phone": "08111222333",
    "role": "user",
    "avatar_url": null,
    "created_at": "2025-01-01T10:00:00Z"
  }
}
```

---

### POST `/auth/me/avatar`
Upload or replace the authenticated user's profile photo.

**Access:** Authenticated (all roles)

**Request:** `multipart/form-data`

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| avatar | file | Yes | Image file (jpg, jpeg, png, webp), max 2MB |

**Response `200`:**
```json
{
  "success": true,
  "message": "Avatar updated successfully",
  "data": {
    "id": "uuid",
    "name": "Budi Santoso",
    "email": "budi@email.com",
    "phone": "08123456789",
    "role": "user",
    "avatar_url": "https://res.cloudinary.com/...",
    "created_at": "2025-01-01T10:00:00Z"
  }
}
```

---

## 2. Admin

### GET `/admin/partners`
List all partner registrations, filterable by status.

**Access:** Admin only

**Query Params:**

| Param | Type | Description |
|-------|------|-------------|
| status | string | `pending`, `approved`, `rejected`. Default: `pending` |
| page | integer | Page number |

**Response `200`:**
```json
{
  "success": true,
  "message": "OK",
  "data": {
    "items": [
      {
        "id": "uuid",
        "name": "Andi",
        "email": "andi@printshop.com",
        "status": "pending",
        "shop": {
          "id": "uuid",
          "shop_name": "Percetakan Maju Jaya",
          "shop_address": "Jl. Soekarno Hatta No. 12, Malang",
          "shop_photo_url": "https://res.cloudinary.com/...",
          "submitted_at": "2025-01-01T10:00:00Z"
        }
      }
    ],
    "meta": {
      "current_page": 1,
      "per_page": 15,
      "total": 4,
      "last_page": 1
    }
  }
}
```

---

### PATCH `/admin/partners/{id}/approve`
Approve a pending partner registration. Triggers an approval email to the partner.

**Access:** Admin only

**Request:** _(no body)_

**Response `200`:**
```json
{
  "success": true,
  "message": "Partner approved successfully",
  "data": {
    "partner_id": "uuid",
    "status": "approved"
  }
}
```

---

### PATCH `/admin/partners/{id}/reject`
Reject a pending partner registration. Triggers a rejection email to the partner.

**Access:** Admin only

**Request:**
```json
{
  "reason": "Shop photo is unclear. Please resubmit with a clearer image."
}
```

**Response `200`:**
```json
{
  "success": true,
  "message": "Partner rejected",
  "data": {
    "partner_id": "uuid",
    "status": "rejected",
    "reason": "Shop photo is unclear. Please resubmit with a clearer image."
  }
}
```

---

## 3. Shop Management (Partner)

### GET `/shops/me`
Get the authenticated partner's own shop profile.

**Access:** Partner only

**Response `200`:**
```json
{
  "success": true,
  "message": "OK",
  "data": {
    "id": "uuid",
    "shop_name": "Percetakan Maju Jaya",
    "shop_address": "Jl. Soekarno Hatta No. 12, Malang",
    "shop_phone": "0341123456",
    "shop_description": "Melayani cetak dokumen dan ATK",
    "shop_photo_url": "https://res.cloudinary.com/...",
    "open_time": "08:00",
    "close_time": "21:00",
    "operating_days": ["monday", "tuesday", "wednesday", "thursday", "friday"],
    "latitude": -7.9666,
    "longitude": 112.6326,
    "status": "approved",
    "average_rating": 4.7,
    "total_reviews": 23
  }
}
```

---

### PUT `/shops/me`
Update the authenticated partner's shop profile.

**Access:** Partner only

**Request:** `multipart/form-data`

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| shop_name | string | No | |
| shop_address | string | No | |
| shop_phone | string | No | |
| shop_description | string | No | |
| open_time | string | No | Format: `HH:MM` |
| close_time | string | No | Format: `HH:MM` |
| operating_days | array | No | |
| shop_photo | file | No | Image file, max 5MB |

**Response `200`:**
```json
{
  "success": true,
  "message": "Shop profile updated",
  "data": { }
}
```

---

### PUT `/shops/me/services`
Configure which print services the shop supports.

**Access:** Partner only

**Request:**
```json
{
  "paper_sizes": ["A4", "A3", "F4"],
  "color_modes": ["black_and_white", "full_color"],
  "sides": ["single", "double"],
  "bindings": ["none", "staple", "spiral"]
}
```

**Response `200`:**
```json
{
  "success": true,
  "message": "Services updated",
  "data": {
    "paper_sizes": ["A4", "A3", "F4"],
    "color_modes": ["black_and_white", "full_color"],
    "sides": ["single", "double"],
    "bindings": ["none", "staple", "spiral"]
  }
}
```

---

### PUT `/shops/me/pricing`
Set per-unit pricing for print services.

**Access:** Partner only

**Request:**
```json
{
  "black_and_white_per_page": 500,
  "full_color_per_page": 2000,
  "double_side_surcharge": 200,
  "binding_prices": {
    "staple": 2000,
    "spiral": 8000
  }
}
```

**Response `200`:**
```json
{
  "success": true,
  "message": "Pricing updated",
  "data": { }
}
```

---

### GET `/shops/me/atk`
List all ATK products in the partner's own catalog.

**Access:** Partner only

**Response `200`:**
```json
{
  "success": true,
  "message": "OK",
  "data": {
    "items": [
      {
        "id": "uuid",
        "name": "Pulpen Pilot",
        "description": "Pulpen tinta hitam",
        "price": 5000,
        "stock": 100,
        "photo_url": "https://res.cloudinary.com/...",
        "is_available": true
      }
    ],
    "meta": { }
  }
}
```

---

### POST `/shops/me/atk`
Add a new ATK product to the shop's catalog.

**Access:** Partner only

**Request:** `multipart/form-data`

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| name | string | Yes | Product name |
| description | string | No | |
| price | integer | Yes | Price in IDR |
| stock | integer | Yes | Available stock |
| photo | file | No | Image file, max 5MB |

**Response `201`:**
```json
{
  "success": true,
  "message": "ATK product added",
  "data": {
    "id": "uuid",
    "name": "Pulpen Pilot",
    "price": 5000,
    "stock": 100,
    "photo_url": "https://res.cloudinary.com/..."
  }
}
```

---

### PUT `/shops/me/atk/{id}`
Update an existing ATK product.

**Access:** Partner only

**Request:** `multipart/form-data` (all fields optional)

**Response `200`:**
```json
{
  "success": true,
  "message": "ATK product updated",
  "data": { }
}
```

---

### DELETE `/shops/me/atk/{id}`
Delete an ATK product from the catalog.

**Access:** Partner only

**Response `200`:**
```json
{
  "success": true,
  "message": "ATK product deleted",
  "data": null
}
```

---

## 4. Discovery (User)

### GET `/shops`
List nearby active shops, sorted by distance. Supports optional filtering.

**Access:** Authenticated User

**Query Params:**

| Param | Type | Required | Description |
|-------|------|----------|-------------|
| lat | float | Yes | User's current latitude |
| lng | float | Yes | User's current longitude |
| radius | integer | No | Search radius in km. Default: `10` |
| services | string | No | Comma-separated: `print`, `atk` |
| paper_sizes | string | No | Comma-separated: `A4`, `A3`, `F4` |
| color_modes | string | No | `black_and_white`, `full_color` |
| bindings | string | No | `staple`, `spiral` |
| min_rating | float | No | e.g. `4.0` |
| page | integer | No | |

**Response `200`:**
```json
{
  "success": true,
  "message": "OK",
  "data": {
    "items": [
      {
        "id": "uuid",
        "shop_name": "Percetakan Maju Jaya",
        "shop_address": "Jl. Soekarno Hatta No. 12, Malang",
        "shop_photo_url": "https://res.cloudinary.com/...",
        "open_time": "08:00",
        "close_time": "21:00",
        "distance_km": 0.8,
        "average_rating": 4.7,
        "total_reviews": 23,
        "is_open": true,
        "services": ["print", "atk"]
      }
    ],
    "meta": { }
  }
}
```

---

### GET `/shops/{id}`
Get full detail of a specific shop, including services and pricing.

**Access:** Authenticated User

**Response `200`:**
```json
{
  "success": true,
  "message": "OK",
  "data": {
    "id": "uuid",
    "shop_name": "Percetakan Maju Jaya",
    "shop_address": "Jl. Soekarno Hatta No. 12, Malang",
    "shop_phone": "0341123456",
    "shop_description": "Melayani cetak dokumen dan ATK",
    "shop_photo_url": "https://res.cloudinary.com/...",
    "open_time": "08:00",
    "close_time": "21:00",
    "operating_days": ["monday", "tuesday", "wednesday", "thursday", "friday"],
    "latitude": -7.9666,
    "longitude": 112.6326,
    "average_rating": 4.7,
    "total_reviews": 23,
    "services": {
      "paper_sizes": ["A4", "A3", "F4"],
      "color_modes": ["black_and_white", "full_color"],
      "sides": ["single", "double"],
      "bindings": ["none", "staple", "spiral"]
    },
    "pricing": {
      "black_and_white_per_page": 500,
      "full_color_per_page": 2000,
      "double_side_surcharge": 200,
      "binding_prices": {
        "staple": 2000,
        "spiral": 8000
      }
    }
  }
}
```

---

### GET `/shops/{id}/atk`
Get the ATK product catalog of a specific shop.

**Access:** Authenticated User

**Query Params:**

| Param | Type | Description |
|-------|------|-------------|
| page | integer | Page number |

**Response `200`:**
```json
{
  "success": true,
  "message": "OK",
  "data": {
    "items": [
      {
        "id": "uuid",
        "name": "Pulpen Pilot",
        "description": "Pulpen tinta hitam",
        "price": 5000,
        "stock": 100,
        "photo_url": "https://res.cloudinary.com/...",
        "is_available": true
      }
    ],
    "meta": { }
  }
}
```

---

## 5. Print Orders (User)

### POST `/orders/print`
Create a new print order and upload the document file.

**Access:** Authenticated User

**Request:** `multipart/form-data`

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| shop_id | uuid | Yes | Target print shop |
| file | file | Yes | PDF or DOCX, max 50MB |
| paper_size | string | Yes | `A4`, `A3`, `F4` |
| color_mode | string | Yes | `black_and_white`, `full_color` |
| sides | string | Yes | `single`, `double` |
| binding | string | Yes | `none`, `staple`, `spiral` |
| copies | integer | Yes | Min: 1 |
| notes | string | No | Additional notes for the shop |

**Response `201`:**
```json
{
  "success": true,
  "message": "Print order created successfully",
  "data": {
    "id": "uuid",
    "shop": {
      "id": "uuid",
      "shop_name": "Percetakan Maju Jaya"
    },
    "file_url": "https://res.cloudinary.com/...",
    "paper_size": "A4",
    "color_mode": "black_and_white",
    "sides": "single",
    "binding": "none",
    "copies": 2,
    "total_pages": 20,
    "final_price": 20000,
    "notes": null,
    "status": "pending",
    "created_at": "2025-01-01T10:00:00Z"
  }
}
```

---

### GET `/orders/print`
Get the authenticated user's print order history.

**Access:** Authenticated User

**Query Params:**

| Param | Type | Description |
|-------|------|-------------|
| status | string | Filter by status: `pending`, `confirmed`, `processing`, `ready_for_pickup`, `completed` |
| page | integer | |

**Response `200`:**
```json
{
  "success": true,
  "message": "OK",
  "data": {
    "items": [
      {
        "id": "uuid",
        "shop_name": "Percetakan Maju Jaya",
        "paper_size": "A4",
        "color_mode": "black_and_white",
        "copies": 2,
        "final_price": 20000,
        "status": "processing",
        "created_at": "2025-01-01T10:00:00Z"
      }
    ],
    "meta": { }
  }
}
```

---

### GET `/orders/print/{id}`
Get full detail of a specific print order, including the latest status.

**Access:** Authenticated User (own orders only)

**Response `200`:**
```json
{
  "success": true,
  "message": "OK",
  "data": {
    "id": "uuid",
    "shop": {
      "id": "uuid",
      "shop_name": "Percetakan Maju Jaya",
      "shop_phone": "0341123456"
    },
    "file_url": "https://res.cloudinary.com/...",
    "paper_size": "A4",
    "color_mode": "black_and_white",
    "sides": "single",
    "binding": "none",
    "copies": 2,
    "total_pages": 20,
    "final_price": 20000,
    "notes": null,
    "status": "processing",
    "status_history": [
      { "status": "pending", "timestamp": "2025-01-01T10:00:00Z" },
      { "status": "confirmed", "timestamp": "2025-01-01T10:05:00Z" },
      { "status": "processing", "timestamp": "2025-01-01T10:10:00Z" }
    ],
    "created_at": "2025-01-01T10:00:00Z"
  }
}
```

---

## 6. Print Orders (Partner)

### GET `/partner/orders/print`
Get all incoming print orders for the authenticated partner's shop.

**Access:** Partner only

**Query Params:**

| Param | Type | Description |
|-------|------|-------------|
| status | string | Filter by status |
| page | integer | |

**Response `200`:**
```json
{
  "success": true,
  "message": "OK",
  "data": {
    "items": [
      {
        "id": "uuid",
        "user": {
          "name": "Budi Santoso",
          "phone": "08123456789"
        },
        "file_url": "https://res.cloudinary.com/...",
        "paper_size": "A4",
        "color_mode": "black_and_white",
        "sides": "single",
        "binding": "none",
        "copies": 2,
        "total_pages": 20,
        "final_price": 20000,
        "notes": null,
        "status": "pending",
        "created_at": "2025-01-01T10:00:00Z"
      }
    ],
    "meta": { }
  }
}
```

---

### PATCH `/partner/orders/print/{id}/status`
Update the status of a print order.

**Access:** Partner only

**Request:**
```json
{
  "status": "confirmed"
}
```

Valid transitions: `pending` → `confirmed` → `processing` → `ready_for_pickup` → `completed`

**Response `200`:**
```json
{
  "success": true,
  "message": "Order status updated",
  "data": {
    "order_id": "uuid",
    "status": "confirmed",
    "updated_at": "2025-01-01T10:05:00Z"
  }
}
```

---

## 7. ATK Orders (User)

### POST `/orders/atk`
Create a new ATK order from a shop's catalog.

**Access:** Authenticated User

**Request:**
```json
{
  "shop_id": "uuid",
  "items": [
    { "atk_id": "uuid", "quantity": 3 },
    { "atk_id": "uuid", "quantity": 1 }
  ],
  "notes": "Tolong siapkan sebelum jam 3 sore"
}
```

**Response `201`:**
```json
{
  "success": true,
  "message": "ATK order created successfully",
  "data": {
    "id": "uuid",
    "shop": {
      "id": "uuid",
      "shop_name": "Percetakan Maju Jaya"
    },
    "items": [
      {
        "atk_id": "uuid",
        "name": "Pulpen Pilot",
        "quantity": 3,
        "unit_price": 5000,
        "subtotal": 15000
      }
    ],
    "final_price": 20000,
    "notes": "Tolong siapkan sebelum jam 3 sore",
    "status": "pending",
    "created_at": "2025-01-01T10:00:00Z"
  }
}
```

---

### GET `/orders/atk`
Get the authenticated user's ATK order history.

**Access:** Authenticated User

**Query Params:**

| Param | Type | Description |
|-------|------|-------------|
| status | string | Filter by status |
| page | integer | |

**Response `200`:**
```json
{
  "success": true,
  "message": "OK",
  "data": {
    "items": [
      {
        "id": "uuid",
        "shop_name": "Percetakan Maju Jaya",
        "total_items": 2,
        "final_price": 20000,
        "status": "pending",
        "created_at": "2025-01-01T10:00:00Z"
      }
    ],
    "meta": { }
  }
}
```

---

### GET `/orders/atk/{id}`
Get full detail of a specific ATK order.

**Access:** Authenticated User (own orders only)

**Response `200`:**
```json
{
  "success": true,
  "message": "OK",
  "data": {
    "id": "uuid",
    "shop": {
      "id": "uuid",
      "shop_name": "Percetakan Maju Jaya",
      "shop_phone": "0341123456"
    },
    "items": [
      {
        "atk_id": "uuid",
        "name": "Pulpen Pilot",
        "quantity": 3,
        "unit_price": 5000,
        "subtotal": 15000
      }
    ],
    "final_price": 20000,
    "notes": "Tolong siapkan sebelum jam 3 sore",
    "status": "processing",
    "status_history": [
      { "status": "pending", "timestamp": "2025-01-01T10:00:00Z" },
      { "status": "confirmed", "timestamp": "2025-01-01T10:03:00Z" },
      { "status": "processing", "timestamp": "2025-01-01T10:05:00Z" }
    ],
    "created_at": "2025-01-01T10:00:00Z"
  }
}
```

---

## 8. ATK Orders (Partner)

### GET `/partner/orders/atk`
Get all incoming ATK orders for the authenticated partner's shop.

**Access:** Partner only

**Query Params:**

| Param | Type | Description |
|-------|------|-------------|
| status | string | Filter by status |
| page | integer | |

**Response `200`:**
```json
{
  "success": true,
  "message": "OK",
  "data": {
    "items": [
      {
        "id": "uuid",
        "user": {
          "name": "Budi Santoso",
          "phone": "08123456789"
        },
        "items": [
          {
            "name": "Pulpen Pilot",
            "quantity": 3,
            "unit_price": 5000,
            "subtotal": 15000
          }
        ],
        "final_price": 20000,
        "notes": "Tolong siapkan sebelum jam 3 sore",
        "status": "pending",
        "created_at": "2025-01-01T10:00:00Z"
      }
    ],
    "meta": { }
  }
}
```

---

### PATCH `/partner/orders/atk/{id}/status`
Update the status of an ATK order.

**Access:** Partner only

**Request:**
```json
{
  "status": "confirmed"
}
```

Valid transitions: `pending` → `confirmed` → `processing` → `ready_for_pickup` → `completed`

**Response `200`:**
```json
{
  "success": true,
  "message": "ATK order status updated",
  "data": {
    "order_id": "uuid",
    "status": "confirmed",
    "updated_at": "2025-01-01T10:03:00Z"
  }
}
```

---

## 9. Reviews

### POST `/orders/print/{id}/review`
Submit a review for a completed print order. One review per order.

**Access:** Authenticated User (own completed orders only)

**Request:**
```json
{
  "rating": 5,
  "comment": "Hasil cetakan sangat bagus dan cepat!"
}
```

**Response `201`:**
```json
{
  "success": true,
  "message": "Review submitted",
  "data": {
    "id": "uuid",
    "order_id": "uuid",
    "shop_id": "uuid",
    "rating": 5,
    "comment": "Hasil cetakan sangat bagus dan cepat!",
    "created_at": "2025-01-01T12:00:00Z"
  }
}
```

---

### POST `/orders/atk/{id}/review`
Submit a review for a completed ATK order. One review per order.

**Access:** Authenticated User (own completed orders only)

**Request:**
```json
{
  "rating": 4,
  "comment": "Stok lengkap, pelayanan ramah."
}
```

**Response `201`:**
```json
{
  "success": true,
  "message": "Review submitted",
  "data": {
    "id": "uuid",
    "order_id": "uuid",
    "shop_id": "uuid",
    "rating": 4,
    "comment": "Stok lengkap, pelayanan ramah.",
    "created_at": "2025-01-01T12:00:00Z"
  }
}
```

---

### GET `/shops/{id}/reviews`
Get all reviews for a specific shop.

**Access:** Authenticated User

**Query Params:**

| Param | Type | Description |
|-------|------|-------------|
| page | integer | |

**Response `200`:**
```json
{
  "success": true,
  "message": "OK",
  "data": {
    "items": [
      {
        "id": "uuid",
        "user_name": "Budi Santoso",
        "rating": 5,
        "comment": "Hasil cetakan sangat bagus dan cepat!",
        "order_type": "print",
        "created_at": "2025-01-01T12:00:00Z"
      }
    ],
    "meta": { }
  }
}
```

---

### GET `/reviews/me`
Get all reviews submitted by the authenticated user.

**Access:** Authenticated User

**Query Params:**

| Param | Type | Description |
|-------|------|-------------|
| page | integer | |

**Response `200`:**
```json
{
  "success": true,
  "message": "OK",
  "data": {
    "items": [
      {
        "id": "uuid",
        "shop": {
          "id": "uuid",
          "shop_name": "Percetakan Maju Jaya"
        },
        "rating": 5,
        "comment": "Hasil cetakan sangat bagus dan cepat!",
        "order_type": "print",
        "created_at": "2025-01-01T12:00:00Z"
      }
    ],
    "meta": { }
  }
}
```

---

### GET `/reviews/me/{id}`
Get detail of a specific review submitted by the authenticated user.

**Access:** Authenticated User (own reviews only)

**Response `200`:**
```json
{
  "success": true,
  "message": "OK",
  "data": {
    "id": "uuid",
    "shop": {
      "id": "uuid",
      "shop_name": "Percetakan Maju Jaya"
    },
    "order_id": "uuid",
    "order_type": "print",
    "rating": 5,
    "comment": "Hasil cetakan sangat bagus dan cepat!",
    "created_at": "2025-01-01T12:00:00Z"
  }
}
```

---

## Appendix — Order Status Reference

| Status | Description |
|--------|-------------|
| `pending` | Order placed, awaiting partner confirmation |
| `confirmed` | Partner has confirmed the order |
| `processing` | Order is being processed/printed |
| `ready_for_pickup` | Order is ready, user can come to the shop |
| `completed` | User has picked up and payment is done |

---

## Appendix — Role Access Summary

| Endpoint Group | User | Partner | Admin |
|----------------|------|---------|-------|
| Health | Public | Public | Public |
| Auth | Yes | Yes | Yes |
| Admin endpoints | No | No | Yes |
| Shop management (`/shops/me`) | No | Yes | No |
| Discovery (`/shops`) | Yes | No | No |
| Print orders (user) | Yes | No | No |
| Print orders (partner) | No | Yes | No |
| ATK orders (user) | Yes | No | No |
| ATK orders (partner) | No | Yes | No |
| Reviews | Yes | No | No |
