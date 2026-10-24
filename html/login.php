<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudyBuddy — Login</title>
    <link rel="stylesheet" href="../css/login.css">
</head>
<body>
    //navigation bar
    <header class="navbar">
        <div class="nav-left">
            <a href="../explore.php" class="logo">Study<span>Buddy</span></a>
        </div>
    </header>
    
    //login form
    <main class="auth-wrap">
        <div class="auth-left">
        <section class="auth-card">
            <h1 class="auth-title">LOGIN</h1>
            <p class="auth-subtitle">Welcome back — sign in to continue learning.</p>

            <!-- Shows a message based on the ?error= / ?registered= flags -->
            <script>
                (function () {
                    var params = new URLSearchParams(window.location.search);
                    if (params.get('registered') === '1') {
                        document.write(
                            '<p style="color:#27ae60;font-weight:bold;">Account created! Please log in.</p>'
                        );
                    }
                    if (params.get('error') === '1') {
                        document.write(
                            '<p style="color:#c0392b;font-weight:bold;">Invalid username or password.</p>'
                        );
                    }
                })();
            </script>

            <form class="auth-form" action="../login.php" method="post">
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

        <!-- Right-side area (plain, reserved for future content) -->
        <aside class="auth-side" aria-hidden="true"></aside>
    </main>

</body>
</html>
