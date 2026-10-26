<?php
require_once __DIR__ . '/auth.php';
require_login();
// TEMP DEBUG — shows errors on screen so we can diagnose. Remove later.
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
require 'db.php';
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>StudyBuddy — Create</title>
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
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@500;600;700&family=Nunito:wght@500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="css/create.css?v=6" />
    <link rel="stylesheet" href="css/theme.css?v=6" />
  </head>
  <body>
    <!-- ============================ NAVIGATION BAR ============================ -->
    <header class="navbar">
      <div class="nav-left">
        <a href="explore.php" class="logo">Study<span>Buddy</span></a>
      </div>

      <nav class="nav-center" aria-label="Primary">
        <a href="explore.php" class="nav-link">Explore</a>
        <a href="create.php" class="nav-link active" aria-current="page"
          >Create</a
        >
        <a href="library.php" class="nav-link">Library</a>
        <a href="progress.php" class="nav-link">Progress</a>
      </nav>

      <div class="nav-right">
        <div class="game-pill" aria-label="Your progress">
          <span class="stat stat--streak" title="Day streak"><span class="ico" aria-hidden="true">&#128293;</span>7</span>
          <span class="divider-dot" aria-hidden="true"></span>
          <span class="stat stat--xp" title="Level"><span class="ico" aria-hidden="true">&#11088;</span>Lv 4</span>
        </div>
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
      <!-- ---------- Breadcrumb ---------- -->
      <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="library.php" class="breadcrumb-link">Decks</a>
        <span class="breadcrumb-sep" aria-hidden="true">/</span>
        <span class="breadcrumb-current">Studio Builder</span>
      </nav>

      <!-- ---------- Studio header ---------- -->
      <div class="studio-header">
        <div class="studio-header-left">
          <h1 class="studio-title">Create a new card deck</h1>
          <p class="studio-subtitle">
            Build interactive study sets with markdown formatting, AI
            enrichment, and smart spaced repetition.
          </p>
        </div>

        <div class="studio-actions">
          <span class="autosave" aria-hidden="true"
            >&#10003; Autosaved 1m ago</span
          >
          <button type="submit" form="deck-form" class="btn-secondary">
            Save Draft
          </button>
        </div>
      </div>

      <!-- ---------- Status banner ---------- -->
      <?php if (isset($_GET['saved']) && $_GET['saved'] === '1'): ?>
        <div class="banner banner--success" role="status">
          &#10003; Draft saved!
        </div>
      <?php elseif (isset($_GET['deleted']) && $_GET['deleted'] === '1'): ?>
        <div class="banner banner--success" role="status">
          &#10003; Deck deleted.
        </div>
      <?php elseif (isset($_GET['published']) && $_GET['published'] === '1'): ?>
        <div class="banner banner--success" role="status">
          &#10003; Deck published! It now appears on the Explore page.
        </div>
      <?php elseif (isset($_GET['error'])): ?>
        <?php
          $error = $_GET['error'];
          if ($error === 'empty') {
              $message = 'Title is required to save a draft.';
          } elseif ($error === 'image') {
              $message = 'There was a problem uploading your cover image.';
          } elseif ($error === 'db') {
              $message = 'Could not save your draft. Please try again.';
          } elseif ($error === 'delete') {
              $message = 'Could not delete the deck. Please try again.';
          } elseif ($error === 'forbidden') {
              $message = 'You can only change decks you created.';
          } elseif ($error === 'publish') {
              $message = 'Could not publish the deck. Please try again.';
          } else {
              $message = 'Something went wrong. Please try again.';
          }
        ?>
        <div class="banner banner--error" role="alert">
          &#9888; <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
        </div>
      <?php endif; ?>

      <!-- PARA SA NAME AND DESCRIPTION NG CARD-->
      <form
        id="deck-form"
        method="post"
        action="create_deck.php"
        enctype="multipart/form-data"
      >
        <?= csrf_field() ?>
        <section class="builder-card" aria-label="Name and Description">
          <div class="section-head">
            <h2 class="section-title">Name &amp; Description</h2>
          </div>
          <hr class="divider" />

          <!-- TITLE -->
          <div class="field">
            <div class="label-row">
              <label class="field-label" for="deck-title"
                >Title <span class="req">*</span></label
              >
              <span class="field-hint">0 / 100 characters</span>
            </div>
            <input
              type="text"
              id="deck-title"
              name="title"
              class="text-input title-input"
              maxlength="100"
              placeholder="Enter a title for your card deck"
            />
          </div>

          <!-- DESCRIPTION -->
          <div class="field">
            <label class="field-label" for="deck-description">Description</label>
            <div class="editor">
              <textarea
                id="deck-description"
                name="description"
                class="text-area"
                placeholder="Master common Spanish dialogue phrases, natural responses, and core social interaction expressions tailored for daily conversational fluency."
              ></textarea>
            </div>
          </div>

          <!-- BOTTOM ROW: Subject Category | Tags | Cover Photo -->
          <div class="field-row">
            <!-- SUBJECT CATEGORY -->
            <div class="field">
              <label class="field-label" for="deck-category"
                >Subject Category</label
              >
              <select id="deck-category" name="category" class="select-input">
                <option value="" selected disabled>
                  Select a subject category
                </option>
                <option value="mathematics">Mathematics</option>
                <option value="science">Science</option>
                <option value="english">English</option>
                <option value="history">History</option>
                <option value="geography">Geography</option>
                <option value="art">Art</option>
                <option value="web-development">Web Development</option>
                <option value="languages">Languages</option>
              </select>
            </div>

            <!-- TAGS -->
            <div class="field">
              <label class="field-label" for="deck-tags">Tags</label>
              <input
                type="text"
                id="deck-tags"
                name="tags"
                class="text-input"
                placeholder="Conversation, Beginner, A2 ..."
              />
            </div>

            <!-- COVER PHOTO -->
            <div class="field">
              <label class="field-label" for="deck-cover">Cover Photo</label>
              <input
                type="file"
                id="deck-cover"
                name="cover"
                accept="image/*"
                class="file-input"
              />
            </div>
          </div>
        </section>
      </form>

      <!-- ---------- Saved drafts ---------- -->
      <section class="saved-section" aria-label="Saved Drafts">
        <h2 class="saved-heading">Saved Drafts</h2>

        <?php
          $decks = null;
          $deckError = null;
          try {
              // Only the logged-in user's own drafts.
              $draftUserId = (int) current_user()['id'];
              $draftStmt = $conn->prepare("SELECT * FROM deck WHERE status = 'draft' AND user_id = ? ORDER BY created_at DESC, id DESC");
              $draftStmt->bind_param('i', $draftUserId);
              $draftStmt->execute();
              $decks = $draftStmt->get_result();
          } catch (mysqli_sql_exception $e) {
              $deckError = $e->getMessage();
          }
        ?>

        <?php if ($deckError): ?>
          <p class="saved-empty" style="color:#fca5a5;">
            Could not load decks: <?= htmlspecialchars($deckError, ENT_QUOTES, 'UTF-8') ?>
          </p>
        <?php elseif ($decks && $decks->num_rows > 0): ?>
          <div class="saved-deck-list">
            
  
            <?php while ($deck = $decks->fetch_assoc()): ?>
              <article class="saved-deck">
                <?php if (!empty($deck['cover_image'])): ?>
                  <div class="saved-deck-cover">
                    <img
                      src="<?= htmlspecialchars($deck['cover_image'], ENT_QUOTES, 'UTF-8') ?>"
                      alt="<?= htmlspecialchars($deck['title'], ENT_QUOTES, 'UTF-8') ?> cover"
                    />
                  </div>
                <?php endif; ?>

                <div class="saved-deck-body">
                  <h3 class="saved-deck-title">
                    <?= htmlspecialchars($deck['title'], ENT_QUOTES, 'UTF-8') ?>
                  </h3>

                  <?php if (!empty($deck['description'])): ?>
                    <p class="saved-deck-desc">
                      <?= htmlspecialchars($deck['description'], ENT_QUOTES, 'UTF-8') ?>
                    </p>
                  <?php endif; ?>

                  <?php if (!empty($deck['category']) || !empty($deck['tags'])): ?>
                    <div class="saved-deck-chips">
                      <?php if (!empty($deck['category'])): ?>
                        <span class="chip chip--category">
                          <span class="chip-dot"></span>
                          <?= htmlspecialchars($deck['category'], ENT_QUOTES, 'UTF-8') ?>
                        </span>
                      <?php endif; ?>
                      <?php if (!empty($deck['tags'])): ?>
                        <?php foreach (array_filter(array_map('trim', explode(',', $deck['tags']))) as $tag): ?>
                          <span class="chip chip--tag">
                            <span class="chip-dot"></span>
                            <?= htmlspecialchars($tag, ENT_QUOTES, 'UTF-8') ?>
                          </span>
                        <?php endforeach; ?>
                      <?php endif; ?>
                    </div>
                  <?php endif; ?>

                  <div class="saved-deck-actions">
                    <a
                      class="btn-secondary saved-deck-manage"
                      href="deck_items.php?id=<?= (int) $deck['id'] ?>"
                    >
                      Manage Items
                    </a>
                    <form
                      class="saved-deck-publish-form"
                      action="publish_deck.php"
                      method="post"
                    >
                      <?= csrf_field() ?>
                      <input
                        type="hidden"
                        name="id"
                        value="<?= (int) $deck['id'] ?>"
                      />
                      <button type="submit" class="btn-public">
                        Publish Deck &#8594;
                      </button>
                    </form>
                    <form
                      class="saved-deck-delete-form"
                      action="delete_deck.php"
                      method="post"
                      onsubmit="return confirm('Delete this deck? This cannot be undone.');"
                    >
                      <?= csrf_field() ?>
                      <input
                        type="hidden"
                        name="id"
                        value="<?= (int) $deck['id'] ?>"
                      />
                      <button
                        type="submit"
                        class="saved-deck-delete"
                        aria-label="Delete deck"
                        title="Delete deck"
                      >
                        &#128465;
                      </button>
                    </form>
                  </div>
                </div>
              </article>
            <?php endwhile; ?>
          </div>
        <?php else: ?>
          <p class="saved-empty">No drafts yet.</p>
        <?php endif; ?>
      </section>
    </main>
    <script src="js/theme-toggle.js"></script>
    <script src="js/user-menu.js"></script>
  </body>
</html>
