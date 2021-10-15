<?php
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
