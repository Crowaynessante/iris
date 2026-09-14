<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/ai_extract.php';
require_admin();

$msg = isset($_GET['msg']) ? $_GET['msg'] : "";
$checks = check_smart_upload_requirements();
$allOk = smart_upload_requirements_ok($checks);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Smart Upload - IRIS Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="topbar">
    <h1>Smart Upload</h1>
    <a href="dashboard.php" class="btn btn-ghost">&larr; Back to Admin</a>
</div>

<div class="container">

    <?php if ($msg): ?><p class="error"><?= htmlspecialchars($msg) ?></p><?php endif; ?>

    <div class="card">
        <h3>Upload any supporting file</h3>
        <p class="sub">CSV files with matching columns are read directly. Everything else — Excel, Word, PDF, or a photo of a report/certificate/ranking table — is read by AI, then shown to you for review before anything is saved.</p>

        <div class="file-types">
            <span class="badge">CSV</span>
            <span class="badge">XLSX / XLS</span>
            <span class="badge">DOCX</span>
            <span class="badge">PDF</span>
            <span class="badge">JPG / PNG</span>
        </div>

        <form action="smart_upload_process.php" method="POST" enctype="multipart/form-data" id="smartUploadForm">
            <label class="drop-zone" id="dropZone" for="upload_file">
                <div class="drop-zone-icon">📄</div>
                <div class="drop-zone-text">
                    <strong>Click to choose a file</strong> or drag it here
                </div>
                <div class="drop-zone-filename" id="dropZoneFilename"></div>
                <input type="file" name="upload_file" id="upload_file"
                       accept=".csv,.xlsx,.xls,.docx,.pdf,.jpg,.jpeg,.png" required hidden>
            </label>

            <button type="submit" class="btn btn-primary btn-block">Upload &amp; Read File</button>
        </form>

        <p class="hint">
            After upload, you'll see exactly what IRIS understood from the file and can correct
            anything before it's saved to the dashboard — nothing is added automatically without your confirmation.
        </p>
    </div>

    <div class="card">
        <h3>Setup Status</h3>
        <p class="sub">What this server needs in order for every file type above to work.</p>
        <ul class="status-list">
            <?php foreach ($checks as $check): ?>
                <li class="status-item <?= $check['ok'] ? 'status-ok' : 'status-bad' ?>">
                    <span class="status-icon"><?= $check['ok'] ? '✅' : '⚠️' ?></span>
                    <span><?= htmlspecialchars($check['label']) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php if (!$allOk): ?>
            <p class="hint">
                CSV files whose columns already match the rankings/colleges/programs templates will still work
                without any of the above — only files that need AI help (Excel, Word, PDF, images, or an
                unrecognized CSV layout) require everything above to be checked off.
            </p>
        <?php endif; ?>
    </div>
</div>

<script>
const dropZone = document.getElementById('dropZone');
const fileInput = document.getElementById('upload_file');
const filenameLabel = document.getElementById('dropZoneFilename');

fileInput.addEventListener('change', () => {
    filenameLabel.textContent = fileInput.files.length ? fileInput.files[0].name : '';
    dropZone.classList.toggle('has-file', fileInput.files.length > 0);
});

['dragover', 'dragleave', 'drop'].forEach(evt => {
    dropZone.addEventListener(evt, e => e.preventDefault());
});
dropZone.addEventListener('dragover', () => dropZone.classList.add('drag-active'));
dropZone.addEventListener('dragleave', () => dropZone.classList.remove('drag-active'));
dropZone.addEventListener('drop', e => {
    dropZone.classList.remove('drag-active');
    if (e.dataTransfer.files.length) {
        fileInput.files = e.dataTransfer.files;
        fileInput.dispatchEvent(new Event('change'));
    }
});
</script>
</body>
</html>
