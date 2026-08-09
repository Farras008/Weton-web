<?php
require_once __DIR__ . '/../lib/FullWetonReport.php';

$reportForTemplate = FullWetonReport::build('1998-05-17', 'siang');
ob_start();
require __DIR__ . '/../templates/email/weton-full.php';
$html = ob_get_clean();

foreach (['Weton Online', 'Pembacaan Weton Lengkap', 'Data Kelahiran', 'Watak Hari', 'Perbintangan', 'Pal Srigati', 'Jodoh &amp; Pernikahan', 'https://weton.online'] as $expected) {
    if (!str_contains($html, $expected)) throw new RuntimeException('Template email tidak memuat: ' . $expected);
}
if (str_contains($html, '[Nama]')) throw new RuntimeException('Template email tidak boleh memakai placeholder nama.');
echo "Email template test passed\n";
