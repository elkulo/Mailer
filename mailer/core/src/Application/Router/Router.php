<?php
/**
 * Mailer | el.kulo v3.2.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2022 A.Sudo
 * Licensed under LGPL-2.1-only (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

namespace App\Application\Router;

use Slim\Routing\RouteContext;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Application\Settings\SettingsInterface;

class Router implements RouterInterface
{

    /**
     * @var SettingsInterface
     */
    private $settings;

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
    public function __construct(SettingsInterface $settings)
    {
        $this->settings = $settings;
    }

    /**
     * @param Request $request
     */
    public function init(Request $request)
    {
        if ($request) {
            $urls = [];
            foreach (static::$urlNames as $name) {
                $dir = RouteContext::fromRequest($request)->getRouteParser()->urlFor($name);
                $urls[$name] = $this->settings->get('siteUrl') . $dir;
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
        $dir = RouteContext::fromRequest($request)->getRouteParser()->urlFor($name);
        $router = $this->settings->get('siteUrl') . $dir;
        return $response->withHeader('Location', $router)->withStatus($statusCode);
    }
}
