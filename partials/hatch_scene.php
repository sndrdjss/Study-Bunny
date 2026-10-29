<?php
/* =========================================================
   StudyBuddy — hatching celebration overlay
   Shown once on explore.php?hatched=1 right after hatch_egg.php.
   Sequence (css/pet.css §5): egg rumbles -> flash -> shell halves
   fly apart -> pet bounces out + confetti (js/pet.js) -> banner.
   Without JS, "Yay!" is a plain link back to Explore.
   ========================================================= */
$hatchPet  = $petState['pet'];
$hatchEgg  = $petState['egg'];
$hatchName = PET_NAMES[$hatchPet];
$article   = in_array(strtolower($hatchName[0]), ['a', 'e', 'i', 'o', 'u'], true) ? 'an' : 'a';
?>
<div class="hatch-overlay" id="hatch-overlay" role="dialog" aria-modal="true" aria-labelledby="hatch-title">
    <div class="hatch-flash" aria-hidden="true"></div>
    <div class="hatch-inner">
        <div class="hatch-scene" aria-hidden="true">
            <div class="hatch-rays"></div>
            <div class="hatch-pet"><?= render_pet($hatchPet) ?></div>
            <div class="hatch-shells">
                <div class="hatch-shell hatch-shell--bottom"><?= render_egg($hatchEgg, PERFECTS_TO_HATCH) ?></div>
                <div class="hatch-shell hatch-shell--top"><?= render_egg($hatchEgg, PERFECTS_TO_HATCH) ?></div>
            </div>
            <div class="hatch-confetti"></div>
        </div>

        <div class="hatch-banner">
            <p class="hatch-banner-eyebrow">Your <?= e(EGGS[$hatchEgg]['name']) ?> hatched!</p>
            <h2 class="hatch-banner-title" id="hatch-title">You got <?= $article ?> <?= e($hatchName) ?>!</h2>
            <a class="btn-yay" href="explore.php">Yay! <span aria-hidden="true">&#10024;</span></a>
        </div>
    </div>
</div>
