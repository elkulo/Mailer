<?php
/**
 * Mailer | el.kulo v1.0.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2021 A.Sudo
 * Licensed under MIT (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

use function DI\Factory;
use Psr\Container\ContainerInterface;
use Monolog\Logger as Monolog;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Processor\MemoryUsageProcessor;
use Monolog\Processor\WebProcessor;
use Whoops\Run as Whoops;
use Whoops\Handler\Handler as WhoopsHandler;
use Whoops\Handler\PrettyPageHandler as WhoopsPageHandler;
use App\Application\Handlers\Validate\ValidateHandler;
use App\Application\Handlers\View\ViewHandler;
use App\Application\Handlers\Mail\WordPressHandler;
use App\Application\Handlers\Mail\PHPMailerHandler;
use App\Application\Handlers\DB\MySQLHandler;
use App\Application\Handlers\DB\SQLiteHandler;
use App\Domain\Mailer;
use App\Application\Actions\MailerAction;

return [
    'logger' => Factory(function () {
        $monolog = new Monolog('mailer');

        // 書式.
        $date_format = 'Y-m-d H:i:s';
        $output      = '[%datetime%][%level_name%]> %message% : %context% : %extra%' . PHP_EOL;
        $formatter   = new LineFormatter($output, $date_format);
        $formatter->includeStacktraces(true);

        // 月単位でログを記録: RotatingFileHandler(<ログファイルへのパス>, <保存数>, <適用する最低レベル>).
        $rotating_file = new RotatingFileHandler(__DIR__ . '/../logs/mailer.log', 180, Monolog::DEBUG);
        $rotating_file->setFilenameFormat('{filename}-{date}-' . date('m-d'), 'Y');
        $rotating_file->setFormatter($formatter);
        $monolog->pushHandler($rotating_file);

        // メモリー使用量を記録.
        $monolog->pushProcessor(new MemoryUsageProcessor());

        // クライアント情報を記録.
        $monolog->pushProcessor(new WebProcessor());

        return $monolog;
    }),
    'whoops' => Factory(function (ContainerInterface $container) {
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
        $whoops->register();
        return $whoops;
    }),
    'MailHandler' => Factory(function () {
        switch (getenv('MAILER_TYPE')) {
            case 'WordPress':
                return new WordPressHandler();
                break;
            default:
                return new PHPMailerHandler();
        }
    }),
    'DBHandler' => Factory(function () {
        switch (getenv('DB_CONNECTION')) {
            case 'MySQL':
                return new MySQLHandler();
                break;
            case 'SQLite':
                return new SQLiteHandler();
                break;
            default:
                return new stdClass();
        }
    }),
    'ValidateHandler' => Factory(function (ContainerInterface $container) {
        return new ValidateHandler($container->get('config'));
    }),
    'ViewHandler' => Factory(function (ContainerInterface $container) {
        return new ViewHandler($container->get('config'));
    }),
    'Mailer' => Factory(function (ContainerInterface $container) {
        return new Mailer($container->get('config'));
    }),
    'MailerAction' => Factory(function (ContainerInterface $container) {
        return new MailerAction($container);
    }),
];
