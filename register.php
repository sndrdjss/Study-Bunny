<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Read the posted fields (names match the form below).
    $lastName  = $_POST['last-name'];
    $firstName = $_POST['first-name'];
    $username  = $_POST['username'];
    $pass      = $_POST['password'];
    $confirm   = $_POST['confirm-password'];

    // Basic validation.
    //para sa fill up all fields kung kulang ang pagka input (fill in)
    if ($lastName === '' || $firstName === '' || $username === '' || $pass === '') {
        header('Location: register.php?error=empty');
        exit;
    }
    //para sa confirmation kung tugma yung nilagay sa password habang nagrereg (password)
    if ($pass !== $confirm) {
        header('Location: register.php?error=mismatch');
        exit;
    }
    //insert the values sa mysql
    try {
        $sql = "INSERT INTO study_buddy_auth (last_name, first_name, username, password)
              VALUES ('$lastName', '$firstName', '$username', '$pass')";
        $conn->query($sql);

        // Success → go to the login page.
        header('Location: login.php?registered=1');
        exit;
    } catch (mysqli_sql_exception $e) {
        if ($conn->errno === 1062) {
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
    <link rel="stylesheet" href="css/register.css">
</head>
<body>

    <!-- ============================ NAVIGATION BAR ============================ -->
    <header class="navbar">
        <div class="nav-left">
            <a href="html/explore.html" class="logo">Study<span>Buddy</span></a>
        </div>
    </header>

    <!-- ============================ REGISTER FORM ============================ -->
    <main class="auth-wrap">
        <div class="auth-left">
        <section class="auth-card">
            <h1 class="auth-title">SIGN UP</h1>
            <p class="auth-subtitle">Create your StudyBuddy account.</p>

            <!-- Shows a message based on the ?error= flag added above -->
            <script>
                (function () {
                    var params = new URLSearchParams(window.location.search);
                    var err = params.get('error');
                    var messages = {
                        empty: 'Please fill in all fields.',
                        mismatch: 'Passwords do not match.',
                        taken: 'That username is already taken.',
                        db: 'Something went wrong. Please try again.'
                    };
                    if (err && messages[err]) {
                        document.write(
                            '<p style="color:#c0392b;font-weight:bold;">' + messages[err] + '</p>'
                        );
                    }
                })();
            </script>

            <form class="auth-form" action="register.php" method="post">
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
                    <input type="password" id="password" name="password" placeholder="Create a password" autocomplete="new-password">
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

        <!-- Right-side area (plain, reserved for future content) -->
        <aside class="auth-side" aria-hidden="true"></aside>
    </main>

</body>
</html>
