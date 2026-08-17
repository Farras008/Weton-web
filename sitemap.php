<?php
require_once __DIR__ . '/lib/ArticleData.php';

header('Content-Type: application/xml; charset=UTF-8');
$baseUrl = 'https://weton.online';
$pages = [
    '/' => '2026-08-17',
    '/artikel.php' => '2026-08-17',
    '/tentang.php' => '2026-08-17',
    '/kebijakan-privasi.php' => '2026-08-17',
    '/kontak.php' => '2026-08-17',
];
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($pages as $path => $modifiedAt): ?>
  <url><loc><?= htmlspecialchars($baseUrl . $path, ENT_XML1, 'UTF-8') ?></loc><lastmod><?= $modifiedAt ?></lastmod></url>
<?php endforeach; ?>
<?php foreach (ArticleData::ARTICLES as $slug => $article): ?>
  <url><loc><?= htmlspecialchars($baseUrl . ArticleData::canonicalPath($slug), ENT_XML1, 'UTF-8') ?></loc><lastmod><?= htmlspecialchars($article['modified_at'], ENT_XML1, 'UTF-8') ?></lastmod></url>
<?php endforeach; ?>
</urlset>
