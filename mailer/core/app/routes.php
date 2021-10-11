<?php
declare(strict_types=1);

use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Application\Settings\SettingsInterface;
use App\Application\Router\RouterInterface;
use App\Application\Actions\Dashboard\IndexDashboardAction;
use App\Application\Actions\Dashboard\CsrfJsonAction;
use App\Application\Actions\Dashboard\CsrfJavaScriptAction;
use App\Application\Actions\Dashboard\ReCaptchaJavaScriptAction;
use App\Application\Actions\Mailer\IndexMailerAction;
use App\Application\Actions\Mailer\ConfirmMailerAction;
use App\Application\Actions\Mailer\CompleteMailerAction;
use App\Application\Actions\HealthCheck\IndexHealthCheckAction;
use App\Application\Actions\HealthCheck\ConfirmHealthCheckAction;
use App\Application\Actions\HealthCheck\ResultHealthCheckAction;

return function (App $app) {
    $settings = $app->getContainer()->get(SettingsInterface::class);
    $router = $app->getContainer()->get(RouterInterface::class);
    $app->options('/{routes:.*}', function (Request $request, Response $response) {
        // CORS Pre-Flight OPTIONS Request Handler
        return $response;
    });

    // ルート
    $app->group('', function (Group $group) use ($router) {

        // ダッシュボード
        $group->get('/', IndexDashboardAction::class)->setName('dashboard');
        $router->set('dashboard');

        // CSRF API for Javascript
        $group->get('/assets-csrf-js', CsrfJavaScriptAction::class)->setName('assets-csrf-js');
        $router->set('assets-csrf-js');

        // CSRF API for JSON
        $group->get('/api/v1/csrf', CsrfJsonAction::class)->setName('api-csrf-json');
        $router->set('api-csrf-json');

        // reCAPTCHA for JavaScript.
        $group->get('/assets-recaptcha-js', ReCaptchaJavaScriptAction::class)->setName('assets-recaptcha-js');
        $router->set('assets-recaptcha-js');

        // 最後のスラッシュを強制.
        $group->get('', function (Request $request, Response $response) use ($router) {
            return $router->redirect('dashboard', $request, $response);
        });
    });

    // メールフォーム
    $app->group('/post', function (Group $group) use ($settings, $router) {

        // ルートにPOSTされた場合、スキップ設定で自動振り分け
        $group->post(
            '',
            empty($settings->get('form')['IS_CONFIRM_SKIP']) ? ConfirmMailerAction::class : CompleteMailerAction::class
        );

        // 投稿画面
        $group->get('', IndexMailerAction::class)->setName('mailer');
        $router->set('mailer');

        // 確認画面
        $group->post('/confirm', ConfirmMailerAction::class)->setName('mailer.confirm');
        $router->set('mailer.confirm');

        // 完了画面
        $group->post('/complete', CompleteMailerAction::class)->setName('mailer.complete');
        $router->set('mailer.complete');

        // 最後のスラッシュを排除.
        $group->get('/', function (Request $request, Response $response) use ($router) {
            return $router->redirect('mailer', $request, $response);
        });
    });

    // ヘルスチェック
    $app->group('/health-check', function (Group $group) use ($router) {

        // 投稿画面
        $group->get('', IndexHealthCheckAction::class)->setName('health-check');
        $router->set('health-check');

        // 確認画面
        $group->post('/confirm', ConfirmHealthCheckAction::class)->setName('health-check.confirm');
        $router->set('health-check.confirm');

        // 完了画面
        $group->post('/result', ResultHealthCheckAction::class)->setName('health-check.result');
        $router->set('health-check.result');

        // 最後のスラッシュを排除.
        $group->get('/', function (Request $request, Response $response) use ($router) {
            return $router->redirect('health-check', $request, $response);
        });
    });
};
