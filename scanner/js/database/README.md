# Database and Persistence

[`dbManager.js`](dbManager.js) is the Scanner's browser-side persistence adapter. It communicates with the PHP/PDO API in `api/iris.php`; there is no Express server.

## Storage

- MySQL is the canonical store when the PHP API is available.
- IndexedDB and localStorage provide browser-side fallback for records.
- Saved graph snapshots are linked to records through `record_id`.

`config/db.php` contains the PDO connection settings and ensures the scanner `records` and `saved_graphs` tables exist. `database.sql` contains the schema for a fresh installation.

## Saved Graph Data

Saved graphs store chart type, labels, original values, rank metadata, axis metadata, and creation time. The supported chart types are Bar, Line, Pie, Doughnut, and Polar Area.

## PHP API Routes

All routes use `api/iris.php` with query parameters:

```text
GET/POST       /api/iris.php?resource=records
GET/PUT/DELETE /api/iris.php?resource=records&id={recordId}
GET/POST       /api/iris.php?resource=graphs
GET/DELETE     /api/iris.php?resource=graphs&id={graphId}
GET            /api/iris.php?resource=graphs&record_id={recordId}
POST           /api/iris.php?resource=graphs&action=export
```

Bulk actions use the corresponding `action` query parameter. The public Observatory reads approved graph data from `api/dashboard_graphs.php`.

## Graph Operations

`DatabaseManager` provides record CRUD, graph save/read/delete, database-copy export, and printable sheet helpers. SQL-formatted text export is built in `../modules/savedGraphsTab.js` from the loaded graph data and is separate from database export. Print Sheet and Print All use `../graphExport.js`.
