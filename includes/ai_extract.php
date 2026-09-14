<?php
// ============================================
// Sends file content (as text, a PDF document block, or an image block)
// to Claude and asks it to return structured JSON matching our schema.
//
// Requires an Anthropic API key set as a server environment variable:
//   CLAUDE_API_KEY
// Get one at https://console.anthropic.com — do NOT hardcode it here.
// ============================================

const EXTRACTION_SCHEMA_PROMPT = <<<PROMPT
You are extracting university ranking, accreditation, and organizational data
from a document (which may be a table, a report, an infographic, or a photo
of a printed page) for a dashboard system.

Return ONLY valid JSON (no markdown fences, no commentary) in exactly this shape:
{
  "rankings": [
    {"ranking_body_short_name": "QS", "year": 2024, "category": "World", "global_rank": "641", "ph_rank": "7", "note": "..."}
  ],
  "ranking_breakdowns": [
    {"ranking_body_short_name": "THE", "year": 2025, "group_label": "SDG", "item_label": "Zero Hunger", "rank_display": "301-400", "note": "..."}
  ],
  "colleges": [
    {"name": "College of Engineering", "short_code": "CEAT", "contribution_percent": 28, "year": 2024}
  ],
  "programs": [
    {"name": "Agricultural Engineering", "college_short_code": "CEAT", "national_rank": 1, "score": 94.2, "movement": 3, "year": 2024}
  ],
  "accreditations": [
    {"program_name": "Doctor of Veterinary Medicine", "accrediting_body": "AUN-QA", "year": 2024, "assessment_date": "July 9-11", "criterion": "Expected Learning Outcomes", "score": "4"}
  ]
}

Rules:
- Known ranking_body_short_name values: QS, THE, CWTS, Webometrics, URAP, SCImago, WURI, AppliedHE, "AD Scientific Index", EduRank. If the document names a different body, still include it with your best-guess short name.
- "global_rank" and "ph_rank" are STRINGS, exactly as published - they may be a plain number ("161"), a band ("801-1000", "1001-1100"), an ordinal ("2nd"), or status text with no number at all ("Reporter Status", "Not listed"). Never convert a band into a single number or invent a number that wasn't printed.
- "category" on a ranking distinguishes multiple lists the same body publishes in one year (e.g. QS "World" vs QS "Asia", THE "Impact" vs THE "World University Rankings"). Omit it if the body only publishes one list.
- Use "rankings" for a single headline rank per body/year/category. Use "ranking_breakdowns" for anything that is a sub-list underneath a headline rank: THE Impact's per-SDG ranks, WURI's top award categories with their own sub-rank, QS Stars' per-category star ratings (put the rating and score together in rank_display, e.g. "5 stars (123/150)"), AppliedHE's regional/national splits, etc. "group_label" names the kind of breakdown (e.g. "SDG", "QS Stars", "WURI Category"); "item_label" is the specific one (e.g. "Zero Hunger", "Teaching").
- Use "accreditations" for program-level accreditation or certification assessments (AUN-QA and similar) - one row per criterion per program per assessment, including a final row with criterion "Overall Verdict" whose score is the text verdict (e.g. "Adequate as Expected") rather than a number.
- If a field is not present in the document, omit it or use null - do not invent numbers or dates.
- If the document contains no data for one of the categories, return an empty array for it.
- "movement" is the change in rank versus the prior period; use 0 if not mentioned.
- Respond with the JSON object and nothing else.
PROMPT;

/**
 * Reports whether the server is actually able to run every part of the
 * Smart Upload pipeline, so the upload page can show a real diagnostic
 * instead of the user guessing why "nothing works". A missing PHP
 * extension or an env var that never made it into the web server's
 * process are both extremely common on a fresh XAMPP/WAMP install and
 * fail completely silently otherwise.
 *
 * @return array<string, array{ok: bool, label: string}>
 */
function check_smart_upload_requirements(): array {
    $checks = [];

    $checks['api_key'] = [
        'ok' => (bool) getenv('CLAUDE_API_KEY'),
        'label' => getenv('CLAUDE_API_KEY')
            ? 'CLAUDE_API_KEY is set — Word, PDF, Excel, and image uploads can reach the AI.'
            : 'CLAUDE_API_KEY is not set for this PHP process — Word/PDF/Excel/image uploads will fail until it is.',
    ];

    $checks['curl'] = [
        'ok' => function_exists('curl_init'),
        'label' => function_exists('curl_init')
            ? 'cURL extension enabled.'
            : 'PHP cURL extension is missing — no file type can reach the AI without it.',
    ];

    $checks['zip'] = [
        'ok' => class_exists('ZipArchive'),
        'label' => class_exists('ZipArchive')
            ? 'ZipArchive available (needed to open .docx/.xlsx files).'
            : 'PHP zip extension is missing — Word (.docx) and Excel (.xlsx) files cannot be opened. Enable "extension=zip" in php.ini and restart the server.',
    ];

    $checks['fileinfo'] = [
        'ok' => function_exists('finfo_open'),
        'label' => function_exists('finfo_open')
            ? 'fileinfo extension enabled.'
            : 'PHP fileinfo extension is missing — file type detection may be less reliable.',
    ];

    return $checks;
}

function smart_upload_requirements_ok(array $checks): bool {
    foreach ($checks as $key => $check) {
        if ($key === 'fileinfo') continue; // nice-to-have, not required
        if (!$check['ok']) return false;
    }
    return true;
}

/**
 * @param array $contentBlocks Anthropic API content blocks (text/document/image)
 * @return array{rankings: array, colleges: array, programs: array}
 * @throws \RuntimeException with a message safe to show the admin directly
 */
function ai_extract_structured_data(array $contentBlocks): array {
    $apiKey = getenv('CLAUDE_API_KEY');
    if (!$apiKey) {
        throw new \RuntimeException(
            "AI extraction is not configured on this server: the CLAUDE_API_KEY environment variable isn't visible to PHP. " .
            "If you already set it in Windows/System settings, make sure it was set BEFORE Apache/XAMPP started (restart the Apache service), " .
            "or set it directly for this app instead — see the Setup section on this page."
        );
    }
    if (!function_exists('curl_init')) {
        throw new \RuntimeException("The PHP cURL extension is not enabled on this server, so it can't reach the Anthropic API.");
    }

    $messageContent = array_merge(
        [["type" => "text", "text" => EXTRACTION_SCHEMA_PROMPT]],
        $contentBlocks
    );

    $payload = json_encode([
        "model" => "claude-sonnet-4-6",
        "max_tokens" => 2000,
        "messages" => [["role" => "user", "content" => $messageContent]]
    ]);

    $ch = curl_init("https://api.anthropic.com/v1/messages");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json",
            "x-api-key: $apiKey",
            "anthropic-version: 2023-06-01"
        ],
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_TIMEOUT => 90
    ]);
    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($curlError) {
        error_log("AI extraction cURL error: $curlError");
        throw new \RuntimeException("Could not reach the Anthropic API (network error: $curlError). Check the server's internet/firewall access.");
    }
    if (!$response) {
        throw new \RuntimeException("The Anthropic API returned an empty response.");
    }

    $decoded = json_decode($response, true);

    if ($httpCode !== 200) {
        $apiErrorMsg = $decoded['error']['message'] ?? $response;
        error_log("AI extraction: API returned HTTP $httpCode: $response");
        throw new \RuntimeException("The Anthropic API rejected the request (HTTP $httpCode): $apiErrorMsg");
    }

    $text = $decoded['content'][0]['text'] ?? null;
    if (!$text) {
        error_log("AI extraction: unexpected API response: $response");
        throw new \RuntimeException("The API response didn't contain the expected data. Please try again.");
    }

    // Be forgiving about stray markdown fences or chatter around the JSON -
    // grab the outermost {...} block rather than assuming the text starts/ends cleanly.
    $start = strpos($text, '{');
    $end = strrpos($text, '}');
    if ($start === false || $end === false || $end < $start) {
        error_log("AI extraction: could not locate JSON in model output: $text");
        throw new \RuntimeException("Could not find structured data in the AI's response. Try again, or try a clearer file.");
    }
    $jsonText = substr($text, $start, $end - $start + 1);

    $parsed = json_decode($jsonText, true);
    if (!is_array($parsed)) {
        error_log("AI extraction: could not parse JSON from model output: $jsonText");
        throw new \RuntimeException("Could not parse the AI's response as valid data. Try again, or try a clearer file.");
    }

    return [
        'rankings' => $parsed['rankings'] ?? [],
        'colleges' => $parsed['colleges'] ?? [],
        'programs' => $parsed['programs'] ?? [],
    ];
}

// ---- Helpers to build the right content block per file type ----

function build_text_block(string $text): array {
    return ["type" => "text", "text" => "Document content:\n\n" . $text];
}

function build_pdf_block(string $filePath): array {
    $base64 = base64_encode(file_get_contents($filePath));
    return [
        "type" => "document",
        "source" => ["type" => "base64", "media_type" => "application/pdf", "data" => $base64]
    ];
}

function build_image_block(string $filePath, string $mimeType): array {
    $base64 = base64_encode(file_get_contents($filePath));
    return [
        "type" => "image",
        "source" => ["type" => "base64", "media_type" => $mimeType, "data" => $base64]
    ];
}
