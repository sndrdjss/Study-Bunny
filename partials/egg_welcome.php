<?php
/* =========================================================
   StudyBuddy — "pick your egg" welcome dialog
   Shown on explore.php when pet_state() stage is "none".
   Without JS every egg is a submit button (picks instantly).
   With JS (js/pet.js) a click selects the egg, then the
   "Choose" button confirms the pick.
   ========================================================= */
$welcomeName = trim((string) (current_user()['first_name'] ?? ''));
?>
<div class="egg-welcome" id="egg-welcome" role="dialog" aria-modal="true"
     aria-labelledby="egg-welcome-title" aria-describedby="egg-welcome-sub">
    <div class="egg-welcome-card">
        <div class="egg-welcome-sparkles" aria-hidden="true">
            <span>&#10022;</span><span>&#10022;</span><span>&#10022;</span><span>&#10022;</span>
        </div>

        <span class="egg-welcome-eyebrow">New friend unlocked</span>
        <h2 class="egg-welcome-title" id="egg-welcome-title">
            Welcome<?= $welcomeName !== '' ? ', ' . e($welcomeName) : '' ?>! Pick your egg
        </h2>
        <p class="egg-welcome-sub" id="egg-welcome-sub">
            Ace <?= PERFECTS_TO_HATCH ?> quizzes with a perfect score and your egg hatches into a surprise pet.
        </p>

        <form action="choose_egg.php" method="post">
            <?= csrf_field() ?>
            <div class="egg-choices">
                <?php $i = 0; foreach (EGGS as $key => $cfg): ?>
                    <button type="submit" name="egg" value="<?= e($key) ?>" class="egg-choice"
                            style="--i: <?= $i++ ?>"
                            data-name="<?= e($cfg['name']) ?>"
                            aria-label="<?= e($cfg['name']) ?>, <?= e($cfg['habitat']) ?> habitat, hatches a <?= e(egg_pool_label($key)) ?>">
                        <?= render_egg($key, 0) ?>
                        <span class="egg-choice-name"><?= e($cfg['name']) ?></span>
                        <span class="egg-choice-habitat"><?= e($cfg['habitat']) ?></span>
                        <span class="egg-choice-pool">Hatches: <?= e(egg_pool_label($key)) ?></span>
                    </button>
                <?php endforeach; ?>
            </div>

            <div class="egg-welcome-actions" hidden>
                <button type="submit" name="egg" value="" class="btn-egg-confirm" disabled>
                    <span>Choose <span class="egg-confirm-name">an egg</span></span><span aria-hidden="true">&#10024;</span>
                </button>
            </div>
        </form>

        <p class="egg-welcome-note">You only get one egg, so pick the habitat you love most!</p>
    </div>
</div>
