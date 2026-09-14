<?php
// ============================================
// Pulls plain text / structured rows out of CSV, Word, and Excel files
// so they can be handed to the AI extractor (or, for CSV, mapped directly).
// Requires: composer require phpoffice/phpword phpoffice/phpspreadsheet
// ============================================

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Recursively walks any PhpWord element (Section, TextRun, ListItemRun,
 * Footnote, Cell, etc.) and pulls out every bit of readable text.
 * The previous version only handled elements with getText() or a flat
 * getElements() and skipped Table entirely (getRows()/getCells(), not
 * getElements()) - so any Word doc that put ranking data in a table
 * (the normal way to present a ranking table) silently lost all of it.
 */
function extract_text_from_word_element($element): string {
    $text = "";

    if (method_exists($element, 'getText')) {
        $t = $element->getText();
        // getText() can return a string or, for some elements, an array of runs
        $text .= is_array($t) ? implode('', $t) : $t;
        return $text . " ";
    }

    // Tables don't expose getElements() - they expose getRows() -> getCells()
    if (method_exists($element, 'getRows')) {
        foreach ($element->getRows() as $row) {
            $cellTexts = [];
            foreach ($row->getCells() as $cell) {
                $cellText = "";
                foreach ($cell->getElements() as $cellElement) {
                    $cellText .= extract_text_from_word_element($cellElement);
                }
                $cellTexts[] = trim($cellText);
            }
            $text .= implode(" | ", $cellTexts) . "\n";
        }
        return $text;
    }

    // Anything else that's a container (TextRun, ListItemRun, Cell, Footnote...)
    if (method_exists($element, 'getElements')) {
        foreach ($element->getElements() as $childElement) {
            $text .= extract_text_from_word_element($childElement);
        }
        return $text . "\n";
    }

    return $text;
}

function extract_text_from_docx(string $filePath): string {
    $phpWord = \PhpOffice\PhpWord\IOFactory::load($filePath);
    $text = "";
    foreach ($phpWord->getSections() as $section) {
        foreach ($section->getElements() as $element) {
            $text .= extract_text_from_word_element($element);
        }
    }
    $text = trim($text);
    if ($text === "") {
        throw new \RuntimeException("No readable text found in this Word document (it may be scanned images pasted into the doc - try exporting it as a PDF instead, or upload a screenshot as an image).");
    }
    return $text;
}

function extract_text_from_spreadsheet(string $filePath): string {
    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
    $text = "";
    foreach ($spreadsheet->getAllSheets() as $sheet) {
        $rows = $sheet->toArray(null, true, true, false);
        // Skip sheets that are entirely empty (common with extra blank tabs)
        $hasContent = false;
        $sheetText = "Sheet: " . $sheet->getTitle() . "\n";
        foreach ($rows as $row) {
            if (count(array_filter($row, fn($c) => $c !== null && $c !== '')) === 0) continue;
            $hasContent = true;
            $sheetText .= implode(" | ", array_map(fn($cell) => $cell ?? '', $row)) . "\n";
        }
        if ($hasContent) $text .= $sheetText;
    }
    $text = trim($text);
    if ($text === "") {
        throw new \RuntimeException("This spreadsheet appears to be empty, or all its sheets are blank.");
    }
    return $text;
}

/**
 * Reads a CSV into a plain array of associative rows (header row => values),
 * trimming empty rows/columns along the way. Used both for the direct
 * column-matched import and as a text fallback for the AI extractor.
 */
function read_csv_rows(string $filePath): array {
    $handle = fopen($filePath, 'r');
    if ($handle === false) {
        throw new \RuntimeException("Could not open the CSV file.");
    }

    // Handle files saved with a UTF-8 BOM (very common from Excel)
    $bom = fread($handle, 3);
    if ($bom !== "\xEF\xBB\xBF") {
        rewind($handle);
    }

    $header = fgetcsv($handle);
    if ($header === false) {
        fclose($handle);
        throw new \RuntimeException("The CSV file appears to be empty.");
    }
    $header = array_map(fn($h) => strtolower(trim((string) $h)), $header);

    $rows = [];
    while (($row = fgetcsv($handle)) !== false) {
        if (count(array_filter($row, fn($c) => trim((string) $c) !== '')) === 0) continue; // blank line
        $row = array_pad($row, count($header), null);
        $rows[] = array_combine($header, array_slice($row, 0, count($header)));
    }
    fclose($handle);
    return $rows;
}

/**
 * Looks at a CSV's header row and, if it matches one of the three known
 * templates (rankings / colleges / programs), maps every row straight into
 * the same {rankings, colleges, programs} shape the AI extractor returns -
 * so CSV goes through the same Smart Upload review screen as every other
 * file type instead of being a special case that bounces the user away.
 * Returns null if the header doesn't match any known template.
 */
function smart_map_csv(string $filePath): ?array {
    $rows = read_csv_rows($filePath);
    if (empty($rows)) {
        return ['rankings' => [], 'colleges' => [], 'programs' => []];
    }
    $columns = array_keys($rows[0]);

    $templates = [
        'rankings' => ['ranking_body_short_name', 'year', 'global_rank', 'ph_rank'],
        'colleges' => ['name', 'short_code', 'contribution_percent', 'year'],
        'programs' => ['name', 'college_short_code', 'national_rank', 'score'],
    ];

    $bestMatch = null;
    $bestScore = 0;
    foreach ($templates as $type => $requiredCols) {
        $score = count(array_intersect($requiredCols, $columns));
        if ($score > $bestScore) {
            $bestScore = $score;
            $bestMatch = $type;
        }
    }

    // Require at least half the template's columns to be present before
    // trusting the guess; otherwise fall back to sending it to the AI as text.
    if ($bestMatch === null || $bestScore < ceil(count($templates[$bestMatch]) / 2)) {
        return null;
    }

    $result = ['rankings' => [], 'colleges' => [], 'programs' => []];
    foreach ($rows as $row) {
        if ($bestMatch === 'rankings') {
            $result['rankings'][] = [
                'ranking_body_short_name' => $row['ranking_body_short_name'] ?? '',
                'year' => $row['year'] ?? null,
                'global_rank' => $row['global_rank'] ?? null,
                'ph_rank' => $row['ph_rank'] ?? null,
                'note' => $row['note'] ?? '',
            ];
        } elseif ($bestMatch === 'colleges') {
            $result['colleges'][] = [
                'name' => $row['name'] ?? '',
                'short_code' => $row['short_code'] ?? '',
                'contribution_percent' => $row['contribution_percent'] ?? null,
                'year' => $row['year'] ?? null,
            ];
        } elseif ($bestMatch === 'programs') {
            $result['programs'][] = [
                'name' => $row['name'] ?? '',
                'college_short_code' => $row['college_short_code'] ?? '',
                'national_rank' => $row['national_rank'] ?? null,
                'score' => $row['score'] ?? null,
                'movement' => $row['movement'] ?? 0,
                'year' => $row['year'] ?? null,
            ];
        }
    }
    return $result;
}

/** Turns CSV rows into a text block, for when the header doesn't match a
 * known template and we need the AI to make sense of the columns instead. */
function csv_rows_to_text(string $filePath): string {
    $rows = read_csv_rows($filePath);
    if (empty($rows)) {
        throw new \RuntimeException("The CSV file appears to be empty.");
    }
    $columns = array_keys($rows[0]);
    $text = implode(" | ", $columns) . "\n";
    foreach ($rows as $row) {
        $text .= implode(" | ", array_map(fn($v) => $v ?? '', $row)) . "\n";
    }
    return $text;
}
