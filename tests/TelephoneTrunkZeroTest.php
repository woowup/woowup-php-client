<?php

/**
 * TelephoneFormatter: some sources keep the trunk "0" after the country code (Pernine/ICG sends
 * "5930…" for Ecuador), and the cleaned number came out as "+5930…" instead of "+593…".
 *
 * Ejecutar: php tests/TelephoneTrunkZeroTest.php
 */

// Autoloader propio sobre src/: el test corre con `php` plano, sin composer install
// y sin depender de que haya un vendor instalado.
spl_autoload_register(function ($class) {
    $prefijo = 'WoowUp\\';
    if (strpos($class, $prefijo) !== 0) {
        return;
    }
    $file = __DIR__ . '/../src/' . str_replace('\\', '/', substr($class, strlen($prefijo))) . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

use WoowUp\Cleansers\TelephoneCleanser;
use WoowUp\Cleansers\Formatters\TelephoneFormatter;

$passed = 0;
$failed = 0;

function check($cond, $msg)
{
    global $passed, $failed;
    if ($cond) { $passed++; echo "  OK   $msg\n"; }
    else       { $failed++; echo "  FAIL $msg\n"; }
}

echo "\n-- el 0 después del código de país sale --\n";
$formatter = new TelephoneFormatter();
$cleanser = new TelephoneCleanser();
foreach ([
    '5930987452163'      => '+593987452163', // Ecuador, como lo manda Pernine
    '+593 0987452163'    => '+593987452163',
    '+593 (0) 98 745 2163' => '+593987452163',
    '+593 02 2618397'    => '+59322618397',  // fijo de Quito con 0 + área
    '0058 0412 7395184'  => '+584127395184', // Venezuela, con 00 internacional
    '+598 099 734 518'   => '+59899734518',  // Uruguay
] as $raw => $expected) {
    $got = $cleanser->sanitize($raw);
    check($got === $expected, "'$raw' → '$expected' (sale " . var_export($got, true) . ")");
}

echo "\n-- lo que no tiene ese 0 no cambia --\n";
foreach ([
    '0987452163'     => '0987452163',     // local de Ecuador, sin código de país
    '+593987452163'  => '+593987452163',  // ya bien
    '+5491134857261' => '+5491134857261',
    '+573148672905'  => '+573148672905',
    '5101734'        => '+5101734',       // 7 dígitos: no alcanza para sacar el 0
] as $raw => $expected) {
    $got = $formatter->clean($raw);
    check($got === $expected, "'$raw' → '$expected' (sale '$got')");
}

echo "\n$passed ok, $failed fallaron\n";
exit($failed > 0 ? 1 : 0);
