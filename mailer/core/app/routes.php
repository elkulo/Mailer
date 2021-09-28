<?php
declare(strict_types=1);

use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
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

    // メールフォーム
    $app->group('', function (Group $group) {
        $group->get('', IndexMailerAction::class);
        $group->post('', ConfirmMailerAction::class);
        $group->post('/complete', CompleteMailerAction::class);
    });

    // ヘルスチェック
    $app->group('/health-check', function (Group $group) {
        $group->get('', IndexHealthCheckAction::class);
        $group->post('/confirm', ConfirmHealthCheckAction::class);
        $group->post('/result', ResultHealthCheckAction::class);
    });
};