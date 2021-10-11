<?php
declare(strict_types=1);

namespace App\Application\Router;

use Slim\Routing\RouteContext;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class Router implements RouterInterface
{
    /**
     * @var array
     */
    private static $router = [];

    /**
     * @var array
     */
    private static $urlNames = [];

    /**
     * Router constructor.
     */
    public function __construct()
    {
    }

    /**
     * @param Request $request
     */
    public function init(Request $request)
    {
        if ($request) {
            $urls = [];
            foreach (static::$urlNames as $name) {
                $urls[$name] = RouteContext::fromRequest($request)
                ->getRouteParser()
                ->fullUrlFor(
                    $request->getUri(),
                    $name
                );
            }
            static::$router = $urls;
        }
    }

    /**
     * @param string $urlName
     * @return void
     */
    public function set(string $urlName): void
    {
        static::$urlNames[] = $urlName;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function getUrl(string $key = '')
    {
        return (empty($key)) ? static::$router : static::$router[$key];
    }

    /**
     * @param string $name
     * @param Request $request
     * @param Response $response
     * @param int $statusCode
     * @return Response
     */
    public function redirect($name, Request $request, Response $response, int $statusCode = 301):Response
    {
        $router = RouteContext::fromRequest($request)
            ->getRouteParser()
            ->fullUrlFor($request->getUri(), $name);
        return $response->withHeader('Location', $router)->withStatus($statusCode);
    }
}
