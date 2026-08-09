<?php
require_once __DIR__ . "/../lib/PalSrigati.php";

function assert_true($cond, $msg) {
    if (!$cond) {
        fwrite(STDERR, "Assertion failed: $msg\n");
        exit(2);
    }
}

// Data validation already runs on include; if we reach here it's valid
echo "PalSrigati data validation passed\n";

// TEST 1: Rabu Wage example -> totalNeptu 11
$expected = [
    6 => 3,12 => 5,18 => 2,24 => 2,30 => 9,36 => 2,42 => 1,48 => 2,54 => 3,60 => 1,66 => 3,72 => 3,78 => 5,84 => 2,90 => 2,96 => 9,102 => 2,108 => 1
];

foreach ($expected as $ageKey => $val) {
    $got = PalSrigati::getValue(11, $ageKey);
    assert_true($got === $val, "PalSrigati[11][$ageKey] expected $val got $got");
}

echo "Rabu Wage (neptu 11) test passed\n";

// TEST 4: Boundary checks for ages
$dob = new DateTimeImmutable('2016-08-09'); // arbitrary

// exactly 6 years
$on = $dob->modify('+6 years');
$k = PalSrigati::getPeriodKeyForDateOfBirth($dob, $on);
assert_true($k === 6, "Boundary: exactly 6 years should map to 6, got $k");

// 6 years +1 day
$on = $dob->modify('+6 years +1 day');
$k = PalSrigati::getPeriodKeyForDateOfBirth($dob, $on);
assert_true($k === 12, "Boundary: 6y+1d should map to 12, got $k");

// 108 years exact
$on = $dob->modify('+108 years');
$k = PalSrigati::getPeriodKeyForDateOfBirth($dob, $on);
assert_true($k === 108, "Boundary: 108y exact should map to 108, got $k");

echo "Boundary age tests passed\n";

echo "ALL TESTS PASSED\n";
exit(0);

?>