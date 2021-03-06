<?php
/**
 * Mailer | el.kulo v1.0.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2021 A.Sudo
 * Licensed under MIT (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

/**
 * Chrome Logger.
 *
 * @param  string $message
 * @param  string|int $level
 * @return void
 */
function console(string $message, $level = 1): void
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
}
