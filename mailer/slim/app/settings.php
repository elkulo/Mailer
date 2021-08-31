<?php
declare(strict_types=1);

use App\Application\Settings\Settings;
use App\Application\Settings\SettingsInterface;
use DI\ContainerBuilder;
use Monolog\Logger;

return function (ContainerBuilder $containerBuilder) {

    // Global Settings Object
    $containerBuilder->addDefinitions([
        SettingsInterface::class => function () {

            // Config
            $config_path = __DIR__ . '/../../config';
            $config = [
                'server' => include $config_path . '/server.php',
                'setting' => include $config_path . '/setting.php',
                'app.path' => __DIR__ . '/../', // ルートパス
            ];

            $log_file = isset($_ENV['docker']) ? 'php://stdout' : __DIR__ . '/../logs/app-' . date("Y-m-d") . '.log';

            return new Settings([
                'displayErrorDetails' => true, // Should be set to false in production
                'logError'            => false,
                'logErrorDetails'     => false,
                'logger' => [
                    'name' => 'slim-app',
                    'path' => $log_file,
                    'level' => Logger::DEBUG,
                ],
                'twig' => [
                    'debug' => isset($_ENV['DEBUG']) ? $_ENV['DEBUG'] : false,
                    'strict_variables' => true,
                    'cache' => __DIR__ . '/../var/cache/twig',
                ],
                'debug' => isset($_ENV['DEBUG']) ? $_ENV['DEBUG'] : false,
                'healh.check' => getenv('HEALTH_CHECK'),
                'config' => $config
            ]);
        }
    ]);
};
