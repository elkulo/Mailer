<?php
declare(strict_types=1);

use App\Application\Settings\Settings;
use App\Application\Settings\SettingsInterface;
use DI\ContainerBuilder;
use Monolog\Logger;
use Dotenv\Dotenv;

return function (ContainerBuilder $containerBuilder) {

    // Dotenv
    $env = __DIR__ . '/../../.env';
    try {
        if (is_readable($env)) {
            $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
            $dotenv->load();
        } else {
            throw new Exception('環境設定ファイルがありません');
        }
    } catch (Exception $e) {
        exit($e->getMessage());
    }

    // Timezone
    if (isset($_ENV['TIME_ZONE'])) {
        date_default_timezone_set($_ENV['TIME_ZONE']);
    }

    // Global Settings Object
    $containerBuilder->addDefinitions([
        SettingsInterface::class => function () {
            return new Settings([
                'displayErrorDetails' => true, // Should be set to false in production
                'logError'            => false,
                'logErrorDetails'     => false,
                'logger' => [
                    'name' => 'slim-app',
                    'path' => isset($_ENV['docker']) ? 'php://stdout' : __DIR__ . '/../logs/app.log',
                    'level' => Logger::DEBUG,
                ],
                'twig' => [
                    'debug' => isset($_ENV['DEBUG']) ? $_ENV['DEBUG'] : false,
                    'strict_variables' => true,
                    'cache' => __DIR__ . '/../var/cache/twig',
                ],
                'debug' => getenv('DEBUG') ? getenv('DEBUG') : false,
                'healh.check' => getenv('HEALTH_CHECK')
            ]);
        }
    ]);
};
