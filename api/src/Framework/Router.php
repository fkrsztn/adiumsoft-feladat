<?php

declare(strict_types=1);

namespace ADS\TakeHome\Framework;

use Closure;
use Throwable;

/**
 * Egyszerű HTTP router.
 */
class Router
{
    /**
     * @var array<string, array<string, Closure(HttpRequest):HttpResponseInterface>>
     */
    private array $routeGroups = [];

    /**
     * @param bool $debug Debug módban a 500-as hibaválasz a kivétel üzenetét
     * és a stack trace-t is tartalmazza. Éles környezetben kikapcsolva kell
     * hagyni, mert belső részleteket (SQL, fájlútvonalak) fedne fel.
     */
    public function __construct(
        private readonly bool $debug = false,
    ) {
    }

    /**
     * Regisztrál egy új végpontot.
     *
     * Az egyszerűség kedvéért most path paramétereket nem kezelünk, csak
     * pontos egyezőséget figyelünk.
     *
     * @param string $method HTTP metódus, pl. "GET", "POST"
     * @param string $path A végpont elérési útja, az URL path része, pl. "/hello"
     * @param Closure(HttpRequest):HttpResponseInterface $handler
     */
    public function addRoute(string $method, string $path, Closure $handler): void
    {
        $routeGroup = $this->routeGroups[$path] ?? [];
        $routeGroup[$method] = $handler;
        $this->routeGroups[$path] = $routeGroup;
    }

    /**
     * Elvégzi a HTTP kérés feldolgozását és a válasz visszaküldését.
     */
    public function handleRequest(): void
    {
        $res = $this->dispatch(
            $_SERVER["REQUEST_METHOD"],
            $_SERVER["REQUEST_URI"],
            HttpRequest::fromGlobals(),
        );
        $this->sendResponse($res);
    }

    /**
     * Megkeresi a kéréshez tartozó végpontot, és visszaadja a választ.
     *
     * Globális változókat nem olvas és nem ír, ezért tesztelhető.
     *
     * @param string $method HTTP metódus, pl. "GET"
     * @param string $uri A kért URI, a query stringgel együtt is lehet.
     */
    public function dispatch(string $method, string $uri, HttpRequest $request): HttpResponseInterface
    {
        // A REQUEST_URI-ban a query string is benne van, köszi PHP.
        [$path] = explode("?", $uri);
        $path = rawurldecode($path);

        $routeGroup = $this->routeGroups[$path] ?? null;
        if ($routeGroup === null) {
            return new JsonHttpResponse(["error" => "nem található"], 404);
        }
        $handler = $routeGroup[$method] ?? null;
        if ($handler === null) {
            return new JsonHttpResponse(["error" => "{$method} nem engedélyezett"], 405);
        }

        try {
            return $handler($request);
        } catch (Throwable $e) {
            // Throwable, nem csak Exception: így a PHP hibák (TypeError, Error)
            // is JSON választ adnak, nem üres 500-at.
            return $this->createErrorResponse($e);
        }
    }

    private function createErrorResponse(Throwable $e): HttpResponseInterface
    {
        // A teljes hiba mindig a szerver naplójába kerül (docker compose logs).
        error_log((string) $e);

        if (!$this->debug) {
            return new JsonHttpResponse(["error" => "szerveroldali hiba"], 500);
        }
        return new JsonHttpResponse([
            "error" => "szerveroldali hiba: {$e->getMessage()}",
            "exception" => $e::class,
            "stackTrace" => $e->getTraceAsString(),
        ], 500);
    }

    private function sendResponse(HttpResponseInterface $response): void
    {
        http_response_code($response->getStatus());

        // Most a példában csak ez az egy van.
        if ($response instanceof JsonHttpResponse) {
            header("Content-Type: application/json");
        }

        $body = $response->getBody();
        if ($body !== null) {
            echo $body;
        }
    }
}
