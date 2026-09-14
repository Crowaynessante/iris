<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();

// --- Pull the latest data (mirrors the Figma cards) ---

// Best overall global rank = lowest rank_value (the derived sortable number)
// across all bodies, latest year. Bands/status text ("801-1000", "Reporter
// Status") don't have a well-defined "lowest", so those are naturally
// excluded here since rank_value is NULL for pure status text.
$best = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT r.global_rank, rb.name AS body_name, r.year
    FROM rankings r JOIN ranking_bodies rb ON rb.id = r.ranking_body_id
    WHERE r.rank_value IS NOT NULL
    ORDER BY r.year DESC, r.rank_value ASC LIMIT 1
"));

$phRank = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT ph_rank FROM rankings WHERE ph_rank IS NOT NULL ORDER BY year DESC LIMIT 1
"));

$bodyCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c FROM ranking_bodies"))['c'];

// Per-body cards (latest year per body). Shows the published global_rank as-is
// (a number, a band, or status text) plus its category when the body
// publishes more than one list (e.g. QS World vs QS Asia).
$bodyCards = mysqli_query($conn, "
    SELECT rb.short_name, rb.name, r.category, r.global_rank, r.year, r.note
    FROM rankings r
    JOIN ranking_bodies rb ON rb.id = r.ranking_body_id
    WHERE r.year = (SELECT MAX(r2.year) FROM rankings r2 WHERE r2.ranking_body_id = r.ranking_body_id)
    ORDER BY rb.short_name, r.category
");

// QS global rank trend (line chart). Uses rank_value (numeric) for plotting
// and keeps global_rank (display text) alongside for the tooltip, since a
// point could be "801-1000" rather than a clean number.
$trend = mysqli_query($conn, "
    SELECT r.year, r.rank_value, r.global_rank
    FROM rankings r JOIN ranking_bodies rb ON rb.id = r.ranking_body_id
    WHERE rb.short_name = 'QS' AND r.category IS NULL
    ORDER BY r.year ASC
");
$trendYears = [];
$trendRanks = [];
$trendDisplay = [];
while ($row = mysqli_fetch_assoc($trend)) {
    $trendYears[] = $row['year'];
    $trendRanks[] = $row['rank_value'] !== null ? (int)$row['rank_value'] : null;
    $trendDisplay[] = $row['global_rank'];
}

// College contribution (latest year, donut chart)
$latestCollegeYear = mysqli_fetch_assoc(mysqli_query($conn, "SELECT MAX(year) AS y FROM colleges"))['y'];
$colleges = mysqli_query($conn, "SELECT name, short_code, contribution_percent FROM colleges WHERE year = " . (int)$latestCollegeYear . " ORDER BY contribution_percent DESC");
$collegeLabels = [];
$collegeValues = [];
$collegeRowsForSummary = [];
while ($row = mysqli_fetch_assoc($colleges)) {
    $collegeLabels[] = $row['name'];
    $collegeValues[] = (float)$row['contribution_percent'];
    $collegeRowsForSummary[] = $row;
}

// Program rankings table (latest year)
$latestProgramYear = mysqli_fetch_assoc(mysqli_query($conn, "SELECT MAX(year) AS y FROM programs"))['y'];
$programs = mysqli_query($conn, "
    SELECT p.national_rank, p.name, c.short_code, p.score, p.movement
    FROM programs p JOIN colleges c ON c.id = p.college_id
    WHERE p.year = " . (int)$latestProgramYear . "
    ORDER BY p.national_rank ASC
");

// Ranking breakdowns (SDG ranks, WURI award categories, QS Stars categories,
// etc.) - one mini chart/table per ranking body, for that body's latest year
// that has breakdown rows.
$breakdownGroups = mysqli_query($conn, "
    SELECT rb.id AS body_id, rb.short_name, rb.name, b.year
    FROM ranking_breakdowns b
    JOIN ranking_bodies rb ON rb.id = b.ranking_body_id
    WHERE b.year = (SELECT MAX(b2.year) FROM ranking_breakdowns b2 WHERE b2.ranking_body_id = b.ranking_body_id)
    GROUP BY rb.id, rb.short_name, rb.name, b.year
    ORDER BY rb.short_name
");
$breakdownSections = [];
while ($g = mysqli_fetch_assoc($breakdownGroups)) {
    $rows = mysqli_query($conn, "
        SELECT group_label, item_label, rank_display, rank_value
        FROM ranking_breakdowns
        WHERE ranking_body_id = " . (int)$g['body_id'] . " AND year = " . (int)$g['year'] . "
        ORDER BY (rank_value IS NULL), rank_value ASC, item_label ASC
    ");
    $items = [];
    while ($r = mysqli_fetch_assoc($rows)) $items[] = $r;
    if ($items) $breakdownSections[] = ['body' => $g, 'items' => $items];
}

// Program accreditation (AUN-QA and similar) - one summary row per
// program/assessment: its overall verdict plus the average of its numeric
// criterion scores.
$accreditations = mysqli_query($conn, "
    SELECT program_name, accrediting_body, year, MAX(assessment_date) AS assessment_date,
           MAX(CASE WHEN criterion = 'Overall Verdict' THEN score END) AS verdict,
           AVG(numeric_score) AS avg_score
    FROM accreditations
    GROUP BY program_name, accrediting_body, year
    ORDER BY year DESC, program_name ASC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CLSU Performance Observatory - IRIS</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
</head>
<body>

<div class="topbar green">
    <div>
        <h1>CLSU Performance Observatory</h1>
        <span>RANKINGS DASHBOARD</span>
    </div>
    <div>
        <span class="pill">Welcome, <?= htmlspecialchars($_SESSION['username']) ?></span>
        <?php if (is_admin()): ?><a href="../admin/dashboard.php" class="btn btn-ghost">Admin</a><?php endif; ?>
        <a href="../auth/logout.php" class="btn btn-ghost">Logout</a>
    </div>
</div>

<div class="container">

    <h2>University-Wide Overview</h2>
    <div class="card overview">
        <div>
            <p class="label">BEST GLOBAL RANK</p>
            <p class="big-number"><?= htmlspecialchars($best['global_rank'] ?? '—') ?></p>
            <p class="sub"><?= htmlspecialchars($best['body_name'] ?? '') ?> · <?= htmlspecialchars($best['year'] ?? '') ?></p>
        </div>
        <div>
            <p class="label">PHILIPPINES RANK</p>
            <p class="mid-number"><?= htmlspecialchars($phRank['ph_rank'] ?? '—') ?></p>

            <p class="label">RANKING BODIES TRACKED</p>
            <p class="mid-number"><?= (int)$bodyCount ?></p>
        </div>
    </div>

    <h2>By Ranking Body</h2>
    <div class="grid-3">
        <?php while ($row = mysqli_fetch_assoc($bodyCards)): ?>
            <div class="card">
                <p class="label"><?= htmlspecialchars($row['short_name']) ?><?= $row['category'] ? ' · ' . htmlspecialchars($row['category']) : '' ?></p>
                <h3><?= htmlspecialchars($row['name']) ?></h3>
                <p class="big-number"><?= htmlspecialchars($row['global_rank'] ?? '—') ?></p>
                <p class="sub">Global rank · <?= htmlspecialchars($row['year']) ?></p>
                <?php if ($row['note']): ?><p class="hint"><?= htmlspecialchars($row['note']) ?></p><?php endif; ?>
            </div>
        <?php endwhile; ?>
    </div>

    <h2>By College</h2>
    <div class="grid-2">
        <div class="card">
            <h3>Global Rank Trend</h3>
            <p class="sub">QS World University Rankings</p>
            <canvas id="trendChart"></canvas>
        </div>
        <div class="card">
            <h3>College Contribution to Rank Score</h3>
            <canvas id="collegeChart"></canvas>
        </div>
    </div>

    <div class="card">
        <h3>AI Summary</h3>
        <div id="ai-summary">Generating summary…</div>
        <button onclick="loadSummary()" class="btn btn-outline btn-sm" style="margin-top:14px;">🔄 Regenerate</button>
    </div>

    <h2>National Program Rankings</h2>
    <div class="card">
        <table>
            <tr><th>Rank</th><th>Program</th><th>College</th><th>Score</th><th>Movement</th></tr>
            <?php while ($row = mysqli_fetch_assoc($programs)):
                $moveClass = $row['movement'] > 0 ? 'up' : ($row['movement'] < 0 ? 'down' : 'flat');
                $moveText = $row['movement'] > 0 ? "+{$row['movement']}" : ($row['movement'] < 0 ? $row['movement'] : "—");
            ?>
                <tr>
                    <td><?= (int)$row['national_rank'] ?></td>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td><?= htmlspecialchars($row['short_code']) ?></td>
                    <td><?= number_format($row['score'], 1) ?></td>
                    <td class="<?= $moveClass ?>"><?= $moveText ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
    </div>

    <?php if (count($breakdownSections)): ?>
    <h2>Category Breakdowns</h2>
    <p class="sub">Per-category detail underneath each body's headline rank — e.g. THE Impact's per-SDG ranks, WURI's award categories, or QS Stars' per-category ratings.</p>
    <div class="grid-3">
        <?php foreach ($breakdownSections as $idx => $section): ?>
            <div class="card">
                <p class="label"><?= htmlspecialchars($section['body']['short_name']) ?> · <?= htmlspecialchars($section['body']['year']) ?></p>
                <h3><?= htmlspecialchars($section['items'][0]['group_label'] ?? 'Breakdown') ?></h3>
                <canvas id="breakdownChart<?= $idx ?>" height="220"></canvas>
                <table style="margin-top:12px;">
                    <?php foreach ($section['items'] as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['item_label']) ?></td>
                            <td style="text-align:right;"><?= htmlspecialchars($item['rank_display'] ?? '—') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (mysqli_num_rows($accreditations) > 0): ?>
    <h2>Program Accreditation</h2>
    <p class="sub">AUN-QA (and similar) program-level assessments — overall verdict plus the average of each program's numeric criterion scores.</p>
    <div class="card">
        <table>
            <tr><th>Program</th><th>Body</th><th>Year</th><th>Assessment Date</th><th>Avg. Criterion Score</th><th>Overall Verdict</th></tr>
            <?php while ($row = mysqli_fetch_assoc($accreditations)): ?>
                <tr>
                    <td><?= htmlspecialchars($row['program_name']) ?></td>
                    <td><?= htmlspecialchars($row['accrediting_body']) ?></td>
                    <td><?= htmlspecialchars($row['year']) ?></td>
                    <td><?= htmlspecialchars($row['assessment_date'] ?? '—') ?></td>
                    <td><?= $row['avg_score'] !== null ? number_format($row['avg_score'], 2) : '—' ?></td>
                    <td><?= htmlspecialchars($row['verdict'] ?? '—') ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
    </div>
    <?php endif; ?>

</div>

<script>
const trendYears = <?= json_encode($trendYears) ?>;
const trendRanks = <?= json_encode($trendRanks) ?>;
const trendDisplay = <?= json_encode($trendDisplay) ?>;
const collegeLabels = <?= json_encode($collegeLabels) ?>;
const collegeValues = <?= json_encode($collegeValues) ?>;

new Chart(document.getElementById('trendChart'), {
    type: 'line',
    data: {
        labels: trendYears,
        datasets: [{
            label: 'Global Rank',
            data: trendRanks,
            borderColor: '#1f7a3f',
            backgroundColor: '#1f7a3f',
            tension: 0.3,
            spanGaps: true
        }]
    },
    options: {
        scales: { y: { reverse: true, title: { display: true, text: 'Lower = better' } } },
        plugins: {
            tooltip: {
                callbacks: {
                    // Show the real published value ("801-1000", "Reporter Status")
                    // rather than just the plotted number, since they can differ.
                    label: (ctx) => 'Rank: ' + (trendDisplay[ctx.dataIndex] ?? '—')
                }
            }
        }
    }
});

new Chart(document.getElementById('collegeChart'), {
    type: 'doughnut',
    data: {
        labels: collegeLabels,
        datasets: [{
            data: collegeValues,
            backgroundColor: ['#1f7a3f', '#0f4d24', '#d4a017', '#f2c14e', '#b0b0b0', '#8fbf9f']
        }]
    }
});

// One small bar chart per category-breakdown section (SDG ranks, WURI
// categories, QS Stars categories, etc). Lower bars = better rank, since the
// axis is reversed the same way the trend chart is.
const breakdownSections = <?= json_encode(array_map(function ($s) {
    return [
        'labels' => array_map(fn($i) => $i['item_label'], $s['items']),
        'values' => array_map(fn($i) => $i['rank_value'] !== null ? (int)$i['rank_value'] : null, $s['items']),
    ];
}, $breakdownSections)) ?>;

breakdownSections.forEach((section, idx) => {
    const el = document.getElementById('breakdownChart' + idx);
    if (!el) return;
    new Chart(el, {
        type: 'bar',
        data: {
            labels: section.labels,
            datasets: [{
                label: 'Rank',
                data: section.values,
                backgroundColor: '#1f7a3f'
            }]
        },
        options: {
            indexAxis: 'y',
            scales: { x: { reverse: true, title: { display: true, text: 'Lower = better' } } },
            plugins: { legend: { display: false } }
        }
    });
});

// Pull the AI-generated plain-language summary of the current dashboard data
function loadSummary() {
    document.getElementById('ai-summary').textContent = "Generating summary…";
    fetch('../api/summary.php')
        .then(res => res.json())
        .then(data => {
            document.getElementById('ai-summary').textContent = data.summary;
        })
        .catch(() => {
            document.getElementById('ai-summary').textContent = "Summary unavailable right now.";
        });
}
loadSummary();
</script>
</body>
</html>
