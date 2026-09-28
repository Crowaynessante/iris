const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { prepareCircularData, serializeChartState } = require('../js/chartData');
const { pairSelectedText } = require('../js/sourceIngestion');
const { normalizeGraphExportItem, buildPrintableGraphSheet, buildSavedChartOption } = require('../js/graphExport');
const savedGraphsSource = fs.readFileSync(path.join(__dirname, '..', 'js', 'modules', 'savedGraphsTab.js'), 'utf8');

test('deduplicates circular chart legend labels while grouping remains optional', () => {
  const rows = [{ label: 'North', value: 2 }, { label: 'North', value: 3 }, { label: 'South', value: 4 }];
  const ungrouped = prepareCircularData(rows, false);
  const grouped = prepareCircularData(rows, true);
  assert.deepEqual(ungrouped.legendLabels, ['North', 'South']);
  assert.equal(ungrouped.rows.length, 3);
  assert.deepEqual(grouped.rows, [{ label: 'North', value: 5 }, { label: 'South', value: 4 }]);
});

test('serializes the current edited chart series after an entity is removed', () => {
  const original = { series: [{ data: [{ name: 'North', value: 2 }, { name: 'South', value: 4 }, { name: 'West', value: 6 }] }] };
  const edited = { series: [{ data: original.series[0].data.slice(1) }] };
  const exported = serializeChartState(edited, { labels: ['North', 'South', 'West'] });
  assert.equal(exported.labels.length, original.series[0].data.length - 1);
  assert.deepEqual(exported.labels, ['South', 'West']);
  assert.deepEqual(exported.values, [4, 6]);
});

test('pairs a selected label with its adjacent extracted value', () => {
  assert.equal(pairSelectedText('2022', '2022\n601-800'), '2022 601-800');
  assert.equal(pairSelectedText('Enrollment', 'Enrollment: 1,250'), 'Enrollment 1,250');
});

test('normalizes saved and draft graphs into a single export payload', () => {
  const draft = {
    title: 'Enrollment trend',
    primaryType: 'line',
    chartData: {
      labels: ['Q1', 'Q2'],
      datasets: [{ data: [120, 150] }]
    }
  };

  const payload = normalizeGraphExportItem(draft, 'rec_123');

  assert.equal(payload.record_id, 'rec_123');
  assert.equal(payload.chart_type, 'line');
  assert.deepEqual(payload.labels, ['Q1', 'Q2']);
  assert.deepEqual(payload.values_data, [120, 150]);
});

test('preserves the exact saved ECharts chart type when the type is nested inside chartData', () => {
  const payload = normalizeGraphExportItem({
    title: 'Distribution',
    chartData: {
      type: 'polarArea',
      labels: ['North', 'South'],
      series: [{ data: [{ name: 'North', value: 65 }, { name: 'South', value: 35 }] }]
    }
  }, 'rec_456');

  assert.equal(payload.chart_type, 'polarArea');
  assert.deepEqual(payload.labels, ['North', 'South']);
  assert.deepEqual(payload.values_data, [65, 35]);
});

test('ranked-bar exports keep raw ranks and horizontal orientation for the published dashboard', () => {
  const option = buildSavedChartOption({
    title: 'SDG Rank',
    chart_type: 'rankedBar',
    labels: ['SDG 1', 'SDG 2'],
    values_data: [12, 45],
    chart_data: {
      rankedBar: { selectedYear: 2025, reverseOrder: true },
      series: [{ data: [{ name: 'SDG 1', value: 12, rawValue: 12 }, { name: 'SDG 2', value: 45, rawValue: 45 }] }]
    }
  });

  assert.equal(option.xAxis.type, 'value');
  assert.equal(option.yAxis.type, 'category');
  assert.deepEqual(option.yAxis.data, ['SDG 2', 'SDG 1']);
  assert.deepEqual(option.series[0].data.map(point => point.value), [45, 12]);
  assert.deepEqual(option.series[0].data.map(point => point.rawValue), [45, 12]);
  assert.equal(option.yAxis.inverse, false);
});

test('builds a printable graph sheet with row data and branding', () => {
  const html = buildPrintableGraphSheet({
    title: 'Quality score',
    chart_type: 'bar',
    labels: ['North', 'South'],
    values_data: [82, 90]
  }, { recordName: 'CLSU Scorecard' });

  assert.match(html, /CLSU Scorecard/);
  assert.match(html, /Quality score/);
  assert.match(html, /North/);
  assert.match(html, /82/);
  assert.match(html, /window\.print/);
  assert.match(html, /class="chart-preview"/);
  assert.match(html, /aria-label="Bar chart"/);
});

test('text export contains SQL statements and graph metadata comments', () => {
  assert.match(savedGraphsSource, /export function buildTextExport/);
  assert.match(savedGraphsSource, /CREATE TABLE IF NOT EXISTS/);
  assert.match(savedGraphsSource, /INSERT INTO/);
  assert.match(savedGraphsSource, /-- Title:/);
  assert.match(savedGraphsSource, /-- Source:/);
  assert.match(savedGraphsSource, /-- Chart Type:/);
});

test('SQL file export uses the shared SQL content and SQL download type', () => {
  assert.match(savedGraphsSource, /data-mode="sql" class="export-choice-button">Export as SQL File \(\.sql\)/);
  assert.match(savedGraphsSource, /text: buildTextExport\(graphs\), mimeType: 'application\/sql'/);
  assert.match(savedGraphsSource, /\.sql`/);
});

test('saved graph publish controls work for individual and bulk actions', () => {
  assert.match(savedGraphsSource, /graph-action-button graph-action-publish/);
  assert.match(savedGraphsSource, /<span>Publish<\/span>/);
  assert.match(savedGraphsSource, /savedGraphsPublishSelected/);
  assert.match(savedGraphsSource, /publishSelectedGraphs\(selectedGraphs\)/);
  assert.match(savedGraphsSource, /approveRecords\(recordIds\)/);
  assert.match(savedGraphsSource, /Publish/);
});

test('saved graph publish helpers are exposed globally for all files', () => {
  assert.match(savedGraphsSource, /window\.SavedGraphsTab|window\.GraphExport/);
  assert.match(savedGraphsSource, /publishSelectedGraphs\s*[:=]/);
  assert.match(savedGraphsSource, /publishSavedGraphs|publishSelectedGraphs/);
});

test('public dashboard includes a manual summary card snapshot section and admin card controls', () => {
  const dashboardSource = fs.readFileSync(path.join(__dirname, '..', '..', 'user', 'dashboard.php'), 'utf8');
  const adminSource = fs.readFileSync(path.join(__dirname, '..', '..', 'admin', 'review_editor.php'), 'utf8');
  assert.match(dashboardSource, /Latest Performance Snapshot/i);
  assert.match(dashboardSource, /summary-card|snapshot-cards/i);
  assert.match(adminSource, /summary card|summary-card|summaryCards/i);
  assert.match(dashboardSource, /No decimals/);
  assert.match(dashboardSource, /1 decimal/);
  assert.match(dashboardSource, /2 decimals/);
  assert.doesNotMatch(dashboardSource, /0 decimals/);
  assert.match(adminSource, /No decimals/);
  assert.match(adminSource, /1 decimal/);
  assert.match(adminSource, /2 decimals/);
  assert.doesNotMatch(adminSource, /0 decimals/);
});

test('public removal hides charts without deleting the saved graph record', () => {
  const dashboardSource = fs.readFileSync(path.join(__dirname, '..', '..', 'user', 'dashboard.php'), 'utf8');
  const apiSource = fs.readFileSync(path.join(__dirname, '..', '..', 'api', 'iris.php'), 'utf8');
  assert.match(dashboardSource, /Unpublish|Hide this published chart from the Observatory/);
  assert.match(dashboardSource, /action=unpublish/);
  assert.match(apiSource, /action\s*===\s*'unpublish'|action\s*===\s*"unpublish"/);
});

test('record approval does not auto-publish every saved graph for that record', () => {
  const apiSource = fs.readFileSync(path.join(__dirname, '..', '..', 'api', 'iris.php'), 'utf8');
  assert.doesNotMatch(apiSource, /UPDATE saved_graphs SET is_published = 1 WHERE record_id IN/);
  assert.doesNotMatch(apiSource, /UPDATE saved_graphs SET is_published = \? WHERE record_id = \?/);
});

test('builds a printable pie chart preview before the data table', () => {
  const html = buildPrintableGraphSheet({
    title: 'Distribution',
    chart_type: 'pie',
    labels: ['North', 'South'],
    values_data: [60, 40]
  });

  assert.match(html, /aria-label="pie chart"/);
  assert.ok(html.indexOf('chart-preview') < html.indexOf('<table>'));
});

test('renders polar-area print sheets as a dedicated polar chart instead of a pie slice layout', () => {
  const html = buildPrintableGraphSheet({
    title: 'Regional spread',
    chart_type: 'polarArea',
    labels: ['North', 'South', 'West'],
    values_data: [18, 42, 30]
  });

  assert.match(html, /Chart Type: POLARAREA/);
  assert.match(html, /aria-label="Polar Area chart"/);
  assert.doesNotMatch(html, /aria-label="pie chart"/);
});

test('studio chart previews use the same decimal precision control as summary cards', () => {
  const html = fs.readFileSync(path.join(__dirname, '..', 'index.php'), 'utf8');
  const engine = fs.readFileSync(path.join(__dirname, '..', 'js', 'modules', 'chartEngine.js'), 'utf8');
  assert.match(html, /studioValuePrecisionSelect|Display Precision/i);
  assert.match(html, /No decimals|1 decimal|2 decimals/i);
  assert.match(engine, /formatChartValueForDisplay|displayPrecision/);
});

test('axis controls are structural and no longer user-facing', () => {
  const app = fs.readFileSync(path.join(__dirname, '..', 'js', 'app.js'), 'utf8');
  const html = fs.readFileSync(path.join(__dirname, '..', 'index.php'), 'utf8');
  assert.equal(app.includes('studioBtnSwapAxes'), false);
  assert.equal(html.includes('studioBtnSwapAxes'), false);
  assert.equal(app.includes('axis-toggle-btn'), false);
  assert.equal(html.includes('studioLabelColSelect'), false);
  assert.equal(html.includes('studioValueColSelect'), false);
  assert.match(app, /ChartMapping\.inferColumns/);
  assert.match(app, /xAxis: isCircular \? undefined : \{ type: 'category', name: 'Rows'/);
  assert.match(app, /yAxis: isCircular \? undefined : \{ type: 'value', name: headerName/);
});
