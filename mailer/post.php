<?php
/**
 * Mailer | el.kulo v1.0.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2021 A.Sudo
 * Licensed under MIT (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

require_once __DIR__ . '/app/vendor/autoload.php';

use App\Actions\Mailer;
use App\Actions\ValidateAction;
use App\Actions\ViewAction;
use App\Handlers\WordPressHandler;
use App\Handlers\PHPMailerHandler;
use App\Handlers\MySQLHandler;
use App\Handlers\SQLiteHandler;
use DI\Container;
use Whoops\Run as Whoops;
use Whoops\Handler\Handler as WhoopsHandler;
use Whoops\Handler\PrettyPageHandler as WhoopsPageHandler;

(function (string $env_path): void {

    try {

        // config ディレクトリまでのパス.
        $config_path = __DIR__ . '/config';

        // Config.
        Dotenv\Dotenv::create($env_path)->load();
        if (file_exists($config_path . '/server.php') && file_exists($config_path . '/setting.php')) {
            require_once $config_path . '/server.php';
            $config = include $config_path . '/setting.php';
        } else {
            throw new \Exception('Mailer Error: Not Config.');
        }
        date_default_timezone_set(TIME_ZONE);

        // Whoops.
        $debug_mode = getenv('MAILER_DEBUG') ? getenv('MAILER_DEBUG') : false;
        $whoops     = new Whoops();
        if ($debug_mode) {
            $whoops->pushHandler(new WhoopsPageHandler());
        } else {
            $whoops->pushHandler(
                function ($exception, $inspector, $run) {
                    // Error Log.
                    logger($exception->getMessage());
                    return WhoopsHandler::DONE;
                }
            );
        }
        $whoops->register();

        // DIコンテナー.
        $container = new Container();

        // ハンドラーの選択.
        switch (getenv('MAILER_TYPE')) {
            case 'WordPress':
                $container->set('MailHandler', new WordPressHandler());
                break;
            default:
                $container->set('MailHandler', new PHPMailerHandler());
        }
        switch (getenv('DB_CONNECTION')) {
            case 'MySQL':
                $container->set('DBHandler', new MySQLHandler());
                break;
            case 'SQLite':
                $container->set('DBHandler', new SQLiteHandler());
                break;
        }

        // Mailer.
        if (isset($config)) {
            $container->set('Validate', new ValidateAction($config));
            $container->set('View', new ViewAction($config));
            $mailer = new Mailer(
                $container->get('MailHandler'),
                $container->get('Validate'),
                $container->get('View'),
                $container->has('DBHandler')? $container->get('DBHandler'): null,
                $config
            );
            $mailer->run();
        }
    } catch (\Exception $e) {
        exit($e->getMessage());
    }

})( isset( $ENV_PATH )? $ENV_PATH : __DIR__ ); // .envまでのパス.
