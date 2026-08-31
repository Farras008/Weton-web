<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/ServiceReading.php';

$reading = ServiceReading::calculate(new DateTimeImmutable('1998-05-17'), 'siang');
foreach (['neptu', 'arahKejayaan', 'watakHari', 'watakPasaran', 'perbintangan', 'watakKelahiran', 'jodoh'] as $key) {
    if (!array_key_exists($key, $reading)) throw new RuntimeException('Service reading missing ' . $key);
}
if (($reading['neptu']['weton'] ?? '') === '') throw new RuntimeException('Service reading weton kosong.');
if (!is_array($reading['jodoh'])) throw new RuntimeException('Service reading jodoh harus berupa array.');

echo "Service reading test passed\n";
