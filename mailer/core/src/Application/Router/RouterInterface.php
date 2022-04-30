<?php
/**
 * Mailer | el.kulo v3.3.1 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2022 A.Sudo
 * Licensed under LGPL-2.1-only (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

namespace App\Application\Router;

use Slim\App;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

interface RouterInterface
{

    /**
     * URLを調べて保存
     *
     * @param App     $app
     * @param Request $request
     * @return void
     */
    public function init(App $app, Request $request): void;

    /**
     * 絶対URLを取得
     *
     * @param string $key
     * @return mixed
     */
    public function getUrl(string $key = '');

    /**
     * 絶対URLでリダイレクト
     *
     * @param string $name
     * @param Request $request
     * @param Response $response
     * @param int $statusCode
     * @return Response
     */
    public function redirect($name, Request $request, Response $response, int $statusCode = 301):Response;
}
