<?php
/**
 * Mailer | el.kulo v1.0.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2021 A.Sudo
 * Licensed under MIT (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

use function DI\Factory;
use Psr\Container\ContainerInterface;
use App\Domain\Mailer;
use App\Application\Actions\MailerAction;
use App\Application\Handlers\Validate\ValidateHandler;
use App\Application\Handlers\View\ViewHandler;
use App\Application\Handlers\Mail\WordPressHandler;
use App\Application\Handlers\Mail\PHPMailerHandler;
use App\Application\Handlers\Mail\SendMailHandler;
use App\Application\Handlers\DB\MySQLHandler;
use App\Application\Handlers\DB\SQLiteHandler;

use App\Application\Handlers\Validate\ValidateHandlerInterface;
use App\Application\Handlers\View\ViewHandlerInterface;
use App\Application\Handlers\Mail\MailHandlerInterface;
use App\Application\Handlers\Mail\MailReserveInterface;
use App\Application\Handlers\DB\DBHandlerInterface;

return [
    Mailer::class => Factory(function (ContainerInterface $container) {
        return new Mailer($container->get('config'));
    }),
    MailerAction::class => Factory(function (ContainerInterface $container) {
        return new MailerAction(
            $container->get('logger'),
            $container->get(Mailer::class),
            $container->get(ValidateHandlerInterface::class),
            $container->get(ViewHandlerInterface::class),
            $container->get(MailHandlerInterface::class),
            $container->get(DBHandlerInterface::class),
            $container->get(MailReserveInterface::class)
        );
    }),
    ValidateHandlerInterface::class => Factory(function (ContainerInterface $container) {
        return new ValidateHandler($container->get('config'));
    }),
    ViewHandlerInterface::class => Factory(function (ContainerInterface $container) {
        return new ViewHandler($container->get('config'));
    }),
    MailHandlerInterface::class => Factory(function (ContainerInterface $container) {
        switch (getenv('MAILER_TYPE')) {
            case 'WordPress':
                return new WordPressHandler();
                break;
            default:
                return new PHPMailerHandler($container->get('config'));
        }
    }),
    DBHandlerInterface::class => Factory(function (ContainerInterface $container) {
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
    MailReserveInterface::class => Factory(function (ContainerInterface $container) {
        return new SendMailHandler($container->get('config'));
    }),
];
