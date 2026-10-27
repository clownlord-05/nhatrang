<?php
declare(strict_types=1);

require_once '../config.php';
require_once '../seo.php';

$method = $_SERVER['REQUEST_METHOD'];

function save_product_gallery_uploads(): array
{
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $maxBytes = 5 * 1024 * 1024;
    $targetDir = __DIR__ . '/../../uploads/products';
    $publicPrefix = 'uploads/products';
    $files = [];

    if (!empty($_FILES['images']) && is_array($_FILES['images']) && is_array($_FILES['images']['name'] ?? null)) {
        $count = count($_FILES['images']['name']);
        for ($i = 0; $i < $count; $i++) {
            $files[] = [
                'name' => (string) ($_FILES['images']['name'][$i] ?? ''),
                'type' => (string) ($_FILES['images']['type'][$i] ?? ''),
                'tmp_name' => (string) ($_FILES['images']['tmp_name'][$i] ?? ''),
                'error' => (int) ($_FILES['images']['error'][$i] ?? UPLOAD_ERR_NO_FILE),
                'size' => (int) ($_FILES['images']['size'][$i] ?? 0),
            ];
        }
    } elseif (!empty($_FILES['image']) && is_array($_FILES['image'])) {
        $files[] = $_FILES['image'];
    }

    if ($files === []) {
        return [];
    }

    if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
        json_response(['message' => 'Không thể tạo thư mục upload.'], 500);
    }

    $saved = [];
    foreach ($files as $file) {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            json_response(['message' => 'Upload ảnh thất bại.'], 400);
        }

        if (($file['size'] ?? 0) > $maxBytes) {
            json_response(['message' => 'Một ảnh vượt quá dung lượng 5MB.'], 400);
        }

        $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExtensions, true)) {
            json_response(['message' => 'Định dạng ảnh không được hỗ trợ.'], 400);
        }

        if (!is_uploaded_file((string) $file['tmp_name'])) {
            json_response(['message' => 'Upload ảnh không hợp lệ.'], 400);
        }

        $fileName = bin2hex(random_bytes(16)) . '.' . $ext;
        $targetPath = rtrim($targetDir, '/\\') . DIRECTORY_SEPARATOR . $fileName;
        if (!move_uploaded_file((string) $file['tmp_name'], $targetPath)) {
            json_response(['message' => 'Không thể lưu ảnh upload.'], 500);
        }

        @chmod($targetPath, 0644);
        $saved[] = rtrim($publicPrefix, '/') . '/' . $fileName;
    }

    return $saved;
}

function product_image_variant(string $path, string $variant): string
{
    if ($path === '' || preg_match('/^(https?:|data:|blob:)/i', $path)) {
        return $path;
    }

    $info = pathinfo($path);
    if (empty($info['dirname']) || empty($info['filename'])) {
        return $path;
    }

    $candidate = rtrim($info['dirname'], '/\\') . '/' . $info['filename'] . '-' . $variant . '.webp';
    $absolute = realpath(__DIR__ . '/../../' . $candidate);
    $root = realpath(__DIR__ . '/../..');
    if ($absolute && $root && strpos($absolute, $root) === 0) {
        return $candidate;
    }

    return $path;
}

function attach_product_image_variants(array $product): array
{
    $image = (string) ($product['image'] ?? '');
    $product['image_card'] = product_image_variant($image, 'card');
    $product['image_display'] = product_image_variant($image, 'display');
    $product['image_thumb'] = product_image_variant($image, 'thumb');
    return $product;
}

switch ($method) {
    case 'GET':
        $id = (int) ($_GET['id'] ?? 0);
        if ($id > 0) {
            $stmt = $conn->prepare('SELECT * FROM products WHERE id = :id LIMIT 1');
            $stmt->execute([':id' => $id]);
            $product = $stmt->fetch();
            if (!$product) {
                json_response(['message' => 'Không tìm thấy sản phẩm.'], 404);
            }

            $imgStmt = $conn->prepare('SELECT image_path FROM product_images WHERE product_id = :id ORDER BY sort_order ASC, id ASC');
            $imgStmt->execute([':id' => $id]);
            $product['images'] = array_column($imgStmt->fetchAll(), 'image_path');
            $product = attach_product_image_variants($product);
            $product['images_display'] = array_map(fn($image) => product_image_variant((string) $image, 'display'), $product['images']);
            $product['images_thumb'] = array_map(fn($image) => product_image_variant((string) $image, 'thumb'), $product['images']);
            json_response($product);
        }

        $stmt = $conn->prepare('SELECT * FROM products ORDER BY id DESC');
        $stmt->execute();
        echo json_encode(array_map('attach_product_image_variants', $stmt->fetchAll()), JSON_UNESCAPED_UNICODE);
        break;

    case 'POST':
        can_manage_content();
        require_csrf();

        $data = request_data();
        $name = trim((string) ($data['name'] ?? ''));
        $category = trim((string) ($data['category'] ?? ''));
        $price = (float) ($data['price'] ?? 0);
        $description = trim((string) ($data['description'] ?? ''));
        $material = trim((string) ($data['material'] ?? ''));
        $thickness = trim((string) ($data['thickness'] ?? ''));
        $technology = trim((string) ($data['technology'] ?? ''));
        $precision = trim((string) ($data['precision_level'] ?? ''));

        if ($name === '' || $category === '') {
            json_response(['message' => 'Tên sản phẩm và danh mục là bắt buộc.'], 400);
        }

        $galleryImages = save_product_gallery_uploads();
        $image = $galleryImages[0] ?? 'assets/images/logo.png';

        $conn->beginTransaction();
        try {
            $stmt = $conn->prepare('INSERT INTO products (name, category, price, description, material, thickness, technology, precision_level, image) VALUES (:name, :category, :price, :description, :material, :thickness, :technology, :precision, :image)');
            $stmt->execute([
                ':name' => $name,
                ':category' => $category,
                ':price' => $price,
                ':description' => $description,
                ':material' => $material,
                ':thickness' => $thickness,
                ':technology' => $technology,
                ':precision' => $precision,
                ':image' => $image,
            ]);

            $productId = (int) $conn->lastInsertId();
            if ($galleryImages !== []) {
                $imageStmt = $conn->prepare('INSERT INTO product_images (product_id, image_path, sort_order) VALUES (:product_id, :image_path, :sort_order)');
                foreach ($galleryImages as $index => $imagePath) {
                    $imageStmt->execute([
                        ':product_id' => $productId,
                        ':image_path' => $imagePath,
                        ':sort_order' => $index,
                    ]);
                }
            }

            $conn->commit();
            refresh_sitemap($conn);
            json_response([
                'message' => 'Sản phẩm đã được thêm.',
                'id' => $productId,
                'image_count' => count($galleryImages),
            ]);
        } catch (Throwable $e) {
            $conn->rollBack();
            error_log('Product create failed: ' . $e->getMessage());
            json_response(['message' => 'Không thể thêm sản phẩm.'], 500);
        }
        break;

    case 'PUT':
        can_manage_content();
        require_csrf();

        $data = request_data();
        $id = (int) ($data['id'] ?? 0);
        $name = trim((string) ($data['name'] ?? ''));
        $category = trim((string) ($data['category'] ?? ''));

        if ($id <= 0 || $name === '' || $category === '') {
            json_response(['message' => 'Thiếu ID, tên sản phẩm hoặc danh mục.'], 400);
        }

        $stmt = $conn->prepare('UPDATE products SET name = :name, category = :category, price = :price, description = :description, material = :material, thickness = :thickness, technology = :technology, precision_level = :precision WHERE id = :id');
        $stmt->execute([
            ':name' => $name,
            ':category' => $category,
            ':price' => (float) ($data['price'] ?? 0),
            ':description' => trim((string) ($data['description'] ?? '')),
            ':material' => trim((string) ($data['material'] ?? '')),
            ':thickness' => trim((string) ($data['thickness'] ?? '')),
            ':technology' => trim((string) ($data['technology'] ?? '')),
            ':precision' => trim((string) ($data['precision_level'] ?? '')),
            ':id' => $id,
        ]);

        refresh_sitemap($conn);
        json_response(['message' => 'Sản phẩm đã được cập nhật.']);
        break;

    case 'DELETE':
        can_manage_content();
        require_csrf();

        $data = request_data();
        $id = (int) ($data['id'] ?? 0);
        if ($id <= 0) {
            json_response(['message' => 'ID sản phẩm là bắt buộc.'], 400);
        }

        $stmt = $conn->prepare('DELETE FROM products WHERE id = :id');
        $stmt->execute([':id' => $id]);
        refresh_sitemap($conn);
        json_response(['message' => 'Sản phẩm đã xóa.']);
        break;

    default:
        json_response(['message' => 'Method not allowed.'], 405);
}
?>
