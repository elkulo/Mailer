<?php
declare(strict_types=1);

use DI\ContainerBuilder;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Monolog\Processor\WebProcessor;
use Monolog\Processor\MemoryUsageProcessor;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Slim\Csrf\Guard;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Views\Twig;
use Slim\Flash\Messages;
use App\Application\Settings\SettingsInterface;
use App\Application\Router\RouterInterface;
use App\Application\Router\Router;
use App\Application\Handlers\File\FileDataHandlerInterface;
use App\Application\Handlers\File\FileDataHandler;
use App\Application\Handlers\Validate\ValidateHandlerInterface;
use App\Application\Handlers\Validate\ValidateHandler;
use App\Application\Handlers\Mail\MailHandlerInterface;
use App\Application\Handlers\Mail\WordPressHandler;
use App\Application\Handlers\Mail\PHPMailerHandler;
use App\Application\Handlers\DB\DBHandlerInterface;
use App\Application\Handlers\DB\MySQLHandler;
use App\Application\Handlers\DB\SQLiteHandler;

return function (ContainerBuilder $containerBuilder) {
    $containerBuilder->addDefinitions([

        // ルーター.
        RouterInterface::class => \DI\autowire(Router::class),

        // ロガー.
        LoggerInterface::class => function (ContainerInterface $c) {
            $settings = $c->get(SettingsInterface::class);

            $loggerSettings = $settings->get('logger');
            $logger = new Logger($loggerSettings['name']);

            // メモリー情報を追加.
            $memory = new MemoryUsageProcessor();
            $logger->pushProcessor($memory);

            // Web情報を追加.
            $ip = new WebProcessor();
            $logger->pushProcessor($ip);

            // ユニークIDを追加.
            $processor = new UidProcessor();
            $logger->pushProcessor($processor);

            $handler = new StreamHandler($loggerSettings['path'], $loggerSettings['level']);
            $logger->pushHandler($handler);

            return $logger;
        },

        // クロスサイトリクエストフォージェリ.
        Guard::class => function () {
            $guard = new Guard(new ResponseFactory(), '_csrf');
            $guard->setPersistentTokenMode(true);
            return $guard;
        },

        // フラッシュメッセージ.
        Messages::class => \DI\autowire()->constructor([]),

        // ビューテンプレート.
        Twig::class => function (ContainerInterface $c) {
            $settings = $c->get(SettingsInterface::class);

            // テンプレートディレクトリをセット.
            $templatePath = [
                __DIR__ . '/../src/Views/mailer/templates',
                __DIR__ . '/../src/Views',
            ];
            if (file_exists($settings->get('templatesDirPath') . '/templates')) {
                array_unshift($templatePath, $settings->get('templatesDirPath') . '/templates');
            }
            $twig = Twig::create($templatePath, $settings->get('twig'));

            // Globalにフラッシュメッセージを設定.
            $twig->getEnvironment()->addGlobal('Flash', $c->get(Messages::class));

            // Globalにサイト情報を設定.
            $twig->getEnvironment()->addGlobal('__SITE_TITLE', $settings->get('siteTitle'));
            $twig->getEnvironment()->addGlobal('__SITE_URL', $settings->get('siteUrl'));
            return $twig;
        },

        // 画像アップローダーの登録.
        FileDataHandlerInterface::class => \DI\autowire(FileDataHandler::class),

        // 検証ハンドラーの登録.
        ValidateHandlerInterface::class => \DI\autowire(ValidateHandler::class),

        // メーラーハンドラーの登録.
        PHPMailerHandler::class => \DI\autowire(PHPMailerHandler::class),
        WordPressHandler::class => \DI\autowire(WordPressHandler::class),

        // メーラーハンドラーの選択.
        MailHandlerInterface::class => function (ContainerInterface $c) {
            $mailSettings = $c->get(SettingsInterface::class)->get('mail');
            $mailHandler = isset($mailSettings['MAILER_DRIVER'])? $mailSettings['MAILER_DRIVER']: 'PHPMailer';
            switch ($mailHandler) {
                case ('WordPress'):
                    return $c->get(WordPressHandler::class);
                    break;
                default:
                    return $c->get(PHPMailerHandler::class);
            }
        },

        // データベースハンドラーの登録.
        MySQLHandler::class => \DI\autowire(MySQLHandler::class),
        SQLiteHandler::class => \DI\autowire(SQLiteHandler::class),

        // データベースハンドラーの選択.
        DBHandlerInterface::class => function (ContainerInterface $c) {
            $dbSettings = $c->get(SettingsInterface::class)->get('database');
            $dbHandler = isset($dbSettings['DB_DRIVER'])? $dbSettings['DB_DRIVER']: null;
            switch ($dbHandler) {
                case ('MariaDB'):
                case ('MySQL'):
                    return $c->get(MySQLHandler::class);
                    break;
                case ('SQLite'):
                    return $c->get(SQLiteHandler::class);
                    break;
                default:
                    return null;
            }
        },
    ]);
};
