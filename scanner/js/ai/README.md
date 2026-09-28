# Graph Generation and Chart Data

This folder contains browser-side chart recommendations. It does not call an external AI service.

## Draft generation

[`graphEngine.js`](graphEngine.js) creates draft chart objects from numeric spreadsheet columns and simple numeric key/value patterns extracted from document text. A draft includes its title, source, recommendation, labels, values, and chart metadata. Persistence and approval are handled by the Scanner's PHP-backed database manager and Studio workflow.

## Shared chart utilities

- `chartMapping.js` infers category/value columns, parses numbers and rank ranges, detects rank fields, and builds the shared ECharts options for Year Ranking.
- `chartData.js` serializes chart state and supplies grouping helpers. Year Ranking duplicate labels are summed; the legacy bar/line grouping behavior remains separate.
- `graphExport.js` normalizes export payloads and builds Print Sheet/Print All documents. Year Ranking print sheets render through the shared builder and include cumulative percentages in the table.
- `../modules/chartEngine.js` owns Studio option routing; `../modules/graphsTab.js` owns draft-card rendering.

## Chart types

Bar, line, pie, doughnut, and polar-area charts use the existing Studio option path. The explicit `year_ranking` type uses `ChartMapping.buildYearRankingOption()` across Studio, saved charts, the Observatory, print, and data exports. It sorts the displayed measure values, computes cumulative percent after filtering/grouping/limiting, and supports an optional 80% reference. Rank-valued measures use `ChartMapping.isRankField()` and do not show a cumulative series.

Saved-graph SQL-formatted text exports use graph data, not rendered canvas pixels. Print exports render a chart preview separately from the data table.
