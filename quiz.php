<?php
/* =========================================================
   StudyBuddy — Quiz player
   Flow (per deck, state kept in session):
     start -> answer each question (POST) -> result
   Answer checking happens server-side so correct answers are
   never sent to the browser before the user answers.
   ========================================================= */
require_once __DIR__ . '/auth.php';
require 'db.php';
require_once __DIR__ . '/pets.php';
require_once __DIR__ . '/partials/pet_art.php';

$deckId = isset($_GET['id']) ? (int) $_GET['id'] : (isset($_POST['deck_id']) ? (int) $_POST['deck_id'] : 0);

function quiz_redirect(int $deckId, string $query = ''): void
{
    header('Location: quiz.php?id=' . $deckId . ($query !== '' ? '&' . $query : ''));
    exit;
}

/* ---------------- Load deck + access check ---------------- */
$deck = null;
$loadError = null;
if ($deckId > 0) {
    try {
        $stmt = $conn->prepare('SELECT id, user_id, title, status FROM deck WHERE id = ?');
        $stmt->bind_param('i', $deckId);
        $stmt->execute();
        $deck = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    } catch (mysqli_sql_exception $e) {
        $loadError = 'load';
    }
}

// Playable if published, or you own it (lets you try drafts before publishing).
$canPlay = $deck && ($deck['status'] === 'published' || owns_deck($deck));

/* ---------------- Load questions (owner/published only) ---------------- */
$questions = [];
if ($canPlay) {
    try {
        $stmt = $conn->prepare('SELECT id, type, question, answer FROM quiz_item WHERE deck_id = ? ORDER BY position, id');
        $stmt->bind_param('i', $deckId);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $row['options'] = [];
            $questions[] = $row;
        }
        $stmt->close();

        foreach ($questions as &$q) {
            if ($q['type'] === 'multiple_choice') {
                $stmt = $conn->prepare('SELECT id, option_text, is_correct FROM quiz_option WHERE quiz_item_id = ? ORDER BY position, id');
                $stmt->bind_param('i', $q['id']);
                $stmt->execute();
                $ores = $stmt->get_result();
                while ($o = $ores->fetch_assoc()) {
                    $q['options'][] = $o;
                }
                $stmt->close();
            }
        }
        unset($q);
    } catch (mysqli_sql_exception $e) {
        $loadError = 'load';
    }
}

$totalQuestions = count($questions);

/* ---------------- Session progress (per deck) ---------------- */
$sk = 'quiz_' . $deckId;   // session key for this deck's run

function quiz_reset(string $sk): void
{
    $_SESSION[$sk] = ['index' => 0, 'score' => 0, 'answered' => 0];
}

/* ---------------- Handle actions ---------------- */
$feedback = null;   // ['correct'=>bool,'your'=>string,'answer'=>string] after answering a card

if ($canPlay && $totalQuestions > 0 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $do = $_POST['do'] ?? '';

    if ($do === 'start' || $do === 'retake') {
        quiz_reset($sk);
        quiz_redirect($deckId);
    }

    if (!isset($_SESSION[$sk])) {
        quiz_reset($sk);
    }
    $state = $_SESSION[$sk];
    $i = (int) $state['index'];

    if ($do === 'answer' && $i < $totalQuestions) {
        $q = $questions[$i];
        $isCorrect = false;
        $yourText = '';
        $answerText = '';

        if ($q['type'] === 'multiple_choice') {
            $chosen = isset($_POST['option_id']) ? (int) $_POST['option_id'] : 0;
            foreach ($q['options'] as $o) {
                if ((int) $o['is_correct'] === 1) {
                    $answerText = $o['option_text'];
                }
                if ((int) $o['id'] === $chosen) {
                    $yourText = $o['option_text'];
                    $isCorrect = (int) $o['is_correct'] === 1;
                }
            }
        } else {
            // Flashcard: typed answer, compared trimmed + case-insensitive.
            $yourText   = trim((string) ($_POST['typed'] ?? ''));
            $answerText = (string) $q['answer'];
            $norm = static fn (string $s): string => mb_strtolower(trim($s), 'UTF-8');
            $isCorrect = $yourText !== '' && $norm($yourText) === $norm($answerText);
        }

        $state['score'] += $isCorrect ? 1 : 0;
        $state['answered'] = $i + 1;
        $_SESSION[$sk] = $state;

        // Log this answer (logged-in users) so Progress can show mistakes to
        // review. Snapshots keep it readable even if the deck changes later.
        // Non-fatal: a logging error must never break the quiz.
        if (is_logged_in()) {
            try {
                $loggerUid  = (int) current_user()['id'];
                $itemId     = (int) $q['id'];
                $qText      = (string) $q['question'];
                $correctInt = $isCorrect ? 1 : 0;
                $log = $conn->prepare(
                    'INSERT INTO quiz_answer
                        (user_id, deck_id, quiz_item_id, question_text, your_answer, correct_answer, is_correct)
                     VALUES (?, ?, ?, ?, ?, ?, ?)'
                );
                $log->bind_param('iiisssi', $loggerUid, $deckId, $itemId, $qText, $yourText, $answerText, $correctInt);
                $log->execute();
                $log->close();
            } catch (mysqli_sql_exception $e) {
                // Ignore — scoring already happened; logging is best-effort.
            }
        }

        // Show feedback for THIS question before advancing on "Next".
        $_SESSION[$sk]['feedback'] = [
            'correct' => $isCorrect,
            'your'    => $yourText,
            'answer'  => $answerText,
            'for'     => $i,
        ];
        quiz_redirect($deckId);
    }

    if ($do === 'next') {
        unset($_SESSION[$sk]['feedback']);
        $_SESSION[$sk]['index'] = min($i + 1, $totalQuestions);

        // Finished? Save the attempt (logged-in users only) once.
        if ($_SESSION[$sk]['index'] >= $totalQuestions && is_logged_in() && empty($_SESSION[$sk]['saved'])) {
            try {
                $uid = (int) current_user()['id'];
                $score = (int) $_SESSION[$sk]['score'];
                $ins = $conn->prepare('INSERT INTO quiz_attempt (user_id, deck_id, score, total) VALUES (?, ?, ?, ?)');
                $ins->bind_param('iiii', $uid, $deckId, $score, $totalQuestions);
                $ins->execute();
                $ins->close();
                $_SESSION[$sk]['saved'] = true;
            } catch (mysqli_sql_exception $e) {
                // Non-fatal: still show the result.
            }
        }
        quiz_redirect($deckId);
    }
}

/* ---------------- View state ---------------- */
$state = $_SESSION[$sk] ?? null;
$started = $state !== null;
$index = $started ? (int) $state['index'] : 0;
$finished = $started && $index >= $totalQuestions;
$feedback = $started ? ($state['feedback'] ?? null) : null;
$current = (!$finished && $index < $totalQuestions) ? $questions[$index] : null;
$token = csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudyBuddy — Quiz<?= $deck ? ': ' . e($deck['title']) : '' ?></title>
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
    <link rel="stylesheet" href="css/quiz.css?v=1">
    <link rel="stylesheet" href="css/pet.css?v=2">
    <link rel="stylesheet" href="css/theme.css?v=14">
</head>
<body>

    <header class="navbar">
        <div class="nav-left">
            <a href="explore.php" class="logo-slot" aria-label="Study Bunny home"><img src="images/study%20buddy%20no%20bg.png" alt="Study Bunny"></a>
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

    <main class="quiz-page">
    <?php if (!$deck || !$canPlay): ?>
        <section class="quiz-card quiz-msg">
            <h1 class="quiz-title">Quiz unavailable</h1>
            <p class="quiz-sub">This quiz isn't available. It may be unpublished or no longer exist.</p>
            <a class="btn-quiz" href="explore.php">&larr; Back to Explore</a>
        </section>

    <?php elseif ($totalQuestions === 0): ?>
        <section class="quiz-card quiz-msg">
            <h1 class="quiz-title"><?= e($deck['title']) ?></h1>
            <p class="quiz-sub">This deck has no questions yet.</p>
            <?php if (owns_deck($deck)): ?>
                <a class="btn-quiz" href="deck_items.php?id=<?= (int) $deck['id'] ?>">Add questions</a>
            <?php else: ?>
                <a class="btn-quiz" href="explore.php">&larr; Back to Explore</a>
            <?php endif; ?>
        </section>

    <?php elseif (!$started): ?>
        <!-- Start screen -->
        <section class="quiz-card quiz-msg">
            <span class="quiz-eyebrow">Quiz</span>
            <h1 class="quiz-title"><?= e($deck['title']) ?></h1>
            <p class="quiz-sub"><?= $totalQuestions ?> question<?= $totalQuestions === 1 ? '' : 's' ?> &middot; <?= $questions[0]['type'] === 'flashcard' ? 'Flashcards' : 'Multiple choice' ?></p>
            <?php if (!is_logged_in()): ?>
                <p class="quiz-note">Log in to save your score.</p>
            <?php endif; ?>
            <form method="post" action="quiz.php">
                <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
                <input type="hidden" name="deck_id" value="<?= (int) $deck['id'] ?>">
                <input type="hidden" name="do" value="start">
                <button type="submit" class="btn-quiz btn-quiz--lg">Start quiz &rarr;</button>
            </form>
        </section>

    <?php elseif ($finished): ?>
        <!-- Result screen -->
        <?php
            $score = (int) $state['score'];
            $pct = $totalQuestions > 0 ? (int) round($score / $totalQuestions * 100) : 0;
        ?>
        <section class="quiz-card quiz-msg">
            <span class="quiz-eyebrow">Result</span>
            <h1 class="quiz-title"><?= e($deck['title']) ?></h1>
            <p class="quiz-score"><?= $score ?> / <?= $totalQuestions ?></p>
            <p class="quiz-sub"><?= $pct ?>% correct<?= (!is_logged_in()) ? ' (not saved — log in to track progress)' : '' ?></p>
            <?php
                // Egg progress reward (display only; the attempt was already saved).
                $isPerfect = $totalQuestions > 0 && $score === $totalQuestions;
                $eggNow    = ($isPerfect && is_logged_in()) ? pet_state($conn, (int) current_user()['id']) : null;
            ?>
            <?php if ($eggNow && in_array($eggNow['stage'], ['incubating', 'ready'], true)): ?>
                <div class="egg-reward" role="status">
                    <?= render_egg($eggNow['egg'], $eggNow['progress'], 'egg-art--fresh') ?>
                    <div>
                        <?php if ($eggNow['stage'] === 'ready'): ?>
                            <p class="egg-reward-title">Your <?= e(EGGS[$eggNow['egg']]['name']) ?> is ready to hatch!</p>
                            <p class="egg-reward-sub"><?= PERFECTS_TO_HATCH ?>/<?= PERFECTS_TO_HATCH ?> perfect scores</p>
                            <a class="egg-reward-link" href="explore.php#pet-stage">Go hatch it &rarr;</a>
                        <?php else: ?>
                            <p class="egg-reward-title">Perfect! Your <?= e(EGGS[$eggNow['egg']]['name']) ?> cracked</p>
                            <p class="egg-reward-sub"><?= (int) $eggNow['progress'] ?>/<?= PERFECTS_TO_HATCH ?> perfect scores &middot; keep going!</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
            <div class="quiz-actions">
                <form method="post" action="quiz.php">
                    <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
                    <input type="hidden" name="deck_id" value="<?= (int) $deck['id'] ?>">
                    <input type="hidden" name="do" value="retake">
                    <button type="submit" class="btn-quiz">Retake</button>
                </form>
                <a class="btn-quiz btn-quiz--ghost" href="deck_items.php?id=<?= (int) $deck['id'] ?>">Back to deck</a>
            </div>
        </section>

    <?php elseif ($feedback !== null && (int) $feedback['for'] === $index): ?>
        <!-- Feedback screen (after answering the current question) -->
        <section class="quiz-card">
            <div class="quiz-progress">Question <?= $index + 1 ?> / <?= $totalQuestions ?></div>
            <?php $isFlash = $current['type'] === 'flashcard'; ?>
            <div class="flip <?= $isFlash ? 'flip--flipped' : '' ?>">
                <div class="flip-inner">
                    <div class="flip-front">
                        <p class="quiz-question"><?= e($current['question']) ?></p>
                    </div>
                    <div class="flip-back">
                        <p class="quiz-answer-label">Answer</p>
                        <p class="quiz-answer"><?= e($feedback['answer']) ?></p>
                    </div>
                </div>
            </div>

            <div class="quiz-verdict <?= $feedback['correct'] ? 'is-correct' : 'is-wrong' ?>">
                <?php if ($feedback['correct']): ?>
                    <span class="verdict-mark" aria-hidden="true">&#10003;</span> Correct!
                <?php else: ?>
                    <span class="verdict-mark" aria-hidden="true">&#10007;</span> Not quite.
                <?php endif; ?>
            </div>
            <?php if (!$isFlash): ?>
                <p class="quiz-your">Your answer: <strong><?= $feedback['your'] !== '' ? e($feedback['your']) : '—' ?></strong></p>
            <?php else: ?>
                <p class="quiz-your">You typed: <strong><?= $feedback['your'] !== '' ? e($feedback['your']) : '—' ?></strong></p>
            <?php endif; ?>

            <form method="post" action="quiz.php">
                <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
                <input type="hidden" name="deck_id" value="<?= (int) $deck['id'] ?>">
                <input type="hidden" name="do" value="next">
                <button type="submit" class="btn-quiz btn-quiz--lg">
                    <?= ($index + 1 >= $totalQuestions) ? 'See result' : 'Next question' ?> &rarr;
                </button>
            </form>
        </section>

    <?php else: ?>
        <!-- Question screen -->
        <section class="quiz-card">
            <div class="quiz-progress">Question <?= $index + 1 ?> / <?= $totalQuestions ?></div>
            <p class="quiz-question"><?= e($current['question']) ?></p>

            <?php if ($current['type'] === 'multiple_choice'): ?>
                <form method="post" action="quiz.php" class="quiz-options">
                    <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
                    <input type="hidden" name="deck_id" value="<?= (int) $deck['id'] ?>">
                    <input type="hidden" name="do" value="answer">
                    <?php foreach ($current['options'] as $o): ?>
                        <button type="submit" name="option_id" value="<?= (int) $o['id'] ?>" class="quiz-option-btn">
                            <?= e($o['option_text']) ?>
                        </button>
                    <?php endforeach; ?>
                </form>
            <?php else: ?>
                <form method="post" action="quiz.php" class="quiz-typed">
                    <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
                    <input type="hidden" name="deck_id" value="<?= (int) $deck['id'] ?>">
                    <input type="hidden" name="do" value="answer">
                    <label class="sr-only" for="typed">Your answer</label>
                    <input type="text" id="typed" name="typed" class="quiz-input" placeholder="Type your answer" autocomplete="off" autofocus>
                    <button type="submit" class="btn-quiz btn-quiz--lg">Check answer</button>
                </form>
            <?php endif; ?>
        </section>
    <?php endif; ?>
    </main>

    <script src="js/theme-toggle.js"></script>
    <script src="js/user-menu.js"></script>
</body>
</html>
