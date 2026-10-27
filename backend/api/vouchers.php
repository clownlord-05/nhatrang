<?php
declare(strict_types=1);

require_once '../config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if (session_admin()) {
            $stmt = $conn->prepare('SELECT * FROM vouchers ORDER BY id DESC');
            $stmt->execute();
        } else {
            $stmt = $conn->prepare('SELECT * FROM vouchers WHERE expiry_date >= CURDATE() AND (usage_limit = 0 OR used_count < usage_limit) ORDER BY id DESC');
            $stmt->execute();
        }
        echo json_encode($stmt->fetchAll(), JSON_UNESCAPED_UNICODE);
        break;

    case 'POST':
        can_manage_content();
        require_csrf();
        $data = request_data();
        $code = strtoupper(trim((string) ($data['code'] ?? '')));
        $discountType = (string) ($data['discount_type'] ?? '');
        $discountValue = (float) ($data['discount_value'] ?? -1);
        $usageLimit = max(0, (int) ($data['usage_limit'] ?? 0));
        $expiryDate = trim((string) ($data['expiry_date'] ?? ''));

        if ($code === '' || !in_array($discountType, ['percent', 'fixed'], true) || $discountValue < 0 || $expiryDate === '') {
            json_response(['message' => 'Thiếu hoặc sai thông tin voucher.'], 400);
        }

        try {
            $stmt = $conn->prepare('INSERT INTO vouchers (code, discount_type, discount_value, usage_limit, expiry_date) VALUES (:code, :type, :value, :limit, :expiry)');
            $stmt->execute([':code' => $code, ':type' => $discountType, ':value' => $discountValue, ':limit' => $usageLimit, ':expiry' => $expiryDate]);
            json_response(['message' => 'Voucher đã được thêm.', 'id' => $conn->lastInsertId()]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                json_response(['message' => 'Mã voucher đã tồn tại.'], 409);
            }
            error_log('Voucher create failed: ' . $e->getMessage());
            json_response(['message' => 'Không thể lưu voucher.'], 500);
        }

    case 'PUT':
        can_manage_content();
        require_csrf();
        $data = request_data();
        $id = (int) ($data['id'] ?? 0);
        $code = strtoupper(trim((string) ($data['code'] ?? '')));
        $discountType = (string) ($data['discount_type'] ?? '');
        $discountValue = (float) ($data['discount_value'] ?? -1);
        $usageLimit = max(0, (int) ($data['usage_limit'] ?? 0));
        $expiryDate = trim((string) ($data['expiry_date'] ?? ''));

        if ($id <= 0 || $code === '' || !in_array($discountType, ['percent', 'fixed'], true) || $discountValue < 0 || $expiryDate === '') {
            json_response(['message' => 'Thiếu thông tin cập nhật voucher.'], 400);
        }

        try {
            $stmt = $conn->prepare('UPDATE vouchers SET code = :code, discount_type = :type, discount_value = :value, usage_limit = :limit, expiry_date = :expiry WHERE id = :id');
            $stmt->execute([':code' => $code, ':type' => $discountType, ':value' => $discountValue, ':limit' => $usageLimit, ':expiry' => $expiryDate, ':id' => $id]);
            json_response(['message' => 'Voucher đã được cập nhật.']);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                json_response(['message' => 'Mã voucher đã tồn tại.'], 409);
            }
            error_log('Voucher update failed: ' . $e->getMessage());
            json_response(['message' => 'Không thể cập nhật voucher.'], 500);
        }
        break;

    case 'DELETE':
        can_manage_content();
        require_csrf();
        $data = request_data();
        $id = (int) ($data['id'] ?? 0);
        if ($id <= 0) {
            json_response(['message' => 'ID voucher là bắt buộc.'], 400);
        }

        $stmt = $conn->prepare('DELETE FROM vouchers WHERE id = :id');
        $stmt->execute([':id' => $id]);
        json_response(['message' => 'Voucher đã xóa.']);
        break;

    default:
        json_response(['message' => 'Method not allowed.'], 405);
}
?>
