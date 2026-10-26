<?php
require_once __DIR__ . '/auth.php';
require 'db.php';

// Search term from the query string (e.g. search.php?q=algebra).
$q       = isset($_GET['q']) ? trim($_GET['q']) : '';
$heading = $q !== '' ? 'Results for "' . $q . '"' : 'Search';

/**
 * Build the correct <img src> for a deck cover.
 * Stored relative paths (images/covers/..) are already root-relative;
 * absolute URLs are used as-is.
 */
function cover_src($path) {
    return $path;
}

// Fixed category list (mirrors the Featured Categories on explore.php).
$ALL_CATEGORIES = [
    'Mathematics', 'Science', 'English', 'History',
    'Geography', 'Art', 'Web Development',
];

$decks            = null;
$loadError        = null;
$matchedCategories = [];

if ($q !== '') {
    // Which fixed categories contain the query substring (case-insensitive)?
    foreach ($ALL_CATEGORIES as $cat) {
        if (stripos($cat, $q) !== false) {
            $matchedCategories[] = $cat;
        }
    }

    // Decks matching title/category/tags/description.
    try {
        $like = '%' . $q . '%';
        $stmt = $conn->prepare(
            "SELECT d.*, u.username AS author_username
             FROM deck d
             LEFT JOIN study_buddy_auth u ON u.id = d.user_id
             WHERE d.status = 'published'
               AND (d.title LIKE ? OR d.category LIKE ? OR d.tags LIKE ? OR d.description LIKE ?)
             ORDER BY d.title ASC"
        );
        $stmt->bind_param('ssss', $like, $like, $like, $like);
        $stmt->execute();
        $decks = $stmt->get_result();
    } catch (mysqli_sql_exception $e) {
        $loadError = $e->getMessage();
    }
}

$deckCount = ($decks && !$loadError) ? $decks->num_rows : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudyBuddy — <?= htmlspecialchars($heading, ENT_QUOTES, 'UTF-8') ?></title>
    <script>
      (function () {
        try {
          if (localStorage.getItem("studybuddy-theme") === "light") {
            document.documentElement.setAttribute("data-theme", "light");
          }
        } catch (e) {}
      })();
    </script>
    <link rel="stylesheet" href="css/explore.css?v=7">
    <link rel="stylesheet" href="css/theme.css?v=8">
</head>
<body>

    <!-- ============================ NAVIGATION BAR ============================ -->
    <header class="navbar">
        <div class="nav-left">
            <a href="explore.php" class="logo">Study<span>Buddy</span></a>
        </div>

        <nav class="nav-center" aria-label="Primary">
            <a href="explore.php" class="nav-link">Explore</a>
            <a href="create.php" class="nav-link">Create</a>
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

    <main class="page">

        <section class="published" aria-label="Search results">
            <nav class="breadcrumb-back">
                <a href="explore.php" class="back-link">&larr; Back to Explore</a>
            </nav>

            <!-- Re-usable search bar so users can refine from the results page. -->
            <form class="search search--compact" role="search" action="search.php" method="get">
                <label for="search-input" class="sr-only">Search subjects, decks, formulas, or concepts</label>
                <div class="search-bar">
                    <span class="search-icon" aria-hidden="true">&#128269;</span>
                    <input
                        type="text"
                        id="search-input"
                        name="q"
                        class="search-input"
                        value="<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="Search subjects, decks, formulas, or concepts ..."
                        autocomplete="off"
                        aria-autocomplete="list"
                        aria-controls="search-suggestions"
                        aria-expanded="false">
                    <button type="submit" class="search-submit sr-only">Search</button>
                </div>
                <ul id="search-suggestions" class="search-suggestions" role="listbox" hidden></ul>
            </form>

            <h2 class="published-title"><?= htmlspecialchars($heading, ENT_QUOTES, 'UTF-8') ?></h2>

            <?php if ($q === ''): ?>
              <p class="published-empty">Type something in the search bar to find categories, decks, and topics.</p>
            <?php else: ?>

              <?php if (!empty($matchedCategories)): ?>
                <h3 class="results-subhead">Matching Categories</h3>
                <div class="results-categories">
                  <?php foreach ($matchedCategories as $cat): ?>
                    <a class="chip chip--category chip--link"
                       href="collection.php?category=<?= urlencode($cat) ?>">
                      <span class="chip-dot"></span>
                      <?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?>
                    </a>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>

              <h3 class="results-subhead">
                Decks<?= $deckCount > 0 ? ' (' . $deckCount . ')' : '' ?>
              </h3>

              <?php if ($loadError): ?>
                <p class="published-empty">Could not run the search. Please try again.</p>
              <?php elseif ($deckCount > 0): ?>
                <div class="published-grid">
                  <?php while ($deck = $decks->fetch_assoc()): ?>
                    <article class="saved-deck">
                      <?php if (!empty($deck['cover_image'])): ?>
                        <div class="saved-deck-cover">
                          <img
                            src="<?= htmlspecialchars(cover_src($deck['cover_image']), ENT_QUOTES, 'UTF-8') ?>"
                            alt="<?= htmlspecialchars($deck['title'], ENT_QUOTES, 'UTF-8') ?> cover"
                          />
                        </div>
                      <?php endif; ?>

                      <div class="saved-deck-body">
                        <h3 class="saved-deck-title">
                          <?= htmlspecialchars($deck['title'], ENT_QUOTES, 'UTF-8') ?>
                        </h3>
                        <?php if (!empty($deck['author_username'])): ?>
                          <span class="deck-author">by @<?= e($deck['author_username']) ?></span>
                        <?php endif; ?>

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
                                <?= htmlspecialchars(ucfirst($deck['category']), ENT_QUOTES, 'UTF-8') ?>
                              </span>
                            <?php endif; ?>
                            <?php if (!empty($deck['tags'])): ?>
                              <?php foreach (array_filter(array_map('trim', explode(',', $deck['tags']))) as $tag): ?>
                                <span class="chip chip--tag">
                                  <span class="chip-dot"></span>
                                  <?= htmlspecialchars(ucfirst($tag), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                              <?php endforeach; ?>
                            <?php endif; ?>
                          </div>
                        <?php endif; ?>

                        <div class="saved-deck-actions">
                          <a class="btn-quiz" href="quiz.php?id=<?= (int) $deck['id'] ?>">
                            Take Quiz <span aria-hidden="true">&rarr;</span>
                          </a>
                        </div>
                      </div>
                    </article>
                  <?php endwhile; ?>
                </div>
              <?php elseif (empty($matchedCategories)): ?>
                <p class="published-empty">No categories or decks matched your search. Try a different term.</p>
              <?php else: ?>
                <p class="published-empty">No decks matched, but check the matching categories above.</p>
              <?php endif; ?>

            <?php endif; ?>
        </section>

    </main>

    <script src="js/theme-toggle.js"></script>
    <script src="js/user-menu.js"></script>
    <script src="js/search-suggest.js?v=1"></script>
</body>
</html>
