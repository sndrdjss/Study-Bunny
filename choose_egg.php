<?php
/* =========================================================
   StudyBuddy — choose a starter egg
   POST { egg, csrf_token } -> creates the user's user_pet row.
   One egg per user: INSERT IGNORE means a second pick (or a
   different egg) never overwrites the first.
   ========================================================= */

require_once __DIR__ . '/auth.php';
require 'db.php';
require_once __DIR__ . '/pets.php';

require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: explore.php');
    exit;
}

verify_csrf();

$egg = (string) ($_POST['egg'] ?? '');
if (!isset(EGGS[$egg])) {
    header('Location: explore.php');
    exit;
}

try {
    $uid  = (int) current_user()['id'];
    $stmt = $conn->prepare('INSERT IGNORE INTO user_pet (user_id, egg_type) VALUES (?, ?)');
    $stmt->bind_param('is', $uid, $egg);
    $stmt->execute();
    $stmt->close();
    $conn->close();
} catch (mysqli_sql_exception $e) {
    header('Location: explore.php?error=egg');
    exit;
}

header('Location: explore.php?egg=chosen');
exit;
