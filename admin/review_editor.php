<?php
$activeNav = 'review';
$pageTitle = 'Review Editor & Records Studio - IRIS Admin';
require_once __DIR__.'/includes/header.php';
?>

<section id="adminDatabaseView">
    <div style="background: var(--bg-card); border: 1px solid var(--border-light); border-left: 5px solid var(--clsu-green); border-radius: var(--radius-lg); padding: 1.5rem 2rem; margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; box-shadow: var(--card-shadow);">
        <div>
            <h2 style="font-size: 1.4rem; font-weight: 800; color: var(--clsu-green);">Review record editor</h2>
            <p style="font-size: 0.88rem; color: var(--text-muted);">Review extracted fields, edit tabular cells, update draft status, and approve visualizations for the CLSU Observatory.</p>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem;">
        <div style="background: var(--bg-card); border: 1px solid var(--border-light); padding: 1.1rem 1.25rem; border-radius: var(--radius-md); box-shadow: var(--card-shadow);">
            <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 800; text-transform: uppercase;">TOTAL SCANNED FILES</div>
            <div id="statTotalDb" style="font-size: 1.6rem; font-weight: 800; font-family: var(--font-mono); color: var(--clsu-green);">0</div>
        </div>
        <div style="background: var(--bg-card); border: 1px solid var(--border-light); padding: 1.1rem 1.25rem; border-radius: var(--radius-md); box-shadow: var(--card-shadow);">
            <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 800; text-transform: uppercase;">PENDING DRAFTS</div>
            <div id="statPendingDb" style="font-size: 1.6rem; font-weight: 800; font-family: var(--font-mono); color: var(--clsu-gold-dark);">0</div>
        </div>
        <div style="background: var(--bg-card); border: 1px solid var(--border-light); padding: 1.1rem 1.25rem; border-radius: var(--radius-md); box-shadow: var(--card-shadow);">
            <div style="font-size: 0.75rem; color: var(--text-muted); font-weight: 800; text-transform: uppercase;">APPROVED FOR DASHBOARD</div>
            <div id="statVerifiedDb" style="font-size: 1.6rem; font-weight: 800; font-family: var(--font-mono); color: var(--clsu-green-light);">0</div>
        </div>
        <div style="background: var(--bg-card); border: 1px solid var(--border-light); padding: 1.1rem 1.25rem; border-radius: var(--radius-md); box-shadow: var(--card-shadow);">
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
                            <span style="font-size: 0.72rem; color: var(--text-dim);">/</span>
                            <span id="acrobatTotalPagesSpan" style="font-size: 0.72rem; color: var(--text-main); font-weight: 600;">1</span>
                            <button type="button" id="btnAcrobatNextPage" class="acrobat-tool-btn" title="Next Page">▼</button>
                        </div>

                        <div class="acrobat-controls-right">
                            <div id="studioDocSheetSelectorContainer" style="display: none; align-items: center; gap: 0.35rem;">
                                <span style="font-size: 0.72rem; color: var(--text-muted); font-weight: 600;">Sheet:</span>
                                <select id="studioDocSheetSelect" class="form-input doc-sheet-select" style="background: var(--bg-input) !important; color: var(--text-main) !important; border-color: var(--border-light) !important;"></select>
                            </div>

                            <div id="acrobatZoomControlsGroup" style="display: flex; align-items: center; gap: 0.25rem; background: var(--bg-highlight); padding: 0.15rem 0.4rem; border-radius: 4px; border: 1px solid var(--border-light);">
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
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.85rem; gap: 1rem; flex-wrap: nowrap;">
                        <div style="flex: 1 1 auto; min-width: 0;">
                            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem; flex-wrap: wrap; min-width: 0;">
                                <span style="font-size: 0.82rem; font-weight: 800; color: var(--clsu-green); text-transform: uppercase; white-space: nowrap;">Chart Title:</span>
                                <input type="text" id="studioChartTitleInput" class="form-input" value="Observatory Draft" placeholder="Type chart title..." style="padding: 0.3rem 0.65rem; font-size: 0.95rem; font-weight: 800; color: var(--clsu-green); border: 1.5px solid var(--border-light); background: var(--bg-input); flex: 1 1 auto; min-width: 180px;" title="Click to edit the chart title">
                            </div>
                            <p id="studioChartSubtitleDisplay" style="font-size: 0.78rem; color: var(--text-muted);">Live interactive rendering from data fields below</p>
                        </div>

                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-left: auto; flex-shrink: 0;">
                            <label style="font-size: 0.78rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase; white-space: nowrap;">Chart Type:</label>
                            <select id="studioChartTypeSelect" class="form-input" style="width: auto; min-width: 150px; padding: 0.35rem 0.75rem; font-size: 0.82rem; font-weight: 700; color: var(--text-main);">
                                <option value="bar">Bar Chart</option>
                                <option value="line">Line Chart</option>
                                <option value="pie">Pie Chart</option>
                                <option value="doughnut">Doughnut Chart</option>
                                <option value="polarArea">Polar Area</option>
                            </select>
                        </div>
                    </div>

                    <div id="studioFieldMappingRow" style="background: rgba(59,130,246,0.08); border: 1px solid rgba(147,197,253,0.45); border-radius: var(--radius-sm); padding: 0.65rem 1rem; margin-bottom: 0.75rem; display: flex; flex-wrap: wrap; align-items: center; gap: 0.65rem;">
                        <span style="font-size: 0.78rem; font-weight: 800; color: var(--text-brand); text-transform: uppercase;"><i class="fa-solid fa-ruler-combined" aria-hidden="true"></i> Field Mapping:</span>
                        <div style="display: flex; align-items: center; gap: 0.35rem;">
                            <label style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); white-space: nowrap;" id="studioCategoryLabel">Category (X-axis):</label>
                            <select id="studioCategoryCol" class="form-input" style="width: auto; padding: 0.3rem 0.55rem; font-size: 0.78rem;" aria-label="Category column"></select>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.35rem;">
                            <label style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); white-space: nowrap;" id="studioValueLabel">Value (Y-axis):</label>
                            <select id="studioValueCol" class="form-input" style="width: auto; padding: 0.3rem 0.55rem; font-size: 0.78rem;" aria-label="Value column"></select>
                        </div>
                        <div id="studioFieldWarning" style="display:none; font-size: 0.75rem; color: #DC2626; font-weight: 700; background: rgba(254,242,242,0.9); border: 1px solid #FECACA; border-radius: 4px; padding: 0.2rem 0.6rem;"></div>
                    </div>

                    <div style="background: var(--bg-highlight); border: 1px solid var(--border-light); border-radius: var(--radius-sm); padding: 0.65rem 1rem; margin-bottom: 0.85rem; display: flex; flex-wrap: wrap; align-items: center; gap: 0.65rem;">
                        <span style="font-size: 0.78rem; font-weight: 800; color: var(--text-muted); text-transform: uppercase;"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> Filter extracted rows:</span>
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
                        <label style="font-size: 0.78rem; color: var(--text-muted); font-weight: 700; white-space: nowrap;">Show <input id="studioRowLimit" class="form-input" type="number" min="1" max="100" value="30" style="width: 4.5rem; display: inline-block; padding: 0.3rem 0.45rem; font-size: 0.78rem;"> rows</label>
                        <label style="font-size: 0.78rem; color: var(--text-muted); font-weight: 700; white-space: nowrap;"><input id="studioGroupDuplicates" type="checkbox" checked style="accent-color: var(--clsu-green); margin-right: 0.25rem;"> Group duplicate labels</label>
                    </div>

                    <div style="height: 320px; position: relative; width: 100%; margin-bottom: 0.75rem;">
                        <div id="studioChartCanvas" style="height: 100%; width: 100%;"></div>
                        <div id="studioChartEmptyState" style="display:none; position:absolute; inset:0; display:flex; flex-direction:column; align-items:center; justify-content:center; background: rgba(15,23,42,0.08); border-radius:var(--radius-sm); border:2px dashed var(--border-light);">
                            <span style="font-size:2rem;"><i class="fa-solid fa-chart-column" aria-hidden="true"></i></span>
                            <p id="studioChartEmptyMsg" style="font-size:0.88rem; color: var(--text-muted); font-weight:600; margin-top:0.5rem; text-align:center; max-width:280px;">Select a Category field and a numeric Value field above to render the chart.</p>
                        </div>
                    </div>
                </div>

                <div class="studio-data-manager">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; flex-wrap: wrap; gap: 0.5rem;">
                        <div>
                            <h4 style="font-size: 0.95rem; font-weight: 800; color: var(--text-main);"><i class="fa-solid fa-chart-column" aria-hidden="true"></i> Editable Data Grid & Custom Fields</h4>
                            <p style="font-size: 0.78rem; color: var(--text-muted);">Edit cell values directly, add new columns/metrics, or paste copied values.</p>
                        </div>

                        <div style="display: flex; gap: 0.5rem;">
                            <button id="studioBtnAddField" type="button" class="btn-studio-action" style="background: var(--bg-highlight); border: 1.5px solid rgba(59,130,246,0.5); color: var(--text-main);">
                                <i class="fa-solid fa-plus" aria-hidden="true"></i> Add Field / Column
                            </button>
                            <button id="studioBtnAddRow" type="button" class="btn-studio-action" style="background: rgba(16,185,129,0.12); border: 1.5px solid rgba(16,185,129,0.7); color: var(--text-main);">
                                <i class="fa-solid fa-plus" aria-hidden="true"></i> Add Row
                            </button>
                        </div>
                    </div>

                    <div id="studioTableContainer" class="table-container" style="max-height: 280px; margin-bottom: 1.25rem;"></div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.25rem; background: var(--bg-highlight); padding: 1.25rem; border-radius: var(--radius-md); border: 1px solid var(--border-light);">
                        <div>
                            <label class="form-label" style="font-weight: 800; font-size: 0.78rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.35rem; display: block;">Classification Category</label>
                            <input type="text" id="studioDocTypeInput" class="form-input" style="font-weight: 600; color: var(--text-main);">
                        </div>
                        <div>
                            <label class="form-label" style="font-weight: 800; font-size: 0.78rem; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.35rem; display: block;">Approval Status</label>
                            <select id="studioStatusSelect" class="form-input" style="font-weight: 600; color: var(--text-main);">
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

    <div id="recordEditModal" class="modal-overlay">
        <div class="modal-card">
            <div class="modal-header">
                <h3 id="recordEditTitle" class="modal-title">Edit Record Data</h3>
                <button id="btnCloseRecordModal" style="background: none; border: none; color: var(--text-muted); font-size: 1.4rem; cursor: pointer;">&times;</button>
            </div>
            <div id="recordEditBody" style="max-height: 75vh; overflow-y: auto; padding-right: 0.5rem;"></div>
        </div>
    </div>
</section>

<?php require_once __DIR__.'/includes/footer.php'; ?>
