<?php
require_once __DIR__ . '/auth.php';
require 'db.php';
require_once __DIR__ . '/progress_stats.php';

require_login();

$uid = (int) current_user()['id'];

// Optional drill-down: only honour a deck the user has actually attempted.
$deckParam = isset($_GET['deck']) ? (int) $_GET['deck'] : 0;
$deckView  = ($deckParam > 0 && user_attempted_deck($conn, $uid, $deckParam)) ? $deckParam : 0;

$overall  = overall_stats($conn, $uid);
$hasData  = $overall['total_quizzes'] > 0;
$game     = user_game_stats($conn, $uid);

if ($deckView > 0) {
    $deckName     = deck_title($conn, $deckView);
    $deckTimeline = score_timeline($conn, $uid, $deckView);
    $deckRow      = null;
    foreach (per_deck_stats($conn, $uid) as $row) {
        if ($row['deck_id'] === $deckView) { $deckRow = $row; break; }
    }
    $mistakes = latest_wrong_answers($conn, $uid, $deckView);
} else {
    $timeline = score_timeline($conn, $uid);
    $decks    = per_deck_stats($conn, $uid);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudyBuddy — Progress</title>
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
    <link rel="stylesheet" href="css/progress.css?v=5">
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
            <a href="progress.php" class="nav-link active" aria-current="page">Progress</a>
        </nav>

        <div class="nav-right">
            <?php include __DIR__ . '/partials/game_pill.php'; ?>
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

    <!-- ============================ PROGRESS DASHBOARD ============================ -->
    <main class="progress-page">

    <?php if (!$hasData): ?>
        <section class="progress-empty">
            <span class="progress-empty-emoji" aria-hidden="true">&#128202;</span>
            <h1 class="progress-empty-title">No progress yet</h1>
            <p class="progress-empty-text">Take a quiz and your stats, score history, and mistakes to review will show up here.</p>
            <a class="btn-progress" href="explore.php">Find a quiz <span aria-hidden="true">&rarr;</span></a>
        </section>

    <?php elseif ($deckView > 0): ?>
        <!-- ---------------- Deck drill-down ---------------- -->
        <section class="progress-head">
            <a class="progress-back" href="progress.php"><span aria-hidden="true">&larr;</span> All decks</a>
            <h1 class="progress-title"><?= e($deckName) ?></h1>
            <p class="progress-sub">Your performance on this deck</p>
        </section>

        <?php if ($deckRow): ?>
            <section class="stat-grid stat-grid--compact" aria-label="Deck stats">
                <div class="stat-card">
                    <span class="stat-value"><?= (int) $deckRow['attempts'] ?></span>
                    <span class="stat-label">Attempts</span>
                </div>
                <div class="stat-card">
                    <span class="stat-value"><?= (int) $deckRow['avg_pct'] ?>%</span>
                    <span class="stat-label">Average</span>
                </div>
                <div class="stat-card stat-card--accent">
                    <span class="stat-value"><?= (int) $deckRow['best_pct'] ?>%</span>
                    <span class="stat-label">Best</span>
                </div>
            </section>
        <?php endif; ?>

        <section class="chart-panel">
            <h2 class="panel-title">Score history</h2>
            <?php if (count($deckTimeline) >= 1): ?>
                <div class="chart-wrap"><canvas id="deckChart" height="240"></canvas></div>
            <?php else: ?>
                <p class="panel-empty">No scored attempts yet.</p>
            <?php endif; ?>
        </section>

        <section class="mistakes">
            <h2 class="panel-title">Mistakes to review</h2>
            <?php if (!empty($mistakes)): ?>
                <p class="mistakes-hint">Your most recent answer for each question you missed. Get one right again and it leaves this list.</p>
                <ul class="mistake-list">
                    <?php foreach ($mistakes as $m): ?>
                        <li class="mistake">
                            <p class="mistake-q"><?= e($m['question_text']) ?></p>
                            <div class="mistake-answers">
                                <span class="mistake-yours">
                                    <span class="mistake-tag mistake-tag--wrong">Your answer</span>
                                    <?= $m['your_answer'] !== null && $m['your_answer'] !== '' ? e($m['your_answer']) : '<em>(blank)</em>' ?>
                                </span>
                                <span class="mistake-correct">
                                    <span class="mistake-tag mistake-tag--right">Correct</span>
                                    <?= e($m['correct_answer'] ?? '') ?>
                                </span>
                            </div>
                            <span class="mistake-when"><?= e(date('M j, Y', strtotime($m['answered_at']))) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="mistakes-empty">
                    <span aria-hidden="true">&#127881;</span>
                    <p>No mistakes to review — nice work! (New quizzes you take will track missed questions here.)</p>
                </div>
            <?php endif; ?>
        </section>

    <?php else: ?>
        <!-- ---------------- Overview ---------------- -->
        <section class="progress-head">
            <h1 class="progress-title">Your Progress</h1>
            <p class="progress-sub">Stats from every quiz you've taken</p>
        </section>

        <section class="level-banner" aria-label="Your level">
            <span class="level-badge">Lv <?= (int) $game['level'] ?></span>
            <div class="level-meter">
                <div class="level-meter-top">
                    <span class="level-xp"><?= (int) $game['xp'] ?> XP</span>
                    <span class="level-next"><?= (int) $game['into'] ?> / <?= (int) $game['needed'] ?> to Lv <?= (int) $game['level'] + 1 ?></span>
                </div>
                <div class="level-bar"><span class="level-bar-fill" style="width: <?= (int) $game['pct'] ?>%"></span></div>
            </div>
            <span class="level-flame" title="Day streak"><span aria-hidden="true">&#128293;</span> <?= (int) $game['streak'] ?></span>
        </section>

        <section class="stat-grid" aria-label="Overall stats">
            <div class="stat-card">
                <span class="stat-value"><?= (int) $overall['total_quizzes'] ?></span>
                <span class="stat-label">Quizzes taken</span>
            </div>
            <div class="stat-card">
                <span class="stat-value"><?= (int) $overall['avg_pct'] ?>%</span>
                <span class="stat-label">Average score</span>
            </div>
            <div class="stat-card stat-card--accent">
                <span class="stat-value"><?= (int) $overall['perfect_count'] ?></span>
                <span class="stat-label">Perfect scores</span>
            </div>
            <div class="stat-card stat-card--streak">
                <span class="stat-value"><span aria-hidden="true">&#128293;</span> <?= (int) $overall['active_day_streak'] ?></span>
                <span class="stat-label">Day streak</span>
            </div>
            <div class="stat-card stat-card--wide">
                <span class="stat-value stat-value--sm"><?= $overall['best_deck'] !== null ? e($overall['best_deck']) : '—' ?></span>
                <span class="stat-label">Best deck<?= $overall['best_deck'] !== null ? ' &middot; ' . (int) $overall['best_deck_pct'] . '%' : '' ?></span>
            </div>
        </section>

        <section class="chart-row">
            <div class="chart-panel">
                <h2 class="panel-title">Score over time</h2>
                <div class="chart-wrap"><canvas id="timelineChart" height="240"></canvas></div>
            </div>
            <div class="chart-panel chart-panel--narrow">
                <h2 class="panel-title">Accuracy</h2>
                <div class="chart-wrap chart-wrap--doughnut">
                    <canvas id="accuracyChart" height="240"></canvas>
                    <span class="doughnut-center"><?= (int) $overall['avg_pct'] ?>%</span>
                </div>
            </div>
        </section>

        <section class="deck-table-section">
            <h2 class="panel-title">By deck</h2>
            <div class="deck-table" role="table" aria-label="Performance by deck">
                <div class="deck-row deck-row--head" role="row">
                    <span role="columnheader">Deck</span>
                    <span role="columnheader">Attempts</span>
                    <span role="columnheader">Avg</span>
                    <span role="columnheader">Best</span>
                    <span role="columnheader">Last played</span>
                </div>
                <?php foreach ($decks as $d): ?>
                    <a class="deck-row" role="row" href="progress.php?deck=<?= (int) $d['deck_id'] ?>">
                        <span class="deck-name" role="cell"><?= e($d['title']) ?></span>
                        <span role="cell" data-head="Attempts"><?= (int) $d['attempts'] ?></span>
                        <span role="cell" data-head="Avg"><?= (int) $d['avg_pct'] ?>%</span>
                        <span role="cell" data-head="Best">
                            <span class="deck-best"><?= (int) $d['best_pct'] ?>%</span>
                        </span>
                        <span role="cell" data-head="Last played"><?= e(date('M j, Y', strtotime($d['last_played']))) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    </main>

    <?php if ($hasData): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
      (function () {
        if (typeof Chart === "undefined") return;

        var reduce = window.matchMedia &&
            window.matchMedia("(prefers-reduced-motion: reduce)").matches;
        Chart.defaults.animation = reduce ? false : { duration: 600 };
        Chart.defaults.font.family = "Nunito, system-ui, sans-serif";

        var css = getComputedStyle(document.documentElement);
        function v(name, fallback) {
          var c = css.getPropertyValue(name);
          return (c && c.trim()) || fallback;
        }
        var mint = v("--mint", "#58cc6a");
        var carrot = v("--carrot", "#ff9838");
        var grid = v("--border", "#2e4d3c");
        var text = v("--text-muted", "#a7c4b1");

        // Vibrant palette for a colorful, gamified feel.
        var C = {
          green:  "#3ddc84",
          teal:   "#18c6c6",
          blue:   "#4aa8ff",
          purple: "#9b6cff",
          pink:   "#ff6fae",
          amber:  "#ffb43d",
          red:    "#ff6b6b"
        };

        // Point color by score band (red < 50, amber < 80, green otherwise).
        function bandColor(pct) {
          if (pct < 50) return C.red;
          if (pct < 80) return C.amber;
          return C.green;
        }

        // A left-to-right rainbow-ish gradient for the line stroke.
        function strokeGradient(ctx, w) {
          var g = ctx.createLinearGradient(0, 0, w, 0);
          g.addColorStop(0.00, C.green);
          g.addColorStop(0.35, C.teal);
          g.addColorStop(0.65, C.blue);
          g.addColorStop(1.00, C.purple);
          return g;
        }
        // Soft vertical fill under the line.
        function fillGradient(ctx, h) {
          var g = ctx.createLinearGradient(0, 0, 0, h);
          g.addColorStop(0, "rgba(74,168,255,0.28)");
          g.addColorStop(1, "rgba(61,220,132,0.02)");
          return g;
        }

        function lineChart(id, labels, data) {
          var el = document.getElementById(id);
          if (!el) return;
          var ctx = el.getContext("2d");
          var stroke = strokeGradient(ctx, el.width || 600);
          var fill = fillGradient(ctx, el.height || 240);
          new Chart(el, {
            type: "line",
            data: {
              labels: labels,
              datasets: [{
                data: data,
                borderWidth: 4,
                fill: true,
                tension: 0.38,
                borderColor: stroke,
                backgroundColor: fill,
                pointRadius: data.length > 30 ? 0 : 5,
                pointHoverRadius: 7,
                pointBackgroundColor: data.map(bandColor),
                pointBorderColor: "#ffffff",
                pointBorderWidth: 2
              }]
            },
            options: {
              responsive: true, maintainAspectRatio: false,
              plugins: { legend: { display: false },
                tooltip: { callbacks: { label: function (c) { return c.parsed.y + "%"; } } } },
              scales: {
                y: { min: 0, max: 100, ticks: { color: text, callback: function (val) { return val + "%"; } }, grid: { color: grid } },
                x: { ticks: { color: text, maxRotation: 0, autoSkip: true, maxTicksLimit: 8 }, grid: { display: false } }
              }
            }
          });
        }

        <?php if ($deckView > 0): ?>
          var dLabels = <?= json_encode(array_column($deckTimeline, 'label')) ?>;
          var dData = <?= json_encode(array_column($deckTimeline, 'pct')) ?>;
          if (dLabels.length) lineChart("deckChart", dLabels, dData);
        <?php else: ?>
          var tLabels = <?= json_encode(array_column($timeline, 'label')) ?>;
          var tData = <?= json_encode(array_column($timeline, 'pct')) ?>;
          lineChart("timelineChart", tLabels, tData);

          var acc = <?= (int) $overall['avg_pct'] ?>;
          var accEl = document.getElementById("accuracyChart");
          if (accEl) {
            // Filled arc gets a vibrant gradient; remainder is a faint track.
            var accColor = (function () {
              var ctx = accEl.getContext("2d");
              var g = ctx.createLinearGradient(0, 0, accEl.width, accEl.height);
              g.addColorStop(0, bandColor(acc));
              g.addColorStop(1, acc < 50 ? C.pink : (acc < 80 ? C.carrot || carrot : C.teal));
              return g;
            })();
            new Chart(accEl, {
              type: "doughnut",
              data: { datasets: [{
                data: [acc, 100 - acc],
                backgroundColor: [accColor, grid],
                borderWidth: 0,
                borderRadius: [20, 0],
                spacing: 0
              }] },
              options: { responsive: true, maintainAspectRatio: false, cutout: "72%", rotation: -90, circumference: 360,
                plugins: { legend: { display: false }, tooltip: { enabled: false } } }
            });
          }
        <?php endif; ?>
      })();
    </script>
    <?php endif; ?>

    <script src="js/theme-toggle.js"></script>
    <script src="js/user-menu.js"></script>
</body>
</html>
