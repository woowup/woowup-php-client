<?php

/**
 * EmailCleanser: prefijo "mailto:", direcciones marcadas como inválidas por el sistema de origen
 * ("invalido+...") y varias direcciones en un mismo valor.
 *
 * - "mailto:" se saca: no puede ser parte de una dirección real.
 * - "invalido+..." devuelve INVALID_EMAIL: Odoo marca así las direcciones que ya sabe inválidas, y
 *   gmail/outlook ignoran lo que va después del "+", así que conservarlas entrega a invalido@...
 * - Dos o más "@" seguidas de un dominio devuelven INVALID_EMAIL: antes la extracción de Gmail
 *   pegaba todo lo anterior a "@gmail" ("maria@hotmail.com / juan@gmail.com" ->
 *   "mariahotmail.comjuan@gmail.com").
 *
 * Fija también lo que NO tiene que cambiar: "@@" y espacios dentro de una sola dirección se siguen
 * corrigiendo, y "invalido" sin "+" o en otra posición no es la marca.
 *
 * Ejecutar: php tests/EmailCleanserPrefixesAndMultipleAddressesTest.php
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

use WoowUp\Cleansers\EmailCleanser;

$passed = 0;
$failed = 0;

function check($cond, $msg)
{
    global $passed, $failed;
    if ($cond) { $passed++; echo "  OK   $msg\n"; }
    else       { $failed++; echo "  FAIL $msg\n"; }
}

function sanitize($email)
{
    return (new EmailCleanser())->sanitize($email);
}

echo "mailto:\n";
foreach ([
    'mailto:juanperez@gmail.com' => 'juanperez@gmail.com',
    'MAILTO:Ana@Hotmail.com'     => 'ana@hotmail.com',
    'mailto: ana@hotmail.com'    => 'ana@hotmail.com',
] as $raw => $expected) {
    $got = sanitize($raw);
    check($got === $expected, "'$raw' -> '$expected' (got " . var_export($got, true) . ")");
}

echo "\ninvalido+ (marca del sistema de origen):\n";
foreach ([
    'Invalido+anaisa0615@gmail.com',
    'invalido+x@hotmail.com',
    'INVALIDO+x@hotmail.com',
    'invalido+anaisa0615@gmail',
    'mailto:invalido+x@gmail.com',
] as $raw) {
    $got = sanitize($raw);
    check($got === EmailCleanser::INVALID_EMAIL, "'$raw' -> INVALID_EMAIL (got " . var_export($got, true) . ")");
}

echo "\nvarias direcciones en un mismo valor:\n";
foreach ([
    'maria@hotmail.com / juan@gmail.com',
    'juan@gmail.com / maria@hotmail.com',
    'a@gmail.com;b@gmail.com',
    'maria@yahoo.com juan@outlook.com',
    'maria@hotmail,com / juan@gmail.com',
] as $raw) {
    $got = sanitize($raw);
    check($got === EmailCleanser::INVALID_EMAIL, "'$raw' -> INVALID_EMAIL (got " . var_export($got, true) . ")");
}

echo "\nsin cambios:\n";
foreach ([
    'kmrr2112@@gmail.com'     => 'kmrr2112@gmail.com',
    'juan peres@gmial.com'    => 'juanperes@gmail.com',
    'invalido@gmail.com'      => 'invalido@gmail.com',
    'juan+invalido@gmail.com' => 'juan+invalido@gmail.com',
    'invalidos+x@gmail.com'   => 'invalidos+x@gmail.com',
    'normal@unal.edu.co'      => 'normal@unal.edu.co',
] as $raw => $expected) {
    $got = sanitize($raw);
    check($got === $expected, "'$raw' -> '$expected' (got " . var_export($got, true) . ")");
}

echo "\n$passed OK, $failed FAIL\n";
exit($failed > 0 ? 1 : 0);
