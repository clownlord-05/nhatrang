<?php
declare(strict_types=1);

require_once '../config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        can_view_admin_data();
        $stmt = $conn->prepare('SELECT * FROM quotes ORDER BY id DESC');
        $stmt->execute();
        echo json_encode($stmt->fetchAll(), JSON_UNESCAPED_UNICODE);
        break;

    case 'POST':
        check_rate_limit('quote', 3, 300); // Max 3 quotes per 5 minutes
        
        $data = request_data();
        $customerName = trim((string) ($data['customer_name'] ?? ''));
        $customerPhone = trim((string) ($data['customer_phone'] ?? ''));
        $customerEmail = trim((string) ($data['customer_email'] ?? ''));
        $material = trim((string) ($data['material'] ?? ''));
        $thickness = (float) ($data['thickness'] ?? 0);
        $dimensions = trim((string) ($data['dimensions'] ?? ''));
        $quantity = max(1, (int) ($data['quantity'] ?? 1));
        $estimatedPrice = (float) ($data['estimated_price'] ?? 0);
        $currency = trim((string) ($data['currency'] ?? 'VND'));
        $voucherCode = trim((string) ($data['voucher_code'] ?? ''));
        $discountAmount = (float) ($data['discount_amount'] ?? 0);
        $note = trim((string) ($data['note'] ?? ''));

        if ($customerName === '' || $customerPhone === '') {
            json_response(['message' => 'Tên và số điện thoại là bắt buộc.'], 400);
        }

        $cadFilePath = save_uploaded_file(
            'cad_file',
            __DIR__ . '/../../uploads/cad',
            'uploads/cad',
            ['dxf', 'dwg', 'ai', 'svg', 'pdf', 'cdr', 'eps', 'stl', 'step', 'iges'],
            25 * 1024 * 1024
        );

        $quoteCode = 'Q-' . date('Ymd-His') . '-' . random_int(100, 999);
        $stmt = $conn->prepare('INSERT INTO quotes (quote_code, customer_name, customer_phone, customer_email, material, thickness, dimensions, quantity, estimated_price, currency, voucher_code, discount_amount, cad_file_path, note) VALUES (:code, :name, :phone, :email, :material, :thickness, :dimensions, :quantity, :price, :currency, :voucher, :discount, :cad, :note)');
        $stmt->execute([
            ':code' => $quoteCode,
            ':name' => $customerName,
            ':phone' => $customerPhone,
            ':email' => $customerEmail,
            ':material' => $material,
            ':thickness' => $thickness,
            ':dimensions' => $dimensions,
            ':quantity' => $quantity,
            ':price' => $estimatedPrice,
            ':currency' => $currency,
            ':voucher' => $voucherCode,
            ':discount' => $discountAmount,
            ':cad' => $cadFilePath,
            ':note' => $note,
        ]);

        json_response(['message' => 'Yêu cầu báo giá đã được gửi.', 'quote_code' => $quoteCode]);
        break;

    case 'PUT':
        can_view_admin_data();
        require_csrf();
        $data = request_data();
        $id = (int) ($data['id'] ?? 0);
        $status = validate_status((string) ($data['status'] ?? 'new'), ['new', 'processing', 'replied', 'done', 'cancelled'], 'new');

        if ($id <= 0) {
            json_response(['message' => 'ID báo giá là bắt buộc.'], 400);
        }

        $stmt = $conn->prepare('UPDATE quotes SET status = :status WHERE id = :id');
        $stmt->execute([':status' => $status, ':id' => $id]);
        json_response(['message' => 'Trạng thái báo giá đã cập nhật.']);
        break;

    default:
        json_response(['message' => 'Method not allowed.'], 405);
}
?>
