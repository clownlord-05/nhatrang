<?php
declare(strict_types=1);

/**
 * Huy CNC Nha Trang — Complete Hardened Backend Bootstrap
 * PDO, Secure Session, CORS, Rate Limit, Upload, CSRF, and Role Helpers
 */

const SESSION_TIMEOUT_SECONDS = 7200; // 2 hours
const LOGIN_LOCK_ATTEMPTS = 5;
const LOGIN_LOCK_MINUTES = 15;

ini_set('display_errors', '0');
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_httponly', '1');

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['SERVER_PORT'] ?? '') === '443');

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => $isHttps,
    'httponly' => true,
    'samesite' => 'Strict',
]);

if (PHP_SAPI === 'cli') {
    $sessionPath = session_save_path();
    if ($sessionPath === '' || !is_dir($sessionPath) || !is_writable($sessionPath)) {
        session_save_path(sys_get_temp_dir());
    }
}

if (!headers_sent()) {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    header('Content-Type: application/json; charset=UTF-8');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: same-origin');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $scheme = $isHttps ? 'https' : 'http';
    $sameOrigin = $host ? "{$scheme}://{$host}" : '';
    $configuredOrigin = getenv('APP_ORIGIN') ?: '';

    if ($origin !== '') {
        if ($origin === $sameOrigin || ($configuredOrigin !== '' && $origin === $configuredOrigin) || strpos($origin, 'localhost') !== false || strpos($origin, '127.0.0.1') !== false) {
            header("Access-Control-Allow-Origin: {$origin}");
            header('Vary: Origin');
            header('Access-Control-Allow-Credentials: true');
        }
    }

    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token, X-Requested-With');
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ─── DATABASE CONNECTION ───────────────────────────────────────────────────
$dbHost    = getenv('DB_HOST') ?: '127.0.0.1';
$dbName    = getenv('DB_NAME') ?: 'huycnc_nhatrang_db';
$dbUser    = getenv('DB_USER') ?: 'root';
$dbPass    = getenv('DB_PASS') ?: '';
$dbCharset = 'utf8mb4';
$pdoOptions = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    try {
        $conn = new PDO("mysql:host={$dbHost};dbname={$dbName};charset={$dbCharset}", $dbUser, $dbPass, $pdoOptions);
    } catch (PDOException $exception) {
        $mysqlCode = (int) ($exception->errorInfo[1] ?? 0);
        if ($mysqlCode === 1049) { // Unknown database, create it!
            $serverConn = new PDO("mysql:host={$dbHost};charset={$dbCharset}", $dbUser, $dbPass, $pdoOptions);
            $safeDbName = str_replace('`', '``', $dbName);
            $serverConn->exec("CREATE DATABASE IF NOT EXISTS `{$safeDbName}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $conn = new PDO("mysql:host={$dbHost};dbname={$dbName};charset={$dbCharset}", $dbUser, $dbPass, $pdoOptions);
        } else {
            throw $exception;
        }
    }
} catch (PDOException $exception) {
    error_log('Database connection failed: ' . $exception->getMessage());
    $conn = null;
}

function get_db(): ?PDO {
    global $conn;
    return $conn;
}

// ─── HELPERS ────────────────────────────────────────────────────────────────
function json_response(array $payload, int $status = 200): void {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function json_input(): array {
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        return [];
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function request_data(): array {
    $json = json_input();
    return $json ?: $_POST;
}

function method_is_unsafe(): bool {
    return in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['POST', 'PUT', 'PATCH', 'DELETE'], true);
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function require_csrf(): void {
    if (!method_is_unsafe()) {
        return;
    }
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if ($token === '' && isset($_POST['csrf_token'])) {
        $token = (string) $_POST['csrf_token'];
    }
    if ($token === '') {
        $input = json_input();
        if (!empty($input['csrf_token'])) {
            $token = (string) $input['csrf_token'];
        }
    }
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        // Enforce strictly unless empty in local dev non-authenticated context
        if (empty($token) && strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false && empty($_SESSION['admin_id'])) {
            return;
        }
        json_response(['message' => 'CSRF token không hợp lệ hoặc đã hết hạn. Vui lòng tải lại trang.'], 419);
    }
}

function validate_status(string $status, array $allowed, string $default): string {
    $status = strtolower(trim($status));
    return in_array($status, $allowed, true) ? $status : $default;
}

function destroy_admin_session(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function session_admin(): ?array {
    if (empty($_SESSION['admin_id'])) {
        return null;
    }
    $last = (int) ($_SESSION['last_activity'] ?? 0);
    if ($last > 0 && (time() - $last) > SESSION_TIMEOUT_SECONDS) {
        destroy_admin_session();
        return null;
    }
    $_SESSION['last_activity'] = time();
    $adminId = (int) $_SESSION['admin_id'];

    global $conn;
    if (isset($conn) && $conn instanceof PDO) {
        $stmt = $conn->prepare('SELECT id, fullname, email, role, status FROM admin WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $adminId]);
        $admin = $stmt->fetch();
        if (!$admin || ($admin['status'] ?? '') !== 'active') {
            destroy_admin_session();
            return null;
        }
        $_SESSION['admin_fullname'] = (string) $admin['fullname'];
        $_SESSION['admin_email']    = (string) $admin['email'];
        $_SESSION['admin_role']     = (string) $admin['role'];
    }

    return [
        'id'       => $adminId,
        'fullname' => (string) ($_SESSION['admin_fullname'] ?? 'Admin Nha Trang'),
        'email'    => (string) ($_SESSION['admin_email'] ?? 'admin@huycnc-nhatrang.com'),
        'role'     => (string) ($_SESSION['admin_role'] ?? 'super_admin'),
    ];
}

function require_auth(array $roles = []): array {
    $admin = session_admin();
    if ($admin === null) {
        json_response(['message' => 'Phiên đăng nhập đã hết hạn hoặc chưa xác thực.'], 401);
    }
    if ($roles !== [] && !in_array($admin['role'], $roles, true)) {
        json_response(['message' => 'Bạn không có quyền thực hiện thao tác này.'], 403);
    }
    return $admin;
}

function can_manage_content(): array {
    return require_auth(['super_admin', 'editor']);
}

function can_manage_admins(): array {
    return require_auth(['super_admin']);
}

function can_view_admin_data(): array {
    return require_auth(['super_admin', 'editor', 'viewer']);
}

function save_uploaded_file(string $field, string $targetDir, string $publicPrefix, array $allowedExtensions, int $maxBytes): ?string {
    if (empty($_FILES[$field]) || !is_array($_FILES[$field])) {
        return null;
    }
    $file = $_FILES[$field];
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        json_response(['message' => 'Upload file thất bại.'], 400);
    }
    if (($file['size'] ?? 0) > $maxBytes) {
        json_response(['message' => 'File vượt quá dung lượng cho phép.'], 400);
    }

    $originalName = (string) ($file['name'] ?? '');
    // Prevent null-byte injection and path traversal in original name
    if (strpos($originalName, "\0") !== false || strpos($originalName, '..') !== false) {
        json_response(['message' => 'Tên file không hợp lệ.'], 400);
    }

    // Strict extension verification
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    
    // Dangerous executable blacklist check
    $dangerousExtensions = [
        'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'phar', 'pht',
        'shtml', 'shtm', 'cgi', 'pl', 'py', 'rb', 'asp', 'aspx', 'jsp', 'exe',
        'sh', 'bat', 'cmd', 'dll', 'bin', 'vbs', 'ps1', 'htaccess', 'user.ini'
    ];
    if (in_array($ext, $dangerousExtensions, true)) {
        json_response(['message' => 'Định dạng file bị nghiêm cấm vì lý do an toàn.'], 400);
    }

    // Check against double extension attack (e.g. exploit.php.png)
    $nameParts = explode('.', strtolower($originalName));
    if (count($nameParts) > 2) {
        for ($i = 0; $i < count($nameParts) - 1; $i++) {
            if (in_array($nameParts[$i], $dangerousExtensions, true)) {
                json_response(['message' => 'Phát hiện phần mở rộng kép không hợp lệ.'], 400);
            }
        }
    }

    if (!in_array($ext, $allowedExtensions, true)) {
        json_response(['message' => 'Định dạng file không được hỗ trợ.'], 400);
    }

    $tmpPath = (string) $file['tmp_name'];
    if (!is_uploaded_file($tmpPath)) {
        json_response(['message' => 'Upload không hợp lệ.'], 400);
    }

    // Content Inspection: Detect PHP tags and malicious scripts in uploaded file
    $contentSnippet = file_get_contents($tmpPath, false, null, 0, min(8192, (int) $file['size']));
    if ($contentSnippet !== false) {
        $lowerSnippet = strtolower($contentSnippet);
        if (
            strpos($lowerSnippet, '<?php') !== false ||
            strpos($lowerSnippet, '<?=') !== false ||
            strpos($lowerSnippet, '<script language="php"') !== false ||
            strpos($lowerSnippet, '<%') !== false
        ) {
            json_response(['message' => 'Nội dung file không an toàn (phát hiện mã thực thi).'], 400);
        }

        // SVG security hardening (prevent Stored XSS / XXE)
        if ($ext === 'svg') {
            if (
                strpos($lowerSnippet, '<script') !== false ||
                strpos($lowerSnippet, 'onload=') !== false ||
                strpos($lowerSnippet, 'onerror=') !== false ||
                strpos($lowerSnippet, 'javascript:') !== false ||
                strpos($lowerSnippet, '<!entity') !== false ||
                strpos($lowerSnippet, 'system') !== false
            ) {
                json_response(['message' => 'File SVG chứa mã script không hợp lệ.'], 400);
            }
        }
    }

    // Image MIME verification for image extensions
    if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
        $imageInfo = @getimagesize($tmpPath);
        if ($imageInfo === false) {
            json_response(['message' => 'File ảnh không hợp lệ hoặc bị lỗi cấu trúc.'], 400);
        }
    }

    if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
        json_response(['message' => 'Không thể tạo thư mục upload.'], 500);
    }

    // Generate cryptographically secure random filename
    $safeName = bin2hex(random_bytes(16)) . '.' . $ext;
    $targetPath = rtrim($targetDir, '/\\') . DIRECTORY_SEPARATOR . $safeName;
    if (!move_uploaded_file($tmpPath, $targetPath)) {
        json_response(['message' => 'Không thể lưu file upload.'], 500);
    }

    @chmod($targetPath, 0644);
    return rtrim($publicPrefix, '/') . '/' . $safeName;
}

function check_rate_limit(string $action, int $limit = 10, int $timeWindow = 300): void {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $key = "rate_limit_{$action}_{$ip}";
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = [];
    }
    $now = time();
    $validRequests = array_filter($_SESSION[$key], fn($time) => $now - $time < $timeWindow);
    $validRequests[] = $now;
    $_SESSION[$key] = $validRequests;
    if (count($validRequests) > $limit) {
        json_response(['message' => 'Bạn gửi yêu cầu quá thường xuyên. Vui lòng thử lại sau ít phút.'], 429);
    }
}
