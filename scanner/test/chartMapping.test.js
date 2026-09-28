const test = require('node:test');
const assert = require('node:assert/strict');
const { inferColumns, isRankField, parseNumericValue, parseRankValue, buildYearRankingOption } = require('../js/chartMapping');

test('rank detection requires an explicit rank field name', () => {
  assert.equal(isRankField('Overall Rank'), true);
  assert.equal(isRankField('Score'), false);
  assert.equal(isRankField('Value'), false);
});

test('structural chart mapping uses row labels on X and numeric column values on Y', () => {
  const mapping = inferColumns(['Year', 'Category', 'Overall Rank'], [
    ['2020', 'Teaching', '123'],
    ['2021', 'Research', '111']
  ]);
  assert.equal(mapping.labelColumn, 1);
  assert.equal(mapping.valueColumn, 2);
});

test('QS Stars formatted ratings remain plottable values', () => {
  const headers = ['Year', 'Category', 'Star Rating', 'Score Rating', 'Validity'];
  const rows = [
    [2020, 'Teaching', '5 stars', '123/150', '2020-2023'],
    [2020, 'Employability', '5 stars', '111/150', '2020-2023']
  ];
  const mapping = inferColumns(headers, rows);
  assert.equal(mapping.labelColumn, 1);
  assert.equal(mapping.valueColumn, 3);
  assert.equal(parseNumericValue('123/150'), 123);
  assert.equal(parseNumericValue('5 stars'), 5);
});

test('rank ranges use their representative midpoint', () => {
  assert.equal(parseRankValue('801-1000'), 900.5);
  assert.equal(parseRankValue('601-800'), 700.5);
  assert.equal(parseRankValue('200'), 200);
});

test('Year Ranking uses sorted bars, paired axes, and cumulative percentages', () => {
  const values = [90, 90, 80, 80, 70, 65, 60, 55, 50, 45];
  const option = buildYearRankingOption({
    measureName: 'Enrollment',
    rows: values.map((value, index) => ({ label: `Year ${index + 1}`, value }))
  });

  assert.deepEqual(option.xAxis.map(axis => [axis.position, axis.min, axis.max]), [['top', 0, 90], ['bottom', 0, 100]]);
  assert.equal(option.xAxis[0].splitLine.show, false);
  assert.equal(option.xAxis[1].splitLine.show, true);
  assert.deepEqual(option.legend.data, ['Enrollment', 'Cumulative']);
  assert.deepEqual(option.series[0].data.map(point => point.rawValue), values);
  assert.deepEqual(option.series[1].data, [13.14, 26.28, 37.96, 49.64, 59.85, 69.34, 78.1, 86.13, 93.43, 100]);
  assert.equal(option.series[1].markLine.data.length, 2);
  assert.equal(option.series[1].markLine.data[0][0].xAxis, 80);
  assert.equal(option.series[1].markLine.data[1][0].yAxis, 'Year 8');
});

test('Year Ranking omits cumulative series for zero totals and negative values', () => {
  const zeroOption = buildYearRankingOption({ measureName: 'Value', rows: [{ label: 'A', value: 0 }] });
  const negativeOption = buildYearRankingOption({ measureName: 'Value', rows: [{ label: 'A', value: -1 }, { label: 'B', value: 2 }] });

  assert.equal(zeroOption.series.length, 1);
  assert.match(zeroOption.title.subtext, /total is 0/);
  assert.equal(negativeOption.series.length, 1);
  assert.match(negativeOption.title.subtext, /negative/);
});

test('Year Ranking uses shared rank semantics and displays original ranks', () => {
  const option = buildYearRankingOption({ measureName: 'Overall Rank', rows: [{ label: 'A', value: 1 }, { label: 'B', value: 5 }] });

  assert.deepEqual(option.series[0].data.map(point => point.value), [4, 0]);
  assert.equal(option.series[0].data[0].rawValue, 1);
  assert.equal(option.series[0].label.formatter({ data: option.series[0].data[0], value: 4 }), '1');
  assert.equal(option.xAxis[0].axisLabel.formatter(4), '1');
  assert.equal(option.series.length, 1);
  assert.match(option.title.subtext, /isn't meaningful for rank values/);
});

test('chart adapters bind line and bar categories to source labels', () => {
  const source = require('node:fs').readFileSync(require('node:path').join(__dirname, '..', 'js', 'modules', 'chartEngine.js'), 'utf8');
  assert.match(source, /const categoryScale = \{ type: 'category', labels: data\.labels/);
  assert.match(source, /x: horizontal \? valueScale : categoryScale/);
  assert.match(source, /y: horizontal \? \{ \.\.\.categoryScale, labels: data\.labels \} : valueScale/);
});