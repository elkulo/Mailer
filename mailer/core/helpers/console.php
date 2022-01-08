<?php
/**
 * Mailer | el.kulo v3.2.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2022 A.Sudo
 * Licensed under LGPL-2.1-only (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

/**
 * Console.
 *
 * @param  string $message
 * @param  string|int $level
 * @return void
 */
function console($message, $level = 1): void
{
    switch ($level) {
        case 'error':
        case 4:
            \ChromePhp::error($message);
            break;
        case 'warning':
        case 'warn':
        case 3:
            \ChromePhp::warn($message);
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
