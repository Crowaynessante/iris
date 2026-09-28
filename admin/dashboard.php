<?php
$activeNav = 'ingestion';
$pageTitle = 'File Ingestion & Overview - IRIS Admin';
require_once __DIR__.'/includes/header.php';
?>

<section id="scannerWorkspaceView" class="admin-view-panel mx-auto w-full max-w-5xl px-4 py-8 flex flex-col items-center">
    <div class="clsu-section-title text-center justify-center mb-6">
        <span><i class="fa-solid fa-chart-column" aria-hidden="true"></i></span> University-Wide Overview & Ingestion
    </div>

    <div id="inlineUploadDropzone" class="dropzone-container upload-dropzone mx-auto w-full max-w-4xl text-center bg-white dark:bg-slate-800 border-2 border-dashed border-emerald-600 dark:border-emerald-500/60 rounded-2xl p-6 sm:p-10 shadow-lg dark:shadow-2xl">
        <div class="dropzone-icon mb-4 flex justify-center">
            <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" class="text-emerald-600 dark:text-emerald-400">
                <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
            </svg>
        </div>

        <h2 class="dropzone-title text-xl sm:text-2xl font-extrabold mb-2 text-slate-900 dark:text-white">Upload Spreadsheets, PDFs, or Word Documents</h2>
        <p class="dropzone-subtitle text-xs sm:text-sm text-slate-500 dark:text-slate-400 max-w-xl mx-auto mb-5">Multi-sheet parsing, institutional text extraction, and draft visualization suggestions for university performance metrics</p>

        <div class="format-badges flex flex-wrap gap-2 justify-center mb-5">
            <span class="format-chip excel"><i class="fa-solid fa-chart-column" aria-hidden="true"></i> Spreadsheets (XLSX, XLS, CSV)</span>
            <span class="format-chip pdf"><i class="fa-solid fa-file-lines" aria-hidden="true"></i> PDF Documents (Reports & Infographs)</span>
            <span class="format-chip docx"><i class="fa-solid fa-file-pen" aria-hidden="true"></i> Word (DOCX Status Links)</span>
        </div>

        <div class="text-xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 mb-5">
            Files must be under 100 MB
        </div>

        <div class="mb-6">
            <button id="btnInlineBrowse" class="btn-icon mx-auto px-8 py-3 text-sm font-semibold rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white shadow-md transition-all" type="button">
                <span><i class="fa-solid fa-folder" aria-hidden="true"></i></span> Browse Institutional Files
            </button>
        </div>

        <div class="samples-container flex items-center justify-center gap-3 pt-5 border-t border-slate-200 dark:border-slate-700/80 w-full overflow-hidden">
            <span class="samples-label shrink-0 whitespace-nowrap text-xs font-bold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Test 1-Click Samples:</span>
            <div class="flex items-center gap-2 overflow-x-auto py-1 max-w-full no-scrollbar">
                <button class="sample-btn shrink-0" data-sample="iao" type="button">
                    <span><i class="fa-solid fa-chart-column" aria-hidden="true"></i></span> IAO Rankings Dataset (.xlsx)
                </button>
            </div>
        </div>
    </div>

    <div id="progressCard" class="progress-card w-full max-w-4xl mx-auto mt-6">
        <div class="progress-header">
            <span id="progressStatus">Initializing scanner...</span>
            <span id="progressPercent">0%</span>
        </div>
        <div class="progress-track">
            <div id="progressFill" class="progress-fill"></div>
        </div>
    </div>

    <div id="workspaceGrid" class="workspace-grid w-full max-w-5xl mt-6" style="display: none;">
        <aside class="queue-sidebar">
            <div class="sidebar-title">
                <span>Ingestion Queue (<span id="queueCount">0</span>)</span>
                <button id="btnClearQueue" style="background: none; border: none; color: var(--text-dim); cursor: pointer; font-size: 0.75rem; font-weight: 700;">Clear All</button>
            </div>
            <div id="queueList" class="queue-list"></div>
        </aside>

        <section class="content-workspace">
            <div class="workspace-tabs">
                <button class="tab-btn active" data-tab="tabOverview">
                    <span><i class="fa-solid fa-clipboard" aria-hidden="true"></i></span> Extracted Fields & Overview
                </button>
                <button class="tab-btn" data-tab="tabViewer">
                    <span><i class="fa-solid fa-eye" aria-hidden="true"></i></span> Document & Data Viewer
                </button>
                <button class="tab-btn" data-tab="tabGraphs">
                    <span><i class="fa-solid fa-chart-line" aria-hidden="true"></i></span> Draft Visualizations (<span id="draftsCountBadge">0</span>)
                </button>
            </div>

            <div id="tabOverview" class="tab-panel active">
                <div class="metrics-row">
                    <div class="summary-card">
                        <div class="summary-title">
                            <span id="summaryDocTitle">Extracted Document Analysis</span>
                            <span id="docFormatBadge" class="format-chip excel">Format</span>
                        </div>
                        <p id="executiveSummaryText" class="summary-text">Select or scan a file to inspect extracted fields.</p>

                        <h4 style="font-size: 0.88rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 0.75rem; color: var(--clsu-green);">Extracted Data Fields & Key Metrics</h4>
                        <div id="extractedFieldsGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 0.75rem; margin-bottom: 1.25rem;"></div>

                        <h4 style="font-size: 0.88rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 0.5rem; color: var(--clsu-green);">Identified Structure Highlights</h4>
                        <ul id="takeawayList" class="takeaway-list"></ul>
                    </div>
                </div>

                <div style="display: flex; gap: 0.75rem; flex-wrap: wrap; padding-top: 1rem; border-top: 1px solid var(--border-light); justify-content: space-between; align-items: center;">
                    <div style="font-size: 0.82rem; color: var(--text-muted);">
                        Status: <span class="badge badge-low" style="display: inline-block;">Draft (Pending Admin Review)</span>
                    </div>
                    <div style="display: flex; gap: 0.75rem;">
                        <button id="btnOpenInEditor" class="btn-icon">
                            <span><i class="fa-solid fa-pen" aria-hidden="true"></i></span> Open review editor
                        </button>
                    </div>
                </div>
            </div>

            <div id="tabViewer" class="tab-panel">
                <div id="viewerControls" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                    <span id="viewerFileMeta" style="font-size: 0.85rem; color: var(--text-muted); font-weight: 600;">File Details</span>
                    <div id="sheetSelectorContainer" style="display: none;">
                        <label style="font-size: 0.82rem; margin-right: 0.5rem; color: var(--clsu-green); font-weight: 700;">Worksheet:</label>
                        <select id="sheetSelect" class="form-input" style="width: auto; padding: 0.35rem 0.75rem; display: inline-block;"></select>
                    </div>
                </div>

                <div id="viewerContentArea" style="min-height: 450px;"></div>
            </div>

            <div id="tabGraphs" class="tab-panel">
                <div style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="font-size: 1.1rem; font-weight: 800; color: var(--clsu-green);">Draft Visualization Suggestions</h3>
                        <p style="font-size: 0.85rem; color: var(--text-muted);">Institutional chart drafts (Bar, Line, Pie) pending Admin review & approval.</p>
                    </div>
                    <span class="badge badge-low">Draft Only — Not Auto-Published</span>
                </div>

                <div id="graphDraftsContainer"></div>
            </div>
        </section>
    </div>
</section>

<?php require_once __DIR__.'/includes/footer.php'; ?>
