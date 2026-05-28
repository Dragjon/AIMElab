<?php
// ============================================================
// includes/db.php  — DB connection + auth helpers
// ============================================================

define('DB_HOST', 'localhost');
define('DB_NAME', '<YOUR DB NAME>');
define('DB_USER', 'YOUR DB USER');   // ← set your MySQL user
define('DB_PASS', 'YOUR DB USER OASS!0'); // ← set your MySQL password
define('DB_CHARSET', 'utf8mb4');

define('SESSION_COOKIE', 'aimelab_sess');
define('SESSION_DAYS',   30);

function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    $dsn = 'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset='.DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
    return $pdo;
}

// ---- Session helpers ----

function generate_token(): string {
    return bin2hex(random_bytes(32));
}

function create_session(int $user_id): string {
    $token   = generate_token();
    $expires = date('Y-m-d H:i:s', time() + SESSION_DAYS * 86400);
    db()->prepare('INSERT INTO user_sessions (token,user_id,expires_at) VALUES(?,?,?)')
        ->execute([$token, $user_id, $expires]);
    // Update last_login
    db()->prepare('UPDATE users SET last_login=NOW() WHERE id=?')->execute([$user_id]);
    setcookie(SESSION_COOKIE, $token, [
        'expires'  => time() + SESSION_DAYS * 86400,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    return $token;
}

function destroy_session(): void {
    $token = $_COOKIE[SESSION_COOKIE] ?? '';
    if ($token) {
        db()->prepare('DELETE FROM user_sessions WHERE token=?')->execute([$token]);
        setcookie(SESSION_COOKIE, '', ['expires' => time()-1, 'path' => '/']);
    }
}

function current_user(): ?array {
    static $cache = false;
    if ($cache !== false) return $cache;
    $token = $_COOKIE[SESSION_COOKIE] ?? '';
    if (!$token) { $cache = null; return null; }
    $stmt = db()->prepare(
        'SELECT u.* FROM users u
         JOIN user_sessions s ON s.user_id = u.id
         WHERE s.token=? AND s.expires_at > NOW()
         LIMIT 1'
    );
    $stmt->execute([$token]);
    $cache = $stmt->fetch() ?: null;
    return $cache;
}

function require_auth(): array {
    $user = current_user();
    if (!$user) {
        // API calls
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(['error' => 'Unauthenticated']);
            exit;
        }
        header('Location: index.php?auth=login');
        exit;
    }
    return $user;
}

function require_admin(): array {
    $user = require_auth();
    if ($user['role'] !== 'admin') {
        header('Location: index.php');
        exit;
    }
    return $user;
}

// ---- Settings helpers ----

function get_settings(int $user_id): array {
    $defaults = [
        'theme' => 'dark', 'font_size' => 'medium',
        'autoadvance' => 0, 'showid' => 1, 'confirm_skip' => 0,
        'hcontrast' => 0, 'reducemotion' => 0, 'largetargets' => 0,
    ];
    $stmt = db()->prepare('SELECT * FROM user_settings WHERE user_id=?');
    $stmt->execute([$user_id]);
    $row = $stmt->fetch();
    if (!$row) return $defaults;
    unset($row['user_id'], $row['updated_at']);
    return array_merge($defaults, $row);
}

function save_settings(int $user_id, array $s): void {
    db()->prepare(
        'INSERT INTO user_settings
            (user_id,theme,font_size,autoadvance,showid,confirm_skip,hcontrast,reducemotion,largetargets)
         VALUES (?,?,?,?,?,?,?,?,?)
         ON DUPLICATE KEY UPDATE
            theme=VALUES(theme), font_size=VALUES(font_size),
            autoadvance=VALUES(autoadvance), showid=VALUES(showid),
            confirm_skip=VALUES(confirm_skip), hcontrast=VALUES(hcontrast),
            reducemotion=VALUES(reducemotion), largetargets=VALUES(largetargets)'
    )->execute([
        $user_id,
        $s['theme']         ?? 'dark',
        $s['font_size']     ?? 'medium',
        (int)($s['autoadvance']  ?? 0),
        (int)($s['showid']       ?? 1),
        (int)($s['confirm_skip'] ?? 0),
        (int)($s['hcontrast']    ?? 0),
        (int)($s['reducemotion'] ?? 0),
        (int)($s['largetargets'] ?? 0),
    ]);
}

function json_response(mixed $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}