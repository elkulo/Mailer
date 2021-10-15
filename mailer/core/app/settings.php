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
                'templatesDirPath' => rtrim(TEMPLATES_DIR_PATH, '/'),
                'settingsDirPath' => rtrim(SETTINGS_DIR_PATH, '/'),
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
                'database' => include rtrim(SETTINGS_DIR_PATH, '/') . '/settings/database.php',
                'form' => include rtrim(SETTINGS_DIR_PATH, '/') . '/settings/form.php',
                'mail' => include rtrim(SETTINGS_DIR_PATH, '/') . '/settings/mail.php',
                'validate' => include rtrim(SETTINGS_DIR_PATH, '/') . '/settings/validate.php',
            ]);
        }
    ]);
};
