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
        $debug_mode = getenv('DEBUG') ? getenv('DEBUG') : false;
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
    }),
];
