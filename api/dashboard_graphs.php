<?php
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = db();
    $sql = "SELECT sg.id, sg.record_id, sg.title, sg.chart_type, sg.orientation,
                   sg.value_axis_reversed, sg.value_axis_min, sg.value_axis_max,
                   sg.rank_semantic, sg.rank_value_min, sg.rank_value_max,
                   sg.labels, sg.values_data, sg.is_published, sg.created_at,
                   r.fileName AS source_file_name, r.status AS source_status
            FROM saved_graphs sg
            INNER JOIN records r ON r.id = sg.record_id
            WHERE r.status = 'Approved'
              AND COALESCE(sg.is_published, CASE WHEN r.status = 'Approved' THEN 1 ELSE 0 END) = 1
            ORDER BY sg.created_at DESC";
    $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as &$row) {
        $row['labels'] = json_decode((string)$row['labels'], true) ?: [];
        $row['values_data'] = json_decode((string)$row['values_data'], true) ?: [];
        $row['value_axis_reversed'] = (bool)$row['value_axis_reversed'];
        $row['rank_semantic'] = (bool)$row['rank_semantic'];
        $row['value_axis_min'] = $row['value_axis_min'] !== null ? (float)$row['value_axis_min'] : null;
        $row['value_axis_max'] = $row['value_axis_max'] !== null ? (float)$row['value_axis_max'] : null;
        $row['rank_value_min'] = $row['rank_value_min'] !== null ? (float)$row['rank_value_min'] : null;
        $row['rank_value_max'] = $row['rank_value_max'] !== null ? (float)$row['rank_value_max'] : null;
    }
    unset($row);

    $cardRows = $pdo->query("SELECT * FROM summary_cards WHERE is_published = 1 ORDER BY display_order ASC, created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
    $cards = array_map(static function (array $card): array {
        $card['display_precision'] = (int)($card['display_precision'] ?? 2);
        $card['display_order'] = (int)($card['display_order'] ?? 0);
        $card['is_published'] = (bool)($card['is_published'] ?? false);
        return $card;
    }, $cardRows);

    echo json_encode(['success' => true, 'graphs' => $rows, 'cards' => $cards], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Unable to load published dashboard graphs.']);
}
