<?php
/* =========================================================
   StudyBuddy — nav "game pill": real day streak + level.
   Include inside .nav-right on any page that has $conn in scope
   (all app pages require db.php). Values come from quiz history
   via progress_stats.php. Guests see a gentle default (Lv 1).
   ========================================================= */

require_once __DIR__ . '/../progress_stats.php';

$pillStats = ['streak' => 0, 'level' => 1, 'pct' => 0, 'xp' => 0];
if (function_exists('user_game_stats') && isset($conn) && $conn instanceof mysqli) {
    $pillUid = (int) (current_user()['id'] ?? 0);
    if ($pillUid > 0) {
        $pillStats = user_game_stats($conn, $pillUid);
    }
}
?>
<div class="game-pill" aria-label="<?= $pillStats['streak'] ?> day streak, level <?= $pillStats['level'] ?>">
    <span class="stat stat--streak" title="Day streak">
        <span class="ico" aria-hidden="true">&#128293;</span><?= (int) $pillStats['streak'] ?>
    </span>
    <span class="divider-dot" aria-hidden="true"></span>
    <span class="stat stat--xp" title="Level <?= (int) $pillStats['level'] ?> &middot; <?= (int) $pillStats['pct'] ?>% to next">
        <span class="ico" aria-hidden="true">&#11088;</span>Lv <?= (int) $pillStats['level'] ?>
    </span>
</div>
