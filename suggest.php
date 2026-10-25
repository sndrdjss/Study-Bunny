<?php
require 'db.php';

header('Content-Type: application/json; charset=utf-8');

$q = isset($_GET['q']) ? trim($_GET['q']) : '';

// Nothing meaningful to suggest for an empty query.
if ($q === '') {
    echo json_encode([]);
    exit;
}

$MAX_RESULTS = 8;

// Fixed category list (mirrors explore.php Featured Categories).
$ALL_CATEGORIES = [
    'Mathematics', 'Science', 'English', 'History',
    'Geography', 'Art', 'Web Development',
];

$results = [];

// 1) Matching categories (fast, in-memory).
foreach ($ALL_CATEGORIES as $cat) {
    if (stripos($cat, $q) !== false) {
        $results[] = [
            'label' => $cat,
            'type'  => 'category',
            'url'   => 'collection.php?category=' . urlencode($cat),
        ];
    }
}

// 2) Matching published deck titles.
try {
    $remaining = $MAX_RESULTS - count($results);
    if ($remaining > 0) {
        $like = '%' . $q . '%';
        $stmt = $conn->prepare(
            "SELECT id, title FROM deck
             WHERE status = 'published' AND title LIKE ?
             ORDER BY title ASC
             LIMIT ?"
        );
        $stmt->bind_param('si', $like, $remaining);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $results[] = [
                'label' => $row['title'],
                'type'  => 'deck',
                'id'    => (int) $row['id'],
                // Honors the single-results-page requirement (3=b).
                'url'   => 'search.php?q=' . urlencode($row['title']),
            ];
        }
        $stmt->close();
    }
} catch (mysqli_sql_exception $e) {
    // On DB error, still return whatever category matches we have.
}

// Cap the overall list.
$results = array_slice($results, 0, $MAX_RESULTS);

echo json_encode($results);
