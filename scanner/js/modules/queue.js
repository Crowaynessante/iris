import { $, formatFileSize } from '../utils/helpers.js';

export function initQueue(ctx) {
  const list = $('queueList'); const count = $('queueCount'); const workspace = $('workspaceGrid');

  const removeQueueItem = itemId => {
    const index = ctx.state.queue.findIndex(item => item.id === itemId);
    if (index === -1) return;

    const [removedItem] = ctx.state.queue.splice(index, 1);
    if (ctx.state.activeScan?.id === removedItem.id) {
      ctx.state.activeScan = ctx.state.queue[0] || null;
      if (!ctx.state.activeScan && workspace) {
        workspace.style.display = 'none';
      }
    }

    ctx.api.renderQueue();
    if (ctx.state.activeScan) {
      ctx.api.setActiveScan(ctx.state.activeScan);
    }
  };

  ctx.api.renderQueue = () => {
    if (count) count.textContent = ctx.state.queue.length; if (!list) return; list.innerHTML = '';
    const icons = { excel: '<i class="fa-solid fa-chart-column" aria-hidden="true"></i>', pdf: '<i class="fa-solid fa-file-lines" aria-hidden="true"></i>', docx: '<i class="fa-solid fa-file-pen" aria-hidden="true"></i>', image: '<i class="fa-solid fa-image" aria-hidden="true"></i>', unknown: '<i class="fa-solid fa-folder" aria-hidden="true"></i>' };
    ctx.state.queue.forEach(item => {
      const element = document.createElement('div'); element.className = `queue-item ${ctx.state.activeScan?.id === item.id ? 'active' : ''}`;
      element.innerHTML = `
        <div class="queue-icon">${icons[item.type] || '<i class="fa-solid fa-folder" aria-hidden="true"></i>'}</div>
        <div class="queue-info">
          <div class="queue-name" title="${item.name}">${item.name}</div>
          <div class="queue-meta"><span>${formatFileSize(item.size)}</span><span class="queue-badge low">Draft</span></div>
        </div>
        <button class="queue-remove" type="button" aria-label="Remove ${item.name}" title="Remove this item">×</button>
      `;
      const removeButton = element.querySelector('.queue-remove');
      removeButton?.addEventListener('click', event => {
        event.stopPropagation();
        removeQueueItem(item.id);
      });
      element.addEventListener('click', () => ctx.api.setActiveScan(item)); list.appendChild(element);
    });
  };
  $('btnClearQueue')?.addEventListener('click', () => { ctx.state.queue = []; ctx.state.activeScan = null; if (workspace) workspace.style.display = 'none'; ctx.api.renderQueue(); });
}
