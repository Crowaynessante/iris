# IRIS

IRIS is a plain-PHP application for institutional data ingestion, review, and publication. Apache/PHP serves the site and PHP/PDO endpoints; browser JavaScript handles document parsing, scanner workflows, and charts. There is no Laravel, Composer application, Node.js web server, or Python service.

## Requirements

- XAMPP (Apache, PHP 8.1 or newer, and MySQL/MariaDB)
- A modern browser with access to the page's CDN-hosted frontend libraries
- Node.js only if you want to run the dependency-free JavaScript test suite

## XAMPP Setup

1. Place or clone this repository under `C:/xampp/htdocs/iris`.
2. Start Apache and MySQL from the XAMPP Control Panel.
3. Create the `iris_db` database and import [`database.sql`](database.sql) using phpMyAdmin.
4. Update the connection constants in [`config/db.php`](config/db.php) if the local MySQL host, port, user, or password differ.
5. Open `http://localhost/iris/` and register an account.
6. To grant that account administrator access, run this in the `iris_db` SQL console:

  ```sql
  UPDATE users SET role = 'admin' WHERE username = 'YOUR_USERNAME';
  ```

7. Sign in again. Administrators can open the Review Editor and Scanner; saved charts appear in the public Observatory only after their graph is explicitly published.

## Main Areas

- **Admin and Review Editor:** manage uploaded records, review extracted data, adjust chart fields, and publish a record with its active Studio chart.
- **Scanner:** browser-based ingestion and parsing for supported spreadsheet and document formats, extraction review, and chart drafting.
- **Saved Dashboard Graphs:** browse saved chart versions, publish individual or selected graphs, print sheets, and export graph data.
- **Public Observatory:** displays graphs whose `saved_graphs.is_published` flag is true. Admins can unpublish a graph without deleting its saved data. Guests do not see admin-only controls.

## Charts

Apache ECharts is used for interactive charts. Studio and saved graphs support Bar, Line, Pie, Doughnut, Polar Area, and Ranked Bar. Spreadsheet drafts use the parsed rows, keep separate numerical columns as separate charts, detect chronological values for line charts, and omit identifier-like columns and invalid cells. Draft chart data is emitted in ECharts format and rendered by the existing ECharts adapter.

## Database and API

`config/db.php` provides the PDO connection and ensures the scanner record/graph tables exist. `api/iris.php` requires a signed-in user and an admin role for mutations. Record approval (`records.status`) and graph publication (`saved_graphs.is_published`) are separate fields and workflows. `api/dashboard_graphs.php` returns only explicitly published graphs and disables HTTP caching so publish/unpublish changes appear immediately.

Scanner persistence is managed by `scanner/js/database/dbManager.js`, which uses the PHP API as the canonical store and browser storage fallbacks for records. The upload handoff between admin pages uses IndexedDB. The PHP app does not require a separate Express server.

## Tests

Tests use Node's built-in runner; there is no `package.json` or `npm test` script. From the repository root, run:

```powershell
node --test scanner/test/*.test.js
```

See [`scanner/test/README.md`](scanner/test/README.md) for coverage and focused test commands.
