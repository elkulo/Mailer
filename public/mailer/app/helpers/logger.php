<?php
declare(strict_types=1);

use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;

/**
 * Monolog
 *
 * @param  string $message
 * @param  array  $content
 * @param  string|int $level
 * @return void
 */
function logger(string $message, array $content = array(), $level = 1): void
{
    $logfile = new RotatingFileHandler(__DIR__ . '/../logs/send.log', 31);
    $logs = new Logger('send');
    $logs->pushHandler($logfile);

    $logs->pushProcessor(function ($record) {
        $record['extra']['host'] = getHostByAddr($_SERVER['REMOTE_ADDR']);
        $record['extra']['ip'] = $_SERVER['REMOTE_ADDR'];
        return $record;
    });

    switch ($level) {
        case 'error':
        case 5:
            $logs->error($message, $content);
            break;
        case 'warning':
        case 'warn':
        case 4:
            $logs->warning($message, $content);
            break;
        case 'notice':
        case 3:
            $logs->notice($message, $content);
            break;
        case 'info':
        case 2:
            $logs->info($message, $content);
            break;
        case 'debug':
        case 'dump':
        case 1:
        default:
            $logs->debug($message, $content);
            break;
    }
}
