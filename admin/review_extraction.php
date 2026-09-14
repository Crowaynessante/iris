<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

if (!isset($_SESSION['pending_extraction'])) {
    header("Location: smart_upload.php");
    exit();
}

$data = $_SESSION['pending_extraction'];
$filename = $_SESSION['pending_extraction_filename'] ?? 'uploaded file';
$fileType = $_SESSION['pending_extraction_file_type'] ?? '';
$rankings = $data['rankings'] ?? [];
$breakdowns = $data['ranking_breakdowns'] ?? [];
$colleges = $data['colleges'] ?? [];
$programs = $data['programs'] ?? [];
$accreditations = $data['accreditations'] ?? [];
$totalRows = count($rankings) + count($breakdowns) + count($colleges) + count($programs) + count($accreditations);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Review Extracted Data - IRIS Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="topbar">
    <h1>Review Before Saving</h1>
    <a href="smart_upload.php" class="btn btn-ghost">← Upload a different file</a>
</div>

<div class="container">
    <div class="card">
        <p>IRIS read <strong><?= htmlspecialchars($filename) ?></strong>
           <?php if ($fileType): ?><span class="badge"><?= htmlspecialchars(strtoupper($fileType)) ?></span><?php endif; ?>
           and found <strong><?= $totalRows ?></strong> row(s) across
           <?= count(array_filter([count($rankings), count($breakdowns), count($colleges), count($programs), count($accreditations)])) ?>
           data type(s). Check every value below, edit anything wrong,
           delete rows you don't want, then confirm to save.</p>
        <?php if ($totalRows === 0): ?>
            <p class="error">No recognizable ranking, accreditation, or organizational data was found in this file.</p>
        <?php endif; ?>
    </div>

    <form action="smart_upload_confirm.php" method="POST">
        <input type="hidden" name="file_type" value="<?= htmlspecialchars($fileType) ?>">

        <?php if (count($rankings)): ?>
        <div class="card">
            <h3>Rankings (<?= count($rankings) ?>)</h3>
            <p class="sub">Headline rank per ranking body/year. Global/PH rank can be a number, a band ("801-1000"), or status text ("Reporter Status") — enter it exactly as published.</p>
            <table>
                <tr><th>Include</th><th>Body</th><th>Category</th><th>Year</th><th>Global Rank</th><th>PH Rank</th><th>Note</th></tr>
                <?php foreach ($rankings as $i => $r): ?>
                <tr>
                    <td><input type="checkbox" name="rankings[<?= $i ?>][include]" checked></td>
                    <td><input type="text" name="rankings[<?= $i ?>][ranking_body_short_name]" value="<?= htmlspecialchars($r['ranking_body_short_name'] ?? '') ?>" style="width:90px"></td>
                    <td><input type="text" name="rankings[<?= $i ?>][category]" value="<?= htmlspecialchars($r['category'] ?? '') ?>" style="width:90px" placeholder="World / Asia / Impact"></td>
                    <td><input type="number" name="rankings[<?= $i ?>][year]" value="<?= htmlspecialchars($r['year'] ?? '') ?>" style="width:80px"></td>
                    <td><input type="text" name="rankings[<?= $i ?>][global_rank]" value="<?= htmlspecialchars($r['global_rank'] ?? '') ?>" style="width:100px"></td>
                    <td><input type="text" name="rankings[<?= $i ?>][ph_rank]" value="<?= htmlspecialchars($r['ph_rank'] ?? '') ?>" style="width:80px"></td>
                    <td><input type="text" name="rankings[<?= $i ?>][note]" value="<?= htmlspecialchars($r['note'] ?? '') ?>"></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <?php endif; ?>

        <?php if (count($breakdowns)): ?>
        <div class="card">
            <h3>Ranking Breakdowns (<?= count($breakdowns) ?>)</h3>
            <p class="sub">Sub-category rows under a headline rank — SDG ranks, WURI award categories, QS Stars categories, and similar.</p>
            <table>
                <tr><th>Include</th><th>Body</th><th>Year</th><th>Group</th><th>Item</th><th>Rank / Rating</th><th>Note</th></tr>
                <?php foreach ($breakdowns as $i => $b): ?>
                <tr>
                    <td><input type="checkbox" name="breakdowns[<?= $i ?>][include]" checked></td>
                    <td><input type="text" name="breakdowns[<?= $i ?>][ranking_body_short_name]" value="<?= htmlspecialchars($b['ranking_body_short_name'] ?? '') ?>" style="width:90px"></td>
                    <td><input type="number" name="breakdowns[<?= $i ?>][year]" value="<?= htmlspecialchars($b['year'] ?? '') ?>" style="width:80px"></td>
                    <td><input type="text" name="breakdowns[<?= $i ?>][group_label]" value="<?= htmlspecialchars($b['group_label'] ?? '') ?>" style="width:100px"></td>
                    <td><input type="text" name="breakdowns[<?= $i ?>][item_label]" value="<?= htmlspecialchars($b['item_label'] ?? '') ?>"></td>
                    <td><input type="text" name="breakdowns[<?= $i ?>][rank_display]" value="<?= htmlspecialchars($b['rank_display'] ?? '') ?>" style="width:120px"></td>
                    <td><input type="text" name="breakdowns[<?= $i ?>][note]" value="<?= htmlspecialchars($b['note'] ?? '') ?>"></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <?php endif; ?>

        <?php if (count($colleges)): ?>
        <div class="card">
            <h3>Colleges (<?= count($colleges) ?>)</h3>
            <table>
                <tr><th>Include</th><th>Name</th><th>Short Code</th><th>Contribution %</th><th>Year</th></tr>
                <?php foreach ($colleges as $i => $c): ?>
                <tr>
                    <td><input type="checkbox" name="colleges[<?= $i ?>][include]" checked></td>
                    <td><input type="text" name="colleges[<?= $i ?>][name]" value="<?= htmlspecialchars($c['name'] ?? '') ?>"></td>
                    <td><input type="text" name="colleges[<?= $i ?>][short_code]" value="<?= htmlspecialchars($c['short_code'] ?? '') ?>" style="width:100px"></td>
                    <td><input type="number" step="0.1" name="colleges[<?= $i ?>][contribution_percent]" value="<?= htmlspecialchars($c['contribution_percent'] ?? '') ?>" style="width:100px"></td>
                    <td><input type="number" name="colleges[<?= $i ?>][year]" value="<?= htmlspecialchars($c['year'] ?? '') ?>" style="width:80px"></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <?php endif; ?>

        <?php if (count($programs)): ?>
        <div class="card">
            <h3>Programs (<?= count($programs) ?>)</h3>
            <table>
                <tr><th>Include</th><th>Name</th><th>College Code</th><th>Nat. Rank</th><th>Score</th><th>Movement</th><th>Year</th></tr>
                <?php foreach ($programs as $i => $p): ?>
                <tr>
                    <td><input type="checkbox" name="programs[<?= $i ?>][include]" checked></td>
                    <td><input type="text" name="programs[<?= $i ?>][name]" value="<?= htmlspecialchars($p['name'] ?? '') ?>"></td>
                    <td><input type="text" name="programs[<?= $i ?>][college_short_code]" value="<?= htmlspecialchars($p['college_short_code'] ?? '') ?>" style="width:100px"></td>
                    <td><input type="number" name="programs[<?= $i ?>][national_rank]" value="<?= htmlspecialchars($p['national_rank'] ?? '') ?>" style="width:90px"></td>
                    <td><input type="number" step="0.1" name="programs[<?= $i ?>][score]" value="<?= htmlspecialchars($p['score'] ?? '') ?>" style="width:90px"></td>
                    <td><input type="number" name="programs[<?= $i ?>][movement]" value="<?= htmlspecialchars($p['movement'] ?? 0) ?>" style="width:80px"></td>
                    <td><input type="number" name="programs[<?= $i ?>][year]" value="<?= htmlspecialchars($p['year'] ?? '') ?>" style="width:80px"></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <?php endif; ?>

        <?php if (count($accreditations)): ?>
        <div class="card">
            <h3>Accreditations (<?= count($accreditations) ?>)</h3>
            <p class="sub">Program-level accreditation/certification assessments (AUN-QA and similar) — one row per criterion.</p>
            <table>
                <tr><th>Include</th><th>Program</th><th>Body</th><th>Year</th><th>Assessment Date</th><th>Criterion</th><th>Score</th></tr>
                <?php foreach ($accreditations as $i => $a): ?>
                <tr>
                    <td><input type="checkbox" name="accreditations[<?= $i ?>][include]" checked></td>
                    <td><input type="text" name="accreditations[<?= $i ?>][program_name]" value="<?= htmlspecialchars($a['program_name'] ?? '') ?>"></td>
                    <td><input type="text" name="accreditations[<?= $i ?>][accrediting_body]" value="<?= htmlspecialchars($a['accrediting_body'] ?? 'AUN-QA') ?>" style="width:100px"></td>
                    <td><input type="number" name="accreditations[<?= $i ?>][year]" value="<?= htmlspecialchars($a['year'] ?? '') ?>" style="width:80px"></td>
                    <td><input type="text" name="accreditations[<?= $i ?>][assessment_date]" value="<?= htmlspecialchars($a['assessment_date'] ?? '') ?>" style="width:130px"></td>
                    <td><input type="text" name="accreditations[<?= $i ?>][criterion]" value="<?= htmlspecialchars($a['criterion'] ?? '') ?>"></td>
                    <td><input type="text" name="accreditations[<?= $i ?>][score]" value="<?= htmlspecialchars($a['score'] ?? '') ?>" style="width:90px"></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <?php endif; ?>

        <?php if ($totalRows > 0): ?>
            <div class="actions-row">
                <button type="submit" class="btn btn-primary">✅ Confirm &amp; Save to Dashboard</button>
                <a href="smart_upload.php" class="btn btn-ghost">Cancel</a>
            </div>
        <?php endif; ?>
    </form>
</div>
</body>
</html>
