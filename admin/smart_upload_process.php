<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/ai_extract.php';
require_once __DIR__ . '/../includes/extractors.php';
require_admin();

if (!isset($_FILES['upload_file']) || $_FILES['upload_file']['error'] !== UPLOAD_ERR_OK) {
    $phpErrorMsgs = [
        UPLOAD_ERR_INI_SIZE => "File exceeds this server's upload_max_filesize setting.",
        UPLOAD_ERR_FORM_SIZE => "File exceeds the form's max size.",
        UPLOAD_ERR_PARTIAL => "The file was only partially uploaded — please try again.",
        UPLOAD_ERR_NO_FILE => "No file was selected.",
    ];
    $code = $_FILES['upload_file']['error'] ?? UPLOAD_ERR_NO_FILE;
    $msg = $phpErrorMsgs[$code] ?? "Upload failed (PHP error code $code).";
    header("Location: smart_upload.php?msg=" . urlencode($msg));
    exit();
}

$tmpPath = $_FILES['upload_file']['tmp_name'];
$originalName = basename($_FILES['upload_file']['name']);
$ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

$maxSize = 15 * 1024 * 1024; // 15MB safety cap for base64-encoded uploads to the API
if ($_FILES['upload_file']['size'] > $maxSize) {
    header("Location: smart_upload.php?msg=" . urlencode("File too large (max 15MB)."));
    exit();
}

$allowedExt = ['csv', 'xlsx', 'xls', 'docx', 'pdf', 'jpg', 'jpeg', 'png'];
if (!in_array($ext, $allowedExt)) {
    header("Location: smart_upload.php?msg=" . urlencode("Unsupported file type: .$ext"));
    exit();
}

try {
    // ---- CSV: try the fast, free, exact column-matched import first ----
    if ($ext === 'csv') {
        $result = smart_map_csv($tmpPath);

        if ($result === null) {
            // Header didn't match any known template - fall back to the AI,
            // same as every other file type, instead of just giving up.
            $checks = check_smart_upload_requirements();
            if (!smart_upload_requirements_ok($checks)) {
                header("Location: smart_upload.php?msg=" . urlencode(
                    "This CSV's columns don't match rankings/colleges/programs, so it needs AI help to read — " .
                    "but the AI isn't fully configured yet. See the Setup Status panel below for what's missing."
                ));
                exit();
            }
            $text = csv_rows_to_text($tmpPath);
            $result = ai_extract_structured_data([build_text_block($text)]);
        }

    } else {
        // Everything else needs the AI extractor
        $checks = check_smart_upload_requirements();
        if (!smart_upload_requirements_ok($checks)) {
            header("Location: smart_upload.php?msg=" . urlencode(
                "AI extraction isn't fully configured on this server yet. See the Setup Status panel below for what's missing."
            ));
            exit();
        }

        $contentBlocks = [];

        if (in_array($ext, ['xlsx', 'xls'])) {
            $text = extract_text_from_spreadsheet($tmpPath);
            $contentBlocks[] = build_text_block($text);

        } elseif ($ext === 'docx') {
            $text = extract_text_from_docx($tmpPath);
            $contentBlocks[] = build_text_block($text);

        } elseif ($ext === 'pdf') {
            $contentBlocks[] = build_pdf_block($tmpPath);

        } elseif (in_array($ext, ['jpg', 'jpeg', 'png'])) {
            $mime = $ext === 'png' ? 'image/png' : 'image/jpeg';
            $contentBlocks[] = build_image_block($tmpPath, $mime);
        }

        $result = ai_extract_structured_data($contentBlocks);
    }

    // Stash for the review screen — nothing touches the database yet
    $_SESSION['pending_extraction'] = $result;
    $_SESSION['pending_extraction_filename'] = $originalName;
    $_SESSION['pending_extraction_file_type'] = $ext;

    header("Location: review_extraction.php");
    exit();

} catch (\Throwable $e) {
    error_log("Smart upload error: " . $e->getMessage());
    header("Location: smart_upload.php?msg=" . urlencode($e->getMessage()));
    exit();
}
