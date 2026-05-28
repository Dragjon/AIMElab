<?php
// ============================================================
// api/api.php  — JSON REST-ish endpoint
// ============================================================
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../includes/db.php';

$action = $_GET['action'] ?? '';

// ---- Public actions ----
if ($action === 'register') { handle_register(); exit; }
if ($action === 'login')    { handle_login();    exit; }
if ($action === 'logout')   { handle_logout();   exit; }

// ---- Authenticated actions ----
$user = current_user();
if (!$user) { json_response(['error' => 'Unauthenticated'], 401); }

switch ($action) {
    // Settings
    case 'get_settings':   handle_get_settings($user);   break;
    case 'save_settings':  handle_save_settings($user);  break;

    // Attempts
    case 'get_attempts':   handle_get_attempts($user);   break;
    case 'add_attempt':    handle_add_attempt($user);    break;
    case 'delete_attempt': handle_delete_attempt($user); break;
    case 'clear_attempts': handle_clear_attempts($user); break;

    // Tests
    case 'get_tests':  handle_get_tests($user);  break;
    case 'add_test':   handle_add_test($user);   break;

    // Profile
    case 'get_profile':    handle_get_profile($user);    break;
    case 'save_profile':   handle_save_profile($user);   break;
    case 'upload_avatar':  handle_upload_avatar($user);  break;
    case 'delete_avatar':  handle_delete_avatar($user);  break;
    case 'change_password':handle_change_password($user);break;

    // Admin
    case 'admin_users':        require_admin(); handle_admin_users();        break;
    case 'admin_delete_user':  require_admin(); handle_admin_delete_user();  break;
    case 'admin_set_role':     require_admin(); handle_admin_set_role();     break;
    case 'admin_stats':        require_admin(); handle_admin_stats();        break;

    default: json_response(['error' => 'Unknown action'], 400);
}

// ============================================================
// AUTH
// ============================================================
function handle_register(): void {
    $body     = json_decode(file_get_contents('php://input'), true) ?? [];
    $username = trim($body['username'] ?? '');
    $email    = trim($body['email']    ?? '');
    $password =      $body['password'] ?? '';

    $errors = [];
    if (!preg_match('/^[a-zA-Z0-9_]{3,32}$/', $username))
        $errors[] = 'Username must be 3–32 characters (letters, numbers, underscores).';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        $errors[] = 'Invalid email address.';
    if (strlen($password) < 8)
        $errors[] = 'Password must be at least 8 characters.';

    if ($errors) { json_response(['errors' => $errors], 422); }

    // Uniqueness check
    $stmt = db()->prepare('SELECT id FROM users WHERE username=? OR email=? LIMIT 1');
    $stmt->execute([$username, $email]);
    if ($stmt->fetch()) {
        json_response(['errors' => ['Username or email already taken.']], 409);
    }

    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    db()->prepare('INSERT INTO users (username,email,password,display_name) VALUES(?,?,?,?)')
        ->execute([$username, $email, $hash, $username]);
    $user_id = (int)db()->lastInsertId();

    // Init default settings
    save_settings($user_id, []);

    $token = create_session($user_id);
    $user  = db()->prepare('SELECT * FROM users WHERE id=?');
    $user->execute([$user_id]);
    $u = $user->fetch();
    json_response(['ok' => true, 'user' => safe_user($u)]);
}

function handle_login(): void {
    $body     = json_decode(file_get_contents('php://input'), true) ?? [];
    $login    = trim($body['login']    ?? '');
    $password =      $body['password'] ?? '';

    $stmt = db()->prepare('SELECT * FROM users WHERE username=? OR email=? LIMIT 1');
    $stmt->execute([$login, $login]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        json_response(['errors' => ['Invalid username/email or password.']], 401);
    }

    create_session((int)$user['id']);
    json_response(['ok' => true, 'user' => safe_user($user)]);
}

function handle_logout(): void {
    destroy_session();
    json_response(['ok' => true]);
}

// ============================================================
// SETTINGS
// ============================================================
function handle_get_settings(array $user): void {
    json_response(get_settings((int)$user['id']));
}

function handle_save_settings(array $user): void {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    save_settings((int)$user['id'], $body);
    json_response(['ok' => true]);
}

// ============================================================
// ATTEMPTS
// ============================================================
function handle_get_attempts(array $user): void {
    $stmt = db()->prepare(
        'SELECT problem_id, correct, user_answer, correct_answer, attempted_at AS `date`
         FROM attempts WHERE user_id=? ORDER BY attempted_at ASC'
    );
    $stmt->execute([(int)$user['id']]);
    $rows = $stmt->fetchAll();

    // Group by problem_id → same structure as original JS: { [id]: [{...}] }
    $out = [];
    foreach ($rows as $r) {
        $id = $r['problem_id'];
        if (!isset($out[$id])) $out[$id] = [];
        $out[$id][] = [
            'correct'       => (bool)$r['correct'],
            'userAnswer'    => $r['user_answer'],
            'correctAnswer' => $r['correct_answer'],
            'date'          => $r['date'],
        ];
    }
    json_response($out);
}

function handle_add_attempt(array $user): void {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $pid  = trim($body['problem_id']     ?? '');
    $corr = (bool)($body['correct']      ?? false);
    $ua   = $body['userAnswer']          ?? null;
    $ca   = $body['correctAnswer']       ?? null;

    if (!$pid) { json_response(['error' => 'Missing problem_id'], 400); }

    db()->prepare(
        'INSERT INTO attempts (user_id,problem_id,correct,user_answer,correct_answer) VALUES(?,?,?,?,?)'
    )->execute([(int)$user['id'], $pid, (int)$corr, $ua, $ca]);

    json_response(['ok' => true, 'id' => (int)db()->lastInsertId()]);
}

function handle_delete_attempt(array $user): void {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $pid  = trim($body['problem_id'] ?? '');
    $date = trim($body['date']       ?? '');
    if (!$pid || !$date) { json_response(['error' => 'Missing params'], 400); }

    db()->prepare(
        'DELETE FROM attempts WHERE user_id=? AND problem_id=? AND attempted_at=? LIMIT 1'
    )->execute([(int)$user['id'], $pid, $date]);

    json_response(['ok' => true]);
}

function handle_clear_attempts(array $user): void {
    db()->prepare('DELETE FROM attempts WHERE user_id=?')->execute([(int)$user['id']]);
    db()->prepare('DELETE FROM tests    WHERE user_id=?')->execute([(int)$user['id']]);
    json_response(['ok' => true]);
}

// ============================================================
// TESTS
// ============================================================
function handle_get_tests(array $user): void {
    $stmt = db()->prepare(
        'SELECT score,total,results_json,taken_at AS `date` FROM tests WHERE user_id=? ORDER BY taken_at ASC'
    );
    $stmt->execute([(int)$user['id']]);
    $rows = $stmt->fetchAll();
    $out = array_map(function($r) {
        return [
            'score'   => (int)$r['score'],
            'total'   => (int)$r['total'],
            'results' => json_decode($r['results_json'], true),
            'date'    => $r['date'],
        ];
    }, $rows);
    json_response($out);
}

function handle_add_test(array $user): void {
    $body    = json_decode(file_get_contents('php://input'), true) ?? [];
    $score   = (int)($body['score']   ?? 0);
    $total   = (int)($body['total']   ?? 0);
    $results = $body['results']        ?? [];

    db()->prepare('INSERT INTO tests (user_id,score,total,results_json) VALUES(?,?,?,?)')
        ->execute([(int)$user['id'], $score, $total, json_encode($results)]);
    json_response(['ok' => true]);
}

// ============================================================
// PROFILE
// ============================================================
function handle_get_profile(array $user): void {
    json_response(safe_user($user));
}

function handle_save_profile(array $user): void {
    $body          = json_decode(file_get_contents('php://input'), true) ?? [];
    $display_name  = substr(trim($body['display_name']  ?? ''), 0, 64);
    $bio           = substr(trim($body['bio']           ?? ''), 0, 500);
    $avatar_color  = $body['avatar_color']  ?? '#6c8ef5';
    $avatar_emoji  = mb_substr(trim($body['avatar_emoji']  ?? '∑'), 0, 2);
    $banner_style  = in_array($body['banner_style'] ?? '', ['gradient','solid','mesh','wave','dots','none'])
                     ? $body['banner_style'] : 'gradient';
    $banner_color1 = $body['banner_color1'] ?? '#6c8ef5';
    $banner_color2 = $body['banner_color2'] ?? '#a78bfa';
    $banner_angle  = max(0, min(360, (int)($body['banner_angle'] ?? 135)));

    foreach ([$avatar_color, $banner_color1, $banner_color2] as &$c)
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $c)) $c = '#6c8ef5';

    db()->prepare(
        'UPDATE users SET display_name=?,bio=?,avatar_color=?,avatar_emoji=?,
         banner_style=?,banner_color1=?,banner_color2=?,banner_angle=? WHERE id=?'
    )->execute([$display_name, $bio, $avatar_color, $avatar_emoji,
                $banner_style, $banner_color1, $banner_color2, $banner_angle,
                (int)$user['id']]);

    json_response(['ok' => true]);
}

function handle_upload_avatar(array $user): void {
    // Accepts multipart/form-data with field "avatar"
    if (empty($_FILES['avatar'])) {
        json_response(['error' => 'No file uploaded'], 400);
    }
    $file = $_FILES['avatar'];
    $maxSize = 2 * 1024 * 1024; // 2 MB
    if ($file['size'] > $maxSize) json_response(['error' => 'File too large (max 2 MB)'], 422);

    $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
    $mime    = mime_content_type($file['tmp_name']);
    if (!in_array($mime, $allowed)) json_response(['error' => 'Invalid file type'], 422);

    $ext     = ['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif','image/webp'=>'webp'][$mime];
    $uid     = (int)$user['id'];
    $dir     = __DIR__ . '/../uploads/avatars/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    // Delete old avatar file if exists
    $old = db()->prepare('SELECT avatar_url FROM users WHERE id=?');
    $old->execute([$uid]);
    $oldUrl = $old->fetchColumn();
    if ($oldUrl) {
        $oldFile = __DIR__ . '/../' . ltrim($oldUrl, '/');
        if (file_exists($oldFile)) @unlink($oldFile);
    }

    $filename = 'avatar_' . $uid . '_' . time() . '.' . $ext;
    $dest     = $dir . $filename;
    if (!move_uploaded_file($file['tmp_name'], $dest))
        json_response(['error' => 'Upload failed'], 500);

    $url = 'uploads/avatars/' . $filename;
    db()->prepare('UPDATE users SET avatar_url=? WHERE id=?')->execute([$url, $uid]);
    json_response(['ok' => true, 'url' => $url]);
}

function handle_delete_avatar(array $user): void {
    $uid = (int)$user['id'];
    $stmt = db()->prepare('SELECT avatar_url FROM users WHERE id=?');
    $stmt->execute([$uid]);
    $url = $stmt->fetchColumn();
    if ($url) {
        $file = __DIR__ . '/../' . ltrim($url, '/');
        if (file_exists($file)) @unlink($file);
        db()->prepare('UPDATE users SET avatar_url=NULL WHERE id=?')->execute([$uid]);
    }
    json_response(['ok' => true]);
}

function handle_change_password(array $user): void {
    $body     = json_decode(file_get_contents('php://input'), true) ?? [];
    $current  = $body['current_password'] ?? '';
    $newpass  = $body['new_password']     ?? '';

    // Re-fetch user to get fresh hash
    $stmt = db()->prepare('SELECT password FROM users WHERE id=?');
    $stmt->execute([(int)$user['id']]);
    $row = $stmt->fetch();

    if (!password_verify($current, $row['password'])) {
        json_response(['errors' => ['Current password is incorrect.']], 401);
    }
    if (strlen($newpass) < 8) {
        json_response(['errors' => ['New password must be at least 8 characters.']], 422);
    }

    $hash = password_hash($newpass, PASSWORD_BCRYPT, ['cost' => 12]);
    db()->prepare('UPDATE users SET password=? WHERE id=?')->execute([$hash, (int)$user['id']]);
    json_response(['ok' => true]);
}

// ============================================================
// ADMIN
// ============================================================
function handle_admin_users(): void {
    $stmt = db()->query(
        'SELECT u.id, u.username, u.email, u.display_name, u.role, u.created_at, u.last_login,
                COUNT(DISTINCT a.id) AS attempt_count
         FROM users u
         LEFT JOIN attempts a ON a.user_id = u.id
         GROUP BY u.id
         ORDER BY u.created_at DESC'
    );
    json_response($stmt->fetchAll());
}

function handle_admin_delete_user(): void {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $id   = (int)($body['user_id'] ?? 0);
    if (!$id) { json_response(['error' => 'Missing user_id'], 400); }
    db()->prepare('DELETE FROM users WHERE id=? AND role != "admin"')->execute([$id]);
    json_response(['ok' => true]);
}

function handle_admin_set_role(): void {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $id   = (int)($body['user_id'] ?? 0);
    $role = $body['role'] === 'admin' ? 'admin' : 'user';
    if (!$id) { json_response(['error' => 'Missing user_id'], 400); }
    db()->prepare('UPDATE users SET role=? WHERE id=?')->execute([$role, $id]);
    json_response(['ok' => true]);
}

function handle_admin_stats(): void {
    $stats = [];
    $stats['total_users']    = (int)db()->query('SELECT COUNT(*) FROM users')->fetchColumn();
    $stats['total_attempts'] = (int)db()->query('SELECT COUNT(*) FROM attempts')->fetchColumn();
    $stats['total_tests']    = (int)db()->query('SELECT COUNT(*) FROM tests')->fetchColumn();
    $stats['users_today']    = (int)db()->query(
        'SELECT COUNT(*) FROM users WHERE DATE(created_at)=CURDATE()'
    )->fetchColumn();
    $stats['active_today']   = (int)db()->query(
        'SELECT COUNT(DISTINCT user_id) FROM attempts WHERE DATE(attempted_at)=CURDATE()'
    )->fetchColumn();
    json_response($stats);
}

// ============================================================
// HELPERS
// ============================================================
function safe_user(array $u): array {
    return [
        'id'            => (int)$u['id'],
        'username'      => $u['username'],
        'email'         => $u['email'],
        'display_name'  => $u['display_name'],
        'bio'           => $u['bio'] ?? '',
        'avatar_color'  => $u['avatar_color'],
        'avatar_emoji'  => $u['avatar_emoji'],
        'avatar_url'    => $u['avatar_url'] ?? null,
        'banner_style'  => $u['banner_style']  ?? 'gradient',
        'banner_color1' => $u['banner_color1'] ?? '#6c8ef5',
        'banner_color2' => $u['banner_color2'] ?? '#a78bfa',
        'banner_angle'  => (int)($u['banner_angle'] ?? 135),
        'role'          => $u['role'],
        'created_at'    => $u['created_at'],
        'last_login'    => $u['last_login'],
    ];
}