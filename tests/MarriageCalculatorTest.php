<?php
require_once __DIR__ . '/../lib/MarriageCalculator.php';

$unresolved = [];
for ($total = 14; $total <= 36; $total++) {
    try { Marriage22::calculate($total); } catch (LogicException) { $unresolved[] = $total; }
    $result23 = Marriage23::calculate($total);
    if (!isset($result23['nama'], $result23['status'], $result23['makna'])) throw new RuntimeException("No. 23 tidak lengkap untuk total $total");
}
// No. 22 can legitimately be unavailable where source data has no deterministic rule.
echo 'No. 22 unresolved totals: ' . ($unresolved ? implode(', ', $unresolved) : 'none') . PHP_EOL;
$ahadWage = MarriageCalculator::getMarriageResult(9, 8);
if ($ahadWage['combinedNeptu'] !== 17 || $ahadWage['result23']['nama'] !== 'Dana' || $ahadWage['result23']['status'] !== 'baik') throw new RuntimeException('Ahad Wage + Selasa Legi harus menghasilkan Dana (baik).');
foreach (MarriageCalculator::getPartnerRecommendations(9) as $group) foreach ($group as $row) if (!isset($row['result23'])) throw new RuntimeException('Rekomendasi jodoh tidak lengkap.');
echo "Marriage calculator tests passed\n";
