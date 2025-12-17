# 📘 Full Stack Trading Engine Assignment

**Laravel API + Vue.js (Composition API) + Real-Time Order Matching**

---

## 📌 Project Overview

This project is a simplified **crypto spot trading engine** implementing **limit orders**, **wallet balances**, **asset locking**, **full-match execution**, and **real-time updates**.

The system ensures:

* Financial integrity
* Concurrency safety
* Atomic balance & asset updates
* Deterministic order matching
* Real-time UI updates via Pusher

---

## 🧱 Repository Structure

```
/
├── backend/        # Laravel API
├── frontend/       # Vue.js (Composition API + Tailwind)
└── README.md
```

---

## 🛠 Technology Stack

### Backend

* Laravel (latest stable)
* MySQL or PostgreSQL
* Laravel Sanctum (API auth)
* Laravel Queues (optional for matching)
* Laravel Broadcasting (Pusher)

### Frontend

* Vue.js (latest stable)
* Composition API
* Tailwind CSS (latest)
* Axios
* Laravel Echo + Pusher JS

### Real-Time

* Pusher (private user channels)

---

## 🔐 Authentication

* API authentication via **Laravel Sanctum**
* Frontend stores auth token securely
* All API routes (except login/register) are authenticated

---

## 🗄 Database Schema

### users

Default Laravel fields plus:

| Column  | Type          | Notes              |
| ------- | ------------- | ------------------ |
| balance | decimal(20,8) | USD wallet balance |

---

### assets

Tracks crypto balances per user.

| Column        | Type          | Notes                    |
| ------------- | ------------- | ------------------------ |
| user_id       | foreignId     | references users         |
| symbol        | string        | BTC, ETH                 |
| amount        | decimal(20,8) | available balance        |
| locked_amount | decimal(20,8) | reserved for sell orders |

Unique constraint: `(user_id, symbol)`

---

### orders

Stores limit orders.

| Column  | Type          | Notes                         |
| ------- | ------------- | ----------------------------- |
| user_id | foreignId     |                               |
| symbol  | string        | BTC, ETH                      |
| side    | enum          | buy / sell                    |
| price   | decimal(20,8) |                               |
| amount  | decimal(20,8) |                               |
| status  | tinyint       | 1=open, 2=filled, 3=cancelled |

---

### trades (Optional but recommended)

Stores executed matches.

| Column        | Type          | Notes |
| ------------- | ------------- | ----- |
| buy_order_id  | foreignId     |       |
| sell_order_id | foreignId     |       |
| symbol        | string        |       |
| price         | decimal(20,8) |       |
| amount        | decimal(20,8) |       |
| usd_volume    | decimal(20,8) |       |
| commission    | decimal(20,8) |       |
| created_at    | timestamp     |       |

---

## 🔌 API Endpoints

### Profile & Wallet

#### `GET /api/profile`

Returns:

* USD balance
* Asset balances (available + locked)
* User metadata

---

### Orders

#### `GET /api/orders?symbol=BTC`

Returns **open orders only** for orderbook:

* Buy orders sorted by price DESC
* Sell orders sorted by price ASC

---

#### `POST /api/orders`

Creates a **limit order**.

Payload:

```json
{
  "symbol": "BTC",
  "side": "buy",
  "price": 95000,
  "amount": 0.01
}
```

---

#### `POST /api/orders/{id}/cancel`

Cancels an open order and:

* Releases locked USD (buy)
* Releases locked assets (sell)

---

## ⚙️ Core Business Logic (CRITICAL)

### Buy Order Flow

1. Check: `users.balance >= price * amount`
2. Lock USD:

   * Deduct from `users.balance`
   * Store locked value internally
3. Create order with status = OPEN
4. Trigger matching engine

---

### Sell Order Flow

1. Check: `assets.amount >= amount`
2. Lock asset:

   * Move amount → `locked_amount`
3. Create order with status = OPEN
4. Trigger matching engine

---

## 🔄 Matching Engine Rules

* **Full match only** (NO partial fills)
* Triggered immediately after order creation
* Matching logic:

  * New BUY → first SELL where `sell.price <= buy.price`
  * New SELL → first BUY where `buy.price >= sell.price`
* Oldest matching order wins (FIFO)

---

## 💰 Commission Rules (MANDATORY)

* Commission = **1.5% of USD volume**

* Example:

  ```
  0.01 BTC @ 95,000 USD
  USD volume = 950
  Commission = 14.25 USD
  ```

* Commission handling:

  * Deduct USD fee from buyer
  * Seller receives full USD OR asset (implementation choice)

* Must be **consistent and documented**

---

## 🔒 Concurrency & Safety Requirements

All matching logic MUST:

* Use `DB::transaction()`
* Lock rows using `SELECT ... FOR UPDATE`
* Prevent double-spend
* Prevent race conditions
* Be idempotent

No balance or asset mutation may occur **outside a transaction**.

---

## 📡 Real-Time Broadcasting (MANDATORY)

### Event: `OrderMatched`

Triggered on successful match.

Broadcast via:

* Pusher
* Private channels:

  ```
  private-user.{user_id}
  ```

Payload includes:

* Updated balances
* Updated assets
* Order status changes
* Trade details

---

## 🎨 Frontend (Vue.js)

### Pages Required

#### 1️⃣ Limit Order Form

* Symbol dropdown (BTC / ETH)
* Side selector (Buy / Sell)
* Price input
* Amount input
* Submit button
* Validation feedback

---

#### 2️⃣ Orders & Wallet Overview

Sections:

* USD balance
* Asset balances
* Order history (open / filled / cancelled)
* Orderbook (live)

---

### Real-Time UI Updates

* Listen via Laravel Echo
* On `OrderMatched`:

  * Update wallet balances
  * Update asset balances
  * Update order status
  * Reflect changes without refresh

---

## 🧪 Optional Enhancements (Bonus)

* Toast notifications
* Order filtering
* Estimated order cost preview
* Trade history UI
* Background queue for matching
* Seeder for demo users & balances

---

## 🧾 Setup Instructions

### Backend

```bash
cd backend
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

Configure:

* Database
* Pusher credentials
* Queue (optional)

---

### Frontend

```bash
cd frontend
npm install
npm run dev
```

Configure:

* API base URL
* Pusher key & cluster

---

## ✅ Evaluation Focus

This project will be evaluated on:

* Financial correctness
* Transaction safety
* Order matching integrity
* Real-time stability
* Code clarity
* Commit hygiene

---

## 🧠 Notes for AI Code Generation

* Always prefer correctness over shortcuts
* No fake balances
* No partial matching
* No optimistic UI without server confirmation
* Transactions are mandatory

---

## 📌 Author

**Jaydeep Sureliya**


