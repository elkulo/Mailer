<?php
declare(strict_types=1);

// Chrome Logger.
function console($message, $level = 1)
{
    switch ($level) {
        case 'error':
        case 4:
            \ChromePhp::error($message);
            break;
        case 'warn':
        case 3:
            \ChromePhp::warn($message);
            break;
        case 'info':
        case 2:
            \ChromePhp::info($message);
            break;
        case 'log':
        case 1:
        default:
            \ChromePhp::log($message);
            break;
    }
    return;
}
