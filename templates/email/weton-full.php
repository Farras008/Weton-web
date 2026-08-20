<?php
/** @var array $reportForTemplate */
$r = $reportForTemplate;
$n = $r['neptu'];
$e = static fn(mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$sectionNumber = 0;
$section = static function (string $title) use (&$sectionNumber, $e): void {
    $sectionNumber++;
    echo '<tr><td class="section-heading" style="padding:34px 0 14px;border-bottom:1px solid #e6dcc8"><table role="presentation" width="100%" cellspacing="0" cellpadding="0"><tr><td class="section-number-cell" width="38" valign="top"><span style="display:inline-block;width:28px;height:28px;color:#214e40;background:#f1dfb6;border-radius:50%;font-family:Georgia,serif;font-size:14px;line-height:28px;text-align:center">' . $sectionNumber . '</span></td><td><h2 class="section-title" style="margin:1px 0 0;color:#123f34;font-family:Georgia,serif;font-size:24px;line-height:1.2">' . $e($title) . '</h2></td></tr></table></td></tr>';
};
$reading = static function (?array $item, string $source) use ($e): void {
    if (!$item) { echo '<p style="margin:16px 0 0;color:#5f6d64;font-size:14px;line-height:1.75">Data belum tersedia dalam sumber yang digunakan.</p>'; return; }
    echo '<div class="reading-card" style="margin-top:18px;padding:18px 20px;background:#faf5e9;border-left:3px solid #bd9140"><p style="margin:0;color:#80611e;font-size:11px;font-weight:bold;letter-spacing:.08em;text-transform:uppercase">Petikan Primbon</p><p style="margin:7px 0 0;color:#40564b;font-size:15px;line-height:1.75">' . $e($item['sumber'] ?? '-') . '</p><p style="margin:17px 0 0;color:#80611e;font-size:11px;font-weight:bold;letter-spacing:.08em;text-transform:uppercase">Makna</p><p style="margin:7px 0 0;color:#40564b;font-size:15px;line-height:1.75">' . $e($item['makna'] ?? '-') . '</p><p style="margin:15px 0 0;color:#748078;font-size:12px;line-height:1.5"><strong>Sumber:</strong> ' . $e($source) . '</p></div>';
};
$subheading = static function (string $title) use ($e): void { echo '<h3 style="margin:22px 0 8px;color:#245342;font-family:Georgia,serif;font-size:18px;line-height:1.3">' . $e($title) . '</h3>'; };
$dataRow = static function (string $label, mixed $value) use ($e): void { echo '<tr><td class="data-label" style="width:48%;padding:10px 12px 10px 0;color:#68766d;font-size:13px;line-height:1.45;border-bottom:1px solid #eee5d4">' . $e($label) . '</td><td class="data-value" style="padding:10px 0;color:#193c31;font-size:14px;font-weight:bold;line-height:1.45;text-align:right;border-bottom:1px solid #eee5d4;word-break:break-word">' . $e($value) . '</td></tr>'; };
?><!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembacaan Weton Lengkap</title>
    <style>
        body { -webkit-text-size-adjust:100%; -ms-text-size-adjust:100%; }
        @media screen and (max-width: 600px) {
            .email-shell { width: 100% !important; }
            .email-gutter { padding: 0 !important; }
            .email-shell { border-radius: 0 !important; }
            .email-header { padding: 28px 20px !important; }
            .header-kicker { margin-bottom: 10px !important; font-size: 10px !important; letter-spacing: .14em !important; }
            .brand-name { font-size: 26px !important; }
            .header-title { margin-top: 7px !important; font-size: 17px !important; }
            .email-content { padding: 24px 20px 30px !important; }
            .hero-title { font-size: 26px !important; line-height: 1.2 !important; }
            .intro-copy { font-size: 15px !important; line-height: 1.65 !important; }
            .summary-grid { margin-top: 20px !important; }
            .summary-cell { display: block !important; width: 100% !important; border-right: 0 !important; border-bottom: 1px solid #eadfca !important; }
            .summary-cell:last-child { border-bottom: 0 !important; }
            .section-heading { padding-top: 28px !important; }
            .section-number-cell { width: 34px !important; }
            .section-title { font-size: 22px !important; }
            .reading-card { padding: 16px !important; }
        }
        @media screen and (max-width: 420px) {
            .data-label, .data-value { display: block !important; box-sizing: border-box !important; width: 100% !important; }
            .data-label { padding: 10px 0 2px !important; border-bottom: 0 !important; }
            .data-value { padding: 2px 0 10px !important; text-align: left !important; }
        }
    </style>
</head>
<body style="margin:0;padding:0;background:#092d25;color:#193c31;font-family:Arial,Helvetica,sans-serif">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#092d25"><tr><td class="email-gutter" align="center" style="padding:28px 16px">
<table class="email-shell" role="presentation" width="680" cellspacing="0" cellpadding="0" style="width:100%;max-width:680px;background:#fffdf8;border-radius:6px;overflow:hidden">
<tr><td class="email-header" style="padding:36px 40px;background:#123f34;color:#fff8e9;text-align:center"><p class="header-kicker" style="margin:0 0 12px;color:#f0cf83;font-size:11px;font-weight:bold;letter-spacing:.18em;text-transform:uppercase">Warisan kalender Jawa</p><div class="brand-name" style="color:#f0cf83;font-family:Georgia,serif;font-size:29px;font-weight:bold;line-height:1.15">Weton Online</div><div class="header-title" style="margin-top:9px;color:#fff8e9;font-family:Georgia,serif;font-size:18px;line-height:1.4">Pembacaan Weton Lengkap</div></td></tr>
<tr><td class="email-content" style="padding:32px 40px 36px"><p style="margin:0;color:#80611e;font-size:11px;font-weight:bold;letter-spacing:.16em;text-transform:uppercase">Lembaran wetonmu</p><h1 class="hero-title" style="margin:8px 0 0;color:#123f34;font-family:Georgia,serif;font-size:32px;line-height:1.15">Hasil pembacaan untuk <?= $e($n['weton']) ?></h1><p class="intro-copy" style="margin:12px 0 0;color:#5b6b62;font-size:15px;line-height:1.7">Berikut rangkuman perhitungan dan pembacaan berdasarkan data kelahiran yang Anda masukkan.</p>
<table class="summary-grid" role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-top:24px;background:#f7eedb;border:1px solid #e3d2a9"><tr><td class="summary-cell" style="width:33.33%;padding:16px 14px;border-right:1px solid #eadfca"><p style="margin:0;color:#80611e;font-size:10px;font-weight:bold;letter-spacing:.08em;text-transform:uppercase">Hari</p><p style="margin:7px 0 0;color:#193c31;font-family:Georgia,serif;font-size:18px;font-weight:bold"><?= $e($r['hari']) ?></p></td><td class="summary-cell" style="width:33.33%;padding:16px 14px;border-right:1px solid #eadfca"><p style="margin:0;color:#80611e;font-size:10px;font-weight:bold;letter-spacing:.08em;text-transform:uppercase">Pasaran</p><p style="margin:7px 0 0;color:#193c31;font-family:Georgia,serif;font-size:18px;font-weight:bold"><?= $e($r['pasaran']) ?></p></td><td class="summary-cell" style="width:33.33%;padding:16px 14px"><p style="margin:0;color:#80611e;font-size:10px;font-weight:bold;letter-spacing:.08em;text-transform:uppercase">Total neptu</p><p style="margin:7px 0 0;color:#193c31;font-family:Georgia,serif;font-size:18px;font-weight:bold"><?= $e($n['totalNeptu']) ?></p></td></tr></table>
<table role="presentation" width="100%" cellspacing="0" cellpadding="0">
<?php $section('Data Kelahiran'); ?><tr><td style="padding-top:17px"><table role="presentation" width="100%" cellspacing="0" cellpadding="0"><?php $dataRow('Tanggal lahir', $r['birthDate']); $dataRow('Waktu lahir', $r['birthTime']); $dataRow('Hari', $r['hari']); $dataRow('Pasaran', $r['pasaran']); $dataRow('Weton', $n['weton']); $dataRow('Neptu hari', $n['neptuHari']); $dataRow('Neptu pasaran', $n['neptuPasaran']); $dataRow('Total neptu', $n['totalNeptu']); ?></table></td></tr>
<?php $section('Watak Hari'); ?><tr><td><?php $reading($r['watakHari'], 'Primbon Jawa — No. 109 Watak Hari.'); ?></td></tr>
<?php $section('Watak Pasaran'); ?><tr><td><?php $reading($r['watakPasaran'], 'Primbon Jawa — No. 110 Watak Pekan/Pasaran.'); ?></td></tr>
<?php $section('Watak Kelahiran'); ?><tr><td><?php $reading($r['watakKelahiran'], 'Primbon Jawa — No. 100 Watak bayi menurut hari dan pekan kelahiran.'); ?></td></tr>
<?php $section('Watak Bayi'); ?><tr><td><?php $subheading('Menurut jumlah neptu ' . $n['totalNeptu']); $reading($r['watakBayi'], 'Primbon Jawa — No. 105 Watak Bayi.'); $subheading('Menurut tanggal lahir'); $reading($r['watakBayiTanggal'], 'Primbon Jawa — No. 101 Watak bayi menurut tanggal kelahiran.'); ?></td></tr>
<?php $section('Arah Kejayaan'); ?><tr><td><div style="margin-top:18px;padding:19px 20px;background:#edf2e8;border-left:3px solid #2c705b"><p style="margin:0;color:#68766d;font-size:13px">Berdasarkan total neptu <?= $e($n['totalNeptu']) ?>, arah kejayaan Anda adalah:</p><p style="margin:8px 0 0;color:#193c31;font-family:Georgia,serif;font-size:21px;font-weight:bold"><?= $e($r['arahKejayaan']['display']) ?></p></div><p style="margin:12px 0 0;color:#748078;font-size:12px;line-height:1.5">Keterangan arah mengikuti data perhitungan Weton Online.</p></td></tr>
<?php $section('Perbintangan'); ?><tr><td><?php $b = $r['perbintangan']; ?><div style="margin-top:18px"><p style="margin:0;color:#80611e;font-size:11px;font-weight:bold;letter-spacing:.08em;text-transform:uppercase">Nama bintang</p><p style="margin:6px 0 0;color:#193c31;font-family:Georgia,serif;font-size:21px;font-weight:bold"><?= $e($b['bintang']) ?></p></div><?php $reading(['sumber' => $b['sumber'], 'makna' => $b['makna']], 'Primbon Jawa — No. 117 Perbintangan.'); ?><table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-top:18px;background:#f7eedb"><?php foreach (['Bala bantuan' => 'balaBantuan', 'Musuh' => 'musuh', 'Kesaktian' => 'kesaktian', 'Syarat' => 'syarat', 'Kejayaan' => 'kejayaan', 'Kemalangan' => 'kemalangan', 'Penyakit' => 'penyakit'] as $label => $key): $dataRow($label, $b[$key] ?? '-'); endforeach; ?></table></td></tr>
<?php $section('Pal Srigati'); ?><tr><td><p style="margin:17px 0 0;color:#5b6b62;font-size:14px;line-height:1.7">Nilai periode aktif ditandai dengan warna emas pada tabel berikut.</p><table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-top:14px;border-collapse:collapse"><tr style="background:#123f34;color:#fff8e9"><th align="left" style="padding:11px 12px;font-size:12px">Usia/periode</th><th align="right" style="padding:11px 12px;font-size:12px">Nilai</th></tr><?php foreach ($r['palSrigati']['timeline'] as $age => $row): $active = (int) $age === (int) $r['palSrigati']['period']; ?><tr style="background:<?= $active ? '#f1dfb6' : '#fffdf8' ?>"><td style="padding:10px 12px;border-bottom:1px solid #e6dcc8;color:#40564b;font-size:13px"><?= $e($row['label']) ?> tahun<?= $active ? ' <strong>(aktif)</strong>' : '' ?></td><td align="right" style="padding:10px 12px;border-bottom:1px solid #e6dcc8;color:#193c31;font-size:14px;font-weight:bold"><?= $e($row['value']) ?></td></tr><?php endforeach; ?></table><p style="margin:12px 0 0;color:#748078;font-size:12px">Sumber: Primbon Jawa — No. 96 Pal Srigati.</p></td></tr>
<?php $section('Jodoh & Pernikahan'); ?><tr><td><p style="margin:17px 0 0;color:#5b6b62;font-size:14px;line-height:1.7">Berikut hasil perbandingan berdasarkan neptu Anda dan seluruh rekomendasi pasangan yang tersedia.</p><?php foreach (['utama'=>'Rekomendasi utama','alternatif'=>'Alternatif','kurang-selaras'=>'Kurang selaras'] as $group => $label): $subheading($label); foreach ($r['jodoh'][$group] as $match): ?><div style="margin:12px 0;padding:16px 18px;border:1px solid #e3d2a9"><p style="margin:0;color:#193c31;font-size:14px;line-height:1.7"><strong>Neptu pasangan <?= $e($match['partnerNeptu']) ?></strong><br>Weton pasangan: <?= $e(implode(' · ', $match['wetonPasangan'])) ?><br>Total neptu pasangan: <?= $e($match['combinedNeptu']) ?></p><?php if ($match['result22']): $m22 = $match['result22']; ?><p style="margin:14px 0 0;color:#40564b;font-size:14px;line-height:1.7"><strong>No. 22 — <?= $e($m22['nama']) ?></strong><br><?= $e($m22['sumber']) ?><br><strong>Makna:</strong> <?= $e($m22['makna']) ?></p><?php else: ?><p style="margin:14px 0 0;color:#68766d;font-size:13px;line-height:1.7"><strong>No. 22:</strong> Belum tersedia untuk total ini dalam data sumber.</p><?php endif; $m23 = $match['result23']; ?><p style="margin:12px 0 0;color:#40564b;font-size:14px;line-height:1.7"><strong>No. 23 — <?= $e($m23['nama']) ?></strong> (<?= $e($m23['status']) ?>)<br><strong>Makna:</strong> <?= $e($m23['makna']) ?></p></div><?php endforeach; endforeach; ?><p style="margin:12px 0 0;color:#748078;font-size:12px">Sumber: Primbon Jawa — No. 22 dan No. 23 untuk perhitungan suami istri.</p></td></tr>
</table>
<div style="margin-top:34px;padding-top:22px;border-top:1px solid #dfd2b8;text-align:center"><p style="margin:0;color:#123f34;font-family:Georgia,serif;font-size:18px;font-weight:bold">Weton Online</p><p style="margin:7px 0"><a href="https://weton.online" style="color:#80611e;font-size:14px;font-weight:bold;text-decoration:none">Kunjungi weton.online</a></p><p style="margin:0;color:#748078;font-size:12px;line-height:1.6">Warisan kalender Jawa, hadir dalam bentuk digital.</p></div><p style="margin:24px 0 0;color:#68766d;font-size:12px;line-height:1.7">Catatan: Pembacaan ini merupakan bagian dari tradisi Primbon Jawa dan digunakan sebagai bahan refleksi budaya. Hasil perhitungan bukan kepastian mengenai masa depan, kecocokan, kesehatan, atau keberlangsungan suatu hubungan.</p>
</td></tr></table></td></tr></table>
</body></html>
