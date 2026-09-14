<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/data_insert.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['csv_file'])) {
    header("Location: dashboard.php?msg=No file received.");
    exit();
}

$type = clean($_POST['upload_type']);
$allowed = ['rankings', 'colleges', 'programs'];
if (!in_array($type, $allowed)) {
    header("Location: dashboard.php?msg=Invalid upload type.");
    exit();
}

$tmpPath = $_FILES['csv_file']['tmp_name'];
$originalName = basename($_FILES['csv_file']['name']);

if (!is_uploaded_file($tmpPath)) {
    header("Location: dashboard.php?msg=Upload failed.");
    exit();
}

$handle = fopen($tmpPath, 'r');
if ($handle === false) {
    header("Location: dashboard.php?msg=Could not read file.");
    exit();
}

$rowsInserted = 0;
$header = fgetcsv($handle); // skip header row

while (($row = fgetcsv($handle)) !== false) {
    if (count(array_filter($row)) === 0) continue; // skip blank lines

    if ($type === 'rankings') {
        // ranking_body_short_name, year, global_rank, ph_rank, note
        // (category isn't in this simple template — use Smart Upload if a
        // ranking body publishes more than one list per year, e.g. QS Asia vs QS World)
        [$shortName, $year, $globalRank, $phRank, $note] = array_pad($row, 5, null);
        if (insert_ranking($conn, $shortName, $year, null, $globalRank, $phRank, $note)) $rowsInserted++;

    } elseif ($type === 'colleges') {
        // name, short_code, contribution_percent, year
        [$name, $shortCode, $percent, $year] = array_pad($row, 4, null);
        if (insert_college($conn, $name, $shortCode, $percent, $year)) $rowsInserted++;

    } elseif ($type === 'programs') {
        // name, college_short_code, national_rank, score, movement, year
        [$name, $collegeCode, $natRank, $score, $movement, $year] = array_pad($row, 6, null);
        if (insert_program($conn, $name, $collegeCode, $natRank, $score, $movement, $year)) $rowsInserted++;
    }
}
fclose($handle);

// Log the upload
$stmt = mysqli_prepare($conn, "INSERT INTO uploads_log (uploaded_by, filename, file_type, upload_type, rows_inserted)
                                VALUES (?, ?, 'csv', ?, ?)");
$userId = (int) $_SESSION['user_id'];
mysqli_stmt_bind_param($stmt, 'issi', $userId, $originalName, $type, $rowsInserted);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

header("Location: dashboard.php?msg=" . urlencode("Uploaded $originalName — $rowsInserted rows inserted."));
exit();
