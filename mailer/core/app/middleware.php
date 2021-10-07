<?php
declare(strict_types=1);

use Slim\App;
use Slim\Csrf\Guard;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use Slim\Flash\Messages;
use App\Application\Middleware\SessionMiddleware;
use App\Application\Settings\SettingsInterface;
use Middlewares\Whoops;

return function (App $app) {
    $app->add(SessionMiddleware::class);

    // Whoops.
    $settings = $app->getContainer()->get(SettingsInterface::class);
    if ($settings->get('debug')) {
        $app->add(Whoops::class);
    }

    // CSRF.
    $app->add(Guard::class);

    // Twig.
    $app->add(TwigMiddleware::createFromContainer($app, Twig::class));

    // Flash Messages.
    $app->add(
        function ($request, $next) use ($app) {
            if (session_status() !== PHP_SESSION_ACTIVE) {
                session_start();
            }
            $app->getContainer()->get(Messages::class)->__construct($_SESSION);
            return $next->handle($request);
        }
    );
};
