<?php
declare(strict_types=1);

use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Application\Actions\Dashboard\DashboardAction;
use App\Application\Actions\Mailer\ApiMailerAction;
use App\Application\Actions\Mailer\IndexMailerAction;
use App\Application\Actions\Mailer\ConfirmMailerAction;
use App\Application\Actions\Mailer\CompleteMailerAction;
use App\Application\Actions\HealthCheck\IndexHealthCheckAction;
use App\Application\Actions\HealthCheck\ConfirmHealthCheckAction;
use App\Application\Actions\HealthCheck\ResultHealthCheckAction;

return function (App $app) {
    $app->options('/{routes:.*}', function (Request $request, Response $response) {
        // CORS Pre-Flight OPTIONS Request Handler
        return $response;
    });

    // ダッシュボード
    $app->get('/', DashboardAction::class);

    // メールフォーム
    $app->group('/post', function (Group $group) {
        $group->get('', IndexMailerAction::class);
        $group->post('/confirm', ConfirmMailerAction::class);
        $group->post('/confirm/complete', CompleteMailerAction::class);
    });
    $app->get('/api/v1/csrf', ApiMailerAction::class);

    // ヘルスチェック
    $app->group('/health-check', function (Group $group) {
        $group->get('', IndexHealthCheckAction::class);
        $group->post('/confirm', ConfirmHealthCheckAction::class);
        $group->post('/confirm/result', ResultHealthCheckAction::class);
    });
};
