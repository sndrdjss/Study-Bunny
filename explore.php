<?php
require_once __DIR__ . '/auth.php';
require 'db.php';
function cover_src($path) {
    return $path;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudyBuddy — Explore</title>
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
    <link rel="stylesheet" href="css/theme.css?v=8">
</head>
<body>

    <!-- ============================ NAVIGATION BAR ============================ -->
    <header class="navbar">
        <div class="nav-left">
            <a href="explore.php" class="logo">Study<span>Buddy</span></a>
        </div>

        <nav class="nav-center" aria-label="Primary">
            <a href="explore.php" class="nav-link active" aria-current="page">Explore</a>
            <a href="create.php" class="nav-link">Create</a>
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

    <main class="page">

        <!-- ============================ HERO HEADING ============================ -->
        <section class="hero">
            <h1 class="hero-title">Explore, Learn, and <span class="gradient-text">Have Fun</span></h1>
            <p class="hero-subtitle">Master equations, codebases, languages, and history with verified peer-crafted decks and spaced repetition.</p>

            <!-- Mascot placeholder (dashed slot, no image yet) -->
            <div class="mascot-slot" aria-hidden="true">
                <span class="mascot-face">&#128048;</span>
                <span class="mascot-label">mascot</span>
            </div>

            <!-- Streak / day-dots strip (static sample) -->
            <div class="streak-strip" aria-label="7 day study streak">
                <span class="streak-count"><span class="flame" aria-hidden="true">&#128293;</span> 7 day streak</span>
                <ul class="streak-dots">
                    <li class="done"></li>
                    <li class="done"></li>
                    <li class="done"></li>
                    <li class="done"></li>
                    <li class="done"></li>
                    <li></li>
                    <li></li>
                </ul>
            </div>

            <!-- ============================ SEARCH BAR ============================ -->
            <form class="search" role="search" action="search.php" method="get">
                <label for="search-input" class="sr-only">Search subjects, decks, formulas, or concepts</label>
                <div class="search-bar">
                    <span class="search-icon" aria-hidden="true">&#128269;</span>
                    <input
                        type="text"
                        id="search-input"
                        name="q"
                        class="search-input"
                        placeholder="Search subjects, decks, formulas, or concepts ..."
                        autocomplete="off"
                        aria-autocomplete="list"
                        aria-controls="search-suggestions"
                        aria-expanded="false">
                    <button type="submit" class="search-submit sr-only">Search</button>
                </div>
                <ul id="search-suggestions" class="search-suggestions" role="listbox" hidden></ul>
            </form>
        </section>

        <!-- ============================ CARD DECK / CAROUSEL ============================ -->
        <section class="carousel" aria-label="Featured learning decks">
            <button type="button" class="carousel-arrow carousel-arrow--prev" aria-label="Previous deck">&#8249;</button>

            <div class="deck">
                <!-- All 7 decks live here; JS assigns prev/active/next positions.
                     Initial classes below match the JS start index so the first
                     paint already shows the correct prev/active/next overlap. -->
                <article class="card card--side card--side-prev">
                    <div class="card-image">
                        <img src="https://i.pinimg.com/1200x/2e/2b/58/2e2b58e005e91f684ad7f5f69ff2fd65.jpg" alt="">
                    </div>
                    <div class="card-content">
                        <h2 class="card-title">Ancient Civilizations</h2>
                        <p class="card-desc">Journey through the rise and fall of ancient civilizations, their leaders, cultures, and the events that shaped them.</p>
                        <div class="card-author">
                            <span class="card-created">CREATED BY</span>
                            <span class="card-name">Emma Davis</span>
                        </div>
                        <div class="card-pills">
                            <span class="pill pill--category">History</span>
                            <span class="pill pill--subject">Civilizations</span>
                        </div>
                        <div class="card-controls">
                            <a class="btn-collection" href="quiz.php?id=6">Take Quiz <span aria-hidden="true">&rarr;</span></a>
                            <form class="heart-form" action="favorite_deck.php" method="post">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="6">
                                <button type="submit" class="btn-heart" aria-label="Add to favorites">&#9825;</button>
                            </form>
                        </div>
                    </div>
                </article>

                <article class="card card--active">
                    <div class="card-image">
                        <img src="https://i.pinimg.com/1200x/ae/6c/5a/ae6c5ad074182cf3d77c42eb041b12d7.jpg" alt="Algebra deck cover">
                    </div>
                    <div class="card-content">
                        <h2 class="card-title">Algebra</h2>
                        <p class="card-desc">Master algebraic equations, functions, and problem solving through clear lessons and plenty of practice questions.</p>
                        <div class="card-author">
                            <span class="card-created">CREATED BY</span>
                            <span class="card-name">Davis Curtis</span>
                        </div>
                        <div class="card-pills">
                            <span class="pill pill--category">Mathematics</span>
                            <span class="pill pill--subject">Equations</span>
                        </div>
                        <div class="card-controls">
                            <a class="btn-collection" href="quiz.php?id=4">Take Quiz <span aria-hidden="true">&rarr;</span></a>
                            <form class="heart-form" action="favorite_deck.php" method="post">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="4">
                                <button type="submit" class="btn-heart" aria-label="Add to favorites">&#9825;</button>
                            </form>
                        </div>
                    </div>
                </article>

                <article class="card card--side card--side-next">
                    <div class="card-image">
                        <img src="https://i.pinimg.com/736x/82/c3/87/82c387769f9306447c918a18fd10691d.jpg" alt="">
                    </div>
                    <div class="card-content">
                        <h2 class="card-title">App Development</h2>
                        <p class="card-desc">Build modern mobile and web apps while learning how different technologies connect to create real products.</p>
                        <div class="card-author">
                            <span class="card-created">CREATED BY</span>
                            <span class="card-name">Daniel Smith</span>
                        </div>
                        <div class="card-pills">
                            <span class="pill pill--category">Web Development</span>
                            <span class="pill pill--subject">Mobile Apps</span>
                        </div>
                        <div class="card-controls">
                            <a class="btn-collection" href="quiz.php?id=7">Take Quiz <span aria-hidden="true">&rarr;</span></a>
                            <form class="heart-form" action="favorite_deck.php" method="post">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id" value="7">
                                <button type="submit" class="btn-heart" aria-label="Add to favorites">&#9825;</button>
                            </form>
                        </div>
                    </div>
                </article>

                <article class="card card--hidden">
                    <div class="card-image"><img src="images/placeholders/science.svg" alt=""></div>
                    <div class="card-content">
                        <h2 class="card-title">Cell Biology</h2>
                        <p class="card-desc">Discover how cells work, how living things grow, and how organisms interact within their ecosystems.</p>
                        <div class="card-author"><span class="card-created">CREATED BY</span><span class="card-name">Sarah Miller</span></div>
                        <div class="card-pills"><span class="pill pill--category">Science</span><span class="pill pill--subject">Living Things</span></div>
                        <div class="card-controls"><a class="btn-collection" href="quiz.php?id=8">Take Quiz <span aria-hidden="true">&rarr;</span></a><form class="heart-form" action="favorite_deck.php" method="post"><?= csrf_field() ?><input type="hidden" name="id" value="8"><button type="submit" class="btn-heart" aria-label="Add to favorites">&#9825;</button></form></div>
                    </div>
                </article>

                <article class="card card--hidden">
                    <div class="card-image"><img src="images/placeholders/english.svg" alt=""></div>
                    <div class="card-content">
                        <h2 class="card-title">Grammar Essentials</h2>
                        <p class="card-desc">Sharpen your sentence structure, punctuation, and grammar rules for clear and confident communication.</p>
                        <div class="card-author"><span class="card-created">CREATED BY</span><span class="card-name">Alex Johnson</span></div>
                        <div class="card-pills"><span class="pill pill--category">English</span><span class="pill pill--subject">Sentence Structure</span></div>
                        <div class="card-controls"><a class="btn-collection" href="quiz.php?id=9">Take Quiz <span aria-hidden="true">&rarr;</span></a><form class="heart-form" action="favorite_deck.php" method="post"><?= csrf_field() ?><input type="hidden" name="id" value="9"><button type="submit" class="btn-heart" aria-label="Add to favorites">&#9825;</button></form></div>
                    </div>
                </article>

                <article class="card card--hidden">
                    <div class="card-image"><img src="images/placeholders/geography.svg" alt=""></div>
                    <div class="card-content">
                        <h2 class="card-title">Landforms &amp; Climate</h2>
                        <p class="card-desc">Explore the landforms, oceans, climate patterns, and natural processes that continuously shape our planet.</p>
                        <div class="card-author"><span class="card-created">CREATED BY</span><span class="card-name">Michael Lee</span></div>
                        <div class="card-pills"><span class="pill pill--category">Geography</span><span class="pill pill--subject">Earth Systems</span></div>
                        <div class="card-controls"><a class="btn-collection" href="quiz.php?id=10">Take Quiz <span aria-hidden="true">&rarr;</span></a><form class="heart-form" action="favorite_deck.php" method="post"><?= csrf_field() ?><input type="hidden" name="id" value="10"><button type="submit" class="btn-heart" aria-label="Add to favorites">&#9825;</button></form></div>
                    </div>
                </article>

                <article class="card card--hidden">
                    <div class="card-image"><img src="images/placeholders/art.svg" alt=""></div>
                    <div class="card-content">
                        <h2 class="card-title">Digital Illustration</h2>
                        <p class="card-desc">Create stunning digital illustrations by mastering composition, color, and the essential creative tools.</p>
                        <div class="card-author"><span class="card-created">CREATED BY</span><span class="card-name">Sophia Brown</span></div>
                        <div class="card-pills"><span class="pill pill--category">Art</span><span class="pill pill--subject">Creative Tools</span></div>
                        <div class="card-controls"><a class="btn-collection" href="quiz.php?id=11">Take Quiz <span aria-hidden="true">&rarr;</span></a><form class="heart-form" action="favorite_deck.php" method="post"><?= csrf_field() ?><input type="hidden" name="id" value="11"><button type="submit" class="btn-heart" aria-label="Add to favorites">&#9825;</button></form></div>
                    </div>
                </article>
            </div>

            <button type="button" class="carousel-arrow carousel-arrow--next" aria-label="Next deck">&#8250;</button>
        </section>

        <!-- ============================ CAROUSEL INDICATOR ============================ -->
        <p class="carousel-indicator"><span class="carousel-current">1</span> / <span class="carousel-total">7</span></p>

        <!-- ============================ FEATURED CATEGORIES ============================ -->
        <section class="featured">
            <h2 class="featured-title">Featured Categories</h2>
            <div class="category-row">
                <a href="collection.php?category=Mathematics" class="category">
                    <span class="category-icon" aria-hidden="true">&#960;</span>
                    <span class="category-name">Mathematics</span>
                </a>
                <a href="collection.php?category=Science" class="category">
                    <span class="category-icon" aria-hidden="true">&#128300;</span>
                    <span class="category-name">Science</span>
                </a>
                <a href="collection.php?category=English" class="category">
                    <span class="category-icon" aria-hidden="true">A</span>
                    <span class="category-name">English</span>
                </a>
                <a href="collection.php?category=History" class="category">
                    <span class="category-icon" aria-hidden="true">&#8987;</span>
                    <span class="category-name">History</span>
                </a>
                <a href="collection.php?category=Geography" class="category">
                    <span class="category-icon" aria-hidden="true">&#127758;</span>
                    <span class="category-name">Geography</span>
                </a>
                <a href="collection.php?category=Art" class="category">
                    <span class="category-icon" aria-hidden="true">&#127912;</span>
                    <span class="category-name">Art</span>
                </a>
                <a href="collection.php?category=Web Development" class="category">
                    <span class="category-icon category-icon--code" aria-hidden="true">&lt;/&gt;</span>
                    <span class="category-name">Web Development</span>
                </a>
            </div>
        </section>

        <!-- ============================ PUBLISHED DECKS ============================ -->
        <section class="published" aria-label="Published Decks">
            <h2 class="published-title">Published Decks</h2>

            <?php
              $published = null;
              $pubError = null;
              try {
                  $published = $conn->query(
                      "SELECT d.*, u.username AS author_username
                       FROM deck d
                       LEFT JOIN study_buddy_auth u ON u.id = d.user_id
                       WHERE d.status = 'published'
                       ORDER BY d.created_at DESC, d.id DESC"
                  );
              } catch (mysqli_sql_exception $e) {
                  $pubError = $e->getMessage();
              }
            ?>

            <?php if ($pubError): ?>
              <p class="published-empty">Could not load published decks.</p>
            <?php elseif ($published && $published->num_rows > 0): ?>
              <div class="published-grid">
                <?php while ($deck = $published->fetch_assoc()): ?>
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
                          <button type="submit" class="btn-heart" aria-label="Add to favorites">&#9825;</button>
                        </form>
                      </div>
                    </div>
                  </article>
                <?php endwhile; ?>
              </div>
            <?php else: ?>
              <p class="published-empty">No published decks yet. Publish one from the Create page to see it here.</p>
            <?php endif; ?>
        </section>

    </main>

    <script src="js/theme-toggle.js"></script>
    <script src="js/user-menu.js"></script>
    <script src="js/explore-carousel.js?v=3"></script>
    <script src="js/search-suggest.js?v=1"></script>
</body>
</html>
