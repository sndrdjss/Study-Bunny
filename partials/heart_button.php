<?php
/* =========================================================
   StudyBuddy — heart (favorite) button partial
   ---------------------------------------------------------
   Expects three variables in scope before include:
     $deckId  (int)    the deck this heart toggles
     $favIds  (array)  set of favorited deck IDs [id => true]
     $return  (string) relative URL to come back to on "remove"
                       (e.g. "explore.php", "collection.php?category=Science")

   Renders a POST form to favorite_deck.php. When the deck is already
   favorited the button gets the filled "on" state (solid glyph + class);
   otherwise it renders the empty outline heart.
   ========================================================= */

$heartDeckId = (int) ($deckId ?? 0);
$heartFavIds = isset($favIds) && is_array($favIds) ? $favIds : [];
$heartReturn = (string) ($return ?? '');
$heartOn     = isset($heartFavIds[$heartDeckId]);
?>
<form class="heart-form" action="favorite_deck.php" method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= $heartDeckId ?>">
    <input type="hidden" name="return" value="<?= htmlspecialchars($heartReturn, ENT_QUOTES, 'UTF-8') ?>">
    <button
        type="submit"
        class="btn-heart<?= $heartOn ? ' btn-heart--on' : '' ?>"
        aria-label="<?= $heartOn ? 'Remove from favorites' : 'Add to favorites' ?>"
        aria-pressed="<?= $heartOn ? 'true' : 'false' ?>"><?= $heartOn ? '&#9829;' : '&#9825;' ?></button>
</form>
