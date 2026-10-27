<?php
declare(strict_types=1);

require_once '../config.php';
require_once '../seo.php';

$method = $_SERVER['REQUEST_METHOD'];
$uploadDir = __DIR__ . '/../../uploads/products';
$publicPrefix = 'uploads/products';
$allowedExt = ['jpg', 'jpeg', 'png', 'webp'];
$maxBytes = 5 * 1024 * 1024;

switch ($method) {
    case 'GET':
        $productId = (int) ($_GET['product_id'] ?? 0);
        if ($productId <= 0) {
            json_response(['message' => 'Thiếu product_id.'], 400);
        }

        $stmt = $conn->prepare('SELECT id, image_path, sort_order FROM product_images WHERE product_id = :product_id ORDER BY sort_order ASC, id ASC');
        $stmt->execute([':product_id' => $productId]);
        json_response(['success' => true, 'data' => $stmt->fetchAll()]);
        break;

    case 'POST':
        can_manage_content();
        require_csrf();

        $productId = (int) ($_POST['product_id'] ?? 0);
        if ($productId <= 0 || empty($_FILES['images']) || !is_array($_FILES['images'])) {
            json_response(['message' => 'Thiếu product_id hoặc file ảnh.'], 400);
        }

        $check = $conn->prepare('SELECT id FROM products WHERE id = :id LIMIT 1');
        $check->execute([':id' => $productId]);
        if (!$check->fetch()) {
            json_response(['message' => 'Sản phẩm không tồn tại.'], 404);
        }

        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            json_response(['message' => 'Không thể tạo thư mục upload.'], 500);
        }

        $orderStmt = $conn->prepare('SELECT COALESCE(MAX(sort_order), -1) AS max_order FROM product_images WHERE product_id = :product_id');
        $orderStmt->execute([':product_id' => $productId]);
        $nextOrder = ((int) ($orderStmt->fetch()['max_order'] ?? -1)) + 1;

        $files = $_FILES['images'];
        $fileCount = is_array($files['name']) ? count($files['name']) : 0;
        $saved = [];
        $skipped = [];

        for ($i = 0; $i < $fileCount; $i++) {
            $originalName = (string) ($files['name'][$i] ?? '');
            $error = (int) ($files['error'][$i] ?? UPLOAD_ERR_NO_FILE);
            $size = (int) ($files['size'][$i] ?? 0);
            $tmpName = (string) ($files['tmp_name'][$i] ?? '');
            $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

            if ($error !== UPLOAD_ERR_OK || $size > $maxBytes || !in_array($ext, $allowedExt, true) || !is_uploaded_file($tmpName)) {
                $skipped[] = $originalName;
                continue;
            }

            $fileName = bin2hex(random_bytes(16)) . '.' . $ext;
            $targetPath = rtrim($uploadDir, '/\\') . DIRECTORY_SEPARATOR . $fileName;
            if (!move_uploaded_file($tmpName, $targetPath)) {
                $skipped[] = $originalName;
                continue;
            }

            @chmod($targetPath, 0644);
            $relativePath = $publicPrefix . '/' . $fileName;
            $insert = $conn->prepare('INSERT INTO product_images (product_id, image_path, sort_order) VALUES (:product_id, :image_path, :sort_order)');
            $insert->execute([
                ':product_id' => $productId,
                ':image_path' => $relativePath,
                ':sort_order' => $nextOrder + count($saved),
            ]);
            $saved[] = $relativePath;
        }

        refresh_sitemap($conn);
        json_response(['success' => true, 'saved' => $saved, 'skipped' => $skipped]);
        break;

    case 'DELETE':
        can_manage_content();
        require_csrf();

        $data = request_data();
        $imageId = (int) ($data['id'] ?? $_GET['id'] ?? 0);
        if ($imageId <= 0) {
            json_response(['message' => 'Thiếu id ảnh.'], 400);
        }

        $stmt = $conn->prepare('SELECT image_path FROM product_images WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $imageId]);
        $row = $stmt->fetch();

        if ($row) {
            $baseDir = realpath($uploadDir);
            $filePath = realpath(__DIR__ . '/../../' . $row['image_path']);
            if ($baseDir && $filePath && strncmp($filePath, $baseDir, strlen($baseDir)) === 0 && is_file($filePath)) {
                unlink($filePath);
            }

            $delete = $conn->prepare('DELETE FROM product_images WHERE id = :id');
            $delete->execute([':id' => $imageId]);
        }

        refresh_sitemap($conn);
        json_response(['success' => true]);
        break;

    default:
        json_response(['message' => 'Method not allowed.'], 405);
}
