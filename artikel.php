<?php
require_once __DIR__ . '/lib/ArticleData.php';

$slug = trim((string) ($_GET['slug'] ?? ''));
$isListing = $slug === '';
$article = $isListing ? null : ArticleData::find($slug);
if (!$isListing && $article === null) http_response_code(404);

$pageTitle = $article ? $article['title'] . ' — Weton Online' : ($isListing ? 'Artikel Weton & Kalender Jawa — Weton Online' : 'Artikel tidak ditemukan — Weton Online');
$description = $article['description'] ?? 'Kumpulan artikel dan panduan seputar weton, neptu, pasaran, dan kalender Jawa.';
$canonical = $article ? 'https://weton.online' . ArticleData::canonicalPath($article['slug']) : 'https://weton.online/artikel.php';
$scriptDirectory = str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/artikel.php')));
$assetBase = rtrim($scriptDirectory, '/') . '/assets';
if ($assetBase === '/assets') $assetBase = '/assets';

$structuredData = null;
if ($article) {
    $structuredData = ['@context' => 'https://schema.org', '@type' => 'Article', 'headline' => $article['title'], 'description' => $article['description'], 'mainEntityOfPage' => $canonical, 'datePublished' => $article['published_at'], 'dateModified' => $article['modified_at'], 'inLanguage' => 'id-ID', 'author' => ['@type' => 'Organization', 'name' => 'Weton Online'], 'publisher' => ['@type' => 'Organization', 'name' => 'Weton Online']];
} elseif ($isListing) {
    $items = [];
    $position = 1;
    foreach (ArticleData::ARTICLES as $itemSlug => $item) {
        $items[] = ['@type' => 'ListItem', 'position' => $position++, 'url' => 'https://weton.online' . ArticleData::canonicalPath($itemSlug), 'name' => $item['title']];
    }
    $structuredData = ['@context' => 'https://schema.org', '@type' => 'CollectionPage', 'name' => 'Artikel Weton & Kalender Jawa', 'mainEntity' => ['@type' => 'ItemList', 'itemListElement' => $items]];
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta name="description" content="<?= htmlspecialchars($description) ?>">
    <link rel="canonical" href="<?= htmlspecialchars($canonical) ?>">
    <link rel="icon" type="image/png" href="<?= htmlspecialchars($assetBase) ?>/favicon.png">
    <link rel="stylesheet" href="<?= htmlspecialchars($assetBase) ?>/css/style.css">
    <?php if ($structuredData): ?><script type="application/ld+json"><?= json_encode($structuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script><?php endif; ?>
</head>
<body>
<main class="site-shell">
    <header class="site-header">
        <a class="brand" href="index.php" aria-label="Weton Online, kembali ke halaman utama"><span class="brand-mark"><span>W</span></span><span>Weton Jawa</span></a>
        <nav class="site-nav" aria-label="Navigasi utama"><a href="index.php#hitung-weton">Hitung Weton</a><a href="artikel.php"<?= $isListing ? ' aria-current="page"' : '' ?>>Artikel</a><a href="tentang.php">Tentang</a><a href="kontak.php">Kontak</a></nav>
    </header>
    <?php if ($isListing): ?>
        <article class="static-page article-list-page"><header class="page-hero"><p class="eyebrow">Perpustakaan Weton</p><h1>Artikel untuk memahami weton, pelan-pelan.</h1><p class="static-lead">Pilih topik yang ingin Anda baca. Daftar ini akan terus berkembang seiring artikel baru ditambahkan.</p></header><div class="page-divider"></div><div class="article-list" aria-label="Daftar artikel"><?php foreach (ArticleData::ARTICLES as $itemSlug => $item): ?><article class="article-list-item"><div><p class="guide-category"><?= htmlspecialchars($item['category']) ?></p><h2><a href="<?= htmlspecialchars(ArticleData::path($itemSlug)) ?>"><?= htmlspecialchars($item['title']) ?></a></h2><p><?= htmlspecialchars($item['description']) ?></p></div><footer><span><?= htmlspecialchars($item['reading_time']) ?></span><a href="<?= htmlspecialchars(ArticleData::path($itemSlug)) ?>">Baca <span aria-hidden="true">→</span></a></footer></article><?php endforeach; ?></div></article>
    <?php elseif (!$article): ?>
        <article class="static-page article-page"><p class="eyebrow">Artikel</p><h1>Artikel tidak ditemukan</h1><p class="static-lead">Artikel yang Anda cari belum tersedia atau tautannya sudah berubah.</p><a class="text-button" href="artikel.php">Lihat semua artikel <span aria-hidden="true">→</span></a></article>
    <?php else: ?>
        <article class="static-page longform-page editorial-page article-page"><header class="page-hero"><p class="eyebrow"><?= htmlspecialchars($article['category']) ?></p><h1><?= htmlspecialchars($article['title']) ?></h1><p class="static-lead"><?= htmlspecialchars($article['intro']) ?></p><p class="article-meta"><?= htmlspecialchars($article['reading_time']) ?> · Weton Online</p></header><div class="page-divider"></div><div class="article-body"><?php foreach ($article['sections'] as [$heading, $paragraphs]): ?><section><h2><?= htmlspecialchars($heading) ?></h2><?php foreach ($paragraphs as $paragraph): ?><p><?= htmlspecialchars($paragraph) ?></p><?php endforeach; ?></section><?php endforeach; ?></div><aside class="article-disclaimer"><strong>Baca dengan bijak</strong><p>Informasi ini membahas tradisi Jawa sebagai bahan pengetahuan dan refleksi, bukan kepastian atau nasihat profesional.</p></aside><section class="page-callout"><h2>Ingin tahu wetonmu?</h2><p>Masukkan tanggal lahir untuk melihat hari, pasaran, dan neptu wetonmu.</p><a class="text-button" href="index.php#hitung-weton">Hitung Weton <span aria-hidden="true">→</span></a></section></article>
    <?php endif; ?>
</main>
<footer class="site-footer"><p>© <?= date('Y') ?> Weton Online · Warisan kalender Jawa, hadir dalam bentuk digital.</p><nav><a href="artikel.php">Artikel</a><a href="tentang.php">Tentang</a><a href="kebijakan-privasi.php">Kebijakan Privasi</a></nav></footer>
</body>
</html>
