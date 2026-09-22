import { $, all } from '../utils/helpers.js';

export function initAdminPortal(ctx) {
  const selectedRecordIds = new Set();

  const escape = value => String(value ?? '').replace(/[&<>"']/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[ch]));
  const showToast = message => {
    const toast = document.createElement('div');
    toast.className = 'pdf-copy-toast visible';
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
  };

  const updateBulkActions = () => {
    const bar = $('adminBulkActions');
    const count = $('adminBulkSelectionCount');
    const deleteBtn = $('adminBulkDelete');
    const approveBtn = $('adminBulkApprove');
    const n = selectedRecordIds.size;
    if (bar) bar.hidden = n === 0;
    if (count) count.textContent = `${n} record${n === 1 ? '' : 's'} selected`;
    if (deleteBtn) deleteBtn.disabled = n === 0;
    if (approveBtn) approveBtn.disabled = n === 0;
  };

  const syncSelectAll = () => {
    const boxes = [...document.querySelectorAll('#adminRecordsTableBody .admin-record-checkbox')];
    const selectAll = $('adminSelectAll');
    if (!selectAll) return;
    const visibleIds = boxes.map(box => box.dataset.id).filter(Boolean);
    selectAll.checked = visibleIds.length > 0 && visibleIds.every(id => selectedRecordIds.has(id));
    selectAll.indeterminate = visibleIds.some(id => selectedRecordIds.has(id)) && !selectAll.checked;
  };

  const bindSelection = () => {
    document.querySelectorAll('#adminRecordsTableBody .admin-record-checkbox').forEach(box => {
      box.checked = selectedRecordIds.has(box.dataset.id);
      box.onchange = () => {
        if (box.checked) selectedRecordIds.add(box.dataset.id);
        else selectedRecordIds.delete(box.dataset.id);
        updateBulkActions();
        syncSelectAll();
      };
    });
    const selectAll = $('adminSelectAll');
    if (selectAll) selectAll.onchange = () => {
      document.querySelectorAll('#adminRecordsTableBody .admin-record-checkbox').forEach(box => {
        if (selectAll.checked) selectedRecordIds.add(box.dataset.id);
        else selectedRecordIds.delete(box.dataset.id);
        box.checked = selectAll.checked;
      });
      updateBulkActions();
      syncSelectAll();
    };
    updateBulkActions();
    syncSelectAll();
  };

  const renderRows = filtered => {
    const body = $('adminRecordsTableBody');
    if (!body) return;
    const html = filtered.map(record => {
      const scannedDate = record.scannedAt || (() => {
        const match = String(record.id || '').match(/^scan_(\d+)_/);
        return match ? new Date(Number(match[1])).toISOString() : '';
      })();
      const status = record.status || 'Pending Review';
      const approved = status === 'Approved';
      return `<tr>
        <td><input class="admin-record-checkbox" type="checkbox" data-id="${escape(record.id)}" aria-label="Select record ${escape(record.id)}"></td>
        <td>${escape(record.id)}</td>
        <td>${escape(record.fileName || 'Untitled')}</td>
        <td>${escape((record.fileType || 'UNKNOWN').toUpperCase())}</td>
        <td><span class="badge">${escape(status)}</span></td>
        <td>${scannedDate ? escape(new Date(scannedDate).toLocaleString()) : 'N/A'}</td>
        <td><div style="display:flex;align-items:center;gap:.5rem;white-space:nowrap;">
          <button class="archive-load-button btn-table-load-studio" data-id="${escape(record.id)}"><i class="fa-solid fa-palette" aria-hidden="true"></i> Review</button>
          ${approved ? '<span class="badge" style="font-weight:800;"><i class="fa-solid fa-check" aria-hidden="true"></i> Approved</span>' : `<button class="archive-load-button btn-table-approve" data-id="${escape(record.id)}"><i class="fa-solid fa-circle-check" aria-hidden="true"></i> Approve</button>`}
          <button class="archive-delete-button btn-table-delete" data-id="${escape(record.id)}" title="Delete record"><i class="fa-solid fa-trash" aria-hidden="true"></i> Delete</button>
        </div></td>
      </tr>`;
    }).join('');
    body.innerHTML = html || '<tr><td colspan="7">No matching scanned records in database.</td></tr>';

    all('.btn-table-load-studio').forEach(button => button.onclick = () => {
      const record = filtered.find(item => String(item.id) === String(button.dataset.id));
      if (record) {
        ctx.state.studioActiveRecord = record;
        ctx.api.renderStudioWorkbench(record);
      }
    });

    all('.btn-table-approve').forEach(button => button.onclick = async () => {
      const id = button.dataset.id;
      if (!confirm('Approve this record for the Observatory?')) return;
      button.disabled = true;
      try {
        await ctx.dbManager.updateRecord(id, { status: 'Approved' });
        selectedRecordIds.delete(id);
        await ctx.api.renderAdminPortal();
        showToast('Record approved for the Observatory.');
      } catch (error) {
        button.disabled = false;
        alert(`Approval failed: ${error.message}`);
      }
    });

    all('.btn-table-delete').forEach(button => button.onclick = async () => {
      const id = button.dataset.id;
      const record = filtered.find(item => String(item.id) === String(id));
      if (!record || !confirm(`Remove "${record.fileName || id}" from the Observatory? This also deletes its published charts permanently.`)) return;
      button.disabled = true;
      try {
        await ctx.dbManager.deleteRecord(id);
        selectedRecordIds.delete(id);
        await ctx.api.renderAdminPortal();
        showToast('Upload and its published charts were removed from the Observatory.');
      } catch (error) {
        button.disabled = false;
        alert(`Delete failed: ${error.message}`);
      }
    });
    bindSelection();
  };

  const confirmBulk = async (mode) => {
    const ids = [...selectedRecordIds];
    if (!ids.length) return;
    const records = await ctx.dbManager.getAllRecords();
    const selected = records.filter(record => ids.includes(String(record.id)));
    const verb = mode === 'approve' ? 'approve' : 'delete';
    const modal = document.createElement('div');
    modal.className = 'modal-overlay active';
    modal.innerHTML = `<div class="modal-card" style="max-width:620px;">
      <div class="modal-header"><h3 class="modal-title">Confirm bulk ${verb}</h3><button type="button" class="export-cancel-button" data-close>Cancel</button></div>
      <p>${mode === 'approve' ? 'Approve' : 'Permanently delete'} <strong>${selected.length}</strong> selected upload${selected.length === 1 ? '' : 's'}${mode === 'approve' ? '?' : ' and their published Observatory charts?'}</p>
      <ul style="max-height:260px;overflow:auto;">${selected.map(r => `<li>${escape(r.fileName || 'Untitled')} <small>(${escape(r.id)})</small></li>`).join('')}</ul>
      <div style="display:flex;justify-content:flex-end;gap:.75rem;margin-top:1rem;"><button type="button" class="${mode === 'approve' ? 'btn-approve-modal' : 'archive-delete-button'}" data-confirm>${mode === 'approve' ? '<i class="fa-solid fa-circle-check" aria-hidden="true"></i> Approve records' : '<i class="fa-solid fa-trash" aria-hidden="true"></i> Delete records'}</button></div>
    </div>`;
    document.body.appendChild(modal);
    const close = () => modal.remove();
    modal.querySelector('[data-close]').onclick = close;
    modal.querySelector('[data-confirm]').onclick = async () => {
      const action = modal.querySelector('[data-confirm]');
      action.disabled = true;
      try {
        const result = mode === 'approve' ? await ctx.dbManager.approveRecords(ids) : await ctx.dbManager.deleteRecords(ids);
        close();
        selectedRecordIds.clear();
        await ctx.api.renderAdminPortal();
        showToast(`${result.successCount} of ${ids.length} records ${mode === 'approve' ? 'approved' : 'deleted'} successfully.`);
      } catch (error) {
        action.disabled = false;
        alert(`${mode === 'approve' ? 'Approval' : 'Delete'} failed: ${error.message}`);
      }
    };
  };

  ctx.api.renderAdminPortal = async () => {
    try {
      const records = await ctx.dbManager.getAllRecords();
      $('statTotalDb').textContent = records.length;
      $('statPendingDb').textContent = records.filter(r => r.status === 'Pending Review' || !r.status).length;
      $('statVerifiedDb').textContent = records.filter(r => ['Approved', 'Verified & Approved'].includes(r.status)).length;
      $('statTablesDb').textContent = records.reduce((sum, r) => sum + Object.keys(r.extractedData || {}).length, 0);

      const select = $('studioRecordSelect');
      if (select) {
        select.innerHTML = records.map(r => `<option value="${escape(r.id)}">${escape(r.fileName)} (${escape((r.fileType || '').toUpperCase())})</option>`).join('');
        select.onchange = event => {
          ctx.state.studioActiveRecord = records.find(r => String(r.id) === String(event.target.value)) || null;
          ctx.api.renderStudioWorkbench(ctx.state.studioActiveRecord);
        };
      }
      if (records.length) {
        ctx.state.studioActiveRecord = records.find(r => String(r.id) === String(ctx.state.studioActiveRecord?.id)) || records[0];
        ctx.api.renderStudioWorkbench(ctx.state.studioActiveRecord);
      }

      const term = (($('adminSearchInput')?.value || '')).toLowerCase();
      const status = $('adminStatusFilter')?.value || 'all';
      const filtered = records.filter(record => {
        const haystack = [record.id, record.fileName, record.docType, record.rawText].join(' ').toLowerCase();
        return haystack.includes(term) && (status === 'all' || record.status === status || (status === 'Pending Review' && !record.status));
      });
      const visible = new Set(filtered.map(r => String(r.id)));
      [...selectedRecordIds].forEach(id => { if (!visible.has(String(id))) selectedRecordIds.delete(id); });
      renderRows(filtered);
    } catch (error) {
      console.error(error);
      alert(`Unable to load scanner records: ${error.message}`);
    }
  };

  $('adminBulkDelete')?.addEventListener('click', () => confirmBulk('delete'));
  $('adminBulkApprove')?.addEventListener('click', () => confirmBulk('approve'));
  $('adminClearSelection')?.addEventListener('click', () => { selectedRecordIds.clear(); bindSelection(); });
  $('adminSearchInput')?.addEventListener('input', () => ctx.api.renderAdminPortal());
  $('adminStatusFilter')?.addEventListener('change', () => ctx.api.renderAdminPortal());

  ctx.api.renderAdminPortal();
}
