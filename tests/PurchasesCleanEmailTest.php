<?php

/**
 * Purchases::cleanEmail(): EmailCleanser::sanitize() devuelve INVALID_EMAIL (noemail@noemail.com) para los
 * usuarios de relleno ("no@mail.com") y para los valores con varias direcciones. Es el placeholder que
 * comparten todos los clientes, así que la compra lo manda como <documento>@noemail.com, igual que
 * Users y Multiusers. Lo usa PurchasesCache de connectors.
 *
 * El endpoint se arma sin constructor (no hace falta Guzzle ni red): solo se prueba cleanEmail().
 *
 * Ejecutar: php tests/PurchasesCleanEmailTest.php
 */

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

use WoowUp\Cleansers\DataCleanser;
use WoowUp\Endpoints\Purchases;

$passed = 0;
$failed = 0;

function check($cond, $msg)
{
    global $passed, $failed;
    if ($cond) { $passed++; echo "  OK   $msg\n"; }
    else       { $failed++; echo "  FAIL $msg\n"; }
}

$purchases = (new ReflectionClass(Purchases::class))->newInstanceWithoutConstructor();
$cleanser  = new ReflectionProperty($purchases, 'cleanser');
$cleanser->setAccessible(true);
$cleanser->setValue($purchases, new DataCleanser());

$cleanEmail = new ReflectionMethod($purchases, 'cleanEmail');
$cleanEmail->setAccessible(true);

function emailDe(array $compra)
{
    global $purchases, $cleanEmail;
    return $cleanEmail->invoke($purchases, $compra)['email'] ?? null;
}

echo "======================================================================\n";
echo " Purchases::cleanEmail\n";
echo "======================================================================\n";

echo "\n--- INVALID_EMAIL va como <identificador>@noemail.com ---\n";

$casos = [
    'document'                    => [['email' => 'no@mail.com', 'document' => '30123456', 'service_uid' => 'X1'], '30123456@noemail.com'],
    'service_uid sin document'    => [['email' => 'test@gmail.com', 'service_uid' => 'X1'], 'X1@noemail.com'],
    'telephone sin los otros'     => [['email' => 'nosesabe@hotmail.com', 'telephone' => '1155556666'], '1155556666@noemail.com'],
    'varias direcciones'          => [['email' => 'ana@hotmail.com / juan@gmail.com', 'document' => '30123456'], '30123456@noemail.com'],
    'sin identificador'           => [['email' => 'no@mail.com'], 'noemail@noemail.com'],
];

foreach ($casos as $caso => [$compra, $esperado]) {
    $out = emailDe($compra);
    check($out === $esperado, "$caso: {$compra['email']} -> " . var_export($out, true));
}

echo "\n--- el resto no cambia ---\n";

$out = emailDe(['email' => 'Ana@Hotmail.com', 'document' => '30123456']);
check($out === 'ana@hotmail.com', "email válido se guarda limpio -> " . var_export($out, true));

$out = emailDe(['email' => 'mail@empresa.com.ar', 'document' => '30123456']);
check($out === 'mail@empresa.com.ar', "mail@ en dominio propio sigue válido -> " . var_export($out, true));

$out = emailDe(['email' => 'sinarroba', 'document' => '30123456']);
check($out === 'sinarroba', "false deja el email crudo -> " . var_export($out, true));

$out = emailDe(['email' => '30123456@noemail.com', 'document' => '30123456']);
check($out === '30123456@noemail.com', "<documento>@noemail.com no cambia -> " . var_export($out, true));

echo "\n$passed OK, $failed FAIL\n";
exit($failed === 0 ? 0 : 1);
