<?php
declare(strict_types=1);

use App\Application\Settings\SettingsInterface;
use DI\ContainerBuilder;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Slim\Views\Twig;

use App\Application\Handlers\Mail\MailHandlerInterface;
use App\Application\Handlers\Mail\WordPressHandler;
use App\Application\Handlers\Mail\PHPMailerHandler;
use App\Application\Handlers\DB\DBHandlerInterface;
use App\Application\Handlers\DB\MySQLHandler;
use App\Application\Handlers\DB\SQLiteHandler;
use App\Application\Middleware\Validate\ValidateMiddleware;
use App\Application\Middleware\Validate\ValidateMiddlewareInterface;
use App\Application\Middleware\View\ViewMiddleware;
use App\Application\Middleware\View\ViewMiddlewareInterface;

return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([
        LoggerInterface::class => function (ContainerInterface $c) {
            $settings = $c->get(SettingsInterface::class);

            $loggerSettings = $settings->get('logger');
            $logger = new Logger($loggerSettings['name']);

            $processor = new UidProcessor();
            $logger->pushProcessor($processor);

            $handler = new StreamHandler($loggerSettings['path'], $loggerSettings['level']);
            $logger->pushHandler($handler);

            return $logger;
        },
        Twig::class => function (ContainerInterface $c) {
            $settings = $c->get(SettingsInterface::class);
            return Twig::create(__DIR__ . '/../src/Views', $settings->get('twig'));
        },

        //
        MailHandlerInterface::class => function (ContainerInterface $c) {
            switch (getenv('MAILER_TYPE')) {
                case 'WordPress':
                    return new WordPressHandler();
                    break;
                default:
                    return new PHPMailerHandler($c->get('config'));
            }
        },
        DBHandlerInterface::class => function (ContainerInterface $c) {
            switch (getenv('DB_CONNECTION')) {
                case 'MySQL':
                    return new MySQLHandler($c->get('config'));
                    break;
                case 'SQLite':
                    return new SQLiteHandler($c->get('config'));
                    break;
                default:
                    return null;
            }
        },
        ValidateMiddlewareInterface::class => \DI\create(ValidateMiddleware::class),
        ViewMiddlewareInterface::class => \DI\create(ViewMiddleware::class),
    ]);
};
