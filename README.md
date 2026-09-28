# IRIS Fused — Plain PHP

This project combines the two supplied IRIS projects into **one plain PHP application**.

## Included

- IRIS Main / International Rapport Insight System
  - Login and registration
  - User Observatory dashboard
  - Admin dashboard
  - Ranking bodies, rankings, breakdowns
  - Colleges, programs, accreditations
  - CSV uploads and smart upload/review flow
- IRIS-7 File Scanner / Analytics
  - XLSX/XLS/CSV parsing in the browser
  - PDF/DOCX viewing and extraction helpers
  - OCR helpers retained from the original frontend
  - Extraction review workspace
  - Record archive/edit/delete
  - Draft and saved graphs
  - Graph export
- **One MySQL database: `iris_db`**
- **Plain PHP + PDO** only for the server-side application
- **Tailwind CSS + Flowbite maintained**
- No Laravel, Composer, Node.js server, or Python server is required for the PHP application.

## Setup in XAMPP

1. Copy `IRIS-FUSED-PLAIN-PHP` into `C:/xampp/htdocs/`.
2. Start Apache and MySQL.
3. Open phpMyAdmin.
4. Import `database.sql` into MySQL.
5. Check `config/db.php` if your MySQL port, username, or password is different.
6. Open:
   `http://localhost/IRIS-FUSED-PLAIN-PHP/`
7. Register an account.
8. Promote the account to admin in phpMyAdmin:
   `UPDATE users SET role='admin' WHERE username='YOUR_USERNAME';`
9. Log in as the admin. The **IRIS-7 Scanner** button opens the fused scanner workspace.

## Important

The browser-side JavaScript from IRIS-7 is intentionally retained because it performs file parsing, PDF/DOCX viewing, charts, and OCR in the browser. The Node.js Express server has been replaced by `api/iris.php`, which uses the same MySQL database as the main IRIS application.
