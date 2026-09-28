const test = require('node:test');
const assert = require('node:assert/strict');
const GraphEngine = require('../js/ai/graphEngine');

function makeSheet(headers, rows) {
  return { type: 'excel', name: 'metrics.xlsx', sheetsData: { Metrics: { headers, rows } } };
}

test('category and numeric values produce ECharts bar options using source values', () => {
  const [draft] = new GraphEngine().generateGraphDrafts(makeSheet(
    ['Department', 'Enrollment'],
    [['Science', 12], ['Arts', 35], ['Business', 21]]
  ));

  assert.equal(draft.primaryType, 'bar');
  assert.deepEqual(draft.chartData.xAxis, { type: 'category', data: ['Science', 'Arts', 'Business'], axisLabel: { interval: 0 } });
  assert.deepEqual(draft.chartData.series[0].data, [12, 35, 21]);
  assert.equal(draft.chartData.series[0].type, 'bar');
  assert.equal(draft.chartData.yAxis.type, 'value');
  assert.equal('datasets' in draft.chartData, false);
});

test('year values with numeric measures recommend a chronological line chart', () => {
  const [draft] = new GraphEngine().generateGraphDrafts(makeSheet(
    ['Year', 'Score'],
    [[2022, 71], [2020, 63], [2021, 68]]
  ));

  assert.equal(draft.primaryType, 'line');
  assert.deepEqual(draft.chartData.xAxis.data, ['2020', '2021', '2022']);
  assert.deepEqual(draft.chartData.series[0].data, [63, 68, 71]);
});

test('actual date values are detected and sorted even under a non-temporal header', () => {
  const [draft] = new GraphEngine().generateGraphDrafts(makeSheet(
    ['Checkpoint', 'Value'],
    [[new Date(2024, 2, 1), 30], [new Date(2024, 0, 1), 10], [new Date(2024, 1, 1), 20]]
  ));

  assert.equal(draft.primaryType, 'line');
  assert.deepEqual(draft.chartData.xAxis.data, ['2024-01-01', '2024-02-01', '2024-03-01']);
  assert.deepEqual(draft.chartData.series[0].data, [10, 20, 30]);
});

test('proportional values summing to a whole recommend pie without percent headers', () => {
  const [draft] = new GraphEngine().generateGraphDrafts(makeSheet(
    ['Faculty', 'Share'],
    [['Science', 25], ['Arts', 35], ['Business', 40]]
  ));

  assert.equal(draft.primaryType, 'pie');
  assert.equal(draft.chartData.series[0].type, 'pie');
  assert.deepEqual(draft.chartData.series[0].data, [
    { name: 'Science', value: 25 },
    { name: 'Arts', value: 35 },
    { name: 'Business', value: 40 }
  ]);
  assert.match(draft.chartData.tooltip.formatter, /\{d\}%/);
});

test('doughnut and polar area are valid Apache ECharts configurations', () => {
  const engine = new GraphEngine();
  const doughnut = engine.buildEChartsOption('doughnut', ['A', 'B'], [4, 6], 'Count');
  const polarArea = engine.buildEChartsOption('polarArea', ['A', 'B'], [4, 6], 'Count');

  assert.equal(doughnut.series[0].type, 'pie');
  assert.deepEqual(doughnut.series[0].radius, ['45%', '72%']);
  assert.equal(polarArea.series[0].type, 'bar');
  assert.equal(polarArea.series[0].coordinateSystem, 'polar');
  assert.deepEqual(polarArea.angleAxis.data, ['A', 'B']);
  assert.deepEqual(polarArea.series[0].data, [4, 6]);
});

test('separate numerical columns produce separate drafts and invalid cells are omitted', () => {
  const drafts = new GraphEngine().generateGraphDrafts(makeSheet(
    ['Category', 'Applications', 'Admissions'],
    [['A', 10, 4], ['B', '', 3], ['C', 30, 'bad'], ['D', null, 8]]
  ));

  assert.equal(drafts.length, 2);
  assert.deepEqual(drafts[0].chartData.series[0].data, [10, 30]);
  assert.deepEqual(drafts[0].chartData.xAxis.data, ['A', 'C']);
  assert.deepEqual(drafts[1].chartData.series[0].data, [4, 3, 8]);
  assert.deepEqual(drafts[1].chartData.xAxis.data, ['A', 'B', 'D']);
});

test('identifier columns are not treated as numerical metrics', () => {
  const [draft] = new GraphEngine().generateGraphDrafts(makeSheet(
    ['Student ID', 'Code', 'Category', 'Score'],
    [[10001, 90001, 'North', 7], [10002, 90002, 'South', 8], [10003, 90003, 'West', 9]]
  ));

  assert.equal(draft.title.startsWith('Score'), true);
  assert.deepEqual(draft.chartData.series[0].data, [7, 8, 9]);
});

test('all parser-provided rows are used instead of a 15-row sample', () => {
  const rows = Array.from({ length: 40 }, (_, index) => [`Category ${index + 1}`, index + 1]);
  const [draft] = new GraphEngine().generateGraphDrafts(makeSheet(['Category', 'Value'], rows));

  assert.equal(draft.chartData.series[0].data.length, 40);
  assert.equal(draft.chartData.series[0].data[39], 40);
});

test('Excel sheets without usable numerical data produce no invented analytics', () => {
  const drafts = new GraphEngine().generateGraphDrafts(makeSheet(
    ['Student ID', 'Name', 'Code'],
    [[10001, 'A', 70001], [10002, 'B', 70002]]
  ));

  assert.deepEqual(drafts, []);
});

test('Excel graph generation never emits Year Ranking', () => {
  const drafts = new GraphEngine().generateGraphDrafts(makeSheet(
    ['Year', 'Enrollment'],
    [[2022, 20], [2023, 30]]
  ));

  assert.ok(drafts.every(draft => draft.primaryType !== 'year_ranking'));
  assert.ok(drafts.every(draft => !JSON.stringify(draft.chartData).includes('Cumulative')));
});
