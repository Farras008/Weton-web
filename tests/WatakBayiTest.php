<?php
require_once __DIR__ . "/../lib/WatakBayi.php";

function assert_true($cond, $msg) {
    if (!$cond) {
        fwrite(STDERR, "Assertion failed: $msg\n");
        exit(2);
    }
}

// Validation runs on include
echo "WatakBayi data validation passed\n";

// Ensure 12 keys
$keys = array_keys(WatakBayi::WATAK_BAYI);
assert_true(count($keys) === 12, 'Expected 12 watak bayi entries');

// Sample checks
$w7 = WatakBayi::get(7);
assert_true(strpos($w7['sumber'], 'Narima') !== false, 'Neptu 7 sumber mismatch');

$w13 = WatakBayi::get(13);
assert_true(strpos($w13['sumber'], 'Senang dipuji') !== false, 'Neptu 13 sumber mismatch');

$w18 = WatakBayi::get(18);
assert_true(strpos($w18['sumber'], 'Berani') !== false, 'Neptu 18 sumber mismatch');

echo "Sample WatakBayi tests passed\n";
exit(0);

?>