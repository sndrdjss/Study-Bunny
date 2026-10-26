<?php
require_once __DIR__ . '/auth.php';
require 'db.php';

// Already logged in → straight to the app.
if (is_logged_in()) {
    header('Location: explore.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    // Read the posted fields (names match the form below).
    $lastName  = trim($_POST['last-name'] ?? '');
    $firstName = trim($_POST['first-name'] ?? '');
    $username  = trim($_POST['username'] ?? '');
    $pass      = (string) ($_POST['password'] ?? '');
    $confirm   = (string) ($_POST['confirm-password'] ?? '');

    // Basic validation.
    //para sa fill up all fields kung kulang ang pagka input (fill in)
    if ($lastName === '' || $firstName === '' || $username === '' || $pass === '') {
        header('Location: register.php?error=empty');
        exit;
    }
    if (mb_strlen($lastName) > 50 || mb_strlen($firstName) > 50) {
        header('Location: register.php?error=name');
        exit;
    }
    if (!preg_match('/^[A-Za-z0-9_.]{3,30}$/', $username)) {
        header('Location: register.php?error=username');
        exit;
    }
    if (strlen($pass) < 8) {
        header('Location: register.php?error=short');
        exit;
    }
    //para sa confirmation kung tugma yung nilagay sa password habang nagrereg (password)
    if ($pass !== $confirm) {
        header('Location: register.php?error=mismatch');
        exit;
    }
    //insert the values sa mysql (prepared statement + hashed password)
    try {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        $stmt = $conn->prepare(
            'INSERT INTO study_buddy_auth (last_name, first_name, username, password) VALUES (?, ?, ?, ?)'
        );
        $stmt->bind_param('ssss', $lastName, $firstName, $username, $hash);
        $stmt->execute();
        $stmt->close();

        // Success → go to the login page.
        header('Location: login.php?registered=1');
        exit;
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() === 1062) {
            header('Location: register.php?error=taken');
        } else {
            header('Location: register.php?error=db');
        }
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudyBuddy — Register</title>
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
    <link rel="stylesheet" href="css/register.css?v=3">
    <link rel="stylesheet" href="css/theme.css?v=8">
</head>
<body>

    <!-- ============================ NAVIGATION BAR ============================ -->
    <header class="navbar">
        <div class="nav-left">
            <a href="explore.php" class="logo">Study<span>Buddy</span></a>
        </div>

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
        </div>
    </header>

    <!-- ============================ REGISTER FORM ============================ -->
    <main class="auth-wrap">
        <div class="auth-left">
        <section class="auth-card">
            <h1 class="auth-title">SIGN UP</h1>
            <p class="auth-subtitle">Create your StudyBuddy account.</p>

            <!-- Styled, themed status banner (replaces inline document.write) -->
            <?php
                $regMessages = [
                    'empty'    => 'Please fill in all fields.',
                    'name'     => 'Names can be at most 50 characters.',
                    'username' => 'Username must be 3–30 characters: letters, numbers, "_" or ".".',
                    'short'    => 'Password must be at least 8 characters.',
                    'mismatch' => 'Passwords do not match.',
                    'taken'    => 'That username is already taken.',
                    'db'       => 'Something went wrong. Please try again.',
                ];
                $regErr = $_GET['error'] ?? '';
            ?>
            <?php if (isset($regMessages[$regErr])): ?>
                <div class="auth-alert auth-alert--error" role="alert">
                    <span class="alert-icon" aria-hidden="true">&#9888;</span>
                    <span><?= htmlspecialchars($regMessages[$regErr], ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            <?php endif; ?>

            <form class="auth-form" action="register.php" method="post">
                <?= csrf_field() ?>
                <div class="field-row">
                    <div class="field">
                        <label for="last-name">Last Name</label>
                        <input type="text" id="last-name" name="last-name" placeholder="Doe" autocomplete="family-name">
                    </div>
                    <div class="field">
                        <label for="first-name">First Name</label>
                        <input type="text" id="first-name" name="first-name" placeholder="Jane" autocomplete="given-name">
                    </div>
                </div>

                <div class="field">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="Choose a username" autocomplete="username">
                </div>

                <div class="field">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Create a password" autocomplete="new-password" minlength="8" aria-describedby="password-hint">
                    <small id="password-hint" class="field-hint">At least 8 characters.</small>
                </div>

                <div class="field">
                    <label for="confirm-password">Confirm Password</label>
                    <input type="password" id="confirm-password" name="confirm-password" placeholder="Re-enter your password" autocomplete="new-password">
                </div>

                <button type="submit" class="auth-submit">SIGN UP</button>
            </form>

            <p class="auth-footer">Already have an account? <a href="login.php">Login</a></p>
        </section>
        </div>

        <!-- Right-side branded showcase panel -->
        <aside class="auth-side">
            <span class="showcase-eyebrow">Join StudyBuddy</span>
            <h2 class="showcase-heading">Build decks. Beat exams. <span class="accent">Have fun.</span></h2>
            <p class="showcase-text">Create an account to craft your own decks, save favorites, and turn study sessions into a streak you'll want to keep.</p>
            <ul class="showcase-features">
                <li><span class="feat-icon" aria-hidden="true">&#43;</span> Create and publish your own decks</li>
                <li><span class="feat-icon" aria-hidden="true">&#9733;</span> Save favorites to your library</li>
                <li><span class="feat-icon" aria-hidden="true">&#9650;</span> Watch your progress climb</li>
            </ul>
        </aside>
    </main>

    <script src="js/theme-toggle.js"></script>

</body>
</html>
