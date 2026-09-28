# PHP Deployment Notes

This repository is the framework-free PHP application. Pages and APIs are served by Apache/PHP and use PDO to access the shared MySQL database. Tailwind CSS, Flowbite, Font Awesome, Apache ECharts, and browser parsing libraries are loaded by the frontend pages.

## Local XAMPP

Follow the full setup in [`README.md`](README.md). In brief: create `iris_db`, import `database.sql`, adjust `config/db.php` for the local connection, start Apache/MySQL, then open `http://localhost/iris/`.

The server-side runtime does not require Laravel, Composer, Node.js, or Python. Node.js is optional and is used only to run the tests with `node --test scanner/test/*.test.js`.

## Browser Processing

The Scanner uses browser-side JavaScript for spreadsheet ingestion and charting. Parser modules also support document text/viewing workflows for PDF and DOCX and image OCR support where the corresponding browser libraries are available. Smart Upload and chart recommendations do not call an external AI API.

## PHP API

- `api/iris.php` handles authenticated record and saved-graph operations.
- `api/dashboard_graphs.php` supplies approved graph data to the public Observatory.
- `config/db.php` centralizes PDO setup and scanner table creation/compatibility migration.
