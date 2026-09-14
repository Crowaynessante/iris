<?php
// ============================================
// Returns a plain-language summary of the current dashboard data.
// Called via fetch() from user/dashboard.php.
//
// Two modes:
//   1. RULE-BASED (default) — works immediately, no API key needed.
//   2. LLM-POWERED — set USE_LLM to true and fill in your API key
//      to have an actual AI model write the summary sentence.
// ============================================

require_once __DIR__ . '/../includes/functions.php';
require_login();
header('Content-Type: application/json');

const USE_LLM = false; // flip to true once you add an API key below

// ---- Gather the same data the dashboard shows ----
$best = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT r.global_rank, rb.name AS body_name, r.year
    FROM rankings r JOIN ranking_bodies rb ON rb.id = r.ranking_body_id
    WHERE r.rank_value IS NOT NULL
    ORDER BY r.year DESC, r.rank_value ASC LIMIT 1
"));

$phRank = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT ph_rank FROM rankings WHERE ph_rank IS NOT NULL ORDER BY year DESC LIMIT 1
"));

$trendRows = mysqli_query($conn, "
    SELECT r.year, r.rank_value AS global_rank
    FROM rankings r JOIN ranking_bodies rb ON rb.id = r.ranking_body_id
    WHERE rb.short_name = 'QS' AND r.category IS NULL AND r.rank_value IS NOT NULL
    ORDER BY r.year ASC
");
$trend = [];
while ($row = mysqli_fetch_assoc($trendRows)) $trend[] = $row;

$topProgramRow = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT name, national_rank, score, movement FROM programs
    ORDER BY national_rank ASC LIMIT 1
"));

// ---- Build the data snapshot both modes use ----
$movement = null;
if (count($trend) >= 2) {
    $movement = $trend[count($trend) - 2]['global_rank'] - $trend[count($trend) - 1]['global_rank'];
}

$dataSnapshot = [
    'best_global_rank' => $best['global_rank'] ?? null,
    'best_body'         => $best['body_name'] ?? null,
    'ph_rank'           => $phRank['ph_rank'] ?? null,
    'positions_gained'  => $movement,
    'top_program'       => $topProgramRow['name'] ?? null,
    'top_program_rank'  => $topProgramRow['national_rank'] ?? null,
];

if (USE_LLM) {
    echo json_encode(['summary' => generate_llm_summary($dataSnapshot)]);
} else {
    echo json_encode(['summary' => generate_rule_based_summary($dataSnapshot)]);
}

// ============================================
// Mode 1: Rule-based (no external API, always works)
// ============================================
function generate_rule_based_summary($d) {
    $parts = [];

    if ($d['best_global_rank']) {
        $parts[] = "CLSU currently holds a best global rank of {$d['best_global_rank']}" .
                   ($d['best_body'] ? " on {$d['best_body']}" : "") . ".";
    }
    if ($d['ph_rank']) {
        $parts[] = "Nationally, it ranks {$d['ph_rank']}th among Philippine universities.";
    }
    if ($d['positions_gained'] !== null) {
        $parts[] = $d['positions_gained'] > 0
            ? "It has climbed {$d['positions_gained']} positions compared to the previous year."
            : ($d['positions_gained'] < 0
                ? "It has dropped " . abs($d['positions_gained']) . " positions compared to the previous year."
                : "Its rank held steady compared to the previous year.");
    }
    if ($d['top_program']) {
        $parts[] = "Its top-performing program is {$d['top_program']}, ranked #{$d['top_program_rank']} nationally.";
    }

    return count($parts) ? implode(" ", $parts) : "Not enough data has been uploaded yet to generate a summary.";
}

// ============================================
// Mode 2: LLM-powered — sends the same data snapshot to an AI model
// and asks it to phrase the summary. Uses Anthropic's Claude API here;
// swap the endpoint/payload if your team prefers a different provider.
//
// SECURITY: never hardcode the real key in this file if it will be
// committed to git or deployed publicly. Store it in an untracked
// config file or a server environment variable instead, e.g.:
//   $apiKey = getenv('CLAUDE_API_KEY');
// ============================================
function generate_llm_summary($d) {
    $apiKey = getenv('CLAUDE_API_KEY'); // set this on your server, don't paste the key here

    if (!$apiKey) {
        return generate_rule_based_summary($d); // safe fallback if no key configured
    }

    $prompt = "Summarize this university ranking data in 2-3 friendly sentences for a dashboard viewer: "
             . json_encode($d);

    $payload = json_encode([
        "model" => "claude-sonnet-4-6",
        "max_tokens" => 300,
        "messages" => [["role" => "user", "content" => $prompt]]
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
        CURLOPT_TIMEOUT => 15
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    $decoded = json_decode($response, true);
    return $decoded['content'][0]['text'] ?? generate_rule_based_summary($d);
}
