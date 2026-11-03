<?php

const REMEMBER_COOKIE = "remember_me";
const REMEMBER_DAYS = 30;

// The page may not have loaded the config yet (auth.php runs first), so this makes its own connection when needed
function rememberMeConnection()
{
    if (isset($GLOBALS['conn']) && $GLOBALS['conn']) {
        return $GLOBALS['conn'];
    }

    $conn = null;
    require __DIR__ . "/../../config/config.php";

    return $conn ?: null;
}

function rememberMeCookieSet(string $value, int $expires): void
{
    setcookie(REMEMBER_COOKIE, $value, [
        'expires'  => $expires,
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,      // JavaScript can never read it
        'samesite' => 'Lax',
    ]);
}

// Called at login when "Keep me signed in" is ticked
function rememberMeIssue($conn, int $userId): void
{
    $selector = bin2hex(random_bytes(12));
    $validator = bin2hex(random_bytes(32));
    $expires = time() + REMEMBER_DAYS * 86400;
    $hash = hash('sha256', $validator); // only the hash is stored
    $agent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);

    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO remember_tokens (user_id, selector, validator_hash, expires_at, user_agent)
         VALUES (?, ?, ?, FROM_UNIXTIME(?), ?)"
    );
    mysqli_stmt_bind_param($stmt, "issis", $userId, $selector, $hash, $expires, $agent);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    rememberMeCookieSet($selector . ':' . $validator, $expires);
}

// Called by auth.php when there is no session: logs the user back in from the cookie, if it is valid
function rememberMeRestore(): void
{
    $cookie = $_COOKIE[REMEMBER_COOKIE] ?? '';

    if ($cookie === '') {
        return;
    }

    if (!preg_match('/^([a-f0-9]{24}):([a-f0-9]{64})$/', $cookie, $m)) {
        rememberMeCookieSet('', time() - 3600);
        return;
    }

    try {

        $conn = rememberMeConnection();

        if (!$conn) {
            return;
        }

        $stmt = mysqli_prepare(
            $conn,
            "SELECT token_id, user_id, validator_hash FROM remember_tokens WHERE selector = ? AND expires_at > NOW() LIMIT 1"
        );
        mysqli_stmt_bind_param($stmt, "s", $m[1]);
        mysqli_stmt_execute($stmt);
        $token = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if (!$token) {
            rememberMeCookieSet('', time() - 3600);
            return;
        }

        if (!hash_equals($token['validator_hash'], hash('sha256', $m[2]))) {
            // Right selector, wrong secret: the cookie may have been copied. End every remembered login for that user.
            rememberMeForgetAll($conn, (int) $token['user_id']);
            rememberMeCookieSet('', time() - 3600);
            return;
        }

        $stmt = mysqli_prepare(
            $conn,
            "SELECT user_id, first_name, last_name, email, designation_id, status FROM users WHERE user_id = ? LIMIT 1"
        );
        mysqli_stmt_bind_param($stmt, "i", $token['user_id']);
        mysqli_stmt_execute($stmt);
        $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        // Deactivated or pending accounts can't come back in through the cookie
        if (!$user || in_array($user['status'], ['Pending', 'Inactive'], true)) {
            rememberMeForgetAll($conn, (int) $token['user_id']);
            rememberMeCookieSet('', time() - 3600);
            return;
        }

        session_regenerate_id(true);

        $_SESSION["user_id"] = $user["user_id"];
        $_SESSION["firstname"] = $user["first_name"];
        $_SESSION["lastname"] = $user["last_name"];
        $_SESSION["email"] = $user["email"];
        $_SESSION["designation_id"] = $user["designation_id"];

        // Another 30 days from this visit
        $expires = time() + REMEMBER_DAYS * 86400;
        $stmt = mysqli_prepare(
            $conn,
            "UPDATE remember_tokens SET last_used_at = NOW(), expires_at = FROM_UNIXTIME(?) WHERE token_id = ?"
        );
        mysqli_stmt_bind_param($stmt, "ii", $expires, $token['token_id']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        rememberMeCookieSet($cookie, $expires);

        $stmt = mysqli_prepare($conn, "UPDATE users SET last_login = NOW() WHERE user_id = ?");
        mysqli_stmt_bind_param($stmt, "i", $user["user_id"]);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    } catch (Throwable $e) {
        error_log("Remember-me restore failed: " . $e->getMessage());
    }
}

// Logout on this device
function rememberMeForget($conn): void
{
    $cookie = $_COOKIE[REMEMBER_COOKIE] ?? '';

    if ($conn && preg_match('/^([a-f0-9]{24}):/', $cookie, $m)) {
        $stmt = mysqli_prepare($conn, "DELETE FROM remember_tokens WHERE selector = ?");
        mysqli_stmt_bind_param($stmt, "s", $m[1]);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    rememberMeCookieSet('', time() - 3600);
}

// Every device of one user (use it when a password is changed or an account is deactivated)
function rememberMeForgetAll($conn, int $userId): void
{
    $stmt = mysqli_prepare($conn, "DELETE FROM remember_tokens WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

// Where each role lands after logging in (the same pages as login_process.php)
function landingPageFor($role): ?string
{
    return [
        1 => "./pages-learner/dashboard.php",
        2 => "./pages-manager/courses.php",
        3 => "./pages-admin/courses.php",
        4 => "./pages-superadmin/courses.php",
    ][(int) $role] ?? null;
}