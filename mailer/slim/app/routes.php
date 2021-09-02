<?php
declare(strict_types=1);

use App\Application\Actions\Mailer\SubmitMailerAction;
use App\Application\Actions\HealthCheck\HealthCheckAction;
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
    $app->group('', function (Group $group) {
        $group->post('/', SubmitMailerAction::class);
        $group->post('/confirm', SubmitMailerAction::class);
        $group->post('/complete', SubmitMailerAction::class);
    });

    // テスター
    $app->group('/health-check', function (Group $group) {
        $group->get('', HealthCheckAction::class);
        $group->post('/result', HealthCheckAction::class);
    });

    // レポート
    $app->group('/report', function (Group $group) {
        $group->get('', HealthCheckAction::class);
        $group->get('/crone', HealthCheckAction::class);
    });
};
