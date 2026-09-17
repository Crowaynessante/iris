# IRIS — Laravel Conversion

This is the Laravel conversion of the uploaded `iris-main` PHP project. The visible content, wording, database fields, CSV formats, Smart Upload flow, dashboard charts, authentication, and AI extraction behavior are kept the same; the PHP pages/backend are reorganized into Laravel routes, controllers, Blade views, models, migrations, and services.

## Requirements
- PHP 8.2+
- Composer
- MySQL/MariaDB
- PHP extensions: `pdo_mysql`, `curl`, `fileinfo`, `zip`
- Node/npm are not required for this version because the existing CSS and Chart.js CDN are retained.

## Setup

1. Copy `.env.example` to `.env`.
2. Create a MySQL database named `iris_db` (or change the DB settings in `.env`).
3. Run:

```bash
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

4. Open `http://127.0.0.1:8000`.
5. Register an account. To make it an admin, run:

```sql
UPDATE users SET role = 'admin' WHERE username = 'YOUR_USERNAME';
```

## Smart Upload / Claude
Set `CLAUDE_API_KEY` in `.env` for Word, PDF, Excel, and image extraction. Matching CSV templates can still be imported without the API key.

The original SQL file is retained at `database/iris_db.sql` for reference/manual import. Laravel's equivalent schema is in `database/migrations/2026_09_16_000000_create_iris_tables.php`.
