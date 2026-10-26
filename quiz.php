<?php
require_once __DIR__ . '/auth.php';
require 'db.php';

// Which deck the quiz is for (used later when the quiz is built).
$id    = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$title = '';

if ($id > 0) {
    try {
        $stmt = $conn->prepare("SELECT title FROM deck WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $title = $row['title'];
        }
    } catch (mysqli_sql_exception $e) {
        // ignore — this is just a placeholder page for now
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudyBuddy — Quiz</title>
    <script>
      (function () {
        try {
          if (localStorage.getItem("studybuddy-theme") === "light") {
            document.documentElement.setAttribute("data-theme", "light");
          }
        } catch (e) {}
      })();
    </script>
    <link rel="stylesheet" href="css/explore.css?v=4">
    <link rel="stylesheet" href="css/theme.css?v=7">
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

    <main class="placeholder">
        <h1 class="placeholder-title">Quiz<?= $title !== '' ? ': ' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') : '' ?></h1>
        <p class="placeholder-text">This quiz is coming soon. We'll build the quiz experience here later.</p>
        <span class="placeholder-badge">Under construction</span>
    </main>

    <script src="js/theme-toggle.js"></script>
    <script src="js/user-menu.js"></script>
</body>
</html>
