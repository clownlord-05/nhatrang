<?php
declare(strict_types=1);

require_once '../config.php';
require_once '../translation_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['message' => 'Method not allowed.'], 405);
}

$data = request_data();
$targetLang = normalize_translation_lang((string) ($data['target_lang'] ?? $data['lang'] ?? 'vi'));
$texts = $data['texts'] ?? [];

if (is_string($texts)) {
    $texts = [$texts];
}

if (!is_array($texts)) {
    json_response(['message' => 'texts must be an array.'], 400);
}

$cleanTexts = [];
foreach ($texts as $text) {
    if (!is_string($text)) {
        continue;
    }

    $text = trim($text);
    if ($text === '') {
        continue;
    }

    $cleanTexts[$text] = mb_substr($text, 0, 900, 'UTF-8');
    if (count($cleanTexts) >= 120) {
        break;
    }
}

$translations = translate_texts($conn, array_values($cleanTexts), $targetLang);

json_response([
    'success' => true,
    'target_lang' => $targetLang,
    'translations' => $translations,
]);
?>
