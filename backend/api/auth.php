<?php
declare(strict_types=1);

require_once '../config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $admin = session_admin();
    if ($admin === null) {
        json_response([
            'authenticated' => false,
            'admin' => null,
            'csrf_token' => csrf_token(),
        ], 200);
    }
    json_response([
        'authenticated' => true,
        'admin' => $admin,
        'user' => $admin,
        'csrf_token' => csrf_token(),
        'expires_in' => SESSION_TIMEOUT_SECONDS,
    ]);
}

if ($method === 'DELETE') {
    require_auth();
    require_csrf();
    destroy_admin_session();
    json_response(['success' => true, 'message' => 'Đã đăng xuất an toàn.']);
}

if ($method !== 'POST') {
    json_response(['message' => 'Method not allowed.'], 405);
}

// Session-based Rate Limiting against Bruteforce
$attempts = (int) ($_SESSION['login_attempts'] ?? 0);
$lockout_time = (int) ($_SESSION['login_lockout'] ?? 0);

if ($lockout_time > time()) {
    $wait = $lockout_time - time();
    json_response(['success' => false, 'message' => "Quá nhiều lần đăng nhập sai. Vui lòng thử lại sau {$wait} giây."], 429);
}

$data = request_data();
$email = strtolower(trim((string) ($data['email'] ?? $data['username'] ?? '')));
$password = (string) ($data['password'] ?? '');

if ($email === '' || $password === '') {
    json_response(['success' => false, 'message' => 'Email và mật khẩu là bắt buộc.'], 400);
}

if (!$conn instanceof PDO) {
    json_response(['success' => false, 'message' => 'Không thể kết nối cơ sở dữ liệu.'], 500);
}

$stmt = $conn->prepare('SELECT * FROM admin WHERE email = :email LIMIT 1');
$stmt->execute([':email' => $email]);
$admin = $stmt->fetch();

if (!$admin) {
    $_SESSION['login_attempts'] = $attempts + 1;
    if ($_SESSION['login_attempts'] >= 5) {
        $_SESSION['login_lockout'] = time() + 180; // 3 minutes lockout
    }
    // Mitigate timing attack
    password_verify($password, '$2y$10$abcdefghijklmnopqrstuvwxyze01234567890123456789012345678');
    json_response(['success' => false, 'message' => 'Thông tin đăng nhập không chính xác.'], 401);
}

if (($admin['status'] ?? '') !== 'active') {
    json_response(['success' => false, 'message' => 'Tài khoản đang bị tạm khóa.'], 403);
}

if (!empty($admin['locked_until']) && strtotime((string) $admin['locked_until']) > time()) {
    $remaining = max(1, (int) ceil((strtotime((string) $admin['locked_until']) - time()) / 60));
    json_response(['success' => false, 'message' => "Tài khoản bị khóa tạm thời. Thử lại sau {$remaining} phút."], 423);
}

if (!password_verify($password, (string) $admin['password_hash'])) {
    $_SESSION['login_attempts'] = $attempts + 1;
    if ($_SESSION['login_attempts'] >= 5) {
        $_SESSION['login_lockout'] = time() + 180;
    }

    $db_attempts = ((int) ($admin['failed_login_attempts'] ?? 0)) + 1;
    $lockedUntil = null;

    if ($db_attempts >= LOGIN_LOCK_ATTEMPTS) {
        $lockedUntil = (new DateTimeImmutable('now'))->modify('+' . LOGIN_LOCK_MINUTES . ' minutes')->format('Y-m-d H:i:s');
    }

    $update = $conn->prepare('UPDATE admin SET failed_login_attempts = :attempts, locked_until = :locked_until WHERE id = :id');
    $update->execute([':attempts' => $db_attempts, ':locked_until' => $lockedUntil, ':id' => $admin['id']]);

    if ($lockedUntil !== null) {
        json_response(['success' => false, 'message' => 'Sai mật khẩu quá 5 lần. Tài khoản bị tạm khóa 15 phút.'], 423);
    }

    json_response(['success' => false, 'message' => 'Thông tin đăng nhập không chính xác.'], 401);
}

// ─── LOGIN SUCCESS ───
unset($_SESSION['login_attempts']);
unset($_SESSION['login_lockout']);

$update = $conn->prepare('UPDATE admin SET failed_login_attempts = 0, locked_until = NULL, last_login_at = NOW() WHERE id = :id');
$update->execute([':id' => $admin['id']]);

session_regenerate_id(true);
$_SESSION['admin_id'] = (int) $admin['id'];
$_SESSION['admin_fullname'] = (string) $admin['fullname'];
$_SESSION['admin_email'] = (string) $admin['email'];
$_SESSION['admin_role'] = (string) $admin['role'];
$_SESSION['last_activity'] = time();
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

json_response([
    'success' => true,
    'message' => 'Đăng nhập thành công.',
    'user' => session_admin(),
    'admin' => session_admin(),
    'csrf_token' => csrf_token(),
    'expires_in' => SESSION_TIMEOUT_SECONDS,
]);
