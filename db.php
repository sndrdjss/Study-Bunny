<?php

$host     = 'localhost';
$user     = 'root';
$password = '';
$database = 'hcieinsproject';


$conn = new mysqli($host, $user, $password, $database);


if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

/**
 * Return the set of deck IDs the given user has favorited.
 *
 * Shape: [deck_id => true, ...] so callers can do O(1) isset() checks
 * when deciding whether to render a filled ("on") heart.
 *
 * Returns an empty array for guests (userId <= 0) or on any DB error,
 * so pages degrade gracefully (every heart simply renders empty).
 */
function favorite_deck_ids(mysqli $conn, int $userId): array {
    if ($userId <= 0) {
        return [];
    }

    $ids = [];
    try {
        $stmt = $conn->prepare('SELECT deck_id FROM deck_favorite WHERE user_id = ?');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $ids[(int) $row['deck_id']] = true;
        }
        $stmt->close();
    } catch (\Throwable $e) {
        return [];
    }

    return $ids;
}
