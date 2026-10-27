<?php
declare(strict_types=1);

require_once '../config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $admin = session_admin();
        if ($admin) {
            $stmt = $conn->prepare('SELECT * FROM blog_posts ORDER BY id DESC');
            $stmt->execute();
        } else {
            $stmt = $conn->prepare("SELECT * FROM blog_posts WHERE status = 'published' ORDER BY published_at DESC, id DESC");
            $stmt->execute();
        }
        echo json_encode($stmt->fetchAll(), JSON_UNESCAPED_UNICODE);
        break;

    case 'POST':
        can_manage_content();
        require_csrf();
        $data = request_data();
        $title = trim((string) ($data['title'] ?? ''));
        $slug = trim((string) ($data['slug'] ?? ''));
        $category = trim((string) ($data['category'] ?? 'general'));
        $excerpt = trim((string) ($data['excerpt'] ?? ''));
        $content = trim((string) ($data['content'] ?? ''));
        $status = validate_status((string) ($data['status'] ?? 'draft'), ['draft', 'published'], 'draft');

        if ($title === '' || $content === '') {
            json_response(['message' => 'Tiêu đề và nội dung là bắt buộc.'], 400);
        }

        if ($slug === '') {
            $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $title), '-'));
        }

        try {
            $stmt = $conn->prepare('INSERT INTO blog_posts (title, slug, category, excerpt, content, status, published_at) VALUES (:title, :slug, :category, :excerpt, :content, :status, :published_at)');
            $stmt->execute([
                ':title' => $title,
                ':slug' => $slug,
                ':category' => $category,
                ':excerpt' => $excerpt,
                ':content' => $content,
                ':status' => $status,
                ':published_at' => $status === 'published' ? date('Y-m-d H:i:s') : null,
            ]);
            json_response(['message' => 'Bài viết đã được tạo.', 'id' => $conn->lastInsertId()]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                json_response(['message' => 'Slug bài viết đã tồn tại.'], 409);
            }
            error_log('Post create failed: ' . $e->getMessage());
            json_response(['message' => 'Không thể tạo bài viết.'], 500);
        }
        break;

    case 'PUT':
        can_manage_content();
        require_csrf();
        $data = request_data();
        $id = (int) ($data['id'] ?? 0);
        $title = trim((string) ($data['title'] ?? ''));
        $slug = trim((string) ($data['slug'] ?? ''));
        $category = trim((string) ($data['category'] ?? 'general'));
        $excerpt = trim((string) ($data['excerpt'] ?? ''));
        $content = trim((string) ($data['content'] ?? ''));
        $status = validate_status((string) ($data['status'] ?? 'draft'), ['draft', 'published'], 'draft');

        if ($id <= 0 || $title === '' || $content === '') {
            json_response(['message' => 'Thiếu dữ liệu cập nhật bài viết.'], 400);
        }

        if ($slug === '') {
            $slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $title), '-'));
        }

        try {
            $stmt = $conn->prepare('UPDATE blog_posts SET title = :title, slug = :slug, category = :category, excerpt = :excerpt, content = :content, status = :status, published_at = CASE WHEN :status_pub = "published" AND published_at IS NULL THEN NOW() ELSE published_at END WHERE id = :id');
            $stmt->execute([
                ':title' => $title,
                ':slug' => $slug,
                ':category' => $category,
                ':excerpt' => $excerpt,
                ':content' => $content,
                ':status' => $status,
                ':status_pub' => $status,
                ':id' => $id,
            ]);
            json_response(['message' => 'Bài viết đã được cập nhật.']);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                json_response(['message' => 'Slug bài viết đã tồn tại.'], 409);
            }
            error_log('Post update failed: ' . $e->getMessage());
            json_response(['message' => 'Không thể cập nhật bài viết.'], 500);
        }
        break;

    case 'DELETE':
        can_manage_content();
        require_csrf();
        $data = request_data();
        $id = (int) ($data['id'] ?? 0);
        if ($id <= 0) {
            json_response(['message' => 'ID bài viết là bắt buộc.'], 400);
        }
        $stmt = $conn->prepare('DELETE FROM blog_posts WHERE id = :id');
        $stmt->execute([':id' => $id]);
        json_response(['message' => 'Bài viết đã xóa.']);
        break;

    default:
        json_response(['message' => 'Method not allowed.'], 405);
}
?>
