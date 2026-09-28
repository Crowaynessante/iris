# Scanner Tests

Tests use Node's built-in `node:test` runner. No npm install or package manifest is required. From the repository root, run the full suite with:

```powershell
node --test scanner/test/*.test.js
```

To run the chart and graph/export coverage by itself:

```powershell
node --test scanner/test/chartMapping.test.js scanner/test/dashboardData.test.js
```

## Coverage

- Chart field inference, numeric parsing, rank detection/inversion, and Year Ranking option data
- Duplicate grouping, cumulative percentages, zero/negative totals, and blank-value handling
- Table filtering and document pagination
- Graph serialization, Year Ranking print tables, and SQL-formatted text exports
- Structural frontend contracts

These tests cover utilities and source-level contracts. They do not replace browser checks for responsive layout, theme updates, dropdown behavior, authentication, or end-to-end Apache/MySQL behavior.

## Known test gap

The current full run has one failing legacy assertion in `integrationLayout.test.js`: it expects the previous Scanner CSS grid width (`minmax(220px, 290px) minmax(0, 1fr)`). The remaining 29 tests pass. Refresh that structural expectation when the Scanner layout contract is next changed.
