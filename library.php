<?php
require_once __DIR__ . '/auth.php';
require 'db.php';

require_login();

/**
 * Build the correct <img src> for a deck cover.
 * Stored relative paths (images/covers/..) are already root-relative;
 * absolute URLs are used as-is.
 */
function cover_src($path) {
    return $path;
}

// Load the decks this user has favorited (newest first).
$favorites = null;
$loadError = null;

try {
    $uid  = (int) current_user()['id'];
    $stmt = $conn->prepare(
        "SELECT d.*, u.username AS author_username
         FROM deck_favorite f
         JOIN deck d ON d.id = f.deck_id
         LEFT JOIN study_buddy_auth u ON u.id = d.user_id
         WHERE f.user_id = ?
         ORDER BY f.created_at DESC, f.id DESC"
    );
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $favorites = $stmt->get_result();
} catch (mysqli_sql_exception $e) {
    $loadError = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudyBuddy — Library</title>
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
    <link rel="stylesheet" href="css/explore.css?v=8">
    <link rel="stylesheet" href="css/library.css?v=3">
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
            <a href="library.php" class="nav-link active" aria-current="page">Library</a>
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

    <!-- ============================ FAVORITED DECKS ============================ -->
    <main class="page">
        <section class="published" aria-label="Your favorited decks">
            <h2 class="published-title">Your Library</h2>

            <?php if (isset($_GET['favorited'])): ?>
              <p class="published-note">Added to your library.</p>
            <?php elseif (isset($_GET['error'])): ?>
              <p class="published-note">We couldn't save that just now — please try again.</p>
            <?php endif; ?>

            <?php if ($loadError): ?>
              <p class="published-empty">Could not load your library. Please try again.</p>
            <?php elseif ($favorites && $favorites->num_rows > 0): ?>
              <div class="published-grid">
                <?php while ($deck = $favorites->fetch_assoc()): ?>
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
                        <form class="heart-form" action="favorite_deck.php" method="post">
                          <?= csrf_field() ?>
                          <input type="hidden" name="id" value="<?= (int) $deck['id'] ?>">
                          <button type="submit" class="btn-heart btn-heart--on" aria-label="Already in favorites">&#9829;</button>
                        </form>
                      </div>
                    </div>
                  </article>
                <?php endwhile; ?>
              </div>
            <?php else: ?>
              <p class="published-empty">No saved decks yet. Tap the &#9825; heart on any deck to add it here.</p>
            <?php endif; ?>
        </section>
    </main>

    <script src="js/theme-toggle.js"></script>
    <script src="js/user-menu.js"></script>
</body>
</html>
