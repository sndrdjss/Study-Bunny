<?php
/* =========================================================
   StudyBuddy — hero mascot slot
   Expects $petState from pet_state(). Optional:
     $petArrive (bool)  play the "egg settles in" animation
   guest / none  -> original dashed placeholder (unchanged)
   incubating    -> egg with N cracks + progress pill
   ready         -> shaking glowing egg + Hatch! button
   hatched       -> the pet, floating, with a name tag
   ========================================================= */

$ps     = $petState ?? ['stage' => 'guest'];
$stage  = $ps['stage'] ?? 'guest';
$arrive = !empty($petArrive) ? ' pet-holder--arrive' : '';
?>
<?php if ($stage === 'incubating' || $stage === 'ready'): ?>
    <?php
        $egg      = $ps['egg'];
        $progress = (int) $ps['progress'];
        $eggName  = EGGS[$egg]['name'];
    ?>
    <div class="pet-stage pet-stage--<?= e($stage) ?>" id="pet-stage">
        <div class="pet-holder<?= $stage === 'ready' ? ' egg-ready' : ' egg-idle' ?><?= $arrive ?>">
            <?= render_egg($egg, $progress) ?>
        </div>

        <div class="pet-progress" role="status" aria-label="<?= e($eggName) ?>: <?= $progress ?> of <?= PERFECTS_TO_HATCH ?> perfect scores">
            <span class="pet-progress-dots" aria-hidden="true">
                <?php for ($i = 1; $i <= PERFECTS_TO_HATCH; $i++): ?>
                    <span class="pet-dot<?= $i <= $progress ? ' is-on' : '' ?>"></span>
                <?php endfor; ?>
            </span>
            <span class="pet-progress-text"><?= $progress ?>/<?= PERFECTS_TO_HATCH ?> perfect scores</span>
        </div>

        <?php if ($stage === 'ready'): ?>
            <form class="pet-hatch-form" action="hatch_egg.php" method="post">
                <?= csrf_field() ?>
                <button type="submit" class="btn-hatch">Hatch! <span aria-hidden="true">&#10024;</span></button>
            </form>
        <?php else: ?>
            <p class="pet-hint">Ace quizzes to hatch your <?= e($eggName) ?></p>
        <?php endif; ?>
    </div>

<?php elseif ($stage === 'hatched'): ?>
    <div class="pet-stage pet-stage--hatched" id="pet-stage">
        <div class="pet-holder pet-idle">
            <?= render_pet($ps['pet']) ?>
        </div>
        <span class="pet-nametag">Your <?= e(PET_NAMES[$ps['pet']]) ?></span>
    </div>

<?php else: ?>
    <!-- Mascot placeholder (dashed slot, no image yet) -->
    <div class="mascot-slot" aria-hidden="true">
        <span class="mascot-face">&#128048;</span>
        <span class="mascot-label">mascot</span>
    </div>
<?php endif; ?>
