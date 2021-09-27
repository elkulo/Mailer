<?php

declare(strict_types=1);

use Slim\App;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use Slim\Flash\Messages;
use Zeuxisoo\Whoops\Slim\WhoopsMiddleware;
use App\Application\Settings\SettingsInterface;
use App\Application\Middleware\SessionMiddleware;

return function (App $app) {
    $app->add(SessionMiddleware::class);

    // Whoops.
    $settings = $app->getContainer()->get(SettingsInterface::class);
    if ($settings->get('debug')) {
        $app->add(new WhoopsMiddleware(['enable' => true]));
    } else {
        $errorMiddleware = $app->addErrorMiddleware(false, true, true);
        $errorHandler    = $errorMiddleware->getDefaultErrorHandler();
        //$errorHandler->registerErrorRenderer('text/html', HtmlErrorRenderer::class);
    }

    // Twig.
    $app->add(TwigMiddleware::createFromContainer($app, Twig::class));

    // Flash Messages.
    $app->add(
        function ($request, $next) {
            if (session_status() !== PHP_SESSION_ACTIVE) {
                session_start();
            }
            $this->get(Messages::class)->__construct($_SESSION);
            return $next->handle($request);
        }
    );
};
