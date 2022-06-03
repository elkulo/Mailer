<?php
/**
 * Mailer | el.kulo v3.3.2 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2022 A.Sudo
 * Licensed under LGPL-2.1-only (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

namespace App\Application\Router;

use Slim\App;
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
     * Router constructor.
     *
     * @param SettingsInterface $settings
     */
    public function __construct(SettingsInterface $settings)
    {
        $this->settings = $settings;
    }

    /**
     * URLを調べて保存
     *
     * @param App     $app
     * @param Request $request
     * @return void
     */
    public function init(App $app, Request $request): void
    {
        $urls = [];

        // ルート一覧を取得.
        $routes = $app->getRouteCollector()->getRoutes();
        foreach ($routes as $route) {
            // 固有のルート名を取得.
            $name = $route->getName();
            if ($name) {
                $dir = RouteContext::fromRequest($request)->getRouteParser()->urlFor($name);
                $urls[$name] = $this->settings->get('siteUrl') . $dir;
            }
        }
        // 絶対URLで格納.
        static::$router = $urls;
    }

    /**
     * 絶対URLを取得
     *
     * @param string $key
     * @return mixed
     */
    public function getUrl(string $key = '')
    {
        return isset(static::$router[$key]) ? static::$router[$key]: false;
    }

    /**
     * 絶対URLでリダイレクト
     *
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
