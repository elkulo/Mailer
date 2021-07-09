<?php
/**
 * Mailer | el.kulo v1.0.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2021 A.Sudo
 * Licensed under MIT (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

require_once __DIR__ . '/app/vendor/autoload.php';

use App\Application\Actions\MailerAction;
use App\Application\Handlers\ValidateHandler;
use App\Application\Handlers\ViewHandler;
use App\Application\Handlers\WordPressHandler;
use App\Application\Handlers\PHPMailerHandler;
use App\Application\Handlers\MySQLHandler;
use App\Application\Handlers\SQLiteHandler;
use DI\Container;
use Monolog\Logger as Monolog;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Processor\MemoryUsageProcessor;
use Monolog\Processor\WebProcessor;
use Whoops\Run as Whoops;
use Whoops\Handler\Handler as WhoopsHandler;
use Whoops\Handler\PrettyPageHandler as WhoopsPageHandler;

(function (string $env_path):void {

    try {

        // DIコンテナー.
        $container = new Container();

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

        // Logger.
        $container->set('logger', function () {
            $monolog = new Monolog('mailer');

            // 書式.
            $date_format = 'Y-m-d H:i:s';
            $output      = '[%datetime%][%level_name%]> %message% : %context% : %extra%' . PHP_EOL;
            $formatter   = new LineFormatter($output, $date_format);
            $formatter->includeStacktraces(true);

            // 月単位でログを記録: RotatingFileHandler(<ログファイルへのパス>, <保存数>, <適用する最低レベル>).
            $rotating_file = new RotatingFileHandler(__DIR__ . '/app/logs/mailer.log', 180, Monolog::DEBUG);
            $rotating_file->setFilenameFormat('{filename}-{date}-' . date('m-d'), 'Y');
            $rotating_file->setFormatter($formatter);
            $monolog->pushHandler($rotating_file);

            // メモリー使用量を記録.
            $monolog->pushProcessor(new MemoryUsageProcessor());

            // クライアント情報を記録.
            $monolog->pushProcessor(new WebProcessor());

            return $monolog;
        });
        
        // Whoops.
        $container->set('whoops', function ($container) {
            $debug_mode = getenv('MAILER_DEBUG') ? getenv('MAILER_DEBUG') : false;
            $whoops     = new Whoops();
            if ($debug_mode) {
                $whoops->pushHandler(new WhoopsPageHandler());
            } else {
                $whoops->pushHandler(
                    function ($exception) use ($container) {
                        $container->get('logger')->error($exception->getMessage());
                        return WhoopsHandler::DONE;
                    }
                );
            }
            return $whoops;
        });
        $container->get('whoops')->register();

        // Handlerの選択.
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

        // Actionの開始.
        if (isset($config)) {
            $container->set('ValidateHandler', new ValidateHandler($config));
            $container->set('ViewHandler', new ViewHandler($config));
            $mailer = new MailerAction(
                $container->get('logger'),
                $container->get('MailHandler'),
                $container->get('ValidateHandler'),
                $container->get('ViewHandler'),
                $container->has('DBHandler')? $container->get('DBHandler'): null,
                $config
            );
            if ( ! $mailer->action() ) {
                throw new \Exception('MailerAction Error.');
            }
        }
    } catch (\Exception $e) {
        $container->get('logger')->error($e->getMessage());
        exit($e->getMessage());
    }

})( isset( $ENV_PATH )? $ENV_PATH : __DIR__ ); // .envまでのパス.
