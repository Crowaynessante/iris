# Scanner Frontend Modules

[`../app.js`](../app.js) initializes the browser application and creates a shared context with scanner, database manager, state, and module APIs. Modules exchange callbacks through `ctx.api`; DOM queries stay with the module that owns the UI.

## State and rendering

[`state.js`](state.js) stores the active scan/record, queues, filters, and chart instances. [`chartEngine.js`](chartEngine.js) handles Studio row filtering, sorting, grouping, limits, and ECharts options. [`studioWorkbench.js`](studioWorkbench.js) connects Studio controls and persists selected chart settings.

Year Ranking is explicitly selected with `chart_type: "year_ranking"`. Studio calls the shared `ChartMapping.buildYearRankingOption()` builder. The same builder is used by saved graph cards, draft cards, the public Observatory, and print export; do not add surface-specific Year Ranking option copies. Its `chart_options` persist the measure display name and cumulative/reference/bar-label toggles.

## Module responsibilities

| Module | Responsibility |
| --- | --- |
| `navigation.js` | Scanner and admin view switching |
| `navigationTabs.js` | Scanner and admin tab switching |
| `fileIngestion.js` | File selection, drag/drop, samples, progress, and scan orchestration |
| `queue.js` | Ingestion queue and active scan selection |
| `overviewTab.js` | Extracted fields and scan overview |
| `viewerTab.js` | Basic scan viewer |
| `graphsTab.js` | Draft chart cards and print actions |
| `savedGraphsTab.js` | Saved graph cards, filtering, selection, exports, print, and deletion |
| `adminPortal.js` | Record archive, search, statistics, and status actions |
| `studioWorkbench.js` | Studio record setup, field mapping, save, and approval |
| `documentViewer.js` | Document viewing, paging, zoom, and copy behavior |
| `tableGrid.js` | Editable Studio table and row/column operations |
| `chartEngine.js` | ECharts rendering and Studio data transformations |
| `studioActions.js` | Studio add-field/add-row actions |

## Saved Graphs and Exports

Saved graph rows and `chart_options` are stored through `dbManager.js` and the PHP graph API. SQL-formatted `.txt` exports are generated from saved labels, values, and metadata; print sheets render chart previews and data tables separately. Year Ranking print and data exports include a cumulative percentage column when meaningful.

## Development Checks

Run the dependency-free tests from the repository root with `node --test scanner/test/*.test.js`. Browser-test responsive layout, theme updates, dropdown behavior, and public/admin navigation when changing these views.
