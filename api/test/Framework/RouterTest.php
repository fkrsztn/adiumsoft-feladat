<?php

declare(strict_types=1);

namespace ADS\TakeHome\Tests\Framework;

use ADS\TakeHome\Framework\HttpRequest;
use ADS\TakeHome\Framework\HttpResponseInterface;
use ADS\TakeHome\Framework\JsonHttpResponse;
use ADS\TakeHome\Framework\Router;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use TypeError;

class RouterTest extends TestCase
{
    private string|false $originalErrorLog;

    protected function setUp(): void
    {
        // A Router minden 500-as hibát naplóz; a tesztek kimenetét ne szemetelje.
        $this->originalErrorLog = ini_set("error_log", "/dev/null");
    }

    protected function tearDown(): void
    {
        if ($this->originalErrorLog !== false) {
            ini_set("error_log", $this->originalErrorLog);
        }
    }

    #[Test]
    public function callsMatchingHandlerAndIgnoresQueryString(): void
    {
        $router = new Router();
        $router->addRoute("GET", "/cities", fn (HttpRequest $req) => new JsonHttpResponse($req->query));

        $res = $router->dispatch("GET", "/cities?cityId=1", new HttpRequest(["cityId" => "1"]));

        $this->assertSame(200, $res->getStatus());
        $this->assertSame(["cityId" => "1"], $this->decodeBody($res));
    }

    #[Test]
    public function decodesUrlEncodedPath(): void
    {
        $router = new Router();
        $router->addRoute("GET", "/régió", fn () => new JsonHttpResponse("ok"));

        $res = $router->dispatch("GET", "/r%C3%A9gi%C3%B3", new HttpRequest([]));

        $this->assertSame(200, $res->getStatus());
    }

    #[Test]
    public function returns404ForUnknownPath(): void
    {
        $router = new Router();

        $res = $router->dispatch("GET", "/unknown", new HttpRequest([]));

        $this->assertSame(404, $res->getStatus());
        $this->assertSame(["error" => "nem található"], $this->decodeBody($res));
    }

    #[Test]
    public function returns405ForUnsupportedMethod(): void
    {
        $router = new Router();
        $router->addRoute("GET", "/cities", fn () => new JsonHttpResponse([]));

        $res = $router->dispatch("POST", "/cities", new HttpRequest([]));

        $this->assertSame(405, $res->getStatus());
        $this->assertSame(["error" => "POST nem engedélyezett"], $this->decodeBody($res));
    }

    #[Test]
    public function hidesErrorDetailsWhenDebugIsOff(): void
    {
        $router = new Router(debug: false);
        $router->addRoute("GET", "/fail", fn () => throw new RuntimeException("SQLSTATE titkos részlet"));

        $res = $router->dispatch("GET", "/fail", new HttpRequest([]));

        $this->assertSame(500, $res->getStatus());
        $this->assertSame(["error" => "szerveroldali hiba"], $this->decodeBody($res));
    }

    #[Test]
    public function catchesPhpErrorsNotOnlyExceptions(): void
    {
        $router = new Router();
        $router->addRoute("GET", "/fail", fn () => throw new TypeError("rossz típus"));

        $res = $router->dispatch("GET", "/fail", new HttpRequest([]));

        $this->assertSame(500, $res->getStatus());
        $this->assertSame(["error" => "szerveroldali hiba"], $this->decodeBody($res));
    }

    #[Test]
    public function includesErrorDetailsWhenDebugIsOn(): void
    {
        $router = new Router(debug: true);
        $router->addRoute("GET", "/fail", fn () => throw new RuntimeException("valami elromlott"));

        $res = $router->dispatch("GET", "/fail", new HttpRequest([]));
        $body = $this->decodeBody($res);

        $this->assertSame(500, $res->getStatus());
        $this->assertSame("szerveroldali hiba: valami elromlott", $body["error"]);
        $this->assertSame(RuntimeException::class, $body["exception"]);
        $this->assertArrayHasKey("stackTrace", $body);
    }

    /**
     * @return array<mixed>
     */
    private function decodeBody(HttpResponseInterface $res): array
    {
        $decoded = json_decode((string) $res->getBody(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertIsArray($decoded);
        return $decoded;
    }
}
