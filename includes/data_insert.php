<?php
// ============================================
// Shared DB-insert helpers. Both the plain CSV upload and the
// AI-extraction review screen call these, so the logic only lives once.
//
// All inserts use prepared statements (mysqli_stmt) rather than string
// interpolation - several of these fields (notes, program names,
// assessment dates) come straight from an uploaded document/image, so
// they shouldn't be trusted enough to paste into a SQL string.
// ============================================

function insert_ranking($conn, $shortName, $year, $category, $globalRank, $phRank, $note) {
    $shortName = trim((string) $shortName);
    $category = trim((string) ($category ?? ''));
    $category = $category === '' ? null : $category;
    $note = trim((string) ($note ?? ''));
    $year = (int) $year;

    $globalRank = ($globalRank !== '' && $globalRank !== null) ? trim((string) $globalRank) : null;
    $phRank = ($phRank !== '' && $phRank !== null) ? trim((string) $phRank) : null;
    $rankValue = $globalRank !== null ? parse_rank_to_value($globalRank) : null;
    $phRankValue = $phRank !== null ? parse_rank_to_value($phRank) : null;

    $bodyStmt = mysqli_prepare($conn, "SELECT id FROM ranking_bodies WHERE short_name = ?");
    mysqli_stmt_bind_param($bodyStmt, 's', $shortName);
    mysqli_stmt_execute($bodyStmt);
    $body = mysqli_fetch_assoc(mysqli_stmt_get_result($bodyStmt));
    mysqli_stmt_close($bodyStmt);
    if (!$body) return false; // unrecognized ranking body short name
    $bodyId = (int) $body['id'];

    $stmt = mysqli_prepare($conn, "INSERT INTO rankings
        (ranking_body_id, year, category, global_rank, rank_value, ph_rank, ph_rank_value, note)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, 'iissisis',
        $bodyId, $year, $category, $globalRank, $rankValue, $phRank, $phRankValue, $note);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}

/**
 * Sub-category rows that sit under a ranking body/year: THE Impact's per-SDG
 * ranks, WURI's top-3 award categories, QS Stars' per-category ratings, etc.
 * Not linked to a specific `rankings` row - just to the body + year - so a
 * breakdown can be saved whether or not the headline rank for that year was
 * uploaded in the same file.
 */
function insert_ranking_breakdown($conn, $shortName, $year, $groupLabel, $itemLabel, $rankDisplay, $note) {
    $shortName = trim((string) $shortName);
    $groupLabel = trim((string) ($groupLabel ?? ''));
    $groupLabel = $groupLabel === '' ? null : $groupLabel;
    $itemLabel = trim((string) $itemLabel);
    $note = trim((string) ($note ?? ''));
    $year = (int) $year;

    if ($itemLabel === '') return false;

    $rankDisplay = ($rankDisplay !== '' && $rankDisplay !== null) ? trim((string) $rankDisplay) : null;
    $rankValue = $rankDisplay !== null ? parse_rank_to_value($rankDisplay) : null;

    $bodyStmt = mysqli_prepare($conn, "SELECT id FROM ranking_bodies WHERE short_name = ?");
    mysqli_stmt_bind_param($bodyStmt, 's', $shortName);
    mysqli_stmt_execute($bodyStmt);
    $body = mysqli_fetch_assoc(mysqli_stmt_get_result($bodyStmt));
    mysqli_stmt_close($bodyStmt);
    if (!$body) return false; // unrecognized ranking body short name
    $bodyId = (int) $body['id'];

    $stmt = mysqli_prepare($conn, "INSERT INTO ranking_breakdowns
        (ranking_body_id, year, group_label, item_label, rank_display, rank_value, note)
        VALUES (?, ?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, 'iisssis',
        $bodyId, $year, $groupLabel, $itemLabel, $rankDisplay, $rankValue, $note);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}

function insert_college($conn, $name, $shortCode, $percent, $year) {
    $name = trim((string) $name);
    $shortCode = trim((string) $shortCode);
    $percent = (float) $percent;
    $year = (int) $year;

    if ($name === '' || $shortCode === '') return false;

    $stmt = mysqli_prepare($conn, "INSERT INTO colleges (name, short_code, contribution_percent, year)
                                    VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, 'ssdi', $name, $shortCode, $percent, $year);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}

function insert_program($conn, $name, $collegeCode, $natRank, $score, $movement, $year) {
    $name = trim((string) $name);
    $collegeCode = trim((string) $collegeCode);
    $natRank = (int) $natRank;
    $score = (float) $score;
    $movement = (int) $movement;
    $year = (int) $year;

    $collegeStmt = mysqli_prepare($conn, "SELECT id FROM colleges WHERE short_code = ? ORDER BY year DESC LIMIT 1");
    mysqli_stmt_bind_param($collegeStmt, 's', $collegeCode);
    mysqli_stmt_execute($collegeStmt);
    $college = mysqli_fetch_assoc(mysqli_stmt_get_result($collegeStmt));
    mysqli_stmt_close($collegeStmt);
    if (!$college) return false; // college must exist first
    $collegeId = (int) $college['id'];

    $stmt = mysqli_prepare($conn, "INSERT INTO programs (name, college_id, national_rank, score, movement, year)
                                    VALUES (?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, 'siidii', $name, $collegeId, $natRank, $score, $movement, $year);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}

/**
 * One criterion score from a program accreditation assessment (AUN-QA and
 * similar). Each program/assessment produces several rows - one per
 * criterion, plus an "Overall Verdict" row whose score is text rather than
 * a number (e.g. "Adequate as Expected").
 */
function insert_accreditation($conn, $programName, $accreditingBody, $year, $assessmentDate, $criterion, $score) {
    $programName = trim((string) $programName);
    $accreditingBody = trim((string) ($accreditingBody ?? ''));
    $accreditingBody = $accreditingBody === '' ? 'AUN-QA' : $accreditingBody;
    $assessmentDate = trim((string) ($assessmentDate ?? ''));
    $assessmentDate = $assessmentDate === '' ? null : $assessmentDate;
    $criterion = trim((string) $criterion);
    $year = (int) $year;

    if ($programName === '' || $criterion === '') return false;

    $score = ($score !== '' && $score !== null) ? trim((string) $score) : null;
    $numericScore = ($score !== null && is_numeric($score)) ? (float) $score : null;

    $stmt = mysqli_prepare($conn, "INSERT INTO accreditations
        (program_name, accrediting_body, year, assessment_date, criterion, score, numeric_score)
        VALUES (?, ?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, 'ssisssd',
        $programName, $accreditingBody, $year, $assessmentDate, $criterion, $score, $numericScore);
    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $ok;
}
