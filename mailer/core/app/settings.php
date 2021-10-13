<?php
declare(strict_types=1);

use DI\ContainerBuilder;
use Monolog\Logger;
use App\Application\Settings\Settings;
use App\Application\Settings\SettingsInterface;

return function (ContainerBuilder $containerBuilder) {

    // Global Settings Object
    $containerBuilder->addDefinitions([
        SettingsInterface::class => function () {

            $log_file = isset($_ENV['docker']) ? 'php://stdout' : __DIR__ . '/../logs/app-' . date("Y-m-d") . '.log';

            return new Settings([
                'phpMinSupport' => '7.4.0',
                'appPath' => __DIR__ . '/../',
                'settingsDirPath' => __DIR__ . '/../../settings',
                'templatesDirPath' => __DIR__ . '/../../templates',
                'debug' => isset($_ENV['DEBUG']) ? $_ENV['DEBUG'] === 'true' : false,
                // Should be set to false in production
                'displayErrorDetails' => isset($_ENV['DEBUG']) ? $_ENV['DEBUG'] === 'true' : false,
                'logError'            => isset($_ENV['DEBUG']) ? $_ENV['DEBUG'] === 'false' : true,
                'logErrorDetails'     => isset($_ENV['DEBUG']) ? $_ENV['DEBUG'] === 'false' : true,
                'logger' => [
                    'name' => 'slim-app',
                    'path' => $log_file,
                    'level' => Logger::DEBUG,
                ],
                'twig' => [
                    'debug' => isset($_ENV['DEBUG']) ? $_ENV['DEBUG'] === 'true' : false,
                    'strict_variables' => true,
                    //'cache' => __DIR__ . '/../var/cache/twig',
                    'cache' => false,
                    'auto_reload' => true
                ],
                'database' => include __DIR__ . '/../../settings/database.php',
                'form' => include __DIR__ . '/../../settings/form.php',
                'mail' => include __DIR__ . '/../../settings/mail.php',
                'validate' => include __DIR__ . '/../../settings/validate.php',
            ]);
        }
    ]);
};
