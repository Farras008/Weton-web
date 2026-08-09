<?php

require_once __DIR__ . '/Hari.php';
require_once __DIR__ . '/Pasaran.php';
require_once __DIR__ . '/NeptuCalculator.php';
require_once __DIR__ . '/ArahKejayaanData.php';
require_once __DIR__ . '/WatakData.php';
require_once __DIR__ . '/PerbintanganData.php';
require_once __DIR__ . '/WatakKelahiranData.php';
require_once __DIR__ . '/PalSrigati.php';
require_once __DIR__ . '/WatakBayi.php';
require_once __DIR__ . '/WatakBayiTanggal.php';
require_once __DIR__ . '/MarriageCalculator.php';

final class FullWetonReport
{
    public static function build(string $birthDate, string $birthTime): array
    {
        if (!in_array($birthTime, ['siang', 'malam'], true)) throw new InvalidArgumentException('Waktu lahir tidak valid.');
        $dob = DateTimeImmutable::createFromFormat('!Y-m-d', $birthDate);
        $errors = DateTimeImmutable::getLastErrors();
        if (!$dob || ($errors !== false && ($errors['warning_count'] || $errors['error_count'])) || $dob->format('Y-m-d') !== $birthDate) {
            throw new InvalidArgumentException('Tanggal lahir tidak valid.');
        }
        $calculationDate = $birthTime === 'malam' ? $dob->modify('+1 day') : $dob;
        $date = $calculationDate->format('Y-m-d');
        $hari = Hari::get($date);
        $pasaran = Pasaran::get($date);
        $neptu = hitungNeptu($hari, $pasaran);
        $total = $neptu['totalNeptu'];
        $period = PalSrigati::getPeriodKeyForDateOfBirth($dob);

        return [
            'birthDate' => $birthDate, 'birthTime' => $birthTime, 'calculationDate' => $date,
            'hari' => $hari, 'pasaran' => $pasaran,
            'neptu' => $neptu,
            'arahKejayaan' => ArahKejayaanData::untukNeptu($total),
            'watakHari' => WatakData::untukHari($hari), 'watakPasaran' => WatakData::untukPasaran($pasaran),
            'watakKelahiran' => WatakKelahiranData::untukWeton($hari, $pasaran),
            'watakBayi' => WatakBayi::get($total), 'watakBayiTanggal' => WatakBayiTanggal::get((int) $dob->format('j')),
            'perbintangan' => PerbintanganData::untukNeptu($total),
            'palSrigati' => ['period' => $period, 'value' => PalSrigati::getValue($total, $period), 'timeline' => PalSrigati::timelineForNeptu($total)],
            'jodoh' => MarriageCalculator::getPartnerRecommendations($total),
        ];
    }
}
