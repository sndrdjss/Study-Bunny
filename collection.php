<?php
require 'db.php';

// Category comes from the query string (e.g. collection.php?category=Mathematics).
$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$heading  = $category !== '' ? ucfirst($category) . ' Decks' : 'Collection';

/**
 * Build the correct <img src> for a deck cover.
 * Absolute URLs (http/https) are used as-is; stored relative paths
 * (e.g. images/covers/..) are already root-relative.
 */
function cover_src($path) {
    return $path;
}

$decks    = null;
$loadError = null;

if ($category !== '') {
    try {
        $stmt = $conn->prepare(
            "SELECT * FROM deck WHERE status = 'published' AND category = ? ORDER BY title ASC"
        );
        $stmt->bind_param('s', $category);
        $stmt->execute();
        $decks = $stmt->get_result();
    } catch (mysqli_sql_exception $e) {
        $loadError = $e->getMessage();
    }
}
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@500;600;700&family=Nunito:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/explore.css?v=6">
    <link rel="stylesheet" href="css/theme.css?v=4">
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
            <a href="html/library.html" class="nav-link">Library</a>
            <a href="html/progress.html" class="nav-link">Progress</a>
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
            <span class="avatar" aria-hidden="true">JD</span>
            <span class="username">Jane Doe</span>
        </div>
    </header>

    <main class="page">

        <section class="published" aria-label="<?= htmlspecialchars($heading, ENT_QUOTES, 'UTF-8') ?>">
            <nav class="breadcrumb-back">
                <a href="explore.php" class="back-link">&larr; Back to Explore</a>
            </nav>

            <h2 class="published-title"><?= htmlspecialchars($heading, ENT_QUOTES, 'UTF-8') ?></h2>

            <?php if ($category === ''): ?>
              <p class="published-empty">No category selected.</p>
            <?php elseif ($loadError): ?>
              <p class="published-empty">Could not load this collection.</p>
            <?php elseif ($decks && $decks->num_rows > 0): ?>
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
            <?php else: ?>
              <p class="published-empty">No published decks in this category yet.</p>
            <?php endif; ?>
        </section>

    </main>

    <script src="js/theme-toggle.js"></script>
</body>
</html>
