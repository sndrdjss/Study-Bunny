<?php
/* =========================================================
   StudyBuddy — Progress page data layer
   Require AFTER auth.php and db.php. All functions are
   read-only and return safe empty/zero shapes when the user
   has no history (or is a guest), so the page never errors.
   ========================================================= */

/**
 * Headline numbers for the overview.
 * @return array{total_quizzes:int, avg_pct:int, perfect_count:int,
 *               best_deck:?string, best_deck_pct:int, active_day_streak:int,
 *               decks_played:int}
 */
function overall_stats(mysqli $conn, int $uid): array
{
    $empty = [
        'total_quizzes'     => 0,
        'avg_pct'           => 0,
        'perfect_count'     => 0,
        'best_deck'         => null,
        'best_deck_pct'     => 0,
        'active_day_streak' => 0,
        'decks_played'      => 0,
    ];
    if ($uid <= 0) {
        return $empty;
    }

    try {
        $stmt = $conn->prepare(
            'SELECT
                COUNT(*)                                             AS total_quizzes,
                COALESCE(ROUND(AVG(score / total) * 100), 0)         AS avg_pct,
                COALESCE(SUM(total > 0 AND score = total), 0)        AS perfect_count,
                COUNT(DISTINCT deck_id)                              AS decks_played
             FROM quiz_attempt
             WHERE user_id = ? AND total > 0'
        );
        $stmt->bind_param('i', $uid);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $out = [
            'total_quizzes'     => (int) $row['total_quizzes'],
            'avg_pct'           => (int) $row['avg_pct'],
            'perfect_count'     => (int) $row['perfect_count'],
            'best_deck'         => null,
            'best_deck_pct'     => 0,
            'active_day_streak' => 0,
            'decks_played'      => (int) $row['decks_played'],
        ];

        if ($out['total_quizzes'] === 0) {
            return $out;
        }

        // Best deck = highest single-attempt percentage (ties: most recent).
        $best = $conn->prepare(
            "SELECT d.title AS title,
                    ROUND(a.score / a.total * 100) AS pct
             FROM quiz_attempt a
             JOIN deck d ON d.id = a.deck_id
             WHERE a.user_id = ? AND a.total > 0
             ORDER BY pct DESC, a.created_at DESC
             LIMIT 1"
        );
        $best->bind_param('i', $uid);
        $best->execute();
        if ($b = $best->get_result()->fetch_assoc()) {
            $out['best_deck']     = $b['title'];
            $out['best_deck_pct'] = (int) $b['pct'];
        }
        $best->close();

        $out['active_day_streak'] = active_day_streak($conn, $uid);
        return $out;
    } catch (\Throwable $e) {
        return $empty;
    }
}

/**
 * Consecutive days (ending today or yesterday) with at least one attempt.
 * Counting from yesterday keeps the streak alive earlier in the day
 * before the user has played yet.
 */
function active_day_streak(mysqli $conn, int $uid): int
{
    if ($uid <= 0) {
        return 0;
    }
    try {
        $stmt = $conn->prepare(
            'SELECT DISTINCT DATE(created_at) AS d
             FROM quiz_attempt
             WHERE user_id = ?
             ORDER BY d DESC'
        );
        $stmt->bind_param('i', $uid);
        $stmt->execute();
        $res = $stmt->get_result();
        $days = [];
        while ($r = $res->fetch_assoc()) {
            $days[$r['d']] = true;
        }
        $stmt->close();

        if (!$days) {
            return 0;
        }

        $today     = new DateTimeImmutable('today');
        $yesterday = $today->sub(new DateInterval('P1D'));
        // Anchor: today if active today, else yesterday if active yesterday, else no streak.
        if (isset($days[$today->format('Y-m-d')])) {
            $cursor = $today;
        } elseif (isset($days[$yesterday->format('Y-m-d')])) {
            $cursor = $yesterday;
        } else {
            return 0;
        }

        $streak = 0;
        while (isset($days[$cursor->format('Y-m-d')])) {
            $streak++;
            $cursor = $cursor->sub(new DateInterval('P1D'));
        }
        return $streak;
    } catch (\Throwable $e) {
        return 0;
    }
}

/**
 * One row per deck the user has attempted.
 * @return array<int,array{deck_id:int,title:string,attempts:int,
 *               avg_pct:int,best_pct:int,last_played:string}>
 */
function per_deck_stats(mysqli $conn, int $uid): array
{
    if ($uid <= 0) {
        return [];
    }
    try {
        $stmt = $conn->prepare(
            "SELECT a.deck_id,
                    COALESCE(d.title, 'Untitled deck')            AS title,
                    COUNT(*)                                      AS attempts,
                    ROUND(AVG(a.score / a.total) * 100)           AS avg_pct,
                    ROUND(MAX(a.score / a.total) * 100)           AS best_pct,
                    MAX(a.created_at)                             AS last_played
             FROM quiz_attempt a
             LEFT JOIN deck d ON d.id = a.deck_id
             WHERE a.user_id = ? AND a.total > 0
             GROUP BY a.deck_id, d.title
             ORDER BY last_played DESC"
        );
        $stmt->bind_param('i', $uid);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($r = $res->fetch_assoc()) {
            $rows[] = [
                'deck_id'     => (int) $r['deck_id'],
                'title'       => (string) $r['title'],
                'attempts'    => (int) $r['attempts'],
                'avg_pct'     => (int) $r['avg_pct'],
                'best_pct'    => (int) $r['best_pct'],
                'last_played' => (string) $r['last_played'],
            ];
        }
        $stmt->close();
        return $rows;
    } catch (\Throwable $e) {
        return [];
    }
}

/**
 * Chronological score percentages for charting. Optionally a single deck.
 * @return array<int,array{label:string, pct:int}>
 */
function score_timeline(mysqli $conn, int $uid, int $deckId = 0): array
{
    if ($uid <= 0) {
        return [];
    }
    try {
        $sql = 'SELECT created_at, ROUND(score / total * 100) AS pct
                FROM quiz_attempt
                WHERE user_id = ? AND total > 0';
        if ($deckId > 0) {
            $sql .= ' AND deck_id = ?';
        }
        $sql .= ' ORDER BY created_at ASC, id ASC';

        $stmt = $conn->prepare($sql);
        if ($deckId > 0) {
            $stmt->bind_param('ii', $uid, $deckId);
        } else {
            $stmt->bind_param('i', $uid);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($r = $res->fetch_assoc()) {
            $rows[] = [
                'label' => date('M j', strtotime($r['created_at'])),
                'pct'   => (int) $r['pct'],
            ];
        }
        $stmt->close();
        return $rows;
    } catch (\Throwable $e) {
        return [];
    }
}

/** Does this user have any attempt for this deck? (validates drill-down param) */
function user_attempted_deck(mysqli $conn, int $uid, int $deckId): bool
{
    if ($uid <= 0 || $deckId <= 0) {
        return false;
    }
    try {
        $stmt = $conn->prepare('SELECT 1 FROM quiz_attempt WHERE user_id = ? AND deck_id = ? LIMIT 1');
        $stmt->bind_param('ii', $uid, $deckId);
        $stmt->execute();
        $exists = (bool) $stmt->get_result()->fetch_row();
        $stmt->close();
        return $exists;
    } catch (\Throwable $e) {
        return false;
    }
}

/* =========================================================
   XP & LEVEL (gamification)
   All derived live from quiz_attempt, so levels are retroactive
   and need no extra storage.

   XP per completed quiz (total > 0):
     base        +10  (for finishing)
     performance +round(score/total * 10)   (0..10 by accuracy)
     perfect     +15  bonus when score == total
   => a perfect quiz is worth 35 XP; a 0% finish is worth 10.

   Level curve: level n starts at XP 50 * (n-1) * n / 2, i.e.
   you need 50 XP for Lv2, then +100 for Lv3, +150 for Lv4, ...
   Early levels come quickly; later ones take progressively more.
   ========================================================= */

const XP_BASE_PER_QUIZ   = 10;
const XP_PERFORMANCE_MAX = 10;
const XP_PERFECT_BONUS   = 15;

/** Total lifetime XP for a user, computed from quiz_attempt. */
function user_xp(mysqli $conn, int $uid): int
{
    if ($uid <= 0) {
        return 0;
    }
    try {
        $stmt = $conn->prepare(
            'SELECT
                COUNT(*)                               AS quizzes,
                COALESCE(SUM(score / total), 0)        AS perf_sum,
                COALESCE(SUM(total > 0 AND score = total), 0) AS perfects
             FROM quiz_attempt
             WHERE user_id = ? AND total > 0'
        );
        $stmt->bind_param('i', $uid);
        $stmt->execute();
        $r = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $quizzes  = (int) $r['quizzes'];
        $perfSum  = (float) $r['perf_sum'];   // sum of accuracy ratios 0..1
        $perfects = (int) $r['perfects'];

        return (int) round(
            $quizzes * XP_BASE_PER_QUIZ
            + $perfSum * XP_PERFORMANCE_MAX
            + $perfects * XP_PERFECT_BONUS
        );
    } catch (\Throwable $e) {
        return 0;
    }
}

/** Total XP required to REACH a given level (level 1 starts at 0). */
function xp_for_level(int $level): int
{
    if ($level <= 1) {
        return 0;
    }
    $n = $level - 1;
    return (int) (50 * $n * ($n + 1) / 2); // 50,150,300,500,... cumulative
}

/**
 * Resolve a level (and progress to the next) from total XP.
 * @return array{level:int, xp:int, into:int, needed:int, pct:int}
 */
function level_from_xp(int $xp): array
{
    $xp = max(0, $xp);
    $level = 1;
    while ($xp >= xp_for_level($level + 1)) {
        $level++;
    }
    $floor  = xp_for_level($level);
    $ceil   = xp_for_level($level + 1);
    $into   = $xp - $floor;
    $needed = $ceil - $floor;
    $pct    = $needed > 0 ? (int) round($into / $needed * 100) : 0;

    return ['level' => $level, 'xp' => $xp, 'into' => $into, 'needed' => $needed, 'pct' => $pct];
}

/**
 * One call for the nav pill: streak + level + xp.
 * @return array{streak:int, level:int, xp:int, into:int, needed:int, pct:int}
 */
function user_game_stats(mysqli $conn, int $uid): array
{
    $lvl = level_from_xp(user_xp($conn, $uid));
    return [
        'streak' => active_day_streak($conn, $uid),
        'level'  => $lvl['level'],
        'xp'     => $lvl['xp'],
        'into'   => $lvl['into'],
        'needed' => $lvl['needed'],
        'pct'    => $lvl['pct'],
    ];
}

/** A deck's title (for the drill-down heading). */
function deck_title(mysqli $conn, int $deckId): string
{
    try {
        $stmt = $conn->prepare('SELECT title FROM deck WHERE id = ?');
        $stmt->bind_param('i', $deckId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ? (string) $row['title'] : 'Deck';
    } catch (\Throwable $e) {
        return 'Deck';
    }
}

/**
 * Questions the user most recently got WRONG for a deck — one entry per
 * question, using only that question's latest answer. A question later
 * answered correctly drops off. Returns [] when there is nothing tracked
 * (e.g. legacy attempts made before answer logging existed).
 * @return array<int,array{question_text:string, your_answer:?string,
 *               correct_answer:?string, answered_at:string}>
 */
function latest_wrong_answers(mysqli $conn, int $uid, int $deckId): array
{
    if ($uid <= 0 || $deckId <= 0) {
        return [];
    }
    try {
        // For each quiz_item_id, keep only the row with the newest answered_at,
        // then surface the ones whose latest result was wrong.
        $sql =
            "SELECT qa.question_text, qa.your_answer, qa.correct_answer, qa.answered_at
             FROM quiz_answer qa
             JOIN (
                 SELECT quiz_item_id, MAX(answered_at) AS latest, MAX(id) AS latest_id
                 FROM quiz_answer
                 WHERE user_id = ? AND deck_id = ? AND quiz_item_id IS NOT NULL
                 GROUP BY quiz_item_id
             ) m ON m.quiz_item_id = qa.quiz_item_id
                AND qa.answered_at = m.latest
                AND qa.id = m.latest_id
             WHERE qa.user_id = ? AND qa.deck_id = ? AND qa.is_correct = 0
             ORDER BY qa.answered_at DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('iiii', $uid, $deckId, $uid, $deckId);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($r = $res->fetch_assoc()) {
            $rows[] = [
                'question_text'  => (string) $r['question_text'],
                'your_answer'    => $r['your_answer'],
                'correct_answer' => $r['correct_answer'],
                'answered_at'    => (string) $r['answered_at'],
            ];
        }
        $stmt->close();
        return $rows;
    } catch (\Throwable $e) {
        return [];
    }
}
