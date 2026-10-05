# ShocoStore

## Requirements

- PHP 8.3 or later with `pdo_mysql`, `openssl`, `mbstring`, `fileinfo`, and `zip` enabled.
- Composer 2.
- MySQL with a `ShocoStore` database.
- Node.js and npm.

## Setup

Create the MySQL database with `CREATE DATABASE ShocoStore CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`. Copy `Backend/api/.env.example` to `Backend/api/.env` and set the MySQL credentials. Then run:

```sh
cd Backend/api
composer install
php artisan key:generate
php artisan migrate --seed
```

Start Laravel and Vite in separate terminals from the repository root:

```sh
npm run backend
npm run dev
```

Laravel serves the API on port 8000; Vite forwards `/api` requests to it. Use Laravel migrations to create the tables instead of importing the SQL schema a second time.

## API

- `GET /api/products` lists the product catalogue.
- `POST /api/products` saves a product with `name`, `price`, and optional `stock`, `description`, `tone`, and `tag` fields.
- `GET /api/orders` lists saved orders.
- `POST /api/orders` saves an order with `productId`, `quantity`, and optional `customerName`; stock is checked and decremented in a database transaction.
- `GET /api/health` checks that the API is running.

The reusable request preset is `Frontend/src/api/store.js`:

```js
import { saveProduct, saveOrder } from './api/store.js'

await saveProduct({ name: 'Hazelnut Energy', price: 5.25, stock: 20 })
await saveOrder({ productId: '1', quantity: 2, customerName: 'Guest' })
```
