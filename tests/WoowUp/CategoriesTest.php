<?php
namespace WoowUpTest\WoowUp;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use WoowUp\Endpoints\Branches;
use WoowUp\Endpoints\Categories;

/**
 * Tests de los borrados asincronicos: que se manda y que se devuelve.
 *
 * Sin red: el handler de Guzzle es un mock. Lo que fijan es el cuerpo exacto de cada request, que es
 * lo que decide que se borra, y que los dos metodos devuelvan el request_id del payload y no un bool.
 *
 * Correr: ./vendor/bin/phpunit tests/WoowUp/CategoriesTest.php
 */
class CategoriesTest extends TestCase
{
    const API_HOST = 'https://api.woowup.com/apiv3';
    const API_KEY  = 'apikey';

    private $sent = [];

    private function endpoint(string $class, array $responses)
    {
        $this->sent = [];
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($this->sent));

        return new $class(self::API_HOST, self::API_KEY, new Client(['handler' => $stack]));
    }

    private function accepted(int $requestId): Response
    {
        return new Response(200, [], json_encode(['payload' => ['request_id' => $requestId], 'message' => '', 'code' => 'ok']));
    }

    private function lastBody(): string
    {
        return (string) end($this->sent)['request']->getBody();
    }

    public function testBulkDeleteWithoutNotifyToSendsAnEmptyObject()
    {
        $categories = $this->endpoint(Categories::class, [$this->accepted(4521)]);

        $this->assertEquals(['request_id' => 4521], $categories->deleteBulk());
        // El schema del backend declara ["object","null"]: un array vacio se serializa [] y lo rechaza.
        $this->assertEquals('{}', $this->lastBody());
    }

    public function testBulkDeleteCarriesTheNotifyAddress()
    {
        $categories = $this->endpoint(Categories::class, [$this->accepted(4521)]);

        $categories->deleteBulk('qa@woowup.com');
        $this->assertEquals('{"notify_to":"qa@woowup.com"}', $this->lastBody());
    }

    public function testListReturnsThePayload()
    {
        $body = json_encode(['payload' => [['id' => 1, 'path' => 'ROPA', 'name' => 'Ropa']], 'code' => 'ok']);
        $categories = $this->endpoint(Categories::class, [new Response(200, [], $body)]);

        $tree = $categories->list();
        $this->assertCount(1, $tree);
        $this->assertEquals('ROPA', $tree[0]->path);
    }

    public function testBranchDeleteSendsTheIdInTheBody()
    {
        $branches = $this->endpoint(Branches::class, [$this->accepted(77)]);

        $this->assertEquals(['request_id' => 77], $branches->delete(103393));
        $this->assertEquals('{"id":103393}', $this->lastBody());
    }

    public function testBranchDeleteCarriesTheNotifyAddress()
    {
        $branches = $this->endpoint(Branches::class, [$this->accepted(77)]);

        $branches->delete('103393', 'qa@woowup.com');
        // El id viaja como entero aunque el caller lo tenga como string.
        $this->assertEquals('{"id":103393,"notify_to":"qa@woowup.com"}', $this->lastBody());
    }
}
