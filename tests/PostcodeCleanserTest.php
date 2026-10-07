<?php

/**
 * PostcodeCleanser: the API rejects a postcode longer than 16 with a 400, and on user creation
 * that drops the customer and every purchase waiting for it. Stores that type the commune or a
 * pickup point in the zip field (Blockstore 1897: "PEDRO AGUIRRE CERDA") hit it routinely.
 *
 * Checks the cleanser on its own and the body that Users::create / Multiusers::update actually
 * send, through Guzzle's MockHandler: no network.
 *
 * Ejecutar: php tests/PostcodeCleanserTest.php
 */

require __DIR__ . '/../vendor/autoload.php';

// Registered after composer and in front of it: the installed autoloader maps WoowUp\ to the
// repo's main checkout, and this test has to exercise the src/ next to it.
spl_autoload_register(function ($class) {
    $prefijo = 'WoowUp\\';
    if (strpos($class, $prefijo) !== 0) {
        return;
    }
    $file = __DIR__ . '/../src/' . str_replace('\\', '/', substr($class, strlen($prefijo))) . '.php';
    if (is_file($file)) {
        require_once $file;
    }
}, true, true);

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use WoowUp\Cleansers\PostcodeCleanser;

$passed = 0;
$failed = 0;

function check($cond, $msg)
{
    global $passed, $failed;
    if ($cond) { $passed++; echo "  OK   $msg\n"; }
    else       { $failed++; echo "  FAIL $msg\n"; }
}

$loadedFrom = (new ReflectionClass(\WoowUp\Endpoints\Endpoint::class))->getFileName();
check(strpos($loadedFrom, realpath(__DIR__ . '/../src')) === 0, "Endpoint se carga desde el src/ de este checkout");

echo "\n-- el cleanser --\n";
$cleanser = new PostcodeCleanser();
check($cleanser->truncate('7550000') === '7550000', 'un codigo postal real queda igual');
check($cleanser->truncate('LAS CONDES') === 'LAS CONDES', 'un texto de hasta 16 queda igual');
check($cleanser->truncate('SAN PEDRO DE LA PAZ') === 'SAN PEDRO DE LA', '"SAN PEDRO DE LA PAZ" (19) se corta a 16 sin el espacio final');
check($cleanser->truncate('BLOCKINDEPENDENCIA') === 'BLOCKINDEPENDENC', '"BLOCKINDEPENDENCIA" (18) se corta a 16');
check($cleanser->truncate(null) === '', 'un valor no string vuelve vacio, igual que street');
$withAccents = $cleanser->truncate('ÑUÑOA ÑUÑOA ÑUÑOA ÑUÑOA');
check(strlen(json_encode($withAccents)) - 2 <= 16, 'con eñes se mide por el largo en JSON, igual que street');

echo "\n-- lo que sale por HTTP --\n";
$history = [];
$stack = HandlerStack::create(new MockHandler([new Response(201, [], '{"payload":{"userapp_id":1}}'), new Response(200, [], '{"payload":[]}')]));
$stack->push(Middleware::history($history));
$http = new Client(['handler' => $stack]);

$users = new \WoowUp\Endpoints\Users('http://mock', 'key', $http);
$users->create(['email' => 'cliente@example.com', 'postcode' => 'PEDRO AGUIRRE CERDA', 'city' => 'Santiago']);
$sent = json_decode((string) $history[0]['request']->getBody(), true);
check($sent['postcode'] === 'PEDRO AGUIRRE CE', 'Users::create manda el postcode recortado (' . $sent['postcode'] . ')');
check($sent['city'] === 'Santiago' && $sent['email'] === 'cliente@example.com', 'Users::create no toca el resto del cliente');

$multiusers = new \WoowUp\Endpoints\Multiusers('http://mock', 'key', $http);
$multiusers->update(['email' => 'cliente@example.com', 'postcode' => 'BLOCKLOSDOMINICOS']);
$sent = json_decode((string) $history[1]['request']->getBody(), true);
check($sent['postcode'] === 'BLOCKLOSDOMINICO', 'Multiusers::update manda el postcode recortado (' . $sent['postcode'] . ')');

echo "\n$passed OK, $failed FAIL\n";
exit($failed > 0 ? 1 : 0);
