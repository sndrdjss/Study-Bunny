<?php
//login database po ituh
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $pass     = $_POST['password'] ?? '';

    $stmt = $conn->prepare('SELECT password FROM study_buddy_auth WHERE username = ?');
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        if ($pass === $row['password']) {
            // Success → go to the create page.
            header('Location: create.php');
            exit;
        }
    }

    // Wrong username/password → back to this login page with an error flag.
    $stmt->close();
    $conn->close();
    header('Location: login.php?error=1');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudyBuddy — Login</title>
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
    <link rel="stylesheet" href="css/login.css?v=3">
    <link rel="stylesheet" href="css/theme.css?v=5">
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

    <!-- ============================ LOGIN FORM ============================ -->
    <main class="auth-wrap">
        <div class="auth-left">
        <section class="auth-card">
            <h1 class="auth-title">LOGIN</h1>
            <p class="auth-subtitle">Welcome back — sign in to continue learning.</p>

            <!-- Styled, themed status banner (replaces inline document.write) -->
            <?php if (isset($_GET['registered']) && $_GET['registered'] === '1'): ?>
                <div class="auth-alert auth-alert--success" role="status">
                    <span class="alert-icon" aria-hidden="true">&#10003;</span>
                    <span>Account created! Please log in.</span>
                </div>
            <?php elseif (isset($_GET['error']) && $_GET['error'] === '1'): ?>
                <div class="auth-alert auth-alert--error" role="alert">
                    <span class="alert-icon" aria-hidden="true">&#9888;</span>
                    <span>Invalid username or password.</span>
                </div>
            <?php endif; ?>

            <form class="auth-form" action="login.php" method="post">
                <div class="field">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="Enter your username" autocomplete="username">
                </div>

                <div class="field">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter your password" autocomplete="current-password">
                </div>

                <button type="submit" class="auth-submit">LOGIN</button>
            </form>

            <p class="auth-footer">Don't have an account? <a href="register.php">Register</a></p>
        </section>
        </div>

        <!-- Right-side branded showcase panel -->
        <aside class="auth-side">
            <span class="showcase-eyebrow">StudyBuddy</span>
            <h2 class="showcase-heading">Learn faster with <span class="accent">smarter decks</span></h2>
            <p class="showcase-text">Pick up where you left off. Review peer-crafted decks, track your streaks, and master any subject with spaced repetition.</p>
            <ul class="showcase-features">
                <li><span class="feat-icon" aria-hidden="true">&#9733;</span> Verified, peer-crafted study decks</li>
                <li><span class="feat-icon" aria-hidden="true">&#8635;</span> Spaced-repetition quizzes that stick</li>
                <li><span class="feat-icon" aria-hidden="true">&#9650;</span> Track progress across every subject</li>
            </ul>
        </aside>
    </main>

    <script src="js/theme-toggle.js"></script>

</body>
</html>
