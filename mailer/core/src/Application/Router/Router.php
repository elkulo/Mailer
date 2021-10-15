<?php
/**
 * Mailer | el.kulo v3.0.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2021 A.Sudo
 * Licensed under LGPL-2.1-only (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
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
        return isset(static::$router[$key]) ? static::$router[$key]: false;
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
