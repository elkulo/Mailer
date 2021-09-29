<?php
declare(strict_types=1);

use DI\ContainerBuilder;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Slim\Csrf\Guard;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Views\Twig;
use Slim\Flash\Messages;
use App\Application\Settings\SettingsInterface;
use App\Application\Handlers\Mail\MailHandler;
use App\Application\Handlers\Mail\WordPressHandler;
use App\Application\Handlers\Mail\PHPMailerHandler;
use App\Application\Handlers\DB\DBHandler;
use App\Application\Handlers\DB\MySQLHandler;
use App\Application\Handlers\DB\SQLiteHandler;
use App\Application\Handlers\Validate\ValidateHandler;

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
        Guard::class => function () {
            $guard = new Guard(new ResponseFactory(), '_csrf');
            $guard->setPersistentTokenMode(true);
            return $guard;
        },
        Messages::class => function () {
            $storage = [];
            return new Messages($storage);
        },
        Twig::class => function (ContainerInterface $c) {
            $settings = $c->get(SettingsInterface::class);
            $twig = Twig::create(
                [
                    __DIR__ . '/../../',
                    __DIR__ . '/../src/Views',
                ],
                $settings->get('twig')
            );
            // Globalにフラッシュメッセージを設定.
            $twig->getEnvironment()->addGlobal('flash', $c->get(Messages::class));
            return $twig;
        },
        MailHandler::class => \DI\autowire(PHPMailerHandler::class),
        DBHandler::class => \DI\autowire(SQLiteHandler::class),
        ValidateHandler::class => \DI\autowire(ValidateHandler::class),
    ]);
};
