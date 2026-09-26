import { $ , all } from '../utils/helpers.js';

const MAX_UPLOAD_SIZE_BYTES = 15 * 1024 * 1024;

export function initFileIngestion(ctx) {
  const dropzone = $('dropzone'); const input = $('fileInput'); const browse = $('btnBrowse');
  const trigger = $('uploadWidgetTrigger'); const modal = $('uploadWidgetModal'); const closeBtn = $('closeUploadWidget');
  const progressCard = $('progressCard'); const workspace = $('workspaceGrid');
  const status = $('progressStatus'); const percent = $('progressPercent'); const fill = $('progressFill');

  const showSizeWarning = fileNames => {
    const existing = document.getElementById('irisUploadSizeWarning');
    if (existing) existing.remove();

    const wrapper = document.createElement('div');
    wrapper.id = 'irisUploadSizeWarning';
    wrapper.setAttribute('role', 'dialog');
    wrapper.setAttribute('aria-modal', 'true');
    wrapper.style.position = 'fixed';
    wrapper.style.inset = '0';
    wrapper.style.background = 'rgba(15, 23, 42, 0.72)';
    wrapper.style.backdropFilter = 'blur(3px)';
    wrapper.style.display = 'flex';
    wrapper.style.alignItems = 'center';
    wrapper.style.justifyContent = 'center';
    wrapper.style.zIndex = '99999';
    wrapper.style.padding = '1rem';

    const card = document.createElement('div');
    card.style.width = 'min(720px, calc(100vw - 1.25rem))';
    card.style.background = '#232b31';
    card.style.border = '1px solid rgba(148, 163, 184, 0.35)';
    card.style.borderRadius = '18px';
    card.style.boxShadow = '0 20px 60px rgba(0,0,0,0.38)';
    card.style.color = '#edf6ff';
    card.style.fontFamily = 'Segoe UI, sans-serif';
    card.style.overflow = 'hidden';

    const header = document.createElement('div');
    header.style.display = 'flex';
    header.style.alignItems = 'center';
    header.style.justifyContent = 'space-between';
    header.style.padding = '1.2rem 1.4rem';
    header.style.borderBottom = '1px solid rgba(148, 163, 184, 0.25)';

    const title = document.createElement('div');
    title.textContent = 'localhost says';
    title.style.fontSize = '0.82rem';
    title.style.fontWeight = '700';
    title.style.letterSpacing = '0.08em';
    title.style.textTransform = 'uppercase';
    title.style.color = '#dfe7f1';

    const closeBtn = document.createElement('button');
    closeBtn.textContent = '×';
    closeBtn.setAttribute('aria-label', 'Close file size warning');
    closeBtn.style.background = 'transparent';
    closeBtn.style.border = 'none';
    closeBtn.style.color = '#f8fafc';
    closeBtn.style.fontSize = '2rem';
    closeBtn.style.lineHeight = '1';
    closeBtn.style.cursor = 'pointer';
    closeBtn.style.padding = '0';
    closeBtn.style.margin = '0';
    closeBtn.addEventListener('click', () => wrapper.remove());

    header.appendChild(title);
    header.appendChild(closeBtn);

    const body = document.createElement('div');
    body.style.padding = '1.4rem 1.4rem 1.1rem';

    const message = document.createElement('p');
    message.textContent = `Failed to scan file ${fileNames}.\nPlease choose a file under 15 MB.`;
    message.style.margin = '0';
    message.style.color = '#edf6ff';
    message.style.fontSize = '1.02rem';
    message.style.lineHeight = '1.6';
    message.style.whiteSpace = 'pre-line';

    const footer = document.createElement('div');
    footer.style.display = 'flex';
    footer.style.justifyContent = 'flex-end';
    footer.style.padding = '0 1.4rem 1.2rem';

    const okBtn = document.createElement('button');
    okBtn.textContent = 'OK';
    okBtn.style.background = '#f3f4f6';
    okBtn.style.border = 'none';
    okBtn.style.borderRadius = '9999px';
    okBtn.style.color = '#111827';
    okBtn.style.fontSize = '1.1rem';
    okBtn.style.fontWeight = '700';
    okBtn.style.cursor = 'pointer';
    okBtn.style.padding = '0.72rem 1.8rem';
    okBtn.style.minWidth = '88px';
    okBtn.addEventListener('click', () => wrapper.remove());

    footer.appendChild(okBtn);
    body.appendChild(message);
    card.appendChild(header);
    card.appendChild(body);
    card.appendChild(footer);
    wrapper.appendChild(card);
    wrapper.addEventListener('click', event => { if (event.target === wrapper) wrapper.remove(); });
    document.body.appendChild(wrapper);
  };

  const validateFiles = files => {
    const selected = Array.from(files || []);
    const tooLarge = selected.filter(file => Number(file?.size || 0) > MAX_UPLOAD_SIZE_BYTES);

    if (tooLarge.length) {
      const names = tooLarge.slice(0, 3).map(file => file.name).join(', ');
      const extra = tooLarge.length > 3 ? ` and ${tooLarge.length - 3} more file(s)` : '';
      showSizeWarning(`${names}${extra}`);
    }

    return selected.filter(file => Number(file?.size || 0) <= MAX_UPLOAD_SIZE_BYTES);
  };

  const openModal = () => {
    if (!modal) return;
    modal.classList.add('active');
    modal.setAttribute('aria-hidden', 'false');
  };
  const closeModal = () => {
    if (!modal) return;
    modal.classList.remove('active');
    modal.setAttribute('aria-hidden', 'true');
  };
  const updateProgress = (text, value) => { if (status) status.textContent = text; if (percent) percent.textContent = `${value}%`; if (fill) fill.style.width = `${value}%`; };
  const handleFiles = async (files) => {
    const selected = validateFiles(files);
    if (!selected.length) {
      if (input) input.value = '';
      return;
    }

    closeModal();
    if (progressCard) progressCard.style.display = 'block'; if (workspace) workspace.style.display = 'grid';
    for (let index = 0; index < selected.length; index += 1) {
      const file = selected[index];
      try {
        updateProgress(`Scanning ${file.name} (${index + 1}/${selected.length})...`, 10);
        const result = await ctx.scanner.scanFile(file, progress => updateProgress(progress.status, progress.progress));
        ctx.state.queue.unshift(result); ctx.api.renderQueue(); await ctx.api.setActiveScan(result);
      } catch (error) {
        console.error('Scan Error:', error);
        showSizeWarning(`${file.name}: ${error.message || 'Unknown error'}`);
      }
    }
    setTimeout(() => { if (progressCard) progressCard.style.display = 'none'; updateProgress('Scan complete!', 100); }, 800);
  };
  ctx.api.handleFiles = handleFiles; ctx.api.updateProgress = updateProgress;
  trigger?.addEventListener('click', openModal);
  closeBtn?.addEventListener('click', closeModal);
  modal?.addEventListener('click', event => { if (event.target === modal) closeModal(); });
  document.addEventListener('keydown', event => { if (event.key === 'Escape' && modal?.classList.contains('active')) closeModal(); });
  browse?.addEventListener('click', () => {
    openModal();
    input?.click();
  });
  input?.addEventListener('change', event => {
    const selected = validateFiles(event.target.files || []);
    if (!selected.length) {
      event.target.value = '';
      return;
    }
    handleFiles(selected);
  });
  ['dragenter', 'dragover'].forEach(name => dropzone?.addEventListener(name, event => { event.preventDefault(); dropzone.classList.add('dragover'); }));
  ['dragleave', 'drop'].forEach(name => dropzone?.addEventListener(name, event => { event.preventDefault(); dropzone.classList.remove('dragover'); }));
  dropzone?.addEventListener('drop', event => handleFiles(Array.from(event.dataTransfer?.files || [])));
  all('.sample-btn').forEach(button => button.addEventListener('click', () => {
    const type = button.getAttribute('data-sample');
    const generator = window.SampleGenerator; let file = null;
    if (type === 'payroll') file = generator?.createSampleExcelFile();
    if (type === 'contract') file = generator?.createSampleDocxFile();
    if (type === 'pdf') file = generator?.createSamplePdfFile();
    if (file) handleFiles([file]);
  }));
}
