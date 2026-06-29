# Extendable Order & Payment Management API

A Laravel **13** REST API for managing orders and payments, built around clean-code
principles and a **pluggable payment-gateway architecture** (Strategy pattern). Adding a new
payment gateway requires creating one class and adding one config line — no changes to existing
controllers, services, or business logic.

- **Modular monolith** via [`nwidart/laravel-modules`](https://github.com/nWidart/laravel-modules) — `Core`, `Auth`, `Order`, `Payment`
- **JWT authentication** via [`php-open-source-saver/jwt-auth`](https://github.com/PHP-Open-Source-Saver/jwt-auth)
- **PHP 8.3+**, PSR-12 (Laravel Pint), PHPUnit
- **31 passing tests** (feature + unit, including gateway logic + the MyFatoorah integration)
- **Gateways:** `credit_card` and `paypal` (simulated, settle instantly) plus **`myfatoorah`** — a real hosted-payment integration via the official **`myfatoorah/laravel-package`** (returns a *pending* payment + `redirect_url`, confirmed asynchronously via webhook)
- **Domain events + observers** (`OrderCreated`, `PaymentProcessed`), per-model **query filters**, and a unified **gateway webhook** endpoint

> See [`TASK_SPECIFICATION.md`](TASK_SPECIFICATION.md) for the full design rationale.

---

## Architecture

Each bounded context is a self-contained module under `Modules/`:

| Module | Responsibility |
|--------|----------------|
| **Core** | Shared `ApiResponse` trait, `ApiException` + centralized exception `Handler`, base `ApiController`. |
| **Auth** | Registration, login, profile, logout (JWT). |
| **Order** | Orders + items, server-side total calculation, status filtering, delete guard. |
| **Payment** | Payments, the gateway **Strategy** + **Manager**, business-rule enforcement. |

Request flow (per module):

```
Request → FormRequest (validation) → Controller (thin) → Service (rules)
        → Repository (interface, DIP) → Eloquent
        → Resource → ApiResponse trait → standardized JSON
Payment → PaymentGatewayManager → PaymentGateway strategy
```

**Patterns used:** Strategy (gateways), Factory/Manager (gateway resolver), Repository (DIP),
Service Layer, DTO (`PaymentResult`), API Resources, PHP Enums. **SOLID** throughout; DRY via the
shared `Core` module.

---

## Setup

### Option A — Docker (no local PHP required)

This repo was developed using the official `composer` image. Any command below maps to:

```bash
docker run --rm -u $(id -u):$(id -g) -e HOME=/tmp -v "$PWD":/app -w /app composer:2 <command>
```

### Option B — Local PHP 8.3+ & Composer

```bash
# 1. Install dependencies
composer install

# 2. Environment
cp .env.example .env
php artisan key:generate
php artisan jwt:secret          # sets JWT_SECRET in .env

# 3. Database (SQLite by default — zero config)
touch database/database.sqlite
php artisan migrate

# 4. Serve
php artisan serve               # http://127.0.0.1:8000
```

> **Database:** defaults to **SQLite** for zero-config local runs. To use MySQL instead, set the
> `DB_*` values in `.env` (commented examples are included) — a `compose.yaml` with MySQL/Sail is
> also provided.

> **Running as root?** If you are using a root environment (e.g. Docker without user mapping),
> run `COMPOSER_ALLOW_SUPERUSER=1 composer install` so the merge-plugin wires modules correctly.

### Run the tests

```bash
php artisan test                # full suite
php artisan test --testsuite=Modules
./vendor/bin/pint               # PSR-12 check/format
```

---

## API Reference

Base URL: `http://127.0.0.1:8000/api`. All responses use a consistent envelope:

```jsonc
// success
{ "success": true,  "message": "...", "data": ... , "meta": { ... } }
// error
{ "success": false, "message": "...", "errors": { ... } }
```

### Authentication

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/api/auth/register` | – | Register, returns JWT |
| POST | `/api/auth/login` | – | Login, returns JWT |
| GET  | `/api/auth/me` | JWT | Current user |
| POST | `/api/auth/logout` | JWT | Invalidate token |

### Orders

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/orders` | List orders. Filters: `?status=pending\|confirmed\|cancelled`, `?per_page=` |
| POST | `/api/orders` | Create order (total computed server-side) |
| GET | `/api/orders/{order}` | Show order with items + payments |
| PUT/PATCH | `/api/orders/{order}` | Update order (recalculates total if items change) |
| DELETE | `/api/orders/{order}` | Delete — `204` on success (blocked `409` if payments exist) |

### Payments

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/payments` | List all payments (paginated) |
| GET | `/api/orders/{order}/payments` | Payments for one order |
| POST | `/api/orders/{order}/payments` | Process payment (order must be `confirmed`) |

All protected endpoints require `Authorization: Bearer <token>`.

### Example — create an order

```bash
curl -X POST http://127.0.0.1:8000/api/orders \
  -H "Authorization: Bearer <TOKEN>" -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
        "customer_name": "Acme",
        "customer_email": "buyer@acme.test",
        "status": "confirmed",
        "items": [
          { "product_name": "Widget", "quantity": 2, "price": 10.50 },
          { "product_name": "Gadget", "quantity": 1, "price": 5.00 }
        ]
      }'
```

Response (`201`): `data.total` is `"26.00"` — computed by the server, never trusted from the client.

A ready-to-import **Postman collection** lives at
[`docs/OrderPaymentAPI.postman_collection.json`](docs/OrderPaymentAPI.postman_collection.json).
The login/register requests auto-save the JWT into a `{{token}}` collection variable.

---

## Business Rules

1. **Orders are owned by the authenticating user.** `GET /api/orders` returns only the current
   user's orders; `GET/PUT/DELETE /api/orders/{order}` and all payment endpoints enforce
   ownership via an `OrderPolicy` — any attempt to access another user's order returns `403
   Forbidden`.
2. **Payments require a confirmed order.** Processing a payment for a `pending`/`cancelled`
   order returns `409 Conflict`. Enforced in `PaymentService::ensureOrderIsPayable()`.
3. **Orders with payments cannot be deleted.** Returns `409 Conflict`. Enforced in
   `OrderObserver::deleting()`.
4. **Order totals are server-calculated** from line items (`Σ quantity × price`); a client-sent
   total is ignored.

---

## Extensibility — Adding a New Payment Gateway

The system uses the **Strategy pattern**. Every gateway implements one interface and is resolved
at runtime by the `PaymentGatewayManager` from configuration.

**Step 1 — create the gateway** (`Modules/Payment/app/Gateways/StripeGateway.php`):

```php
namespace Modules\Payment\Gateways;

use Modules\Order\Models\Order;
use Modules\Payment\Contracts\PaymentGateway;
use Modules\Payment\Contracts\PaymentResult;

class StripeGateway implements PaymentGateway
{
    public function __construct(private readonly array $config = []) {}

    public function key(): string
    {
        return 'stripe';
    }

    public function charge(Order $order, array $payload): PaymentResult
    {
        // ... call the Stripe SDK using $this->config ...
        return PaymentResult::success('STRIPE-REF-123');
    }
}
```

**Step 2 — register it** in `Modules/Payment/config/config.php`:

```php
'gateways' => [
    CreditCardGateway::class,
    PaypalGateway::class,
    StripeGateway::class,   // ← the only change
],
'credentials' => [
    'stripe' => ['secret' => env('STRIPE_SECRET')],
],
```

**That's it.** `POST /api/orders/{order}/payments` now accepts `{ "method": "stripe" }`.
No controller, service, or manager code changes — this is the Open/Closed Principle in action,
and it's covered by a dedicated test
(`PaymentGatewayManagerTest::test_a_newly_registered_gateway_is_resolvable_without_touching_the_manager`).

An unknown method returns a descriptive `422` via `UnsupportedGatewayException`.

---

## Testing

31 tests / 96 assertions covering authentication, order CRUD + validation + total calculation,
status filtering, both business rules, payment processing per gateway, payment listing, the
gateway manager/strategy in isolation, the MyFatoorah integration (charge + webhook, with the
MyFatoorahClient seam faked), and the gateway webhook reconciliation flow.

```
Tests:    31 passed (96 assertions)
```

---

## Assumptions & Notes

- **JWT over Sanctum:** the brief explicitly requires JWT, so
  `php-open-source-saver/jwt-auth` (the maintained fork of `tymon/jwt-auth`) is used with an
  `api` guard (`config/auth.php`). The default guard is set to `api`.
- **SQLite by default** for a frictionless review; MySQL config + `compose.yaml` are included.
- **Gateways are simulated** (no real network calls) and always return success — the focus is the
  extensibility design, not a specific provider integration. A real gateway would use the injected
  credentials and may return `PaymentResult::failed()`, which is persisted as a `failed` payment.
- **Payment amount** is taken from the order total at processing time.
- **Items on update** are treated as a full replacement of the order's line items.
- **Order ownership is enforced** via `OrderPolicy`: each user can only access, modify, and pay
  for their own orders. The `GET /api/payments` endpoint is also scoped to the authenticated
  user's orders. Gateway credentials are configured via `.env` / module config.
