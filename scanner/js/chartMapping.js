(function (root, factory) {
  if (typeof module === 'object' && module.exports) {
    module.exports = factory();
  } else {
    root.ChartMapping = factory();
  }
})(typeof self !== 'undefined' ? self : this, function () {
  function isNumeric(value) {
    return parseNumericValue(value) !== null;
  }

  function isRankField(header) {
    return /\brank\b/i.test(String(header || ''));
  }

  function parseRankValue(value) {
    if (typeof value === 'number') return Number.isFinite(value) ? value : null;
    const text = String(value ?? '').trim().replace(/,/g, '');
    const range = text.match(/^(\d+(?:\.\d+)?)\s*-\s*(\d+(?:\.\d+)?)$/);
    if (range) return (Number(range[1]) + Number(range[2])) / 2;
    return parseNumericValue(text);
  }

  function parseNumericValue(value) {
    if (typeof value === 'number') return Number.isFinite(value) ? value : null;
    if (value === null || value === undefined) return null;
    const text = String(value).trim().replace(/,/g, '');
    if (!text) return null;
    const match = text.match(/^[-+]?\d+(?:\.\d+)?/);
    if (!match) return null;
    const parsed = Number(match[0]);
    return Number.isFinite(parsed) ? parsed : null;
  }

  /**
   * Classify each column and return best default label/value columns plus full metadata.
   * Returns { labelColumn, valueColumn, numericColumns, labelColumns, columnTypes }
   */
  function inferColumns(headers, rows) {
    const safeHeaders = headers || [];
    const safeRows = rows || [];
    let labelColumn = safeHeaders.length > 0 ? 0 : -1;
    let valueColumn = safeHeaders.length > 1 ? 1 : 0;
    const numericColumns = [];
    const labelColumns = [];
    const columnTypes = {}; // colIdx -> 'numeric' | 'text' | 'mixed'

    for (let column = 0; column < safeHeaders.length; column++) {
      const values = safeRows
        .map(row => row && row[column])
        .filter(value => value !== null && value !== undefined && String(value).trim() !== '');
      if (values.length === 0) { columnTypes[column] = 'mixed'; continue; }
      const numericCount = values.filter(isNumeric).length;
      const ratio = numericCount / values.length;
      if (ratio >= 0.7) {
        numericColumns.push(column);
        columnTypes[column] = 'numeric';
      } else if (ratio <= 0.3) {
        labelColumns.push(column);
        columnTypes[column] = 'text';
      } else {
        columnTypes[column] = 'mixed';
      }
    }

    const metricHeader = /rank|score|value|amount|count|total|rate|percent|percentage|points|metric/i;
    valueColumn =
      numericColumns.find(column => metricHeader.test(String(safeHeaders[column]))) ||
      numericColumns[numericColumns.length - 1] ||
      valueColumn;

    // Prefer text columns as label
    for (let column = 0; column < safeHeaders.length; column++) {
      if (column !== valueColumn && columnTypes[column] === 'text') {
        labelColumn = column;
        break;
      }
    }
    // fallback: first column that isn't valueColumn
    if (labelColumn === valueColumn) {
      for (let column = 0; column < safeHeaders.length; column++) {
        if (column !== valueColumn) { labelColumn = column; break; }
      }
    }

    return { labelColumn, valueColumn, numericColumns, labelColumns, columnTypes };
  }

  function buildYearRankingOption(config = {}) {
    const measureName = String(config.measureName || 'Value');
    const rankSemantic = config.rankSemantic === true || isRankField(measureName);
    const showCumulativeLine = config.showCumulativeLine !== false && !rankSemantic;
    const showReference = config.show80Reference !== false;
    const showBarLabels = config.showBarValueLabels !== false;
    const isDark = config.isDark === true;
    const compact = Number(config.width) > 0 && Number(config.width) < 520;
    const rows = (Array.isArray(config.rows) ? config.rows : [])
      .map((row, index) => ({
        label: String(row?.label ?? `Item ${index + 1}`),
        value: row?.value === null || row?.value === undefined || String(row.value).trim() === ''
          ? NaN
          : Number(String(row.value).replace(/,/g, ''))
      }))
      .filter(row => Number.isFinite(row.value));
    if (config.preserveOrder !== true) rows.sort((left, right) => rankSemantic ? left.value - right.value : right.value - left.value);
    const labels = rows.map(row => row.label);
    const rawValues = rows.map(row => row.value);
    const total = rawValues.reduce((sum, value) => sum + value, 0);
    const hasNegativeValues = rawValues.some(value => value < 0);
    const hasUsableCumulative = !rankSemantic && !hasNegativeValues && total !== 0;
    const lineVisible = showCumulativeLine && hasUsableCumulative;
    const rankMaximum = rankSemantic ? Math.max(...rawValues, 0) : 0;
    const plottedValues = rankSemantic ? rawValues.map(value => rankMaximum - value) : rawValues;
    const plottedMaximum = Math.max(...plottedValues, 0);
    let runningTotal = 0;
    const cumulative = rawValues.map((value, index) => {
      runningTotal += value;
      return index === rawValues.length - 1 ? 100 : Number((runningTotal / total * 100).toFixed(2));
    });
    const referenceIndex = cumulative.findIndex(value => value >= 80);
    const notice = rankSemantic
      ? "Cumulative view isn't meaningful for rank values"
      : hasNegativeValues
        ? 'Cumulative view is unavailable when values are negative.'
        : total === 0 && rawValues.length
          ? 'Cumulative view is unavailable because the total is 0.'
          : '';
    const colors = isDark
      ? { text: '#E5E7EB', muted: '#A7B0BC', grid: 'rgba(229, 231, 235, 0.16)', bar: '#35B86A', line: '#F0C44A', area: 'rgba(53, 184, 106, 0.16)', reference: '#9CA3AF' }
      : { text: '#1F2937', muted: '#596579', grid: 'rgba(31, 41, 55, 0.12)', bar: '#009639', line: '#E0A70D', area: 'rgba(0, 150, 57, 0.12)', reference: '#6A6A6A' };
    const referenceMarkLine = lineVisible && showReference && referenceIndex >= 0 ? {
      symbol: 'none',
      silent: true,
      lineStyle: { color: colors.reference, width: 1, type: 'dashed' },
      label: { show: false },
      data: [
        [{ xAxis: 80, yAxis: labels[0] }, { xAxis: 80, yAxis: labels[labels.length - 1] }],
        [{ xAxis: 0, yAxis: labels[referenceIndex] }, { xAxis: 100, yAxis: labels[referenceIndex] }]
      ]
    } : undefined;
    const axisFormatter = rankSemantic ? value => String(rankMaximum - Number(value)) : undefined;
    const barData = rows.map((row, index) => ({ value: plottedValues[index], rawValue: row.value }));
    const cumulativeSeries = {
      name: 'Cumulative',
      type: 'line',
      xAxisIndex: 1,
      yAxisIndex: 0,
      encode: { x: 0, y: 1 },
      data: cumulative.map((value, index) => ({
        name: labels[index],
        value: [value, labels[index]],
        cumulativePercent: value
      })),
      symbol: 'circle',
      symbolSize: compact ? 6 : 8,
      smooth: 0.2,
      lineStyle: { color: colors.line, width: 2.5 },
      itemStyle: { color: colors.line },
      areaStyle: { color: colors.area },
      label: { show: lineVisible, position: 'right', color: colors.text, fontSize: compact ? 9 : 10, formatter: params => `${Math.round(params.data.cumulativePercent)}%` },
      labelLayout: { hideOverlap: true },
      markLine: referenceMarkLine
    };
    const option = {
      animationDuration: 250,
      title: notice ? { subtext: notice, left: 'center', bottom: 34, subtextStyle: { color: colors.muted, fontSize: 11 } } : undefined,
      color: [colors.bar, colors.line],
      tooltip: {
        trigger: 'axis',
        axisPointer: { type: 'shadow' },
        formatter: params => {
          const index = Array.isArray(params) ? params[0]?.dataIndex : params?.dataIndex;
          const entries = [`<b>${labels[index] ?? ''}</b>`, `${measureName}: <b>${rawValues[index] ?? ''}</b>`];
          if (lineVisible) entries.push(`Cumulative: <b>${cumulative[index]}%</b>`);
          return entries.join('<br/>');
        }
      },
      legend: { show: true, data: lineVisible ? [measureName, 'Cumulative'] : [measureName], top: 4, textStyle: { color: colors.text, fontSize: 11 } },
      grid: { left: compact ? 64 : 84, right: compact ? 40 : 58, top: 56, bottom: lineVisible ? 74 : 52, containLabel: false },
      xAxis: [
        {
          type: 'value',
          position: 'top',
          min: 0,
          max: plottedMaximum,
          splitNumber: 5,
          axisLabel: { color: colors.text, fontSize: compact ? 9 : 10, formatter: axisFormatter },
          axisLine: { lineStyle: { color: colors.grid } },
          splitLine: { show: false }
        },
        {
          type: 'value',
          position: 'bottom',
          min: 0,
          max: 100,
          interval: 20,
          axisLabel: { color: colors.text, fontSize: compact ? 9 : 10, formatter: '{value}%' },
          axisLine: { lineStyle: { color: colors.grid } },
          splitLine: { show: true, lineStyle: { color: colors.grid, type: 'dashed' } }
        }
      ],
      yAxis: {
        type: 'category',
        data: labels,
        inverse: true,
        axisTick: { show: false },
        axisLine: { lineStyle: { color: colors.grid } },
        axisLabel: { color: colors.text, fontSize: compact ? 9 : 11, width: compact ? 84 : 140, overflow: 'truncate', interval: labels.length > 14 ? Math.ceil(labels.length / 14) - 1 : 0 }
      },
      series: [
        {
          name: measureName,
          type: 'bar',
          xAxisIndex: 0,
          yAxisIndex: 0,
          data: barData,
          barMaxWidth: 24,
          itemStyle: { color: colors.bar, borderRadius: [0, 3, 3, 0] },
          label: {
            show: showBarLabels,
            position: 'insideLeft',
            color: '#FFFFFF',
            fontSize: compact ? 9 : 10,
            formatter: params => String(params.data?.rawValue ?? params.value)
          },
          labelLayout: { hideOverlap: true }
        },
        ...(lineVisible ? [cumulativeSeries] : [])
      ]
    };
    return option;
  }

  return { inferColumns, isNumeric, isRankField, parseRankValue, parseNumericValue, buildYearRankingOption };
});
