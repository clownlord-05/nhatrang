<?php
declare(strict_types=1);

require_once '../config.php';

$method = $_SERVER['REQUEST_METHOD'];
$dataFile = __DIR__ . '/../data/materials.json';

$defaultData = [
    'rates' => [
        'mica' => 120000,
        'wood' => 45000,
        'inox' => 550000,
        'alu' => 320000,
        'iron' => 180000,
        'copper' => 750000
    ],
    'thickness' => [
        '2' => 1.0,
        '5' => 1.4,
        '10' => 2.0,
        '20' => 3.2
    ],
    'setup_fee' => 150000,
    'last_updated' => date('Y-m-d H:i:s')
];

function get_materials_data(string $dataFile, array $defaultData, $conn): array
{
    if (isset($conn) && $conn instanceof PDO) {
        try {
            $conn->exec("CREATE TABLE IF NOT EXISTS material_settings (
                setting_key VARCHAR(50) PRIMARY KEY,
                setting_value TEXT NOT NULL,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            $stmt = $conn->query("SELECT setting_key, setting_value FROM material_settings");
            $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            if (!empty($rows)) {
                $rates = isset($rows['rates']) ? json_decode($rows['rates'], true) : $defaultData['rates'];
                $thickness = isset($rows['thickness']) ? json_decode($rows['thickness'], true) : $defaultData['thickness'];
                $setup_fee = isset($rows['setup_fee']) ? (float)$rows['setup_fee'] : $defaultData['setup_fee'];
                return [
                    'rates' => is_array($rates) ? $rates : $defaultData['rates'],
                    'thickness' => is_array($thickness) ? $thickness : $defaultData['thickness'],
                    'setup_fee' => $setup_fee,
                    'last_updated' => $rows['updated_at'] ?? date('Y-m-d H:i:s')
                ];
            }
        } catch (Throwable $e) {
            error_log('DB material_settings fetch error: ' . $e->getMessage());
        }
    }

    if (file_exists($dataFile)) {
        $json = file_get_contents($dataFile);
        $parsed = json_decode($json, true);
        if (is_array($parsed) && isset($parsed['rates'])) {
            return array_merge($defaultData, $parsed);
        }
    }

    return $defaultData;
}

switch ($method) {
    case 'GET':
        $data = get_materials_data($dataFile, $defaultData, $conn ?? null);
        json_response($data);
        break;

    case 'POST':
    case 'PUT':
        can_manage_content();
        require_csrf();
        $input = request_data();

        $rates = $input['rates'] ?? null;
        $thickness = $input['thickness'] ?? null;
        $setupFee = isset($input['setup_fee']) ? (float)$input['setup_fee'] : null;

        if (!is_array($rates)) {
            json_response(['message' => 'Dữ liệu giá vật liệu không hợp lệ.'], 400);
        }

        $cleanRates = [];
        foreach ($rates as $key => $val) {
            $cleanRates[preg_replace('/[^a-z0-9_]/', '', (string)$key)] = max(0, (float)$val);
        }

        $cleanThickness = [];
        if (is_array($thickness)) {
            foreach ($thickness as $key => $val) {
                $cleanThickness[(string)$key] = max(0.1, (float)$val);
            }
        } else {
            $cleanThickness = $defaultData['thickness'];
        }

        $cleanSetupFee = ($setupFee !== null && $setupFee >= 0) ? $setupFee : 150000;
        $now = date('Y-m-d H:i:s');

        $resultData = [
            'rates' => $cleanRates,
            'thickness' => $cleanThickness,
            'setup_fee' => $cleanSetupFee,
            'last_updated' => $now
        ];

        if (isset($conn) && $conn instanceof PDO) {
            try {
                $conn->exec("CREATE TABLE IF NOT EXISTS material_settings (
                    setting_key VARCHAR(50) PRIMARY KEY,
                    setting_value TEXT NOT NULL,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

                $stmt = $conn->prepare("REPLACE INTO material_settings (setting_key, setting_value) VALUES (:key, :val)");
                $stmt->execute([':key' => 'rates', ':val' => json_encode($cleanRates, JSON_UNESCAPED_UNICODE)]);
                $stmt->execute([':key' => 'thickness', ':val' => json_encode($cleanThickness, JSON_UNESCAPED_UNICODE)]);
                $stmt->execute([':key' => 'setup_fee', ':val' => (string)$cleanSetupFee]);
            } catch (Throwable $e) {
                error_log('DB material_settings save error: ' . $e->getMessage());
            }
        }

        $dataDir = dirname($dataFile);
        if (!is_dir($dataDir)) {
            @mkdir($dataDir, 0755, true);
        }
        @file_put_contents($dataFile, json_encode($resultData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        json_response(['message' => 'Đã cập nhật giá vật liệu thị trường thành công!', 'data' => $resultData]);
        break;

    default:
        json_response(['message' => 'Phương thức không được hỗ trợ.'], 405);
}
