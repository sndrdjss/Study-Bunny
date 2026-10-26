<?php
require_once __DIR__ . '/auth.php';
require 'db.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$deck  = null;
$items = [];        // each: quiz_item row + 'options' => [...] for MC
$loadError = null;

if ($id > 0) {
    try {
        // Look up the deck.
        $stmt = $conn->prepare('SELECT id, user_id, title, description, category, tags, status FROM deck WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res  = $stmt->get_result();
        $deck = $res->fetch_assoc();
        $stmt->close();

        if ($deck) {
            // Load the quiz items for this deck, ordered by position then id.
            $stmt = $conn->prepare(
                'SELECT id, type, question, answer, position FROM quiz_item WHERE deck_id = ? ORDER BY position, id'
            );
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $row['options'] = [];
                $items[] = $row;
            }
            $stmt->close();

            // Load options for any multiple_choice items.
            foreach ($items as &$item) {
                if ($item['type'] === 'multiple_choice') {
                    $stmt = $conn->prepare(
                        'SELECT option_text, is_correct, position FROM quiz_option WHERE quiz_item_id = ? ORDER BY position, id'
                    );
                    $stmt->bind_param('i', $item['id']);
                    $stmt->execute();
                    $ores = $stmt->get_result();
                    while ($orow = $ores->fetch_assoc()) {
                        $item['options'][] = $orow;
                    }
                    $stmt->close();
                }
            }
            unset($item);
        }
    } catch (mysqli_sql_exception $e) {
        $loadError = $e->getMessage();
    }
}

// Only the deck's owner may add or remove items; everyone else gets a read-only view.
$isOwner = owns_deck($deck);
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>StudyBuddy — Manage Items</title>
    <!-- Apply saved theme before paint to avoid a flash of the wrong theme. -->
    <script>
      (function () {
        try {
          if (localStorage.getItem("studybuddy-theme") === "light") {
            document.documentElement.setAttribute("data-theme", "light");
          }
        } catch (e) {}
      })();
    </script>
    <link rel="stylesheet" href="css/create.css?v=5" />
    <link rel="stylesheet" href="css/theme.css?v=8" />
  </head>
  <body>
    <!-- ============================ NAVIGATION BAR ============================ -->
    <header class="navbar">
      <div class="nav-left">
        <a href="explore.php" class="logo">Study<span>Buddy</span></a>
      </div>

      <nav class="nav-center" aria-label="Primary">
        <a href="explore.php" class="nav-link">Explore</a>
        <a href="create.php" class="nav-link active" aria-current="page">Create</a>
        <a href="library.php" class="nav-link">Library</a>
        <a href="progress.php" class="nav-link">Progress</a>
      </nav>

      <div class="nav-right">
        <label class="theme-switch" title="Toggle light / dark mode">
          <input type="checkbox" role="switch" aria-label="Toggle light or dark mode" />
          <span class="track">
            <svg class="icon track-icon sun" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
            <svg class="icon track-icon moon" viewBox="0 0 24 24" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
            <span class="knob">
              <svg class="icon icon-sun" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
              <svg class="icon icon-moon" viewBox="0 0 24 24" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
            </span>
          </span>
        </label>
        <?php include __DIR__ . '/partials/nav_user.php'; ?>
      </div>
    </header>

    <!-- ============================ PAGE CONTENT ============================ -->
    <main class="page">

      <?php if (!$deck): ?>
        <!-- ---------- Not found state ---------- -->
        <nav class="breadcrumb" aria-label="Breadcrumb">
          <a href="create.php" class="breadcrumb-link">Create</a>
          <span class="breadcrumb-sep" aria-hidden="true">/</span>
          <span class="breadcrumb-current">Manage Items</span>
        </nav>

        <div class="studio-header">
          <div class="studio-header-left">
            <h1 class="studio-title">Deck not found</h1>
            <p class="studio-subtitle">
              We couldn&rsquo;t find that deck. It may have been deleted.
            </p>
          </div>
        </div>

        <?php if ($loadError): ?>
          <div class="banner banner--error" role="alert">
            &#9888; Could not load items: <?= htmlspecialchars($loadError, ENT_QUOTES, 'UTF-8') ?>
          </div>
        <?php endif; ?>

        <p class="saved-empty">
          <a href="create.php" class="btn-secondary">&#8592; Back to Create</a>
        </p>

      <?php else: ?>
        <!-- ---------- Breadcrumb ---------- -->
        <nav class="breadcrumb" aria-label="Breadcrumb">
          <a href="create.php" class="breadcrumb-link">Create</a>
          <span class="breadcrumb-sep" aria-hidden="true">/</span>
          <span class="breadcrumb-current">
            <?= htmlspecialchars($deck['title'], ENT_QUOTES, 'UTF-8') ?>
          </span>
        </nav>

        <!-- ---------- Studio header ---------- -->
        <div class="studio-header">
          <div class="studio-header-left">
            <h1 class="studio-title">
              <?= $isOwner ? 'Manage items &mdash; ' : '' ?><?= htmlspecialchars($deck['title'], ENT_QUOTES, 'UTF-8') ?>
            </h1>
            <p class="studio-subtitle">
              <?= $isOwner
                  ? 'Add flashcards and multiple-choice questions to this deck.'
                  : 'You are viewing this deck. Only its creator can add or remove items.' ?>
            </p>
          </div>
          <div class="studio-actions">
            <?php if ($isOwner): ?>
              <?php if ($items): ?>
                <a class="btn-public" href="quiz.php?id=<?= (int) $deck['id'] ?>">Take Quiz</a>
              <?php endif; ?>
              <a href="create.php" class="btn-secondary">&#8592; Back to Create</a>
            <?php else: ?>
              <a href="explore.php" class="btn-secondary">&#8592; Back to Explore</a>
            <?php endif; ?>
          </div>
        </div>

        <!-- ---------- Status banner ---------- -->
        <?php if (isset($_GET['added']) && $_GET['added'] === '1'): ?>
          <div class="banner banner--success" role="status">
            &#10003; Item added!
          </div>
        <?php elseif (isset($_GET['removed']) && $_GET['removed'] === '1'): ?>
          <div class="banner banner--success" role="status">
            &#10003; Item removed.
          </div>
        <?php elseif (isset($_GET['error'])): ?>
          <?php
            $error = $_GET['error'];
            if ($error === 'question') {
                $message = 'A question is required.';
            } elseif ($error === 'answer') {
                $message = 'Flashcards need an answer.';
            } elseif ($error === 'options') {
                $message = 'Multiple-choice questions need 2 to 4 non-empty options.';
            } elseif ($error === 'correct') {
                $message = 'Mark exactly one option as the correct answer.';
            } elseif ($error === 'type') {
                $message = 'Please choose a valid item type.';
            } elseif ($error === 'delete') {
                $message = 'Could not remove that item. Please try again.';
            } elseif ($error === 'db') {
                $message = 'Could not save the item. Please try again.';
            } else {
                $message = 'Something went wrong. Please try again.';
            }
          ?>
          <div class="banner banner--error" role="alert">
            &#9888; <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
          </div>
        <?php endif; ?>

        <!-- ---------- Add item form (owner only) ---------- -->
        <?php if ($isOwner): ?>
        <?php
          // Lock the deck to a single item type. Once the deck has >=1 item, the
          // type is fixed (all-flashcards OR all-multiple-choice); only an EMPTY
          // deck lets the user choose. null = empty deck -> user picks.
          $deckType = $items ? $items[0]['type'] : null;
        ?>
        <section class="builder-card" aria-label="Add an item">
          <div class="section-head">
            <h2 class="section-title">Add an item</h2>
          </div>
          <hr class="divider" />

          <form method="post" action="add_quiz_item.php" class="item-add-form">
            <?= csrf_field() ?>
            <input type="hidden" name="deck_id" value="<?= (int) $deck['id'] ?>" />

            <!-- TYPE -->
            <?php if ($deckType === null): ?>
            <div class="field">
              <label class="field-label" for="item-type">Item type</label>
              <select id="item-type" name="type" class="select-input">
                <option value="flashcard" selected>Flashcard</option>
                <option value="multiple_choice">Multiple choice</option>
              </select>
            </div>
            <?php else: ?>
            <div class="field">
              <span class="field-label">Item type</span>
              <input type="hidden" name="type" value="<?= e($deckType) ?>" />
              <span class="chip chip--type chip--<?= e($deckType) ?>">
                <span class="chip-dot"></span>
                <?= $deckType === 'flashcard' ? 'Flashcards' : 'Multiple choice' ?>
              </span>
              <span class="field-hint">This deck is locked to one type.</span>
            </div>
            <?php endif; ?>

            <!-- QUESTION -->
            <div class="field">
              <label class="field-label" for="item-question">
                Question <span class="req">*</span>
              </label>
              <div class="editor">
                <textarea
                  id="item-question"
                  name="question"
                  class="text-area"
                  placeholder="Enter the question or prompt"
                ></textarea>
              </div>
            </div>

            <!-- ANSWER (flashcard only) -->
            <?php if ($deckType === null || $deckType === 'flashcard'): ?>
            <div class="field" id="answer-field">
              <label class="field-label" for="item-answer">
                Answer <span class="req">*</span>
              </label>
              <input
                type="text"
                id="item-answer"
                name="answer"
                class="text-input"
                placeholder="Enter the answer"
              />
            </div>
            <?php endif; ?>

            <!-- OPTIONS (multiple choice only) -->
            <?php if ($deckType === null || $deckType === 'multiple_choice'): ?>
            <div class="field" id="options-field"<?= $deckType === null ? ' hidden' : '' ?>>
              <label class="field-label">
                Options <span class="req">*</span>
                <span class="field-hint">Mark exactly one as correct</span>
              </label>
              <div class="option-rows">
                <?php for ($i = 0; $i < 4; $i++): ?>
                  <div class="option-row">
                    <input
                      type="radio"
                      name="correct"
                      value="<?= $i ?>"
                      class="option-radio"
                      aria-label="Mark option <?= $i + 1 ?> correct"
                    />
                    <input
                      type="text"
                      name="option[<?= $i ?>]"
                      class="text-input option-text"
                      placeholder="Option <?= $i + 1 ?>"
                    />
                  </div>
                <?php endfor; ?>
              </div>
            </div>
            <?php endif; ?>

            <div class="item-add-actions">
              <button type="submit" class="btn-public">Add item</button>
            </div>
          </form>
        </section>
        <?php endif; ?>

        <!-- ---------- Existing items ---------- -->
        <section class="saved-section" aria-label="Deck items">
          <h2 class="saved-heading">Items (<?= count($items) ?>)</h2>

          <?php if (count($items) === 0): ?>
            <p class="saved-empty"><?= $isOwner ? 'No items yet. Add your first item above.' : 'This deck has no items yet.' ?></p>
          <?php else: ?>
            <div class="item-list">
              <?php foreach ($items as $item): ?>
                <article class="item-row item-row--<?= htmlspecialchars($item['type'], ENT_QUOTES, 'UTF-8') ?>">
                  <div class="item-row-head">
                    <span class="chip chip--type chip--<?= htmlspecialchars($item['type'], ENT_QUOTES, 'UTF-8') ?>">
                      <span class="chip-dot"></span>
                      <?= $item['type'] === 'flashcard' ? 'Flashcard' : 'Multiple choice' ?>
                    </span>
                    <?php if ($isOwner): ?>
                    <form
                      class="item-delete-form"
                      action="delete_quiz_item.php"
                      method="post"
                      onsubmit="return confirm('Remove this item? This cannot be undone.');"
                    >
                      <?= csrf_field() ?>
                      <input type="hidden" name="id" value="<?= (int) $item['id'] ?>" />
                      <input type="hidden" name="deck_id" value="<?= (int) $deck['id'] ?>" />
                      <button
                        type="submit"
                        class="item-delete"
                        aria-label="Delete item"
                        title="Delete item"
                      >
                        &#128465;
                      </button>
                    </form>
                    <?php endif; ?>
                  </div>

                  <p class="item-question">
                    <?= htmlspecialchars($item['question'], ENT_QUOTES, 'UTF-8') ?>
                  </p>

                  <?php if ($item['type'] === 'flashcard'): ?>
                    <p class="item-answer">
                      <span class="item-answer-label">Answer:</span>
                      <?= htmlspecialchars((string) $item['answer'], ENT_QUOTES, 'UTF-8') ?>
                    </p>
                  <?php else: ?>
                    <ul class="item-options">
                      <?php foreach ($item['options'] as $opt): ?>
                        <li class="item-option<?= (int) $opt['is_correct'] === 1 ? ' item-option--correct' : '' ?>">
                          <?php if ((int) $opt['is_correct'] === 1): ?>
                            <span class="item-option-mark" aria-hidden="true">&#10003;</span>
                          <?php endif; ?>
                          <?= htmlspecialchars($opt['option_text'], ENT_QUOTES, 'UTF-8') ?>
                        </li>
                      <?php endforeach; ?>
                    </ul>
                  <?php endif; ?>
                </article>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </section>
      <?php endif; ?>
    </main>

    <!-- Toggle answer vs options fields based on the selected item type. -->
    <script>
      (function () {
        var typeSelect = document.getElementById("item-type");
        var answerField = document.getElementById("answer-field");
        var optionsField = document.getElementById("options-field");
        if (!typeSelect || !answerField || !optionsField) return;

        function sync() {
          var isFlashcard = typeSelect.value === "flashcard";
          answerField.hidden = !isFlashcard;
          optionsField.hidden = isFlashcard;
        }

        typeSelect.addEventListener("change", sync);
        sync();
      })();
    </script>
    <script src="js/theme-toggle.js"></script>
    <script src="js/user-menu.js"></script>
  </body>
</html>
