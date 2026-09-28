# Graph Generation and Chart Data

This folder contains browser-side chart recommendations. It does not call an external AI service.

## Draft generation

[`graphEngine.js`](graphEngine.js) creates draft chart objects from numeric spreadsheet columns and simple numeric key/value patterns extracted from document text. A draft includes its title, source, recommendation, labels, values, and chart metadata. Persistence and approval are handled by the Scanner's PHP-backed database manager and Studio workflow.

## Shared chart utilities

- `chartMapping.js` infers category/value columns and parses numeric and rank values used by Studio charts.
- `chartData.js` serializes chart state and supplies grouping helpers.
- `graphExport.js` normalizes export payloads and builds Print Sheet/Print All documents.
- `../modules/chartEngine.js` owns Studio option routing; `../modules/graphsTab.js` owns draft-card rendering.

## Chart types

The supported types are Bar, Line, Pie, Doughnut, and Polar Area. Excel drafts use the parser-provided rows, retain separate numerical fields as separate chart suggestions, infer chronological sequences from values as well as headers, and emit Apache ECharts option objects. Invalid cells are omitted rather than converted to zero; identifier-like columns and structural document-statistics fallbacks are excluded from Excel chart generation.

Saved-graph SQL-formatted text exports use graph data, not rendered canvas pixels. Print exports render a chart preview separately from the data table.
