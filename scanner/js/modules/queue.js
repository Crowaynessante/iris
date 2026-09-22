import { $, formatFileSize } from '../utils/helpers.js';

export function initQueue(ctx) {
  const list = $('queueList'); const count = $('queueCount'); const workspace = $('workspaceGrid');
  ctx.api.renderQueue = () => {
    if (count) count.textContent = ctx.state.queue.length; if (!list) return; list.innerHTML = '';
    const icons = { excel: '<i class="fa-solid fa-chart-column" aria-hidden="true"></i>', pdf: '<i class="fa-solid fa-file-lines" aria-hidden="true"></i>', docx: '<i class="fa-solid fa-file-pen" aria-hidden="true"></i>', image: '<i class="fa-solid fa-image" aria-hidden="true"></i>', unknown: '<i class="fa-solid fa-folder" aria-hidden="true"></i>' };
    ctx.state.queue.forEach(item => {
      const element = document.createElement('div'); element.className = `queue-item ${ctx.state.activeScan?.id === item.id ? 'active' : ''}`;
      element.innerHTML = `<div class="queue-icon">${icons[item.type] || '<i class="fa-solid fa-folder" aria-hidden="true"></i>'}</div><div class="queue-info"><div class="queue-name" title="${item.name}">${item.name}</div><div class="queue-meta"><span>${formatFileSize(item.size)}</span><span class="queue-badge low">Draft</span></div></div>`;
      element.addEventListener('click', () => ctx.api.setActiveScan(item)); list.appendChild(element);
    });
  };
  $('btnClearQueue')?.addEventListener('click', () => { ctx.state.queue = []; ctx.state.activeScan = null; if (workspace) workspace.style.display = 'none'; ctx.api.renderQueue(); });
}
