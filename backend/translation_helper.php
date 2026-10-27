<?php
declare(strict_types=1);

/**
 * Huy CNC Nha Trang — Translation Helper
 * Multi-language translation support with fallback
 */

function normalize_translation_lang(string $lang): string {
    $lang = strtolower(trim($lang));
    $allowed = ['vi', 'en', 'zh', 'ru', 'ko', 'ja'];
    if (in_array($lang, $allowed, true)) {
        return $lang;
    }
    // Prefix matches e.g. "en-US" -> "en"
    $short = substr($lang, 0, 2);
    return in_array($short, $allowed, true) ? $short : 'vi';
}

function translate_texts(?PDO $conn, array $texts, string $targetLang): array {
    $targetLang = normalize_translation_lang($targetLang);
    $results = [];

    if ($targetLang === 'vi') {
        foreach ($texts as $t) {
            $results[$t] = $t;
        }
        return $results;
    }

    // Common technical translation dictionary for CNC / metalworking
    $dictionary = [
        'en' => [
            'Trang chủ' => 'Home',
            'Sản phẩm' => 'Products',
            'Dịch vụ' => 'Services',
            'Báo giá' => 'Get a Quote',
            'Liên hệ' => 'Contact Us',
            'Giới thiệu' => 'About Us',
            'Tin tức' => 'News',
            'Vật liệu' => 'Materials',
            'Giải pháp' => 'Solutions',
            'Cắt Laser Kim Loại' => 'Sheet Metal Laser Cutting',
            'Gia công Phay CNC' => 'CNC Milling & Machining',
            'Chấn Gập CNC' => 'CNC Press Brake Bending',
            'Hàn Laser & Đánh Bóng' => 'Laser Welding & Polishing',
            'Biển Quảng Cáo Chữ Nổi' => 'Illuminated Channel Letters',
            'Gia công theo yêu cầu' => 'Custom Metal Fabrication',
            'Inox 304' => 'Stainless Steel 304',
            'Nhôm 6061' => 'Aluminum 6061',
            'Sắt thép' => 'Carbon Steel',
            'Đồng thau' => 'Brass',
        ],
        'zh' => [
            'Trang chủ' => '首页',
            'Sản phẩm' => '产品中心',
            'Dịch vụ' => '加工服务',
            'Báo giá' => '在线报价',
            'Liên hệ' => '联系我们',
            'Giới thiệu' => '关于我们',
            'Tin tức' => '新闻资讯',
            'Vật liệu' => '加工材质',
            'Giải pháp' => '解决方案',
            'Cắt Laser Kim Loại' => '金属激光切割',
            'Gia công Phay CNC' => 'CNC精密铣削',
            'Chấn Gập CNC' => 'CNC数控折弯',
            'Hàn Laser & Đánh Bóng' => '激光焊接与表面抛光',
            'Biển Quảng Cáo Chữ Nổi' => '发光字广告标识',
            'Gia công theo yêu cầu' => '按需定制加工',
            'Inox 304' => '304不锈钢',
            'Nhôm 6061' => '6061铝合金',
            'Sắt thép' => '碳钢钢材',
            'Đồng thau' => '黄铜',
        ],
        'ru' => [
            'Trang chủ' => 'Главная',
            'Sản phẩm' => 'Продукция',
            'Dịch vụ' => 'Услуги',
            'Báo giá' => 'Запросить расчет',
            'Liên hệ' => 'Контакты',
            'Giới thiệu' => 'О компании',
            'Tin tức' => 'Новости',
            'Vật liệu' => 'Материалы',
            'Giải pháp' => 'Решения',
            'Cắt Laser Kim Loại' => 'Лазерная резка металла',
            'Gia công Phay CNC' => 'Фрезерная обработка ЧПУ',
            'Chấn Gập CNC' => 'Гибка металла на ЧПУ',
            'Hàn Laser & Đánh Bóng' => 'Лазерная сварка и полировка',
            'Biển Quảng Cáo Chữ Nổi' => 'Объемные буквы и вывески',
            'Gia công theo yêu cầu' => 'Изготовление на заказ',
            'Inox 304' => 'Нержавеющая сталь 304',
            'Nhôm 6061' => 'Алюминий 6061',
            'Sắt thép' => 'Сталь',
            'Đồng thau' => 'Латунь',
        ]
    ];

    $langDict = $dictionary[$targetLang] ?? [];

    foreach ($texts as $text) {
        if (isset($langDict[$text])) {
            $results[$text] = $langDict[$text];
        } else {
            // Default to original text if not found
            $results[$text] = $text;
        }
    }

    return $results;
}
