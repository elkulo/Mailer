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
    if (is_readable($env)) {
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
        $dotenv->load();
    } else {
        throw new \Exception('環境設定ファイルがありません');
    }

    // Timezone
    if (isset($_ENV['TIME_ZONE'])) {
        date_default_timezone_set($_ENV['TIME_ZONE']);
    }

    // Config
    $config_path = __DIR__ . '/../../config';
    if (file_exists($config_path . '/server.php') && file_exists($config_path . '/setting.php')) {
        // 定数.
        $config = [
            'config' => [
                'server' => include $config_path . '/server.php',
                'setting' => include $config_path . '/setting.php',
                'app.path' => __DIR__ . '/../', // ルートパス
            ]
        ];
    } else {
        throw new \Exception('Mailer Error: Not Config.');
    }
    $containerBuilder->addDefinitions($config);

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
                'debug' => isset($_ENV['DEBUG']) ? $_ENV['DEBUG'] : false,
                'healh.check' => getenv('HEALTH_CHECK')
            ]);
        }
    ]);
};
