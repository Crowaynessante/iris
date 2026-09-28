import { $ } from '../utils/helpers.js';

export function initNavigation(ctx) {
  const scannerButton = $('navScannerBtn');
  const scannerView = $('scannerWorkspaceView');
  const adminView = $('adminDatabaseView');

  const showReviewWorkspace = async () => {
    scannerButton?.classList.add('active');
    if (scannerView) scannerView.style.display = 'block';
    if (adminView) adminView.style.display = 'none';
  };

  scannerButton?.addEventListener('click', event => {
    event.preventDefault();
    showReviewWorkspace();
  });

  ctx.api.openReviewStudio = async (recordId) => {
    if (window.location.pathname.includes('/admin/')) {
      const param = recordId ? `?record_id=${encodeURIComponent(recordId)}` : '';
      window.location.href = `review_editor.php${param}`;
      return;
    }
    await showReviewWorkspace();
    scannerView?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    if (adminView) {
      adminView.style.display = 'block';
      await ctx.api.renderAdminPortal();
    }
  };
}
