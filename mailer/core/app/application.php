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
use App\Application\Handlers\DB\MySQLHandler;
use App\Application\Handlers\DB\SQLiteHandler;

return [
    'Mailer' => Factory(function (ContainerInterface $container) {
        return new Mailer($container->get('config'));
    }),
    'MailerAction' => Factory(function (ContainerInterface $container) {
        return new MailerAction(
            $container->get('logger'),
            $container->get('Mailer'),
            $container->get('ValidateHandler'),
            $container->get('ViewHandler'),
            $container->get('MailHandler'),
            $container->has('DBHandler') ? $container->get('DBHandler') : null
        );
    }),
    'ValidateHandler' => Factory(function (ContainerInterface $container) {
        return new ValidateHandler($container->get('config'));
    }),
    'ViewHandler' => Factory(function (ContainerInterface $container) {
        return new ViewHandler($container->get('config'));
    }),
    'MailHandler' => Factory(function () {
        switch (getenv('MAILER_TYPE')) {
            case 'WordPress':
                return new WordPressHandler();
                break;
            default:
                return new PHPMailerHandler();
        }
    }),
    'DBHandler' => Factory(function () {
        switch (getenv('DB_CONNECTION')) {
            case 'MySQL':
                return new MySQLHandler();
                break;
            case 'SQLite':
                return new SQLiteHandler();
                break;
            default:
                return new stdClass();
        }
    }),
];
