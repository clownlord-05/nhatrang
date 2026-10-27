<?php
declare(strict_types=1);

require_once '../config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        can_view_admin_data();
        $stmt = $conn->prepare('SELECT * FROM contacts ORDER BY id DESC');
        $stmt->execute();
        echo json_encode($stmt->fetchAll(), JSON_UNESCAPED_UNICODE);
        break;

    case 'POST':
        check_rate_limit('contact', 3, 300); // Max 3 contacts per 5 minutes
        
        $data = request_data();
        $name = trim((string) ($data['name'] ?? ''));
        $phoneEmail = trim((string) ($data['phone_email'] ?? ''));
        $message = trim((string) ($data['message'] ?? ''));

        if ($name === '' || $message === '') {
            json_response(['message' => 'Tên và nội dung là bắt buộc.'], 400);
        }

        $stmt = $conn->prepare('INSERT INTO contacts (name, phone_email, message) VALUES (:name, :phone_email, :message)');
        $stmt->execute([':name' => $name, ':phone_email' => $phoneEmail, ':message' => $message]);
        json_response(['message' => 'Liên hệ đã được ghi nhận.', 'id' => $conn->lastInsertId()]);
        break;

    case 'PUT':
        can_view_admin_data();
        require_csrf();
        $data = request_data();
        $id = (int) ($data['id'] ?? 0);
        if ($id <= 0) {
            json_response(['message' => 'ID liên hệ là bắt buộc.'], 400);
        }

        $stmt = $conn->prepare("UPDATE contacts SET status = 'read' WHERE id = :id");
        $stmt->execute([':id' => $id]);
        json_response(['message' => 'Tin nhắn đã đánh dấu đã đọc.']);
        break;

    case 'DELETE':
        can_manage_content();
        require_csrf();
        $data = request_data();
        $id = (int) ($data['id'] ?? 0);
        if ($id <= 0) {
            json_response(['message' => 'ID liên hệ là bắt buộc.'], 400);
        }

        $stmt = $conn->prepare('DELETE FROM contacts WHERE id = :id');
        $stmt->execute([':id' => $id]);
        json_response(['message' => 'Tin nhắn đã xóa.']);
        break;

    default:
        json_response(['message' => 'Method not allowed.'], 405);
}
?>
