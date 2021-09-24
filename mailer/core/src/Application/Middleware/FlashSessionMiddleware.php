<?php
declare(strict_types=1);

namespace App\Application\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface as Middleware;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Flash\Messages;

class FlashSessionMiddleware implements Middleware
{
    /**
     * @var Messages
     */
    private $flash;

    public function __construct(Messages $flash)
    {
        $this->flash = $flash;
    }

    /**
     * {@inheritdoc}
     */
    public function process(Request $request, RequestHandler $handler): Response
    {
        // Set new flash message storage
        $this->flash->__construct($_SESSION);

        return $handler->handle($request);
    }
}
