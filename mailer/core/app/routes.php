<?php
declare(strict_types=1);

use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Application\Actions\Dashboard\IndexDashboardAction;
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
    $app->get('/', IndexDashboardAction::class)->setName('dashboard');

    // メールフォーム
    $app->group('/post', function (Group $group) {
        $group->get('', IndexMailerAction::class)->setName('mailer');
        $group->post('', ConfirmMailerAction::class);
        $group->post('/confirm', ConfirmMailerAction::class)->setName('mailer.confirm');
        $group->post('/confirm/complete', CompleteMailerAction::class)->setName('mailer.confirm.complete');
    });
    $app->get('/api/v1/csrf', ApiMailerAction::class);

    // ヘルスチェック
    $app->group('/health-check', function (Group $group) {
        $group->get('', IndexHealthCheckAction::class)->setName('health-check');
        $group->post('/confirm', ConfirmHealthCheckAction::class)->setName('health-check.confirm');
        $group->post('/confirm/result', ResultHealthCheckAction::class)->setName('health-check.confirm.result');
    });
};
