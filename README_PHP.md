# IRIS — Plain PHP (no Laravel)

This folder is a framework-free PHP conversion of the Laravel branch. The Blade templates were converted to ordinary `.php` pages while keeping the Tailwind CSS CDN, Flowbite 2.3, Font Awesome, and Apache ECharts markup and styling.

## XAMPP
1. Copy `iris-main-PHP` into `C:/xampp/htdocs/`.
2. Start Apache and MySQL.
3. Import `database.sql` into phpMyAdmin.
4. Edit `config/db.php` and set MySQL port to `3306` or `3307`.
5. Register a user, then promote it with `UPDATE users SET role='admin' WHERE username='your_username';`.
6. Open `http://localhost/iris-main-PHP/`.

Smart Upload uses plain PHP cURL for Anthropic and PHP ZipArchive for DOCX. Set `CLAUDE_API_KEY` in the Apache/PHP environment if AI extraction is needed. XLS/XLSX server extraction is not bundled as a framework dependency; CSV, DOCX, PDF, and images are supported directly.
