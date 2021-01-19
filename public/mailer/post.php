<?php
require_once __DIR__ . '/app/vendor/autoload.php';

use App\Application\Mailer;
use Dotenv\Dotenv;
use Whoops\Run as Whoops;
use Whoops\Handler\Handler as WhoopsHandler;
use Whoops\Handler\PrettyPageHandler as WhoopsPageHandler;

(function () {

    /**
     * 設定ファイル(.env)までのパス
     */
    $env_path = __DIR__;

    // Config
    Dotenv::create($env_path)->load();
    require_once  __DIR__ . '/config/server.php';
    require_once  __DIR__ . '/config/setting.php';
    date_default_timezone_set(TIME_ZONE);

    // Whoops
    $debug_mode = getenv('MAILER_DEBUG') ? getenv('MAILER_DEBUG') : false;
    $whoops     = new Whoops();
    if ($debug_mode) {
        $whoops->pushHandler(new WhoopsPageHandler());
    } else {
        $whoops->pushHandler(
            function ($exception, $inspector, $run) {
                // エラーログの出力.
                logger($exception->getMessage());
                return WhoopsHandler::DONE;
            }
        );
    }
    $whoops->register();

    // Mailer
    $mailer = new Mailer($setting);
    $mailer->init();
})();
