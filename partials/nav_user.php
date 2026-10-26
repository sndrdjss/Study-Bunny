<?php
/* =========================================================
   Navbar account area. Requires auth.php to be loaded first.
   Logged in -> avatar + first name button with dropdown menu.
   Guest     -> "Log in" / "Sign up" buttons.
   ========================================================= */
$navUser = current_user();
if ($navUser !== null):
    $navFullName = trim($navUser['first_name'] . ' ' . $navUser['last_name']);
    if ($navFullName === '') {
        $navFullName = $navUser['username'];
    }
    $navFirst = $navUser['first_name'] !== '' ? $navUser['first_name'] : $navUser['username'];
?>
<div class="user-menu">
    <button type="button" class="user-menu__btn" aria-haspopup="menu" aria-expanded="false" aria-controls="user-menu-list" aria-label="Account menu for <?= e($navFullName) ?>">
        <?php $navAvatar = avatar_src($navUser); ?>
        <?php if ($navAvatar !== null): ?>
            <span class="avatar avatar--img" aria-hidden="true"><img src="<?= e($navAvatar) ?>" alt=""></span>
        <?php else: ?>
            <span class="avatar" aria-hidden="true"><?= e(user_initials($navUser)) ?></span>
        <?php endif; ?>
        <span class="username"><?= e($navFirst) ?></span>
        <svg class="user-menu__chevron" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" focusable="false"><path d="M6 9l6 6 6-6" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </button>
    <ul id="user-menu-list" class="user-menu__list" role="menu" hidden>
        <li role="none">
            <a role="menuitem" class="user-menu__item" href="profile.php">
                <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><circle cx="12" cy="8" r="4" fill="none" stroke="currentColor" stroke-width="2"/><path d="M4 21a8 8 0 0 1 16 0" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                Profile
            </a>
        </li>
        <li role="none">
            <form method="post" action="logout.php">
                <?= csrf_field() ?>
                <button role="menuitem" type="submit" class="user-menu__item user-menu__logout">
                    <svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3M10 17l-5-5 5-5M5 12h11" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Log out
                </button>
            </form>
        </li>
    </ul>
</div>
<?php else: ?>
<a class="nav-auth-btn nav-auth-btn--ghost" href="login.php">Log in</a>
<a class="nav-auth-btn" href="register.php">Sign up</a>
<?php endif; ?>
