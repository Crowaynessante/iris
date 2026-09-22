<?php require_once __DIR__.'/../includes/functions.php'; require_admin();
$pdo=db();$msg=flash('error')?:flash('success');$logs=$pdo->query('SELECT * FROM uploads_log ORDER BY uploaded_at DESC LIMIT 10')->fetchAll();$errorsFirst=''; ?>

<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IAO Admin Control Panel - IRIS</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#ecfdf5',
                            100: '#d1fae5',
                            500: '#10b981',
                            600: '#059669',
                            700: '#047857',
                            800: '#065f46',
                            900: '#064e3b',
                            gold: '#f59e0b'
                        }
                    }
                }
            }
        }
    </script>
    <!-- Flowbite CSS & JS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.js"></script>
    <!-- External parsing and charting dependencies used by the embedded scanner -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/echarts@5.5.1/dist/echarts.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/mammoth@1.6.0/mammoth.browser.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/docx-preview@latest/dist/docx-preview.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/docx-preview@latest/dist/docx-preview.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= e(base_url('scanner/css/styles.css')) ?>">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .admin-scanner-shell { width: 100%; }
        .admin-scanner-shell .app-container { max-width: 1600px !important; margin: 0 auto; }
        .admin-view-panel[hidden] { display: none !important; }
        .admin-view-switcher { display: inline-flex; align-items: center; gap: .35rem; padding: .25rem; border: 1px solid #334155; background: #1e293b; border-radius: .8rem; }
        .admin-view-switcher button { border: 0; border-radius: .55rem; padding: .55rem .75rem; color: #cbd5e1; background: transparent; font-size: .76rem; font-weight: 800; cursor: pointer; transition: .2s; }
        .admin-view-switcher button:hover, .admin-view-switcher button.is-active { color: #fff; background: #059669; }
        .admin-observatory-frame { display: block; width: 100%; min-height: 1450px; border: 0; background: #f9fafb; }
        @media(max-width:900px){.admin-view-switcher button{padding:.5rem;font-size:.7rem}.admin-observatory-frame{min-height:1900px}}
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 min-h-screen flex flex-col">

    <style>
        /* Admin navigation: dark IRIS colorway */
        .admin-nav{background:#182436!important;border-bottom:1px solid #334155!important;box-shadow:0 8px 24px rgba(0,0,0,.22);}
        .admin-nav-inner{min-height:76px;}
        .admin-brand-title{color:#f8fafc!important;}
        .admin-brand-sub{color:#94a3b8!important;}
        .admin-nav-link{display:inline-flex;align-items:center;gap:.5rem;padding:.65rem .9rem;border:1px solid #334155;background:#223047;color:#e2e8f0;border-radius:.7rem;font-size:.78rem;font-weight:700;transition:.2s;}
        .admin-nav-link:hover{background:#2b3b54;color:#fff;border-color:#10b981;}
        .admin-nav-link.scanner{background:#059669;border-color:#10b981;color:#fff;box-shadow:0 4px 14px rgba(16,185,129,.18);}
        .admin-nav-link.scanner:hover{background:#10b981;}
        .admin-theme-btn{color:#cbd5e1!important;background:#223047!important;border:1px solid #334155!important;}
        .admin-theme-btn:hover{color:#fff!important;background:#2b3b54!important;}
        .admin-profile-btn{background:#223047!important;border:1px solid #334155!important;color:#e2e8f0!important;}
        .admin-profile-btn:hover{background:#2b3b54!important;}
        .admin-dropdown{background:#1e293b!important;border:1px solid #334155!important;color:#e2e8f0!important;}
        .admin-dropdown .dropdown-name{color:#f8fafc!important;}
        .admin-dropdown a{color:#cbd5e1!important;}
        .admin-dropdown a:hover{background:#2b3b54!important;color:#fff!important;}
        .admin-dropdown .signout{color:#fca5a5!important;}
        #page-loader{position:fixed;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(15,23,42,.68);backdrop-filter:blur(6px);z-index:10000;transition:opacity .3s ease,visibility .3s ease;}
        #page-loader.hidden{opacity:0;visibility:hidden;pointer-events:none;}
        .iris-loader{position:relative;width:72px;height:72px;border-radius:50%;background:conic-gradient(#10b981,#34d399,#fbbf24,#10b981);animation:spin 1s linear infinite;box-shadow:0 0 30px rgba(16,185,129,.5)}
        .iris-loader::before{content:"";position:absolute;inset:10px;border-radius:50%;background:rgba(15,23,42,.9);border:2px solid rgba(255,255,255,.18)}
        .iris-loader::after{content:"IRIS";position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;letter-spacing:.12em;color:#d1fae5}
        @keyframes spin{to{transform:rotate(360deg)}}
        @media(max-width:900px){.admin-nav-actions .public-link{display:none!important}.admin-brand-sub{display:none!important}}
    </style>

    <div id="page-loader" aria-live="polite" aria-label="Loading page">
        <div class="iris-loader" aria-hidden="true"></div>
    </div>

    <!-- Admin Navigation Bar -->
    <nav class="admin-nav sticky top-0 z-50 backdrop-blur-md bg-opacity-95">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="admin-nav-inner flex items-center justify-between gap-4">
                <a href="<?= e(base_url('admin/dashboard.php')) ?>" class="logo-refresh-trigger flex items-center gap-3 min-w-0" data-target="<?= e(base_url('admin/dashboard.php')) ?>">
                    <div class="w-11 h-11 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-400 flex items-center justify-center shadow-lg shadow-emerald-500/20 ring-2 ring-emerald-400/30 overflow-hidden shrink-0">
                        <img src="<?= e(base_url('images/iris-logo.png')) ?>" alt="IRIS Logo" class="w-11 h-11 object-contain">
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="admin-brand-title text-xl font-extrabold tracking-tight">IRIS Admin</span>
                            <span class="text-[10px] px-2 py-1 font-extrabold rounded-full bg-amber-400 text-slate-900 border border-amber-300">IRIS FILE INGESTION</span>
                        </div>
                        <p class="admin-brand-sub text-[11px] font-semibold uppercase tracking-wider">International Affairs Office Control Panel</p>
                    </div>
                </a>

                <div class="admin-nav-actions flex items-center gap-2">
                    <a href="<?= e(base_url('user/dashboard.php')) ?>" class="admin-nav-link public-link" aria-label="Open Observatory" title="Open Observatory">
                        <i class="fa-solid fa-chart-pie" aria-hidden="true"></i><span>Observatory</span>
                    </a>
                    <button id="theme-toggle" type="button" class="admin-theme-btn rounded-lg text-sm p-2.5" aria-label="Toggle theme">
                        <i id="theme-toggle-dark-icon" class="hidden fa-solid fa-moon text-base"></i>
                        <i id="theme-toggle-light-icon" class="hidden fa-solid fa-sun text-base text-amber-400"></i>
                    </button>
                    <div class="relative">
                        <button type="button" class="admin-profile-btn flex items-center gap-2 p-1.5 rounded-full focus:ring-2 focus:ring-emerald-500" id="user-menu-button" aria-expanded="false" data-dropdown-toggle="user-dropdown" data-dropdown-placement="bottom">
                            <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-emerald-500 to-amber-400 flex items-center justify-center text-white font-bold text-xs shadow">
                                <?= strtoupper(substr(($_SESSION['username'] ?? 'A'), 0, 2)) ?>
                            </div>
                            <span class="hidden sm:inline-block font-semibold text-xs px-1"><?= htmlspecialchars(($_SESSION['username'] ?? 'A')) ?></span>
                            <i class="fa-solid fa-chevron-down text-[10px] text-slate-400 mr-1"></i>
                        </button>
                        <div class="admin-dropdown z-50 hidden my-3 w-56 text-base list-none rounded-xl shadow-2xl" id="user-dropdown">
                            <div class="px-4 py-3 border-b border-slate-700">
                                <span class="dropdown-name block text-sm font-bold"><?= htmlspecialchars(($_SESSION['username'] ?? 'A')) ?></span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-amber-400 text-slate-900 mt-1">ADMINISTRATOR</span>
                            </div>
                            <ul class="py-2" aria-labelledby="user-menu-button">
                                <li><a href="<?= e(base_url('user/dashboard.php')) ?>" class="block px-4 py-2 text-sm"><i class="fa-solid fa-globe mr-2"></i> Observatory View</a></li>
                            </ul>
                            <div class="py-1 border-t border-slate-700">
                                <form method="POST" action="<?= e(base_url('auth/logout.php')) ?>">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="signout block w-full text-left px-4 py-2 text-sm"><i class="fa-solid fa-right-from-bracket mr-2"></i> Sign Out</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <main class="admin-scanner-shell flex-1 w-full py-8">
        <div class="app-container">
            <?php if ($msg || ''): ?>
                <div id="alert-3" class="flex items-center p-4 mb-4 text-emerald-800 rounded-xl bg-emerald-50 dark:bg-gray-800 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800" role="alert">
                    <i class="fa-solid fa-circle-check text-lg mr-3"></i>
                    <div class="text-sm font-medium">
                        <?= htmlspecialchars($msg ?: '') ?>
                    </div>
                    <button type="button" class="ml-auto -mx-1.5 -my-1.5 bg-emerald-50 text-emerald-500 rounded-lg focus:ring-2 focus:ring-emerald-400 p-1.5 hover:bg-emerald-200 inline-flex items-center justify-center h-8 w-8 dark:bg-gray-800 dark:text-emerald-400 dark:hover:bg-gray-700" data-dismiss-target="#alert-3" aria-label="Close">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            <?php endif; ?>

            <section id="scannerWorkspaceView" class="admin-view-panel">
                <div class="clsu-section-title">
                    <span><i class="fa-solid fa-chart-column" aria-hidden="true"></i></span> University-Wide Overview & Ingestion
                </div>

                <div id="dropzone" class="dropzone-container">
                    <div class="dropzone-icon">
                        <svg width="32" height="32" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                    </div>

                    <h1 class="dropzone-title">Upload Spreadsheets, PDFs, or Word Documents</h1>
                    <p class="dropzone-subtitle">Multi-sheet parsing, institutional text extraction, and draft visualization suggestions for university performance metrics</p>

                    <div class="format-badges">
                        <span class="format-chip excel"><i class="fa-solid fa-chart-column" aria-hidden="true"></i> Spreadsheets (XLSX, XLS, CSV)</span>
                        <span class="format-chip pdf"><i class="fa-solid fa-file-lines" aria-hidden="true"></i> PDF Documents (Reports & Infographs)</span>
                        <span class="format-chip docx"><i class="fa-solid fa-file-pen" aria-hidden="true"></i> Word (DOCX Status Links)</span>
                    </div>

                    <input type="file" id="fileInput" multiple accept=".xlsx,.xls,.csv,.docx,.doc,.pdf" style="display: none;">

                    <div style="margin-bottom: 1.5rem;">
                        <button id="btnBrowse" class="btn-icon" style="padding: 0.75rem 2rem; font-size: 0.95rem; margin: 0 auto;">
                            <span><i class="fa-solid fa-folder" aria-hidden="true"></i></span> Browse Institutional Files
                        </button>
                    </div>

                    <div class="samples-container">
                        <span class="samples-label">Test 1-Click Samples:</span>
                        <button class="sample-btn" data-sample="payroll">
                            <span><i class="fa-solid fa-chart-column" aria-hidden="true"></i></span> QAO Evaluation Scores (.xlsx)
                        </button>
                        <button class="sample-btn" data-sample="pdf">
                            <span><i class="fa-solid fa-file-lines" aria-hidden="true"></i></span> OAD Infograph Stats (.pdf)
                        </button>
                        <button class="sample-btn" data-sample="contract">
                            <span><i class="fa-solid fa-file-pen" aria-hidden="true"></i></span> Program Accreditation (.docx)
                        </button>
                    </div>
                </div>

                <div id="progressCard" class="progress-card">
                    <div class="progress-header">
                        <span id="progressStatus">Initializing scanner...</span>
                        <span id="progressPercent">0%</span>
                    </div>
                    <div class="progress-track">
                        <div id="progressFill" class="progress-fill"></div>
                    </div>
                </div>

                <div id="workspaceGrid" class="workspace-grid" style="display: none;">
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

            <section id="adminDatabaseView" style="display: none;">
                <div class="clsu-section-title">
                    <span><i class="fa-solid fa-database" aria-hidden="true"></i></span> Review archive & record history
                </div>

                <div class="workspace-tabs" style="margin-bottom: 1.25rem;">
                    <button class="admin-tab-btn active" data-admin-tab="adminRecordsPanel">
                        <span><i class="fa-solid fa-clipboard" aria-hidden="true"></i></span> Records & Dashboard Studio
                    </button>
                    <button class="admin-tab-btn" data-admin-tab="adminSavedGraphsPanel">
                        <span><i class="fa-solid fa-folder-tree" aria-hidden="true"></i></span> Saved Dashboard Graphs
                    </button>
                </div>

                <div id="adminSavedGraphsPanel" class="admin-tab-panel" style="display: none;">
                    <div style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                        <div>
                            <h3 style="font-size: 1.1rem; font-weight: 800; color: var(--clsu-green);">Saved Dashboard Graphs</h3>
                            <p style="font-size: 0.85rem; color: var(--text-muted);">Approved and saved chart versions grouped per file record.</p>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                            <button id="savedGraphsViewAllBtn" type="button" class="saved-graphs-bulk-button">View All</button>
                            <label style="font-size: 0.78rem; font-weight: 800; color: #334155; text-transform: uppercase;">File:</label>
                            <select id="savedGraphsRecordSelect" class="form-input" style="width: auto; min-width: 220px;">
                                <option value="">Loading files...</option>
                            </select>
                        </div>
                    </div>

                    <div id="savedGraphsBulkToolbar" style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem; padding: 0.75rem 1rem; background: #F8FAF8; border: 1px solid var(--border-light); border-radius: var(--radius-sm);">
                        <label style="display: inline-flex; align-items: center; gap: 0.45rem; font-weight: 700; font-size: 0.82rem;">
                            <input id="savedGraphsSelectAll" type="checkbox"> Select All
                        </label>
                        <span id="savedGraphsSelectionCount" style="font-size: 0.8rem; color: var(--text-muted);">0 selected</span>
                        <button id="savedGraphsPrintAll" type="button" class="saved-graphs-bulk-button" disabled>Print All</button>
                        <button id="savedGraphsExportSelected" type="button" class="saved-graphs-bulk-button" disabled>Export</button>
                        <button id="savedGraphsDeleteSelected" type="button" class="archive-delete-button" disabled><i class="fa-solid fa-trash" aria-hidden="true"></i> Delete</button>
                    </div>

                    <div id="savedDashboardGraphsContainer"></div>
                </div>

                <div id="adminRecordsPanel" class="admin-tab-panel active">
                    <div style="background: #FFFFFF; border: 1px solid var(--border-light); border-left: 5px solid var(--clsu-green); border-radius: var(--radius-lg); padding: 1.5rem 2rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; box-shadow: var(--card-shadow);">
                        <div>
                            <h2 style="font-size: 1.4rem; font-weight: 800; color: var(--clsu-green);">Review record editor</h2>
                            <p style="font-size: 0.88rem; color: var(--text-muted);">Review extracted fields, edit tabular cells, update draft status, and approve visualizations for the CLSU Observatory.</p>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
                        <div style="background: #FFFFFF; border: 1px solid var(--border-light); padding: 1.1rem 1.25rem; border-radius: var(--radius-md); box-shadow: var(--card-shadow);">
                            <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 800; text-transform: uppercase;">TOTAL SCANNED FILES</div>
                            <div id="statTotalDb" style="font-size: 1.6rem; font-weight: 800; font-family: var(--font-mono); color: var(--clsu-green);">0</div>
                        </div>
                        <div style="background: #FFFFFF; border: 1px solid var(--border-light); padding: 1.1rem 1.25rem; border-radius: var(--radius-md); box-shadow: var(--card-shadow);">
                            <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 800; text-transform: uppercase;">PENDING DRAFTS</div>
                            <div id="statPendingDb" style="font-size: 1.6rem; font-weight: 800; font-family: var(--font-mono); color: var(--clsu-gold-dark);">0</div>
                        </div>
                        <div style="background: #FFFFFF; border: 1px solid var(--border-light); padding: 1.1rem 1.25rem; border-radius: var(--radius-md); box-shadow: var(--card-shadow);">
                            <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 800; text-transform: uppercase;">APPROVED FOR DASHBOARD</div>
                            <div id="statVerifiedDb" style="font-size: 1.6rem; font-weight: 800; font-family: var(--font-mono); color: var(--clsu-green-light);">0</div>
                        </div>
                        <div style="background: #FFFFFF; border: 1px solid var(--border-light); padding: 1.1rem 1.25rem; border-radius: var(--radius-md); box-shadow: var(--card-shadow);">
                            <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 800; text-transform: uppercase;">EXTRACTED TABLES</div>
                            <div id="statTablesDb" style="font-size: 1.6rem; font-weight: 800; font-family: var(--font-mono); color: var(--clsu-green);">0</div>
                        </div>
                    </div>

                    <div class="studio-container" id="studioContainer" style="margin-bottom: 2rem;">
                        <div class="studio-header-card">
                            <div style="display: flex; align-items: center; gap: 1rem; flex-wrap: wrap; justify-content: space-between; width: 100%;">
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <span style="font-size: 1.4rem;"><i class="fa-solid fa-palette" aria-hidden="true"></i></span>
                                    <div>
                                        <div style="font-size: 0.75rem; font-weight: 800; color: var(--clsu-green); text-transform: uppercase; letter-spacing: 0.05em;">ACTIVE DASHBOARD STUDIO WORKBENCH</div>
                                        <div style="font-size: 1.2rem; font-weight: 800; color: #0F172A;" id="studioActiveFileName">Loading Scanned Dataset...</div>
                                    </div>
                                </div>

                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <label style="font-size: 0.82rem; font-weight: 800; color: #334155; text-transform: uppercase;">Switch Dataset:</label>
                                    <select id="studioRecordSelect" class="form-input" style="width: auto; min-width: 250px; font-weight: 700; color: #0F172A;"></select>
                                </div>
                            </div>
                        </div>

                        <div class="studio-grid">
                            <div class="studio-left-card">
                                <div class="studio-card-title" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.4rem;">
                                    <span><i class="fa-solid fa-window-maximize" aria-hidden="true"></i> Scanned Source Document Window</span>
                                    <span class="badge badge-low" style="font-size: 0.68rem; background: #ECFDF5; color: #047857;">Live Ingestion View</span>
                                </div>
                                <p style="font-size: 0.78rem; color: #64748B; margin-bottom: 0.75rem;">Read the full file directly side-by-side. Click any cell or word to copy value directly into your dashboard fields.</p>

                                <div class="doc-viewer-container">
                                    <div class="acrobat-toolbar">
                                        <div class="acrobat-title-group">
                                            <span class="acrobat-badge-icon" id="acrobatDocBadge">PDF</span>
                                            <span class="acrobat-filename" id="docWindowTitle">document.docx</span>
                                        </div>

                                        <div class="acrobat-controls-center" id="acrobatPageNavControls">
                                            <button type="button" id="btnAcrobatPrevPage" class="acrobat-tool-btn" title="Previous Page">▲</button>
                                            <input type="number" id="acrobatCurrentPageInput" class="acrobat-page-input" value="1" min="1" max="1" title="Go to Page">
                                            <span style="font-size: 0.72rem; color: #94A3B8;">/</span>
                                            <span id="acrobatTotalPagesSpan" style="font-size: 0.72rem; color: #E2E8F0; font-weight: 600;">1</span>
                                            <button type="button" id="btnAcrobatNextPage" class="acrobat-tool-btn" title="Next Page">▼</button>
                                        </div>

                                        <div class="acrobat-controls-right">
                                            <div id="studioDocSheetSelectorContainer" style="display: none; align-items: center; gap: 0.35rem;">
                                                <span style="font-size: 0.72rem; color: #CBD5E1; font-weight: 600;">Sheet:</span>
                                                <select id="studioDocSheetSelect" class="form-input doc-sheet-select" style="background: #202225 !important; color: #FFF !important; border-color: #4A4E53 !important;"></select>
                                            </div>

                                            <div id="acrobatZoomControlsGroup" style="display: flex; align-items: center; gap: 0.25rem; background: #202225; padding: 0.15rem 0.4rem; border-radius: 4px; border: 1px solid #3A3E42;">
                                                <button type="button" id="btnAcrobatZoomOut" class="acrobat-tool-btn" title="Zoom Out">−</button>
                                                <span id="acrobatZoomValue" class="acrobat-zoom-label">100%</span>
                                                <button type="button" id="btnAcrobatZoomIn" class="acrobat-tool-btn" title="Zoom In">+</button>
                                                <button type="button" id="btnAcrobatFitWidth" class="acrobat-tool-btn" title="Fit Width" style="font-size: 0.68rem; margin-left: 2px;">↔</button>
                                            </div>

                                            <span id="docWindowPageCount" style="display: none;"></span>
                                            <span id="docWindowWordCount" style="display: none;"></span>
                                        </div>
                                    </div>

                                    <div id="studioDocContentArea" class="acrobat-viewer-body">
                                        <div class="acrobat-page-card">
                                            <p style="color: #64748B; text-align: center;">Loading document content...</p>
                                        </div>
                                    </div>
                                </div>

                                <div style="margin-top: 0.65rem; font-size: 0.74rem; color: #64748B; display: flex; align-items: center; justify-content: space-between;">
                                    <span><i class="fa-solid fa-lightbulb" aria-hidden="true"></i> <strong>Tip:</strong> Highlight or click any text to copy directly.</span>
                                    <span id="docWindowCopyStatus" style="color: var(--clsu-green); font-weight: 700;"></span>
                                </div>
                            </div>

                            <div class="studio-right-card">
                                <div class="studio-chart-box">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.85rem; flex-wrap: wrap; gap: 0.75rem;">
                                        <div style="flex: 1; min-width: 250px;">
                                            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
                                                <span style="font-size: 0.82rem; font-weight: 800; color: var(--clsu-green); text-transform: uppercase;">Chart Title:</span>
                                                <input type="text" id="studioChartTitleInput" class="form-input" value="Observatory Draft" placeholder="Type chart title..." style="padding: 0.3rem 0.65rem; font-size: 0.95rem; font-weight: 800; color: var(--clsu-green); border: 1.5px solid #CBD5E1; background: #FFFFFF; flex: 1;" title="Click to edit the chart title">
                                            </div>
                                            <p id="studioChartSubtitleDisplay" style="font-size: 0.78rem; color: #64748B;">Live interactive rendering from data fields below</p>
                                        </div>

                                        <div style="display: flex; gap: 0.5rem; align-items: center;">
                                            <label style="font-size: 0.78rem; font-weight: 800; color: #334155; text-transform: uppercase;">Chart Type:</label>
                                            <select id="studioChartTypeSelect" class="form-input" style="width: auto; padding: 0.35rem 0.75rem; font-size: 0.82rem; font-weight: 700; color: #0F172A;">
                                                <option value="bar">Bar Chart</option>
                                                <option value="line">Line Chart</option>
                                                <option value="pie">Pie Chart</option>
                                                <option value="doughnut">Doughnut Chart</option>
                                                <option value="polarArea">Polar Area</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div id="studioFieldMappingRow" style="background: #EFF6FF; border: 1px solid #BFDBFE; border-radius: var(--radius-sm); padding: 0.65rem 1rem; margin-bottom: 0.75rem; display: flex; flex-wrap: wrap; align-items: center; gap: 0.65rem;">
                                        <span style="font-size: 0.78rem; font-weight: 800; color: #1D4ED8; text-transform: uppercase;"><i class="fa-solid fa-ruler-combined" aria-hidden="true"></i> Field Mapping:</span>
                                        <div style="display: flex; align-items: center; gap: 0.35rem;">
                                            <label style="font-size: 0.75rem; font-weight: 700; color: #334155; white-space: nowrap;" id="studioCategoryLabel">Category (X-axis):</label>
                                            <select id="studioCategoryCol" class="form-input" style="width: auto; padding: 0.3rem 0.55rem; font-size: 0.78rem;" aria-label="Category column"></select>
                                        </div>
                                        <div style="display: flex; align-items: center; gap: 0.35rem;">
                                            <label style="font-size: 0.75rem; font-weight: 700; color: #334155; white-space: nowrap;" id="studioValueLabel">Value (Y-axis):</label>
                                            <select id="studioValueCol" class="form-input" style="width: auto; padding: 0.3rem 0.55rem; font-size: 0.78rem;" aria-label="Value column"></select>
                                        </div>
                                        <div id="studioFieldWarning" style="display:none; font-size: 0.75rem; color: #DC2626; font-weight: 700; background: #FEF2F2; border: 1px solid #FECACA; border-radius: 4px; padding: 0.2rem 0.6rem;"></div>
                                    </div>

                                    <div style="background: #F8FAF8; border: 1px solid var(--border-light); border-radius: var(--radius-sm); padding: 0.65rem 1rem; margin-bottom: 0.85rem; display: flex; flex-wrap: wrap; align-items: center; gap: 0.65rem;">
                                        <span style="font-size: 0.78rem; font-weight: 800; color: #334155; text-transform: uppercase;"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> Filter extracted rows:</span>
                                        <select id="studioFilterField" class="form-input" style="width: auto; padding: 0.3rem 0.55rem; font-size: 0.78rem;" aria-label="Filter data scope">
                                            <option value="all">All selected data</option>
                                            <option value="context">Context / label only</option>
                                            <option value="value">Metric / value only</option>
                                        </select>
                                        <select id="studioFilterOperator" class="form-input" style="width: auto; padding: 0.3rem 0.55rem; font-size: 0.78rem;" aria-label="Filter operator">
                                            <option value="all">All rows</option>
                                            <option value="contains">Contains</option>
                                            <option value="starts-with">Starts with</option>
                                            <option value="ends-with">Ends with</option>
                                            <option value="equals">Equals</option>
                                            <option value="not-equals">Does not equal</option>
                                            <option value="greater-than">Value greater than</option>
                                            <option value="less-than">Value less than</option>
                                            <option value="between">Value between</option>
                                        </select>
                                        <input id="studioFilterValue" class="form-input" type="search" placeholder="Broad search across selected data..." style="min-width: 190px; flex: 1; padding: 0.3rem 0.65rem; font-size: 0.78rem;" aria-label="Filter value">
                                        <input id="studioFilterUpperValue" class="form-input" type="number" placeholder="Maximum" style="display: none; width: 6.5rem; padding: 0.3rem 0.65rem; font-size: 0.78rem;" aria-label="Filter maximum value">
                                        <select id="studioSortOrder" class="form-input" style="width: auto; padding: 0.3rem 0.55rem; font-size: 0.78rem;" aria-label="Sort chart rows">
                                            <option value="source">Source order</option>
                                            <option value="value-asc">Metric: low to high</option>
                                            <option value="value-desc">Metric: high to low</option>
                                            <option value="label-asc">Label: A to Z</option>
                                            <option value="label-desc">Label: Z to A</option>
                                        </select>
                                        <button id="studioReverseSortOrder" type="button" class="btn-studio-action" aria-pressed="false" style="padding: 0.3rem 0.55rem; font-size: 0.78rem;">⇄ Reverse order</button>
                                        <button id="studioReverseValueAxis" type="button" class="btn-studio-action" aria-pressed="false" style="padding: 0.3rem 0.55rem; font-size: 0.78rem;">⇄ Reverse value axis</button>
                                        <label style="font-size: 0.78rem; color: #334155; font-weight: 700; white-space: nowrap;">Show <input id="studioRowLimit" class="form-input" type="number" min="1" max="100" value="30" style="width: 4.5rem; display: inline-block; padding: 0.3rem 0.45rem; font-size: 0.78rem;"> rows</label>
                                        <label style="font-size: 0.78rem; color: #334155; font-weight: 700; white-space: nowrap;"><input id="studioGroupDuplicates" type="checkbox" checked style="accent-color: var(--clsu-green); margin-right: 0.25rem;"> Group duplicate labels</label>
                                    </div>

                                    <div style="height: 320px; position: relative; width: 100%; margin-bottom: 0.75rem;">
                                        <div id="studioChartCanvas" style="height: 100%; width: 100%;"></div>
                                        <div id="studioChartEmptyState" style="display:none; position:absolute; inset:0; display:flex; flex-direction:column; align-items:center; justify-content:center; background:rgba(248,250,248,0.95); border-radius:var(--radius-sm); border:2px dashed #CBD5E1;">
                                            <span style="font-size:2rem;"><i class="fa-solid fa-chart-column" aria-hidden="true"></i></span>
                                            <p id="studioChartEmptyMsg" style="font-size:0.88rem; color:#64748B; font-weight:600; margin-top:0.5rem; text-align:center; max-width:280px;">Select a Category field and a numeric Value field above to render the chart.</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="studio-data-manager">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; flex-wrap: wrap; gap: 0.5rem;">
                                        <div>
                                            <h4 style="font-size: 0.95rem; font-weight: 800; color: #0F172A;"><i class="fa-solid fa-chart-column" aria-hidden="true"></i> Editable Data Grid & Custom Fields</h4>
                                            <p style="font-size: 0.78rem; color: #64748B;">Edit cell values directly, add new columns/metrics, or paste copied values.</p>
                                        </div>

                                        <div style="display: flex; gap: 0.5rem;">
                                            <button id="studioBtnAddField" type="button" class="btn-studio-action" style="background: #EFF6FF; border: 1.5px solid #3B82F6; color: #1D4ED8;">
                                                <i class="fa-solid fa-plus" aria-hidden="true"></i> Add Field / Column
                                            </button>
                                            <button id="studioBtnAddRow" type="button" class="btn-studio-action" style="background: #ECFDF5; border: 1.5px solid #10B981; color: #065F46;">
                                                <i class="fa-solid fa-plus" aria-hidden="true"></i> Add Row
                                            </button>
                                        </div>
                                    </div>

                                    <div id="studioTableContainer" class="table-container" style="max-height: 280px; margin-bottom: 1.25rem;"></div>

                                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.25rem; background: #F8FAF8; padding: 1.25rem; border-radius: var(--radius-md); border: 1px solid var(--border-light);">
                                        <div>
                                            <label class="form-label" style="font-weight: 800; font-size: 0.78rem; color: #334155; text-transform: uppercase; margin-bottom: 0.35rem; display: block;">Classification Category</label>
                                            <input type="text" id="studioDocTypeInput" class="form-input" style="font-weight: 600; color: #0F172A;">
                                        </div>
                                        <div>
                                            <label class="form-label" style="font-weight: 800; font-size: 0.78rem; color: #334155; text-transform: uppercase; margin-bottom: 0.35rem; display: block;">Approval Status</label>
                                            <select id="studioStatusSelect" class="form-input" style="font-weight: 600; color: #0F172A;">
                                                <option value="Pending Review">Pending Review</option>
                                                <option value="Approved">Approved for Dashboard</option>
                                                <option value="Needs Revision">Needs Revision</option>
                                            </select>
                                        </div>
                                        <div style="grid-column: 1 / -1;">
                                            <label class="form-label" style="font-weight: 800; font-size: 0.78rem; color: #334155; text-transform: uppercase; margin-bottom: 0.35rem; display: block;">Admin Verification Notes</label>
                                            <textarea id="studioNotesInput" class="form-input" rows="2" placeholder="Add verification logs and approval notes..." style="font-weight: 500; color: #0F172A; line-height: 1.5;"></textarea>
                                        </div>
                                    </div>

                                    <div style="display: flex; justify-content: flex-end; gap: 0.85rem; padding-top: 1rem; border-top: 1px solid var(--border-light);">
                                        <button id="studioBtnSave" type="button" class="btn-save-modal">
                                            <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save Dashboard Changes
                                        </button>
                                        <button id="studioBtnApprove" type="button" class="btn-approve-modal">
                                            <i class="fa-solid fa-circle-check" aria-hidden="true"></i> Approve for Observatory
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="clsu-section-title" style="margin-top: 2rem;">
                        <span><i class="fa-solid fa-folder" aria-hidden="true"></i></span> Scanned Records Archive & Ingestion Logs
                    </div>

                    <div style="display: flex; gap: 1rem; margin-bottom: 1rem; flex-wrap: wrap;">
                        <input type="text" id="adminSearchInput" class="form-input" placeholder="Search records by filename, category, or values..." style="flex: 1; min-width: 250px;">
                        <select id="adminStatusFilter" class="form-input" style="width: auto;">
                            <option value="all">All Statuses</option>
                            <option value="Pending Review">Pending Review</option>
                            <option value="Approved">Approved for Dashboard</option>
                            <option value="Needs Revision">Needs Revision</option>
                        </select>
                    </div>

                    <div class="table-container" style="box-shadow: var(--card-shadow);">
                        <div id="adminBulkActions" class="admin-bulk-actions" hidden>
                            <span id="adminBulkSelectionCount">0 records selected</span>
                            <button id="adminBulkApprove" type="button" class="btn-approve-modal" disabled><i class="fa-solid fa-circle-check" aria-hidden="true"></i> Bulk Approve</button>
                            <button id="adminBulkDelete" type="button" class="archive-delete-button" disabled><i class="fa-solid fa-trash" aria-hidden="true"></i> Bulk Delete</button>
                            <button id="adminClearSelection" type="button" class="export-cancel-button">Clear selection</button>
                        </div>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th><input id="adminSelectAll" type="checkbox" aria-label="Select all visible records"></th>
                                    <th>Record ID</th>
                                    <th>File Name</th>
                                    <th>Format</th>
                                    <th>Review Status</th>
                                    <th>Scanned Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="adminRecordsTableBody"></tbody>
                        </table>
                    </div>
                </div>
            </section>

            <section id="observatoryView" class="admin-view-panel" hidden>
                <iframe
                    id="adminObservatoryFrame"
                    class="admin-observatory-frame"
                    src="<?= e(base_url('user/dashboard.php')) ?>"
                    title="IRIS Observatory dashboard"
                    loading="lazy"></iframe>
            </section>
        </div>


    <div id="recordEditModal" class="modal-overlay">
        <div class="modal-card">
            <div class="modal-header">
                <h3 id="recordEditTitle" class="modal-title">Edit Record Data</h3>
                <button id="btnCloseRecordModal" style="background: none; border: none; color: var(--text-muted); font-size: 1.4rem; cursor: pointer;">&times;</button>
            </div>

            <div id="recordEditBody" style="max-height: 75vh; overflow-y: auto; padding-right: 0.5rem;"></div>
        </div>
    </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 py-6 mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between text-xs text-gray-500 dark:text-gray-400 gap-4">
            <div class="flex items-center space-x-2">
                <span class="font-bold text-gray-800 dark:text-gray-200">IRIS Admin</span>
                <span>&bull; IAO'S INTERNATIONAL RAPPORT INSIGHT SYSTEM</span>
            </div>
            <div>
                Powered by Flowbite &amp; Tailwind CSS
            </div>
        </div>
    </footer>

    <!-- Theme Toggle Script -->
    <script>
        (function () {
            const loader = document.getElementById('page-loader');
            const hideLoader = () => {
                if (loader) {
                    loader.classList.add('hidden');
                }
            };

            document.querySelectorAll('.logo-refresh-trigger').forEach((link) => {
                link.addEventListener('click', function (event) {
                    const target = this.getAttribute('data-target') || this.href;
                    event.preventDefault();
                    loader && loader.classList.remove('hidden');
                    const currentUrl = window.location.href.split('#')[0];
                    if (target && target.split('#')[0] === currentUrl.split('#')[0]) {
                        window.location.reload();
                        return;
                    }
                    window.location.href = target;
                });
            });

            setTimeout(hideLoader, 90);
            window.addEventListener('load', hideLoader);
        })();

        const themeToggleDarkIcon = document.getElementById('theme-toggle-dark-icon');
        const themeToggleLightIcon = document.getElementById('theme-toggle-light-icon');
        const themeToggleBtn = document.getElementById('theme-toggle');

        if (document.documentElement.classList.contains('dark')) {
            themeToggleLightIcon.classList.remove('hidden');
        } else {
            themeToggleDarkIcon.classList.remove('hidden');
        }

        themeToggleBtn.addEventListener('click', function() {
            themeToggleDarkIcon.classList.toggle('hidden');
            themeToggleLightIcon.classList.toggle('hidden');

            if (document.documentElement.classList.contains('dark')) {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('color-theme', 'light');
            } else {
                document.documentElement.classList.add('dark');
                localStorage.setItem('color-theme', 'dark');
            }
        });

        (function () {
            const tabs = document.querySelectorAll('.admin-view-tab');
            const panels = document.querySelectorAll('.admin-view-panel');
            const observatoryFrame = document.getElementById('adminObservatoryFrame');

            function showView(viewId, updateHash = true) {
                panels.forEach((panel) => {
                    panel.hidden = panel.id !== viewId;
                });
                tabs.forEach((tab) => {
                    const active = tab.dataset.adminView === viewId;
                    tab.classList.toggle('is-active', active);
                    tab.setAttribute('aria-selected', active ? 'true' : 'false');
                });
                if (updateHash) {
                    history.replaceState(null, '', viewId === 'observatoryView' ? '#observatory' : '#scanner');
                }
                if (viewId === 'observatoryView' && observatoryFrame && !observatoryFrame.src) {
                    observatoryFrame.src = <?= json_encode(base_url('user/dashboard.php')) ?>;
                }
            }

            tabs.forEach((tab) => {
                tab.addEventListener('click', () => showView(tab.dataset.adminView));
            });

            if (observatoryFrame) {
                observatoryFrame.addEventListener('load', () => {
                    try {
                        const frameDocument = observatoryFrame.contentDocument;
                        const frameNav = frameDocument.querySelector('body > nav');
                        const frameFooter = frameDocument.querySelector('body > footer');
                        const frameMain = frameDocument.querySelector('body > main');
                        if (frameNav) frameNav.style.display = 'none';
                        if (frameFooter) frameFooter.style.display = 'none';
                        if (frameMain) {
                            frameMain.style.maxWidth = 'none';
                            frameMain.style.padding = '1.5rem';
                            frameMain.style.margin = '0';
                        }
                        frameDocument.body.style.background = '#f9fafb';
                    } catch (error) {
                        console.warn('Unable to trim embedded observatory chrome.', error);
                    }
                });
            }

            showView(window.location.hash === '#observatory' ? 'observatoryView' : 'scannerWorkspaceView', false);
        })();
    </script>

    <script src="<?= e(base_url('scanner/js/parsers/imageOcrPipeline.js')) ?>"></script>
    <script src="<?= e(base_url('scanner/js/parsers/excelParser.js')) ?>"></script>
    <script src="<?= e(base_url('scanner/js/parsers/docxParser.js')) ?>"></script>
    <script src="<?= e(base_url('scanner/js/parsers/docxViewerComponent.js')) ?>"></script>
    <script src="<?= e(base_url('scanner/js/parsers/pdfParser.js')) ?>"></script>
    <script src="<?= e(base_url('scanner/js/parsers/pdfViewerComponent.js')) ?>"></script>
    <script src="<?= e(base_url('scanner/js/ai/graphEngine.js')) ?>"></script>
    <script src="<?= e(base_url('scanner/js/database/dbManager.js')) ?>"></script>
    <script src="<?= e(base_url('scanner/js/samples.js')) ?>"></script>
    <script src="<?= e(base_url('scanner/js/scanner.js')) ?>"></script>
    <script src="<?= e(base_url('scanner/js/tableFilter.js')) ?>"></script>
    <script src="<?= e(base_url('scanner/js/chartData.js')) ?>"></script>
    <script src="<?= e(base_url('scanner/js/chartMapping.js')) ?>"></script>
    <script src="<?= e(base_url('scanner/js/sourceIngestion.js')) ?>"></script>
    <script src="<?= e(base_url('scanner/js/documentPagination.js')) ?>"></script>
    <script src="<?= e(base_url('scanner/js/graphExport.js')) ?>"></script>
    <script type="module" src="<?= e(base_url('scanner/js/app.js')) ?>"></script>
</body>
</html>
