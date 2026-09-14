<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$msg = isset($_GET['msg']) ? $_GET['msg'] : "";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>IAO Admin - IRIS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="topbar">
    <h1>IRIS Admin — International Affairs Office</h1>
    <a href="../auth/logout.php" class="btn btn-ghost">Logout</a>
</div>

<div class="container">
    <?php if ($msg): ?><p class="success"><?= htmlspecialchars($msg) ?></p><?php endif; ?>

    <div class="actions-row">
        <a href="smart_upload.php" class="btn btn-primary">📄 Smart Upload — Word / PDF / Excel / Image</a>
        <a href="../user/dashboard.php" class="btn btn-outline">View public dashboard →</a>
    </div>

    <div class="card">
        <h3>Upload Data</h3>
        <p class="sub">Upload a CSV file for one of the data types below. The system will parse it and refresh the public dashboard automatically.</p>

        <form action="upload_process.php" method="POST" enctype="multipart/form-data">
            <label>Data type</label>
            <select name="upload_type" required>
                <option value="rankings">Rankings (by ranking body)</option>
                <option value="colleges">College Contributions</option>
                <option value="programs">Program Rankings</option>
            </select>

            <label>CSV File</label>
            <input type="file" name="csv_file" accept=".csv" required>

            <button type="submit" class="btn btn-primary btn-block">Upload &amp; Process</button>
        </form>

        <p class="hint">
            Expected CSV columns —<br>
            <b>rankings:</b> ranking_body_short_name, year, global_rank, ph_rank, note<br>
            <b>colleges:</b> name, short_code, contribution_percent, year<br>
            <b>programs:</b> name, college_short_code, national_rank, score, movement, year<br>
            For anything with a per-category breakdown (SDG ranks, QS Stars categories, WURI
            award categories) or program accreditation data, use <a href="smart_upload.php">Smart Upload</a> instead —
            those aren't part of this plain CSV template.
        </p>
    </div>

    <div class="card">
        <h3>Recent Uploads</h3>
        <p class="sub">File type is the literal format of the uploaded file; Data type is what IRIS filed it under.</p>
        <table>
            <tr><th>File</th><th>File Type</th><th>Data Type</th><th>Rows</th><th>Uploaded</th></tr>
            <?php
            $logs = mysqli_query($conn, "SELECT * FROM uploads_log ORDER BY uploaded_at DESC LIMIT 10");
            $anyLogs = false;
            while ($row = mysqli_fetch_assoc($logs)) {
                $anyLogs = true;
                $fileTypeLabel = $row['file_type'] ? strtoupper($row['file_type']) : '—';
                echo "<tr><td>" . htmlspecialchars($row['filename']) . "</td>"
                   . "<td><span class='badge'>" . htmlspecialchars($fileTypeLabel) . "</span></td>"
                   . "<td><span class='badge'>" . htmlspecialchars($row['upload_type']) . "</span></td>"
                   . "<td>{$row['rows_inserted']}</td><td>{$row['uploaded_at']}</td></tr>";
            }
            if (!$anyLogs) echo '<tr><td colspan="5" class="sub">No uploads yet.</td></tr>';
            ?>
        </table>
    </div>
</div>
</body>
</html>
