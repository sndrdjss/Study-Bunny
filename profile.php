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

/** Load the user row fresh from the database (includes password hash). */
function profile_load_user(mysqli $conn, int $id): ?array
{
    $stmt = $conn->prepare('SELECT id, first_name, last_name, username, password, created_at FROM study_buddy_auth WHERE id = ?');
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

    // Unknown action.
    profile_redirect();
}

/* ---------------- Flash messages (whitelisted codes only) ---------------- */
$successMessages = [
    'name'     => 'Your name has been updated.',
    'password' => 'Your password has been changed.',
];
$errorMessages = [
    'name_empty' => 'Please enter both your first and last name.',
    'name_long'  => 'Names can be at most 50 characters each.',
    'current'    => 'Your current password is incorrect.',
    'short'      => 'Your new password must be at least 8 characters.',
    'mismatch'   => 'The new passwords do not match.',
    'same'       => 'Your new password must be different from your current one.',
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
    <link rel="stylesheet" href="css/profile.css?v=1">
    <link rel="stylesheet" href="css/theme.css?v=6">
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
            <span class="profile-avatar" aria-hidden="true"><?= e(user_initials($user)) ?></span>
            <div class="profile-identity">
                <h1 id="profile-name" class="profile-name"><?= e($fullName) ?></h1>
                <p class="profile-handle">@<?= e($user['username']) ?></p>
                <?php if ($memberSince !== ''): ?>
                    <p class="profile-since">Member since <?= e($memberSince) ?></p>
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

            <!-- ============================ PLACEHOLDERS ============================ -->
            <section class="profile-card profile-card--placeholder" aria-labelledby="my-decks-title">
                <div class="card-head">
                    <h2 id="my-decks-title" class="card-title">My decks</h2>
                    <span class="badge-soon">Coming soon</span>
                </div>
                <p class="card-text">All the decks you've created and saved will show up here.</p>
            </section>

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
