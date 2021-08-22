<?php
declare(strict_types=1);

use App\Application\Actions\User\ListUsersAction;
use App\Application\Actions\User\ViewUserAction;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

//
use App\Application\Actions\Mailer\SubmitMailerAction;

return function (App $app) {
    $app->options('/{routes:.*}', function (Request $request, Response $response) {
        // CORS Pre-Flight OPTIONS Request Handler
        return $response;
    });

    //
    $app->get('/', SubmitMailerAction::class);
    $app->post('/', SubmitMailerAction::class);

    $app->group('/test', function (Group $group) {
        $group->get('', function (Request $request, Response $response) {
            $response->getBody()->write('Test!');
            return $response;
        });
        $group->post('/health-check', function (Request $request, Response $response) {
            $response->getBody()->write('Health Check!');
            return $response;
        });
    });

    $app->group('/report', function (Group $group) {
        $group->get('', ListUsersAction::class);
        $group->get('/cron', ViewUserAction::class);
    });
};
