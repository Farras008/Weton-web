<?php
require_once __DIR__ . '/../lib/FullWetonReport.php';

$report = FullWetonReport::build('1998-05-17', 'siang');
foreach (['birthDate','birthTime','hari','pasaran','neptu','watakHari','watakPasaran','watakKelahiran','watakBayi','watakBayiTanggal','arahKejayaan','perbintangan','palSrigati','jodoh'] as $key) {
    if (!array_key_exists($key, $report) || $report[$key] === null) throw new RuntimeException("Full report missing: $key");
}
if (count($report['palSrigati']['timeline']) !== 18) throw new RuntimeException('Full report harus memuat 18 periode Pal Srigati.');
if ($report['neptu']['hari'] !== $report['hari'] || $report['neptu']['pasaran'] !== $report['pasaran']) throw new RuntimeException('Hari/pasaran report tidak konsisten.');
echo "FullWetonReport test passed\n";
