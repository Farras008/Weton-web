<?php
declare(strict_types=1);

require_once __DIR__ . '/Hari.php';
require_once __DIR__ . '/Pasaran.php';
require_once __DIR__ . '/NeptuCalculator.php';
require_once __DIR__ . '/ArahKejayaanData.php';
require_once __DIR__ . '/WatakData.php';
require_once __DIR__ . '/PerbintanganData.php';
require_once __DIR__ . '/WatakKelahiranData.php';
require_once __DIR__ . '/WatakBayi.php';
require_once __DIR__ . '/WatakBayiTanggal.php';
require_once __DIR__ . '/MarriageCalculator.php';
require_once __DIR__ . '/PalSrigati.php';

/** Shared calculation boundary for focused services and the complete reading. */
final class ServiceReading
{
    public static function character(array $reading): array { return ['neptu' => $reading['neptu'], 'watakHari' => $reading['watakHari'], 'watakPasaran' => $reading['watakPasaran'], 'watakKelahiran' => $reading['watakKelahiran'], 'watakBayi' => $reading['watakBayi'], 'watakBayiTanggal' => $reading['watakBayiTanggal']]; }
    public static function direction(array $reading): array { return ['neptu' => $reading['neptu'], 'arahKejayaan' => $reading['arahKejayaan']]; }
    public static function rezeki(array $reading): array { return ['neptu' => $reading['neptu'], 'palSrigati' => $reading['palSrigati'] ?? null]; }
    public static function jodoh(array $reading): array { return ['neptu' => $reading['neptu'], 'jodoh' => $reading['jodoh']]; }

    public static function calculate(DateTimeImmutable $birthDate, string $birthTime): array
    {
        $wetonDate = $birthTime === 'malam' ? $birthDate->modify('+1 day') : $birthDate;
        $hari = Hari::get($wetonDate->format('Y-m-d'));
        $pasaran = Pasaran::get($wetonDate->format('Y-m-d'));
        $neptu = hitungNeptu($hari, $pasaran);
        $period = PalSrigati::getPeriodKeyForDateOfBirth($birthDate);
        return [
            'tanggalWeton' => $wetonDate,
            'hari' => $hari,
            'pasaran' => $pasaran,
            'neptu' => $neptu,
            'arahKejayaan' => ArahKejayaanData::untukNeptu($neptu['totalNeptu']),
            'watakHari' => WatakData::untukHari($hari),
            'watakPasaran' => WatakData::untukPasaran($pasaran),
            'perbintangan' => PerbintanganData::untukNeptu($neptu['totalNeptu']),
            'watakKelahiran' => WatakKelahiranData::untukWeton($hari, $pasaran),
            'watakBayi' => isset(WatakBayi::WATAK_BAYI[$neptu['totalNeptu']]) ? WatakBayi::get($neptu['totalNeptu']) : null,
            'watakBayiTanggal' => WatakBayiTanggal::get((int) $birthDate->format('j')),
            'jodoh' => MarriageCalculator::getPartnerRecommendations($neptu['totalNeptu']),
            'palSrigati' => isset(PalSrigati::PAL_SRIGATI[$neptu['totalNeptu']]) ? ['periodKey' => $period, 'value' => PalSrigati::getValue($neptu['totalNeptu'], $period), 'timeline' => PalSrigati::timelineForNeptu($neptu['totalNeptu'], 60)] : null,
        ];
    }
}
