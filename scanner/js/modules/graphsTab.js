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
    container.innerHTML = '';
    Object.values(ctx.state.chartInstances).forEach(chart => chart?.destroy?.());
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
      card.innerHTML = `<div class="graph-card-header"><div><div class="graph-card-title">${escapeHtml(draft.title)}</div><div style="font-size:.78rem;color:var(--text-muted);margin-top:.25rem">Source: ${escapeHtml(draft.source)}</div></div><div><button class="export-draft-mysql" type="button"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Export to MySQL</button><button class="export-draft-print" type="button"><i class="fa-solid fa-print" aria-hidden="true"></i> Print Sheet</button><select class="form-input chart-type-select" data-draft-idx="${index}" style="width:auto;padding:.25rem .5rem;font-size:.8rem"><option value="bar" ${selectedType === 'bar' ? 'selected' : ''}>Bar Chart</option><option value="line" ${selectedType === 'line' ? 'selected' : ''}>Line Chart</option><option value="pie" ${selectedType === 'pie' ? 'selected' : ''}>Pie Chart</option></select></div></div><div style="font-size:.82rem;color:var(--accent-cyan);margin-bottom:1rem"><i class="fa-solid fa-lightbulb" aria-hidden="true"></i> <strong>AI Recommendation:</strong> ${escapeHtml(draft.recommendation)}</div><div class="graph-canvas-container" style="height:320px;position:relative"><canvas id="${canvasId}"></canvas></div>`;
      container.appendChild(card);

      card.querySelector('.export-draft-mysql').onclick = () => showExportChoice(async mode => {
        if (mode === 'database') {
          const confirmed = window.confirm(
            'Publish this chart to the Observatory Page? It will be written to live MySQL and visible to all registered users.'
          );
          if (!confirmed) return;

          try {
            await ctx.dbManager.approveRecords([scan.id]);
            await ctx.dbManager.exportGraph(window.GraphExport.normalizeGraphExportItem(draft, scan.id), scan.id);
            alert('Chart published to the Observatory Page and is now visible to registered users.');
          } catch (error) {
            alert(`Unable to publish chart: ${error.message}`);
            return;
          }
        } else {
          await downloadText({ fileName: `iris_draft_${Date.now()}.txt`, text: draftText({ ...draft, record_id: scan.id }) });
          alert('1 graph exported as a text file.');
        }
        await ctx.api.renderSavedGraphsTab();
      });
      card.querySelector('.export-draft-print').onclick = () => ctx.dbManager.printGraphSheet(draft, { recordName: scan.name || 'IRIS report' });
      setTimeout(() => { const canvas = $(canvasId); if (canvas) ctx.state.chartInstances[canvasId] = createChart(canvas, selectedType, draft.chartData); }, 50);
    });

    all('.chart-type-select').forEach(select => select.addEventListener('change', event => {
      const index = Number(event.target.dataset.draftIdx);
      drafts[index].primaryType = event.target.value;
      drafts[index].chart_type = event.target.value;
      const id = `chart_canvas_${index}`;
      ctx.state.chartInstances[id]?.destroy?.();
      const canvas = $(id);
      if (canvas) ctx.state.chartInstances[id] = createChart(canvas, event.target.value, drafts[index].chartData);
    }));
  };
}
