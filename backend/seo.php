<?php
declare(strict_types=1);

/**
 * Huy CNC Nha Trang — SEO & Dynamic Sitemap Generator
 */

function refresh_sitemap(?PDO $conn = null): bool {
    try {
        if ($conn === null) {
            $conn = get_db();
        }

        $baseUrl = getenv('APP_URL') ?: 'https://huycnc-nhatrang.com';
        $baseUrl = rtrim($baseUrl, '/');
        $today = date('Y-m-d');

        $staticPages = [
            ['path' => '/', 'priority' => '1.0', 'changefreq' => 'daily'],
            ['path' => '/solutions.html', 'priority' => '0.9', 'changefreq' => 'weekly'],
            ['path' => '/materials.html', 'priority' => '0.9', 'changefreq' => 'weekly'],
            ['path' => '/tracking.html', 'priority' => '0.9', 'changefreq' => 'daily'],
            ['path' => '/products.html', 'priority' => '0.8', 'changefreq' => 'daily'],
            ['path' => '/quote.html', 'priority' => '0.8', 'changefreq' => 'weekly'],
            ['path' => '/portfolio.html', 'priority' => '0.8', 'changefreq' => 'weekly'],
            ['path' => '/blog.html', 'priority' => '0.8', 'changefreq' => 'daily'],
            ['path' => '/about.html', 'priority' => '0.7', 'changefreq' => 'monthly'],
            ['path' => '/contact.html', 'priority' => '0.7', 'changefreq' => 'monthly'],
        ];

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($staticPages as $page) {
            $xml .= "  <url>\n";
            $xml .= "    <loc>" . htmlspecialchars($baseUrl . $page['path'], ENT_XML1) . "</loc>\n";
            $xml .= "    <lastmod>{$today}</lastmod>\n";
            $xml .= "    <changefreq>{$page['changefreq']}</changefreq>\n";
            $xml .= "    <priority>{$page['priority']}</priority>\n";
            $xml .= "  </url>\n";
        }

        // Add dynamic products from DB
        if ($conn instanceof PDO) {
            try {
                $stmt = $conn->query("SELECT slug, updated_at FROM products WHERE status = 'active' ORDER BY id DESC LIMIT 500");
                if ($stmt) {
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $slug = trim((string) ($row['slug'] ?? ''));
                        if ($slug === '') continue;
                        $mod = !empty($row['updated_at']) ? substr((string)$row['updated_at'], 0, 10) : $today;
                        $loc = htmlspecialchars($baseUrl . '/product-detail.html?slug=' . urlencode($slug), ENT_XML1);
                        $xml .= "  <url>\n";
                        $xml .= "    <loc>{$loc}</loc>\n";
                        $xml .= "    <lastmod>{$mod}</lastmod>\n";
                        $xml .= "    <changefreq>weekly</changefreq>\n";
                        $xml .= "    <priority>0.8</priority>\n";
                        $xml .= "  </url>\n";
                    }
                }
            } catch (Throwable $e) {
                error_log('Sitemap product fetch error: ' . $e->getMessage());
            }

            // Add dynamic posts from DB if posts table exists
            try {
                $stmt = $conn->query("SELECT slug, updated_at FROM posts WHERE status = 'published' ORDER BY id DESC LIMIT 200");
                if ($stmt) {
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $slug = trim((string) ($row['slug'] ?? ''));
                        if ($slug === '') continue;
                        $mod = !empty($row['updated_at']) ? substr((string)$row['updated_at'], 0, 10) : $today;
                        $loc = htmlspecialchars($baseUrl . '/post-detail.php?slug=' . urlencode($slug), ENT_XML1);
                        $xml .= "  <url>\n";
                        $xml .= "    <loc>{$loc}</loc>\n";
                        $xml .= "    <lastmod>{$mod}</lastmod>\n";
                        $xml .= "    <changefreq>weekly</changefreq>\n";
                        $xml .= "    <priority>0.7</priority>\n";
                        $xml .= "  </url>\n";
                    }
                }
            } catch (Throwable $e) {
                // Table might not exist yet, ignore
            }
        }

        $xml .= '</urlset>' . "\n";

        $sitemapPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'sitemap.xml';
        file_put_contents($sitemapPath, $xml, LOCK_EX);
        return true;
    } catch (Throwable $e) {
        error_log('Failed to generate sitemap: ' . $e->getMessage());
        return false;
    }
}
