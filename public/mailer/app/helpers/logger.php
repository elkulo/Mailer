<?php

declare(strict_types=1);

use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;

// Monolog.
function logger($message, $level = 1)
{
    $logfile = new RotatingFileHandler(dirname(__DIR__) . '/logs/send.log', 31);
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
            $logs->error($message);
            break;
        case 'warning':
        case 'warn':
        case 4:
            $logs->warning($message);
            break;
        case 'notice':
        case 3:
            $logs->notice($message);
            break;
        case 'info':
        case 2:
            $logs->info($message);
            break;
        case 'debug':
        case 'dump':
        case 1:
        default:
            $logs->debug($message);
            break;
    }
    return;
}
