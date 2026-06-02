# PowerDivision

REST API for managing user account balance, built with **Laravel 13**, **PostgreSQL**, **Redis** and **nginx + php-fpm** running in Docker.

---

## Tech Stack

| Layer | Technology |
|---|---|
| Framework | Laravel 13 / PHP 8.3 |
| Web server | nginx |
| PHP runtime | php-fpm |
| Database | PostgreSQL 16 |
| Cache / Locking | Redis 7 |
| Containerization | Docker / Docker Compose |

---

## Requirements

- Docker Desktop
- Docker Compose v2+
- `curl` (for the test script)

---

## Getting Started

```bash
# 1. Copy environment file
cp .env.example .env

# 2. Build and start containers
docker-compose up -d --build

# 3. Run migrations and seeder (creates test user with ID=1)
docker-compose exec app php artisan migrate --seed
```

Application available at: `http://localhost:8080`

---

## Endpoint

### POST `/api/accounts/{userId}/transactions`

Credits (positive amount) or debits (negative amount) a user account.

**URL Parameters**

| Param | Type | Description |
|---|---|---|
| `userId` | integer | User ID |

**Request Body (JSON)**

| Field | Type | Description |
|---|---|---|
| `amount` | float | Transaction amount, e.g. `100` or `-30` (≠ 0, range ±999999.99) |

**Example — top-up:**

```bash
curl -X POST http://localhost:8080/api/accounts/1/transactions \
  -H "Content-Type: application/json" \
  -d '{"amount": 100}'
```

**Response 200:**

```json
{
    "data": {
        "user_id": 1,
        "balance": 100.00,
        "last_transaction_at": "2026-06-02T12:00:00Z"
    }
}
```

**Error codes:**

| HTTP | Error key | Description |
|---|---|---|
| 422 | `insufficient_funds` | Balance would go negative |
| 422 | `validation_error` | Invalid input data |
| 404 | `account_not_found` | Account does not exist |
| 503 | `lock_timeout` | Could not acquire account lock |

---

## Architecture

```
app/
├── Http/
│   ├── Controllers/AccountController.php   # HTTP handling, error mapping
│   ├── Middleware/LoggingMiddleware.php     # Logs every response to /dev/stdout
│   └── Requests/TransactionRequest.php     # Input validation
├── Services/
│   └── AccountService.php                  # Business logic, Redis lock, sleep(5)
├── Repositories/
│   ├── AccountRepository.php               # accounts table access
│   └── TransactionRepository.php           # transactions table access
├── Models/
│   ├── Account.php
│   └── Transaction.php
└── Exceptions/
    ├── AccountNotFoundException.php
    └── InsufficientFundsException.php
```

**Protection against negative balance:**
Every transaction acquires a **Redis distributed lock** (TTL 30s). Inside the lock, the balance is read, validated and updated within a PostgreSQL transaction. Concurrent requests queue up — they cannot read a stale balance.

---

## Logging

Every response is logged to `/dev/stdout` in the format:

```
POST /api/accounts/1/transactions application/json 200 5037ms
```

Logs are available via:

```bash
docker-compose logs -f app
```

---

## Test Script

The script tops up the account by +100, then fires **10 parallel** charge requests of -30.
Expected result: 3 succeed, 7 fail with `insufficient_funds`.

```bash
./scripts/test_transactions.sh
# or with custom parameters:
./scripts/test_transactions.sh [USER_ID] [BASE_URL]
```

Compatible with: macOS, Linux, Git Bash (Windows), WSL.

---

## Environment Variables (`.env`)

| Variable | Default | Description |
|---|---|---|
| `DB_CONNECTION` | `pgsql` | Database driver |
| `DB_HOST` | `postgres` | PostgreSQL host (Docker service name) |
| `DB_DATABASE` | `powerdivision` | Database name |
| `REDIS_HOST` | `redis` | Redis host (Docker service name) |
| `CACHE_STORE` | `redis` | Cache backend |
| `QUEUE_CONNECTION` | `redis` | Queue backend |
| `SESSION_DRIVER` | `redis` | Session backend |
| `LOG_STACK` | `stdout` | Log channel |
