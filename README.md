# Laravel Futures Matching Engine

A high-performance **Laravel-based futures trading engine** leveraging **Redis** for a ZSET-based orderbook and scalable order processing with queues and workers.

---

## 🚀 Setup Instructions

1. **Clone the Repository**

   ```bash
   git clone https://github.com/amidesfahani/lightmatch-laravel.git
   cd lightmatch-laravel
   composer install
   cp .env.example .env
   php artisan key:generate
   ```

2. **Configure Environment**

   Update `.env` with your **database** and **Redis** credentials.

3. **Run Migrations**

   ```bash
   php artisan migrate
   ```

---

## 🔢 Database Seeding

Seed large datasets for testing users, wallets, and orders.

**Command Format**:

```bash
php artisan db:seed-records {count} --only={target}
```

**Available `{count}` Options**:
- `small` → 1,000 orders
- `10k` → 10,000 orders
- `100k` → 100,000 orders
- `1m` → 1,000,000 orders
- `10m` → 10,000,000 orders

**Available `--only` Options**:
- `users` → Seed only users
- `wallets` → Seed only wallets
- `orders` → Seed only orders
- `all` → Seed users, wallets, and orders

**Examples**:
```bash
php artisan db:seed-records small --only=all
php artisan db:seed-records 10k --only=orders
php artisan db:seed-records 1m --only=orders
```

---

## ⚙️ Run Matching Queue Workers

Start the queue workers to process matching and default jobs:

```bash
php artisan queue:work --queue=default,matching
```

---

## 💻 Development

**Compile Assets**:
```bash
composer run dev
```

**Enable Hot Reloading** (with Vite):
```bash
composer run hot
```

---

## 📊 Load Testing with Locust

1. **Install Locust**:
   ```bash
   pip install locust
   ```

2. **Run Locust Test**:
   ```bash
   locust -f locustfile.py --host=http://127.0.0.1:8000
   ```

3. Open `http://localhost:8089` in your browser to start load testing.

---

## 🧠 Key Features

- **Redis-Powered Matching**: Utilizes Redis ZSET for a high-performance orderbook.
- **Batch Processing**: Matching jobs dispatched in batches of 200 buy orders to prevent overload.
- **Symbol-Specific Tables**: Orders stored in tables like `orders_btc_usd`.
- **Wallet Management**: Supports `balance` and `frozen_balance` for margin trading.
- **Dynamic PnL**: Profit and loss calculated and cached in Redis.

---

## 📂 Directory Structure

```plaintext
app/
├── Actions/
│   └── PlaceOrderAction.php
├── Jobs/
│   ├── MatchFuturesOrdersJob.php
│   └── MatchOrdersBatchJob.php
├── Services/
│   └── FuturesMatchingService.php
├── Models/
│   ├── Order.php
│   └── Wallet.php
database/
├── migrations/
│   └── create_orders_{symbol}_table.php
├── seeders/
│   ├── UsersSeeder.php
│   ├── WalletsSeeder.php
│   └── OrdersSeeder.php
```

---

## 🧪 Test Commands Summary

```bash
# Run queue workers for matching and default jobs
php artisan queue:work --queue=default,matching

# Seed database with test data
php artisan db:seed-records small --only=all
php artisan db:seed-records 10k --only=orders
php artisan db:seed-records 1m --only=orders

# Compile assets
composer run dev

# Run Locust for load testing
locust -f locustfile.py --host=http://127.0.0.1:8000
```