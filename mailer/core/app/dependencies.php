<?php
/**
 * Mailer | el.kulo v1.0.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2021 A.Sudo
 * Licensed under MIT (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

use Psr\Container\ContainerInterface;
use App\Application\Handlers\Mail\MailHandlerInterface;
use App\Application\Handlers\Mail\WordPressHandler;
use App\Application\Handlers\Mail\PHPMailerHandler;
use App\Application\Handlers\DB\DBHandlerInterface;
use App\Application\Handlers\DB\MySQLHandler;
use App\Application\Handlers\DB\SQLiteHandler;

return [
    MailHandlerInterface::class => DI\factory(function (ContainerInterface $container) {
        switch (getenv('MAILER_TYPE')) {
            case 'WordPress':
                return new WordPressHandler();
                break;
            default:
                return new PHPMailerHandler($container->get('config'));
        }
    }),
    DBHandlerInterface::class => DI\factory(function (ContainerInterface $container) {
        switch (getenv('DB_CONNECTION')) {
            case 'MySQL':
                return new MySQLHandler($container->get('config'));
                break;
            case 'SQLite':
                return new SQLiteHandler($container->get('config'));
                break;
            default:
                return null;
        }
    }),
];
