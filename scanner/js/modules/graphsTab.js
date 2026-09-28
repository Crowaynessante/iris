import { $, all, escapeHtml } from '../utils/helpers.js';
import { createChart } from './chartEngine.js';
import { downloadText, showExportChoice } from './savedGraphsTab.js';

function draftText(graph) {
  const labels = JSON.stringify(graph.labels || graph.chartData?.labels || []);
  const values = JSON.stringify(graph.values_data || graph.chartData?.datasets?.[0]?.data || []);
  const parsedLabels = JSON.parse(labels);
  const parsedValues = JSON.parse(values);
  return [`Title: ${graph.title || 'Saved Chart'}`, `Chart Type: ${(graph.chart_type || graph.primaryType || 'bar').toUpperCase()}`, `Source Record ID: ${graph.record_id || ''}`, '', 'Category: Value', ...parsedLabels.map((label, index) => `${label || `Item ${index + 1}`}: ${parsedValues[index] ?? ''}`)].join('\n');
}

export function initGraphsTab(ctx) {
  ctx.api.renderGraphsTab = async scan => {
    const container = $('graphDraftsContainer');
    container.querySelectorAll('.graph-card').forEach(card => { card._chartResizeObserver?.disconnect?.(); card._chart?.dispose?.(); card._chart?.destroy?.(); });
    container.innerHTML = '';
    Object.values(ctx.state.chartInstances).forEach(chart => { chart?.dispose?.(); chart?.destroy?.(); });
    ctx.state.chartInstances = {};
    const savedGraphs = scan.id ? await ctx.dbManager.getGraphsByRecord(scan.id) : [];
    const drafts = savedGraphs.length > 0 ? savedGraphs : (scan.graphDrafts || []);
    scan.graphDrafts = drafts;

    if (!drafts.length) {
      container.innerHTML = '<div style="color:var(--text-muted);padding:2rem;text-align:center">No numerical series detected to build chart drafts.</div>';
      return;
    }

    drafts.forEach((draft, index) => {
      const canvasId = `chart_canvas_${index}`;
      const card = document.createElement('div');
      card.className = 'graph-card';
      const selectedType = draft.chart_type || draft.primaryType || 'bar';
      // <button class="export-draft-mysql" type="button" title="Export to MySQL" aria-label="Export to MySQL">/* */</button>
      card.innerHTML = `<div class="graph-card-header"><div><div class="graph-card-title">${escapeHtml(draft.title)}</div><div style="font-size:.78rem;color:var(--text-muted);margin-top:.25rem">Source: ${escapeHtml(draft.source)}</div></div><div><button class="export-draft-print" type="button"><i class="fa-solid fa-print" aria-hidden="true"></i> Print Sheet</button><select class="form-input chart-type-select" data-draft-idx="${index}" style="width:auto;padding:.25rem .5rem;font-size:.8rem"><option value="bar" ${selectedType === 'bar' ? 'selected' : ''}>Bar Chart</option><option value="year_ranking" ${selectedType === 'year_ranking' ? 'selected' : ''}>Year Ranking</option><option value="line" ${selectedType === 'line' ? 'selected' : ''}>Line Chart</option><option value="pie" ${selectedType === 'pie' ? 'selected' : ''}>Pie Chart</option></select></div></div><div style="font-size:.82rem;color:var(--accent-cyan);margin-bottom:1rem"><i class="fa-solid fa-lightbulb" aria-hidden="true"></i> <strong>AI Recommendation:</strong> ${escapeHtml(draft.recommendation)}</div><div class="graph-canvas-container" style="height:320px;position:relative"><canvas id="${canvasId}"></canvas></div>`;
      container.appendChild(card);
      if (selectedType === 'year_ranking') {
        const host = document.createElement('div');
        host.id = canvasId;
        host.style.cssText = 'width:100%;height:100%;';
        card.querySelector(`#${canvasId}`)?.replaceWith(host);
      }

      card.querySelector('.export-draft-print').onclick = () => ctx.dbManager.printGraphSheet(draft, { recordName: scan.name || 'IRIS report' });
      const render = () => {
        const host = $(canvasId);
        if (!host) return;
        card._chartResizeObserver?.disconnect?.();
        card._chart?.dispose?.();
        card._chart?.destroy?.();
        if ((draft.chart_type || draft.primaryType) === 'year_ranking' && window.echarts && window.ChartMapping) {
          const savedOptions = draft.chartOptions || draft.chartData?.chartOptions || {};
          const labels = draft.chartData?.labels || [];
          const values = draft.chartData?.datasets?.[0]?.data || [];
          card._chart = window.echarts.init(host);
          card._chart.setOption(window.ChartMapping.buildYearRankingOption({
            measureName: savedOptions.measureName || draft.chartData?.datasets?.[0]?.label || 'Value',
            rows: labels.map((label, valueIndex) => ({ label, value: values[valueIndex] })).filter(row => row.value !== null && row.value !== undefined && String(row.value).trim() !== ''),
            rankSemantic: draft.rankSemantic === true || draft.chartData?.rankSemantic === true,
            preserveOrder: true,
            showCumulativeLine: savedOptions.showCumulativeLine !== false,
            show80Reference: savedOptions.show80Reference !== false,
            showBarValueLabels: savedOptions.showBarValueLabels !== false,
            isDark: document.documentElement.classList.contains('dark'),
            width: host.clientWidth
          }));
          if (typeof ResizeObserver !== 'undefined') {
            card._chartResizeObserver = new ResizeObserver(() => card._chart?.resize?.());
            card._chartResizeObserver.observe(host);
          }
        } else {
          card._chart = createChart(host, draft.chart_type || draft.primaryType || selectedType, draft.chartData);
        }
        ctx.state.chartInstances[canvasId] = card._chart;
      };
      card._renderChart = render;
      setTimeout(render, 50);
    });

    all('.chart-type-select').forEach(select => select.addEventListener('change', event => {
      const index = Number(event.target.dataset.draftIdx);
      drafts[index].primaryType = event.target.value;
      drafts[index].chart_type = event.target.value;
      const id = `chart_canvas_${index}`;
      const card = event.target.closest('.graph-card');
      ctx.state.chartInstances[id]?.dispose?.();
      ctx.state.chartInstances[id]?.destroy?.();
      if (event.target.value === 'year_ranking' && card) {
        const host = document.createElement('div');
        host.id = id;
        host.style.cssText = 'width:100%;height:100%;';
        card.querySelector(`#${id}`)?.replaceWith(host);
      } else if (card && !card.querySelector(`#${id}`)?.matches('canvas')) {
        const canvas = document.createElement('canvas');
        canvas.id = id;
        card.querySelector(`#${id}`)?.replaceWith(canvas);
      }
      card?._renderChart?.();
    }));
  };
  new MutationObserver(() => { if (ctx.state.activeScan) ctx.api.renderGraphsTab(ctx.state.activeScan); }).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
}
