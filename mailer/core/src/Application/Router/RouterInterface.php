<?php
/**
 * Mailer | el.kulo v3.1.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2022 A.Sudo
 * Licensed under LGPL-2.1-only (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

namespace App\Application\Router;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

interface RouterInterface
{
    /**
     * @param string $urlName
     * @return void
     */
    public function set(string $urlName): void;

    /**
     * @param string $key
     * @return mixed
     */
    public function getUrl(string $key = '');

    /**
     * @param string $name
     * @param Request $request
     * @param Response $response
     * @param int $statusCode
     * @return Response
     */
    public function redirect($name, Request $request, Response $response, int $statusCode = 301):Response;
}
