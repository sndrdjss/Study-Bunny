<?php
/* =========================================================
   StudyBuddy — hatch the egg
   POST { csrf_token } -> if the egg is ready (enough perfect
   scores), the SERVER picks a random pet from the egg's pool
   and saves it. "WHERE pet IS NULL" makes it one-time: a
   repeat POST or refresh can never reroll the pet.
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

$uid   = (int) current_user()['id'];
$state = pet_state($conn, $uid);

if ($state['stage'] === 'hatched') {
    header('Location: explore.php#pet-stage');
    exit;
}
if ($state['stage'] !== 'ready') {
    header('Location: explore.php');
    exit;
}

try {
    $pet  = pick_pet($state['egg']);
    $stmt = $conn->prepare('UPDATE user_pet SET pet = ?, hatched_at = NOW() WHERE user_id = ? AND pet IS NULL');
    $stmt->bind_param('si', $pet, $uid);
    $stmt->execute();
    $hatchedNow = $stmt->affected_rows === 1;
    $stmt->close();
    $conn->close();
} catch (mysqli_sql_exception $e) {
    header('Location: explore.php?error=hatch');
    exit;
}

// Only play the celebration for the request that actually hatched it.
header('Location: explore.php' . ($hatchedNow ? '?hatched=1' : '#pet-stage'));
exit;
