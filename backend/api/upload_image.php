<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
$admin = session_admin();
if (!$admin || !in_array($admin['role'], ['super_admin', 'editor'])) {
    json_response(['error' => 'Unauthorized'], 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method Not Allowed'], 405);
}

require_csrf();

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    json_response(['error' => 'Vui lòng chọn một ảnh hợp lệ.'], 400);
}

$file = $_FILES['image'];
$targetDir = __DIR__ . '/../../uploads/posts';
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0755, true);
}

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
if (!in_array($ext, $allowedExts)) {
    json_response(['error' => 'Định dạng ảnh không được hỗ trợ.'], 400);
}

$newFileName = 'post-' . uniqid() . '-' . time() . '.' . $ext;
$targetPath = $targetDir . '/' . $newFileName;

// Check MIME type for extra security
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

$allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
if (!in_array($mime, $allowedMimes, true)) {
    json_response(['error' => 'File không phải là định dạng ảnh hợp lệ.'], 400);
}

if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    json_response(['error' => 'Không thể lưu file.'], 500);
}

$url = 'uploads/posts/' . $newFileName;
json_response(['success' => true, 'url' => $url, 'message' => 'Tải lên thành công!']);
