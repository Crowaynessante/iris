<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/data_insert.php';
require_admin();

$inserted = 0;
$skipped = 0;
$categoriesUsed = [];

foreach (($_POST['rankings'] ?? []) as $r) {
    if (!isset($r['include'])) continue;
    $ok = insert_ranking($conn, $r['ranking_body_short_name'], $r['year'], $r['category'] ?? null, $r['global_rank'], $r['ph_rank'], $r['note']);
    if ($ok) { $inserted++; $categoriesUsed['rankings'] = true; } else { $skipped++; }
}

foreach (($_POST['breakdowns'] ?? []) as $b) {
    if (!isset($b['include'])) continue;
    $ok = insert_ranking_breakdown($conn, $b['ranking_body_short_name'], $b['year'], $b['group_label'] ?? null, $b['item_label'], $b['rank_display'], $b['note'] ?? '');
    if ($ok) { $inserted++; $categoriesUsed['ranking_breakdowns'] = true; } else { $skipped++; }
}

foreach (($_POST['colleges'] ?? []) as $c) {
    if (!isset($c['include'])) continue;
    $ok = insert_college($conn, $c['name'], $c['short_code'], $c['contribution_percent'], $c['year']);
    if ($ok) { $inserted++; $categoriesUsed['colleges'] = true; } else { $skipped++; }
}

foreach (($_POST['programs'] ?? []) as $p) {
    if (!isset($p['include'])) continue;
    $ok = insert_program($conn, $p['name'], $p['college_short_code'], $p['national_rank'], $p['score'], $p['movement'], $p['year']);
    if ($ok) { $inserted++; $categoriesUsed['programs'] = true; } else { $skipped++; }
}

foreach (($_POST['accreditations'] ?? []) as $a) {
    if (!isset($a['include'])) continue;
    $ok = insert_accreditation($conn, $a['program_name'], $a['accrediting_body'] ?? 'AUN-QA', $a['year'], $a['assessment_date'] ?? null, $a['criterion'], $a['score']);
    if ($ok) { $inserted++; $categoriesUsed['accreditations'] = true; } else { $skipped++; }
}

// Log it — upload_type reflects whichever category (or categories) this file
// actually produced rows in, and file_type is the literal format uploaded
// (csv/xlsx/docx/pdf/jpg/png), so the admin page shows both at a glance.
$filename = trim((string) ($_SESSION['pending_extraction_filename'] ?? 'unknown'));
$fileType = trim((string) ($_POST['file_type'] ?? ($_SESSION['pending_extraction_file_type'] ?? '')));
$uploadType = count($categoriesUsed) === 1 ? array_key_first($categoriesUsed) : (count($categoriesUsed) > 1 ? 'mixed' : 'none');

$stmt = mysqli_prepare($conn, "INSERT INTO uploads_log (uploaded_by, filename, file_type, upload_type, rows_inserted)
                                VALUES (?, ?, ?, ?, ?)");
$userId = (int) $_SESSION['user_id'];
mysqli_stmt_bind_param($stmt, 'isssi', $userId, $filename, $fileType, $uploadType, $inserted);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

unset($_SESSION['pending_extraction']);
unset($_SESSION['pending_extraction_filename']);
unset($_SESSION['pending_extraction_file_type']);

$msg = "Saved $inserted row(s).";
if ($skipped > 0) $msg .= " $skipped row(s) skipped (e.g. unknown ranking body, or a college/program code that doesn't exist yet — add that college first).";

header("Location: dashboard.php?msg=" . urlencode($msg));
exit();
