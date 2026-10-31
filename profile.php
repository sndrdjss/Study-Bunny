<?php
require_once __DIR__ . '/auth.php';
require 'db.php';
require_login();

/** Redirect (PRG) and stop. Only ever called before any output. */
function profile_redirect(string $query = ''): void
{
    header('Location: profile.php' . ($query !== '' ? '?' . $query : ''));
    exit;
}

/** Delete an old avatar file, but only inside images/avatars/. */
function profile_delete_avatar_file(string $relPath): void
{
    if ($relPath === '') {
        return;
    }
    $dir  = realpath(__DIR__ . '/images/avatars');
    $file = realpath(__DIR__ . '/' . $relPath);
    if ($dir && $file && str_starts_with($file, $dir . DIRECTORY_SEPARATOR) && is_file($file)) {
        @unlink($file);
    }
}

/** Load the user row fresh from the database (includes password hash). */
function profile_load_user(mysqli $conn, int $id): ?array
{
    $stmt = $conn->prepare('SELECT id, first_name, last_name, username, password, avatar, created_at FROM study_buddy_auth WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();
    return $row ?: null;
}

$sessionUser = current_user();
$userId = (int) ($sessionUser['id'] ?? 0);

try {
    $user = $userId > 0 ? profile_load_user($conn, $userId) : null;
} catch (Throwable $ex) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Could not load your profile right now. Please try again later.';
    exit;
}

if ($user === null) {
    // Account no longer exists (or bad session) — drop the session user.
    unset($_SESSION['user']);
    session_regenerate_id(true);
    header('Location: login.php');
    exit;
}

/* ---------------- POST handling (Post/Redirect/Get) ---------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $action = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';

    if ($action === 'update_name') {
        $first = trim(is_string($_POST['first_name'] ?? null) ? $_POST['first_name'] : '');
        $last  = trim(is_string($_POST['last_name'] ?? null) ? $_POST['last_name'] : '');

        if ($first === '' || $last === '') {
            profile_redirect('error=name_empty');
        }
        if (mb_strlen($first, 'UTF-8') > 50 || mb_strlen($last, 'UTF-8') > 50) {
            profile_redirect('error=name_long');
        }

        try {
            $stmt = $conn->prepare('UPDATE study_buddy_auth SET first_name = ?, last_name = ? WHERE id = ?');
            $id = (int) $user['id'];
            $stmt->bind_param('ssi', $first, $last, $id);
            $stmt->execute();
            $stmt->close();
        } catch (Throwable $ex) {
            profile_redirect('error=db');
        }

        $user['first_name'] = $first;
        $user['last_name']  = $last;
        set_session_user($user);
        profile_redirect('msg=name');
    }

    if ($action === 'change_password') {
        $current = is_string($_POST['current_password'] ?? null) ? $_POST['current_password'] : '';
        $new     = is_string($_POST['new_password'] ?? null) ? $_POST['new_password'] : '';
        $confirm = is_string($_POST['confirm_password'] ?? null) ? $_POST['confirm_password'] : '';

        if (!password_verify($current, (string) $user['password'])) {
            profile_redirect('error=current');
        }
        if (strlen($new) < 8) {
            profile_redirect('error=short');
        }
        if ($new !== $confirm) {
            profile_redirect('error=mismatch');
        }
        if ($new === $current) {
            profile_redirect('error=same');
        }

        try {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $conn->prepare('UPDATE study_buddy_auth SET password = ? WHERE id = ?');
            $id = (int) $user['id'];
            $stmt->bind_param('si', $hash, $id);
            $stmt->execute();
            $stmt->close();
        } catch (Throwable $ex) {
            profile_redirect('error=db');
        }

        session_regenerate_id(true);
        profile_redirect('msg=password');
    }

    if ($action === 'update_avatar') {
        if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] === UPLOAD_ERR_NO_FILE) {
            profile_redirect('error=avatar_none');
        }
        if ($_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            // Covers INI_SIZE / FORM_SIZE / partial uploads.
            profile_redirect('error=avatar_big');
        }
        if ($_FILES['avatar']['size'] > 2 * 1024 * 1024) {
            profile_redirect('error=avatar_big');
        }

        // Confirm it's a real image of an allowed type.
        $info = getimagesize($_FILES['avatar']['tmp_name']);
        $allowed = [
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_PNG  => 'png',
            IMAGETYPE_WEBP => 'webp',
        ];
        if ($info === false || !isset($allowed[$info[2]])) {
            profile_redirect('error=avatar_type');
        }

        $dir = __DIR__ . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'avatars';
        if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
            profile_redirect('error=db');
        }

        $filename = 'u' . (int) $user['id'] . '_' . bin2hex(random_bytes(6)) . '.' . $allowed[$info[2]];
        $target   = $dir . DIRECTORY_SEPARATOR . $filename;
        if (!move_uploaded_file($_FILES['avatar']['tmp_name'], $target)) {
            profile_redirect('error=db');
        }
        $newPath = 'images/avatars/' . $filename;
        $oldPath = trim((string) ($user['avatar'] ?? ''));

        try {
            $stmt = $conn->prepare('UPDATE study_buddy_auth SET avatar = ? WHERE id = ?');
            $id = (int) $user['id'];
            $stmt->bind_param('si', $newPath, $id);
            $stmt->execute();
            $stmt->close();
        } catch (Throwable $ex) {
            @unlink($target);
            profile_redirect('error=db');
        }

        profile_delete_avatar_file($oldPath);
        $user['avatar'] = $newPath;
        set_session_user($user);
        profile_redirect('msg=avatar');
    }

    if ($action === 'remove_avatar') {
        $oldPath = trim((string) ($user['avatar'] ?? ''));
        try {
            $stmt = $conn->prepare('UPDATE study_buddy_auth SET avatar = NULL WHERE id = ?');
            $id = (int) $user['id'];
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();
        } catch (Throwable $ex) {
            profile_redirect('error=db');
        }
        profile_delete_avatar_file($oldPath);
        $user['avatar'] = '';
        set_session_user($user);
        profile_redirect('msg=avatar_removed');
    }

    // Unknown action.
    profile_redirect();
}

/* ---------------- Flash messages (whitelisted codes only) ---------------- */
$successMessages = [
    'name'     => 'Your name has been updated.',
    'password' => 'Your password has been changed.',
    'avatar'   => 'Your profile picture has been updated.',
    'avatar_removed' => 'Your profile picture has been removed.',
];
$errorMessages = [
    'name_empty' => 'Please enter both your first and last name.',
    'name_long'  => 'Names can be at most 50 characters each.',
    'current'    => 'Your current password is incorrect.',
    'short'      => 'Your new password must be at least 8 characters.',
    'mismatch'   => 'The new passwords do not match.',
    'same'       => 'Your new password must be different from your current one.',
    'avatar_none' => 'Please choose an image to upload.',
    'avatar_type' => 'Please upload a JPG, PNG or WebP image.',
    'avatar_big'  => 'That image is too large. Please keep it under 2 MB.',
    'db'         => 'Something went wrong saving your changes. Please try again.',
];

$msgCode   = is_string($_GET['msg'] ?? null) ? $_GET['msg'] : '';
$errorCode = is_string($_GET['error'] ?? null) ? $_GET['error'] : '';
$successText = $successMessages[$msgCode] ?? '';
$errorText   = $errorMessages[$errorCode] ?? '';

/* ---------------- View data ---------------- */
$fullName = trim($user['first_name'] . ' ' . $user['last_name']);
if ($fullName === '') {
    $fullName = $user['username'];
}
$memberSince = '';
if (!empty($user['created_at'])) {
    $ts = strtotime((string) $user['created_at']);
    if ($ts !== false) {
        $memberSince = date('F Y', $ts);
    }
}

/* ---------------- My decks ---------------- */
$myDecks        = [];
$decksError     = false;
$draftDecks     = [];
$publishedDecks = [];
$totalQuestions = 0;
try {
    $deckOwnerId = (int) $user['id'];
    $stmt = $conn->prepare(
        'SELECT d.id, d.title, d.category, d.status, d.cover_image, d.created_at,
                (SELECT COUNT(*) FROM quiz_item q WHERE q.deck_id = d.id) AS item_count
           FROM deck d
          WHERE d.user_id = ?
          ORDER BY d.created_at DESC, d.id DESC'
    );
    if ($stmt === false) {
        // mysqli not in exception mode — normalise to the same error path.
        throw new mysqli_sql_exception('Could not prepare deck query.');
    }
    $stmt->bind_param('i', $deckOwnerId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $myDecks[] = $row;
    }
    $stmt->close();
} catch (mysqli_sql_exception $ex) {
    $decksError = true;
    $myDecks = [];
}

foreach ($myDecks as $deckRow) {
    $totalQuestions += (int) $deckRow['item_count'];
    if (($deckRow['status'] ?? '') === 'published') {
        $publishedDecks[] = $deckRow;
    } else {
        $draftDecks[] = $deckRow;
    }
}
$totalDecks     = count($myDecks);
$publishedCount = count($publishedDecks);
$draftCount     = count($draftDecks);

/** Render one deck list row (title, category chip, question count, actions). */
function profile_render_deck_row(array $deck, bool $isDraft): void
{
    $id    = (int) $deck['id'];
    $count = (int) $deck['item_count'];
    $title = trim((string) $deck['title']) !== '' ? (string) $deck['title'] : 'Untitled deck';
    $cat   = trim((string) ($deck['category'] ?? ''));
    ?>
    <li class="deck-row">
        <div class="deck-row-main">
            <span class="deck-row-title" title="<?= e($title) ?>"><?= e($title) ?></span>
            <span class="deck-row-meta">
                <?php if ($cat !== ''): ?>
                    <span class="deck-chip"><?= e($cat) ?></span>
                <?php endif; ?>
                <span class="deck-row-count"><?= $count ?> <?= $count === 1 ? 'question' : 'questions' ?></span>
            </span>
        </div>
        <div class="deck-row-actions">
            <a class="btn-ghost btn-sm" href="deck_items.php?id=<?= $id ?>" aria-label="Manage <?= e($title) ?>">Manage</a>
            <?php if ((int) $deck['item_count'] > 0): ?>
                <a class="btn-ghost btn-sm" href="quiz.php?id=<?= $id ?>" aria-label="Take quiz for <?= e($title) ?>">Take Quiz</a>
            <?php endif; ?>
            <?php if ($isDraft): ?>
                <form method="post" action="publish_deck.php" class="deck-publish-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= $id ?>">
                    <button type="submit" class="btn-primary btn-sm" aria-label="Publish <?= e($title) ?>">Publish</button>
                </form>
            <?php endif; ?>
        </div>
    </li>
    <?php
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudyBuddy — Profile</title>
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
    <link rel="stylesheet" href="css/profile.css?v=4">
    <link rel="stylesheet" href="css/theme.css?v=14">
</head>
<body>

    <!-- ============================ NAVIGATION BAR ============================ -->
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

    <main class="profile-page">

        <?php if ($successText !== ''): ?>
            <div class="auth-alert auth-alert--success" role="status">
                <span class="alert-icon" aria-hidden="true">&#10003;</span>
                <span><?= e($successText) ?></span>
            </div>
        <?php endif; ?>
        <?php if ($errorText !== ''): ?>
            <div class="auth-alert auth-alert--error" role="alert">
                <span class="alert-icon" aria-hidden="true">!</span>
                <span><?= e($errorText) ?></span>
            </div>
        <?php endif; ?>

        <!-- ============================ HEADER CARD ============================ -->
        <section class="profile-card profile-header" aria-labelledby="profile-name">
            <?php $headerAvatar = avatar_src($user); ?>
            <?php if ($headerAvatar !== null): ?>
                <span class="profile-avatar profile-avatar--img" aria-hidden="true"><img src="<?= e($headerAvatar) ?>" alt=""></span>
            <?php else: ?>
                <span class="profile-avatar" aria-hidden="true"><?= e(user_initials($user)) ?></span>
            <?php endif; ?>
            <div class="profile-identity">
                <h1 id="profile-name" class="profile-name"><?= e($fullName) ?></h1>
                <p class="profile-handle">@<?= e($user['username']) ?></p>
                <?php if ($memberSince !== ''): ?>
                    <p class="profile-since">Member since <?= e($memberSince) ?></p>
                <?php endif; ?>

                <form class="avatar-form" method="post" action="profile.php" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update_avatar">
                    <input type="file" id="avatar" name="avatar" accept="image/png,image/jpeg,image/webp" class="avatar-file">
                    <label for="avatar" class="btn-primary btn-sm avatar-choose">Change photo</label>
                    <button type="submit" class="btn-ghost btn-sm avatar-upload-btn">Upload</button>
                    <span class="field-hint">JPG, PNG or WebP, up to 2&nbsp;MB.</span>
                </form>
                <?php if ($headerAvatar !== null): ?>
                    <form class="avatar-remove-form" method="post" action="profile.php">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="remove_avatar">
                        <button type="submit" class="btn-ghost btn-sm">Remove photo</button>
                    </form>
                <?php endif; ?>
            </div>
        </section>

        <div class="profile-grid">

            <!-- ============================ EDIT NAME ============================ -->
            <section class="profile-card" aria-labelledby="edit-name-title">
                <h2 id="edit-name-title" class="card-title">Edit name</h2>
                <form class="profile-form" method="post" action="profile.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update_name">

                    <div class="field">
                        <label for="first_name">First name</label>
                        <input type="text" id="first_name" name="first_name" value="<?= e($user['first_name']) ?>" required maxlength="50" autocomplete="given-name">
                    </div>

                    <div class="field">
                        <label for="last_name">Last name</label>
                        <input type="text" id="last_name" name="last_name" value="<?= e($user['last_name']) ?>" required maxlength="50" autocomplete="family-name">
                    </div>

                    <div class="field">
                        <span class="field-label">Username</span>
                        <p class="readonly-value">@<?= e($user['username']) ?></p>
                        <p class="field-hint" id="username-hint">Usernames can't be changed.</p>
                    </div>

                    <button type="submit" class="btn-primary">Save name</button>
                </form>
            </section>

            <!-- ============================ CHANGE PASSWORD ============================ -->
            <section class="profile-card" aria-labelledby="change-password-title">
                <h2 id="change-password-title" class="card-title">Change password</h2>
                <form class="profile-form" method="post" action="profile.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="change_password">

                    <div class="field">
                        <label for="current_password">Current password</label>
                        <input type="password" id="current_password" name="current_password" required autocomplete="current-password">
                    </div>

                    <div class="field">
                        <label for="new_password">New password</label>
                        <input type="password" id="new_password" name="new_password" required minlength="8" autocomplete="new-password" aria-describedby="new-password-hint">
                        <p class="field-hint" id="new-password-hint">At least 8 characters.</p>
                    </div>

                    <div class="field">
                        <label for="confirm_password">Confirm new password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required minlength="8" autocomplete="new-password" aria-describedby="confirm-password-hint">
                        <p class="field-hint" id="confirm-password-hint">At least 8 characters.</p>
                    </div>

                    <button type="submit" class="btn-primary">Update password</button>
                </form>
            </section>

            <!-- ============================ MY DECKS ============================ -->
            <section class="profile-card profile-card--wide my-decks" aria-labelledby="my-decks-title">
                <div class="card-head">
                    <h2 id="my-decks-title" class="card-title">My decks</h2>
                    <a href="create.php" class="btn-ghost btn-sm">+ New deck</a>
                </div>

                <?php if ($decksError): ?>
                    <div class="auth-alert auth-alert--error" role="alert">
                        <span class="alert-icon" aria-hidden="true">!</span>
                        <span>We couldn't load your decks right now. Please try again later.</span>
                    </div>
                <?php elseif ($totalDecks === 0): ?>
                    <div class="decks-empty">
                        <p class="card-text">You haven't made any decks yet.</p>
                        <a href="create.php" class="btn-primary">Create your first deck</a>
                    </div>
                <?php else: ?>
                    <ul class="deck-stats" aria-label="Deck stats">
                        <li class="deck-stat">
                            <span class="deck-stat-num"><?= $totalDecks ?></span>
                            <span class="deck-stat-label">Decks created</span>
                        </li>
                        <li class="deck-stat">
                            <span class="deck-stat-num"><?= $publishedCount ?></span>
                            <span class="deck-stat-label">Published</span>
                        </li>
                        <li class="deck-stat">
                            <span class="deck-stat-num"><?= $totalQuestions ?></span>
                            <span class="deck-stat-label">Questions written</span>
                        </li>
                    </ul>

                    <div class="deck-group">
                        <h3 class="deck-group-title">Drafts <span class="deck-group-count">(<?= $draftCount ?>)</span></h3>
                        <?php if ($draftCount === 0): ?>
                            <p class="deck-group-empty">No drafts.</p>
                        <?php else: ?>
                            <ul class="deck-list">
                                <?php foreach ($draftDecks as $deck) { profile_render_deck_row($deck, true); } ?>
                            </ul>
                        <?php endif; ?>
                    </div>

                    <div class="deck-group">
                        <h3 class="deck-group-title">Published <span class="deck-group-count">(<?= $publishedCount ?>)</span></h3>
                        <?php if ($publishedCount === 0): ?>
                            <p class="deck-group-empty">No published decks yet.</p>
                        <?php else: ?>
                            <ul class="deck-list">
                                <?php foreach ($publishedDecks as $deck) { profile_render_deck_row($deck, false); } ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </section>

            <!-- ============================ PLACEHOLDERS ============================ -->

            <section class="profile-card profile-card--placeholder" aria-labelledby="stats-title">
                <div class="card-head">
                    <h2 id="stats-title" class="card-title">Stats &amp; streaks</h2>
                    <span class="badge-soon">Coming soon</span>
                </div>
                <p class="card-text">Track your study streaks, XP and review history in one place.</p>
            </section>

        </div>
    </main>

    <script src="js/theme-toggle.js"></script>
    <script src="js/user-menu.js"></script>
</body>
</html>
