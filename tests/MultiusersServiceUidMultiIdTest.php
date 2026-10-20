<?php

/**
 * Multiusers::update / updateAsync: PUT /multiusers answers 400 "No identificator received, please
 * check multi-id settings" when the body carries service_uid and the account does not have it in its
 * multi-id (Filomena 1893: 1502/1502 PUTs, Grupo Vallejo 1855/1856/1857: 267/267). The endpoint reads
 * GET /account once per instance and leaves service_uid out of the PUT in that case.
 *
 * Checks Account::multidentificatorSettings() and the body that the PUT actually sends, through
 * Guzzle's MockHandler: no network.
 *
 * Ejecutar: php tests/MultiusersServiceUidMultiIdTest.php
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
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use WoowUp\Endpoints\Account;
use WoowUp\Endpoints\Multiusers;

$passed = 0;
$failed = 0;

function check($cond, $msg)
{
    global $passed, $failed;
    if ($cond) { $passed++; echo "  OK   $msg\n"; }
    else       { $failed++; echo "  FAIL $msg\n"; }
}

const HOST = 'https://api.woowup.com/apiv3';

function http(array $queue, &$history)
{
    $history = [];
    $stack   = HandlerStack::create(new MockHandler($queue));
    $stack->push(Middleware::history($history));

    return new Client(['handler' => $stack]);
}

function accountResponse($settings)
{
    return new Response(200, [], json_encode([
        'code'    => 'ok',
        'payload' => ['app_id' => 1893, 'multidentificator_settings' => $settings],
    ]));
}

function ok()
{
    return new Response(200, [], '{"code":"ok"}');
}

function byMethod(array $history, $method)
{
    return array_values(array_filter($history, function ($t) use ($method) {
        return $t['request']->getMethod() === $method;
    }));
}

function body($transaction)
{
    return json_decode((string) $transaction['request']->getBody(), true);
}

// Failures of GET /account that must leave the PUT as it is today. 503/502/429 are left out on
// purpose: Endpoint::request() retries them with sleep().
function accountFailures()
{
    return [
        'HTTP 500'                       => new Response(500, [], 'error'),
        'HTTP 404'                       => new Response(404, [], '{}'),
        'excepcion de conexion'          => new ConnectException('timeout', new Request('GET', HOST . '/account')),
        'RuntimeException'               => new RuntimeException('boom'),
        'body no JSON'                   => new Response(200, [], 'not json'),
        'payload sin multi-id'           => new Response(200, [], '{"code":"ok","payload":{"app_id":1}}'),
        'multidentificator_settings string' => accountResponse('document'),
        'multidentificator_settings vacio'  => accountResponse([]),
    ];
}

$loadedFrom = (new ReflectionClass(Multiusers::class))->getFileName();
check(strpos($loadedFrom, realpath(__DIR__ . '/../src')) === 0, 'Multiusers se carga desde el src/ de este checkout');

$noUid   = ['document' => ['type' => 'hard'], 'email' => ['type' => 'soft'], 'telephone' => ['type' => 'soft']];
$withUid = $noUid + ['service_uid' => ['type' => 'soft']];
$user    = [
    'service_uid' => 'ABC123',
    'document'    => '30111222',
    'email'       => 'cliente@example.com',
    'telephone'   => '1155556666',
    'first_name'  => 'Ana',
];

echo "\n-- Account::multidentificatorSettings() --\n";
$account = new Account(HOST, 'key', http([accountResponse($noUid)], $history));
$settings = $account->multidentificatorSettings();
check($settings === $noUid, 'devuelve el multi-id del payload como array');
check(count($history) === 1 && $history[0]['request']->getMethod() === 'GET'
    && (string) $history[0]['request']->getUri() === HOST . '/account', 'hace GET {host}/account');

$account = new Account(HOST, 'key', http([accountResponse($withUid)], $history));
check($account->multidentificatorSettings() === $withUid, 'con Id externo habilitado incluye service_uid');

foreach (accountFailures() as $label => $response) {
    $account = new Account(HOST, 'key', http([$response], $history));
    $threw   = false;
    try {
        $result = $account->multidentificatorSettings();
    } catch (\Throwable $e) {
        $threw = true;
    }
    check(!$threw && $result === null, "$label: devuelve null sin tirar");
}

foreach (['update', 'updateAsync'] as $method) {
    $run = function (Multiusers $multiusers, array $payload) use ($method) {
        return $method === 'update' ? $multiusers->update($payload) : $multiusers->updateAsync($payload)->wait();
    };

    echo "\n-- Multiusers::$method --\n";

    // Multi-id without service_uid: it is dropped, the rest of the customer goes out.
    $multiusers = new Multiusers(HOST, 'key', http([accountResponse($noUid), ok()], $history));
    $callerCopy = $user;
    $result     = $run($multiusers, $callerCopy);
    $puts       = byMethod($history, 'PUT');
    $sent       = $puts ? body($puts[0]) : [];
    check(count($puts) === 1 && (string) $puts[0]['request']->getUri() === HOST . '/multiusers', 'hace PUT {host}/multiusers');
    check(!array_key_exists('service_uid', $sent), 'multi-id sin service_uid: el PUT no lo manda');
    check(($sent['document'] ?? null) === '30111222' && ($sent['email'] ?? null) === 'cliente@example.com'
        && ($sent['telephone'] ?? null) === '1155556666' && ($sent['first_name'] ?? null) === 'Ana',
        'multi-id sin service_uid: conserva document, email, telephone y el resto');
    check($callerCopy === $user, 'el array del caller no se modifica');
    if ($method === 'update') {
        check($result === true, 'update devuelve true con 200');
    }

    // Multi-id with service_uid: body untouched.
    $multiusers = new Multiusers(HOST, 'key', http([accountResponse($withUid), ok()], $history));
    $run($multiusers, $user);
    $puts = byMethod($history, 'PUT');
    check($puts && body($puts[0]) === $user, 'multi-id con service_uid: el PUT lo manda y el body queda intacto');

    // GET /account fails: body untouched, no exception, the failure is memoized.
    foreach (accountFailures() as $label => $response) {
        $multiusers = new Multiusers(HOST, 'key', http([$response, ok(), ok()], $history));
        $threw      = false;
        try {
            $run($multiusers, $user);
            $run($multiusers, $user);
        } catch (\Throwable $e) {
            $threw = true;
        }
        $puts = byMethod($history, 'PUT');
        check(!$threw && count($puts) === 2 && body($puts[0]) === $user && body($puts[1]) === $user,
            "GET /account falla ($label): no tira y el body queda intacto");
        check(count(byMethod($history, 'GET')) === 1, "GET /account falla ($label): no se reintenta en el segundo PUT");
    }

    // Input without service_uid: GET /account is not called.
    $withoutUid = $user;
    unset($withoutUid['service_uid']);
    $multiusers = new Multiusers(HOST, 'key', http([ok()], $history));
    $run($multiusers, $withoutUid);
    check(count(byMethod($history, 'GET')) === 0, 'body sin service_uid: no consulta /account');
    $puts = byMethod($history, 'PUT');
    check($puts && body($puts[0]) === $withoutUid, 'body sin service_uid: el PUT sale intacto');

    // Several updates on the same instance: a single GET /account.
    $multiusers = new Multiusers(HOST, 'key', http([accountResponse($noUid), ok(), ok(), ok()], $history));
    $run($multiusers, $user);
    $run($multiusers, ['service_uid' => 'XYZ', 'email' => 'otro@example.com']);
    $run($multiusers, $user);
    $puts = byMethod($history, 'PUT');
    check(count(byMethod($history, 'GET')) === 1 && count($puts) === 3, 'tres updates en la misma instancia: un solo GET /account');
    check(count(array_filter($puts, function ($t) { return array_key_exists('service_uid', body($t)); })) === 0,
        'tres updates en la misma instancia: ninguno manda service_uid');
}

echo "\n$passed OK, $failed FAIL\n";
exit($failed > 0 ? 1 : 0);
