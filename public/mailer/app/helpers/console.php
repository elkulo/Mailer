<?php

declare(strict_types=1);

// Chrome Logger.
function console($message, $level = 1)
{
    switch ($level) {
        case 'error':
        case 5:
            \ChromePhp::error($message);
            break;
        case 'warning':
        case 'warn':
        case 4:
            \ChromePhp::warn($message);
            break;
        case 'notice':
        case 3:
            \ChromePhp::info($message);
            break;
        case 'info':
        case 2:
            \ChromePhp::info($message);
            break;
        case 'debug':
        case 'dump':
        case 'log':
        case 1:
        default:
            \ChromePhp::log($message);
            break;
    }
    return;
}
