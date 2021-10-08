<?php
declare(strict_types=1);

use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;
use Slim\Routing\RouteContext as Router;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Application\Settings\SettingsInterface;
use App\Application\Actions\Dashboard\IndexDashboardAction;
use App\Application\Actions\Mailer\ApiMailerAction;
use App\Application\Actions\Mailer\IndexMailerAction;
use App\Application\Actions\Mailer\ConfirmMailerAction;
use App\Application\Actions\Mailer\CompleteMailerAction;
use App\Application\Actions\HealthCheck\IndexHealthCheckAction;
use App\Application\Actions\HealthCheck\ConfirmHealthCheckAction;
use App\Application\Actions\HealthCheck\ResultHealthCheckAction;

return function (App $app) {
    $settings = $app->getContainer()->get(SettingsInterface::class);

    $app->options('/{routes:.*}', function (Request $request, Response $response) {
        // CORS Pre-Flight OPTIONS Request Handler
        return $response;
    });

    // ダッシュボード
    $app->group('', function (Group $group) {
        $group->get('/', IndexDashboardAction::class)->setName('dashboard');

        // 最後のスラッシュを強制.
        $group->get('', function (Request $request, Response $response) {
            $router = Router::fromRequest($request)
                ->getRouteParser()
                ->fullUrlFor($request->getUri(), 'dashboard');
            return $response->withHeader('Location', $router)->withStatus(301);
        });
    });

    // メールフォーム
    $app->group('/post', function (Group $group) use ($settings) {
        $formSettings = $settings->get('form');

        // ルートにPOSTされた場合、スキップ設定で自動振り分け
        $group->post(
            '',
            empty($formSettings['IS_CONFIRM_SKIP']) ? ConfirmMailerAction::class : CompleteMailerAction::class
        );
        $group->get('', IndexMailerAction::class)->setName('mailer');
        $group->post('/confirm', ConfirmMailerAction::class)->setName('mailer.confirm');
        $group->post('/complete', CompleteMailerAction::class)->setName('mailer.complete');

        // 最後のスラッシュを排除.
        $group->get('/', function (Request $request, Response $response) {
            $router = Router::fromRequest($request)
                ->getRouteParser()
                ->fullUrlFor($request->getUri(), 'mailer');
            return $response->withHeader('Location', $router)->withStatus(301);
        });
    });

    // API
    $app->get('/api/v1/csrf', ApiMailerAction::class);

    // ヘルスチェック
    $app->group('/health-check', function (Group $group) {
        $group->get('', IndexHealthCheckAction::class)->setName('health-check');
        $group->post('/confirm', ConfirmHealthCheckAction::class)->setName('health-check.confirm');
        $group->post('/result', ResultHealthCheckAction::class)->setName('health-check.result');

        // 最後のスラッシュを排除.
        $group->get('/', function (Request $request, Response $response) {
            $router = Router::fromRequest($request)
                ->getRouteParser()
                ->fullUrlFor($request->getUri(), 'health-check');
            return $response->withHeader('Location', $router)->withStatus(301);
        });
    });
};
