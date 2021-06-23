<?php
/**
 * Mailer | el.kulo v1.0.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2021 A.Sudo
 * Licensed under MIT (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

use Monolog\Logger as Monolog;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Processor\MemoryUsageProcessor;
use Monolog\Processor\WebProcessor;

/**
 * Monolog
 *
 * @param  string $message
 * @param  string|int $level
 * @param  array  $content
 * @return void
 */
function logger(string $message, $level = 1, array $content = array()): void
{
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

    switch ($level) {
        case 'error':
        case 5:
            $monolog->error($message, $content);
            break;
        case 'warning':
        case 'warn':
        case 4:
            $monolog->warning($message, $content);
            break;
        case 'notice':
        case 3:
            $monolog->notice($message, $content);
            break;
        case 'info':
        case 2:
            $monolog->info($message, $content);
            break;
        case 'debug':
        case 'dump':
        case 1:
        default:
            $monolog->debug($message, $content);
            break;
    }
}
