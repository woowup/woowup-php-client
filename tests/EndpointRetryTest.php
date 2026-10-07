<?php

/**
 * Endpoint: which failures get retried and how many times.
 *
 * 429/502/503 keep the long budget (25 attempts). 500, 504 and failures with no response at all
 * (timeout, dropped connection) get a short cap of 5 attempts, and a 4xx is never retried. Runs
 * against Guzzle's MockHandler: no network. The 500-forever case waits for the real backoff (~15s).
 *
 * Ejecutar: php tests/EndpointRetryTest.php
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
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

class RetryProbeEndpoint extends \WoowUp\Endpoints\Endpoint
{
    public function callPost($data)
    {
        return $this->post('http://mock/purchases', $data);
    }

    public function callPostAsync($data)
    {
        return $this->postAsync('http://mock/purchases', $data);
    }
}

$passed = 0;
$failed = 0;

function check($cond, $msg)
{
    global $passed, $failed;
    if ($cond) { $passed++; echo "  OK   $msg\n"; }
    else       { $failed++; echo "  FAIL $msg\n"; }
}

/**
 * @return array [RetryProbeEndpoint, MockHandler]
 */
function endpointWith(array $queue)
{
    $mock = new MockHandler($queue);
    $endpoint = new RetryProbeEndpoint('http://mock', 'key', new Client(['handler' => HandlerStack::create($mock)]));
    return [$endpoint, $mock];
}

function dropped()
{
    return new ConnectException('cURL error 52: Empty reply from server', new Request('POST', 'http://mock/purchases'), null, ['errno' => 52]);
}

function served(MockHandler $mock, int $queued)
{
    return $queued - count($mock);
}

$loadedFrom = (new ReflectionClass(\WoowUp\Endpoints\Endpoint::class))->getFileName();
check(strpos($loadedFrom, realpath(__DIR__ . '/../src')) === 0, "Endpoint se carga desde el src/ de este checkout ($loadedFrom)");

echo "\n-- fallas pasajeras: reintenta y entra --\n";
foreach (['500' => new Response(500), '504' => new Response(504), 'sin respuesta (cURL 52)' => dropped()] as $label => $first) {
    list($endpoint, $mock) = endpointWith([$first, new Response(200)]);
    $response = $endpoint->callPost(['invoice_number' => 'X']);
    check($response->getStatusCode() === 200 && served($mock, 2) === 2, "$label y despues 200: entra al segundo intento");
}

echo "\n-- errores de datos: no reintenta --\n";
list($endpoint, $mock) = endpointWith([new Response(400, [], '{"code":"invalid_email"}'), new Response(200)]);
try {
    $endpoint->callPost(['invoice_number' => 'X']);
    check(false, '400 tira excepcion');
} catch (ClientException $e) {
    check(served($mock, 2) === 1, '400 falla al primer intento, sin reintento');
}

echo "\n-- 500 permanente: corta en el tope corto --\n";
$start = microtime(true);
list($endpoint, $mock) = endpointWith(array_fill(0, 6, new Response(500)));
try {
    $endpoint->callPost(['invoice_number' => 'X']);
    check(false, '500 permanente tira excepcion');
} catch (ServerException $e) {
    check(served($mock, 6) === 5, '500 permanente: 5 intentos y relanza el 500 original');
}
check(microtime(true) - $start < 25, '500 permanente cuesta segundos, no el presupuesto de 25 intentos');

echo "\n-- 429: mismo comportamiento que antes --\n";
list($endpoint, $mock) = endpointWith([new Response(429, ['Retry-After' => '1']), new Response(200)]);
\WoowUp\Endpoints\Endpoint::resetThrottleCount();
$response = $endpoint->callPost(['invoice_number' => 'X']);
$throttles = array_sum(\WoowUp\Endpoints\Endpoint::getThrottleCounts());
check($response->getStatusCode() === 200 && served($mock, 2) === 2 && $throttles === 1, '429 y despues 200: entra y cuenta el throttle');

echo "\n-- camino async --\n";
foreach (['500' => new Response(500), 'sin respuesta (cURL 52)' => dropped()] as $label => $first) {
    list($endpoint, $mock) = endpointWith([$first, new Response(200)]);
    $response = $endpoint->callPostAsync(['invoice_number' => 'X'])->wait();
    check($response->getStatusCode() === 200 && served($mock, 2) === 2, "async: $label y despues 200: entra al segundo intento");
}

echo "\n$passed OK, $failed FAIL\n";
exit($failed > 0 ? 1 : 0);
