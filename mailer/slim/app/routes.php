<?php
declare(strict_types=1);

use App\Application\Actions\Mailer\SubmitMailerAction;
use App\Application\Actions\Tester\TesterAction;
use App\Application\Actions\Tester\HealthCheckAction;
use App\Application\Actions\User\ListUsersAction;
use App\Application\Actions\User\ViewUserAction;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

return function (App $app) {
    $app->options('/{routes:.*}', function (Request $request, Response $response) {
        // CORS Pre-Flight OPTIONS Request Handler
        return $response;
    });

    // 送信
    $app->get('/', SubmitMailerAction::class);
    $app->post('/', SubmitMailerAction::class);

    // テスター
    $app->group('/tester', function (Group $group) {
        $group->get('', TesterAction::class);
        $group->post('/health-check', HealthCheckAction::class);
    });

    // レポート
    $app->group('/report', function (Group $group) {
        $group->get('', ListUsersAction::class);
        $group->get('/cron', ViewUserAction::class);
    });
};
