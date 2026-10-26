<?php
/* =========================================================
   StudyBuddy — shared session / auth helpers
   Include with: require_once __DIR__ . '/auth.php';
   Must be included BEFORE any output (it starts the session
   and may send redirect headers).
   ========================================================= */

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/** HTML-escape a value for safe output. */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** The logged-in user (id, first_name, last_name, username, created_at) or null. */
function current_user(): ?array
{
    return isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : null;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

/** Store a user row in the session (call after login / profile edits). */
function set_session_user(array $row): void
{
    $_SESSION['user'] = [
        'id'         => (int) $row['id'],
        'first_name' => (string) $row['first_name'],
        'last_name'  => (string) $row['last_name'],
        'username'   => (string) $row['username'],
        'avatar'     => isset($row['avatar']) ? (string) $row['avatar'] : '',
        'created_at' => (string) ($row['created_at'] ?? ''),
    ];
}

/** The user's uploaded avatar path, or null to fall back to initials. */
function avatar_src(?array $user): ?string
{
    if (!$user) {
        return null;
    }
    $a = trim((string) ($user['avatar'] ?? ''));
    return $a !== '' ? $a : null;
}

/**
 * Only allow redirect targets inside this site: a relative path or
 * a local filename. Rejects absolute URLs, "//host", and schemes.
 */
function safe_next(?string $next, string $default = 'explore.php'): string
{
    $next = trim((string) $next);
    if ($next === '' || strlen($next) > 500) {
        return $default;
    }
    if (preg_match('#^(?:[a-z][a-z0-9+.\-]*:|//|\\\\)#i', $next)) {
        return $default;
    }
    if (preg_match('/[\r\n\x00]/', $next)) {
        return $default;
    }
    if (str_contains($next, '\\')) {
        return $default;
    }
    return $next;
}

/** Redirect guests to the login page, remembering where they wanted to go. */
function require_login(): void
{
    if (is_logged_in()) {
        return;
    }
    // GET requests come back to the same URL; POST endpoints fall back to their form page.
    $next = $_SERVER['REQUEST_METHOD'] === 'GET' ? ($_SERVER['REQUEST_URI'] ?? '') : '';
    header('Location: login.php' . ($next !== '' ? '?next=' . urlencode($next) : ''));
    exit;
}

/** For non-HTML / background requests: reply 401 instead of redirecting. */
function require_login_api(): void
{
    if (is_logged_in()) {
        return;
    }
    http_response_code(401);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Login required.';
    exit;
}

/* ---------------- CSRF protection ---------------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Hidden input to put inside every POST form. */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/** Stop the request if the POSTed token does not match the session token. */
function verify_csrf(): void
{
    $sent = $_POST['csrf_token'] ?? '';
    if (!is_string($sent) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $sent)) {
        http_response_code(400);
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'Your session expired or the form is invalid. Please go back, refresh the page and try again.';
        exit;
    }
}

/** Two-letter initials for the avatar, e.g. "Jane Doe" -> "JD". */
function user_initials(?array $user): string
{
    if (!$user) {
        return '?';
    }
    $first = mb_substr(trim($user['first_name'] ?? ''), 0, 1, 'UTF-8');
    $last  = mb_substr(trim($user['last_name'] ?? ''), 0, 1, 'UTF-8');
    $initials = mb_strtoupper($first . $last, 'UTF-8');
    if ($initials === '') {
        $initials = mb_strtoupper(mb_substr($user['username'] ?? '?', 0, 1, 'UTF-8'), 'UTF-8');
    }
    return $initials;
}


/* ---------------- Deck ownership (Tier 2) ---------------- */

/**
 * Return the deck row if it exists AND belongs to the logged-in user, else null.
 * Unowned (user_id NULL) decks never match, so nobody can edit them.
 */
function fetch_owned_deck(mysqli $conn, int $deckId): ?array
{
    $user = current_user();
    if (!$user || $deckId <= 0) {
        return null;
    }
    $uid  = (int) $user['id'];
    $stmt = $conn->prepare('SELECT * FROM deck WHERE id = ? AND user_id = ?');
    $stmt->bind_param('ii', $deckId, $uid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

/** True when the given deck row belongs to the logged-in user. */
function owns_deck(?array $deck): bool
{
    $user = current_user();
    return $user && $deck && isset($deck['user_id']) && (int) $deck['user_id'] === (int) $user['id'];
}
