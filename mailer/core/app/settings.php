<?php
declare(strict_types=1);

use DI\ContainerBuilder;
use Monolog\Logger;
use App\Application\Settings\Settings;
use App\Application\Settings\SettingsInterface;

return function (ContainerBuilder $containerBuilder) {

    // サイト設定値
    $site = include rtrim(SETTINGS_DIR_PATH, '/') . '/settings/site.php';

    // Global Settings Object
    $containerBuilder->addDefinitions([
        SettingsInterface::class => function () use ($site) {

            $log_file = isset($_ENV['docker']) ? 'php://stdout' : __DIR__ . '/../logs/app-' . date("Y-m-d") . '.log';

            return new Settings([
                'phpMinSupport' => '7.4.0',
                'appPath' => __DIR__ . '/../',
                'siteTitle' => $site['SITE_TITLE'],
                'siteUrl' => (empty($_SERVER['HTTPS']) ? 'http://' : 'https://') . rtrim($site['SITE_DOMAIN'], '/'),
                'siteLang' => $site['SITE_LANG'],
                'timeZone' => $site['TIME_ZONE'],
                'templatesDirPath' => rtrim(TEMPLATES_DIR_PATH, '/'),
                'settingsDirPath' => rtrim(SETTINGS_DIR_PATH, '/'),
                'debug' => isset($site['DEBUG']) ? $site['DEBUG'] : false,
                // Should be set to false in production
                'displayErrorDetails' => isset($site['DEBUG']) ? $site['DEBUG'] : false,
                'logError'            => isset($site['DEBUG']) ? ! $site['DEBUG'] : true,
                'logErrorDetails'     => isset($site['DEBUG']) ? ! $site['DEBUG'] : true,
                'logger' => [
                    'name' => 'slim-app',
                    'path' => $log_file,
                    'level' => Logger::DEBUG,
                ],
                'twig' => [
                    'debug' => isset($site['DEBUG']) ? $site['DEBUG'] === 'true' : false,
                    'strict_variables' => true,
                    //'cache' => __DIR__ . '/../var/cache/twig',
                    'cache' => false,
                    'auto_reload' => true
                ],
                'database' => include rtrim(SETTINGS_DIR_PATH, '/') . '/settings/database.php',
                'form' => include rtrim(SETTINGS_DIR_PATH, '/') . '/settings/form.php',
                'mail' => include rtrim(SETTINGS_DIR_PATH, '/') . '/settings/mail.php',
                'validate' => include rtrim(SETTINGS_DIR_PATH, '/') . '/settings/validate.php',
                'test' => DI\get('db.host')
            ]);
        }
    ]);

    // Should be set to true in production
    if (isset($site['DEBUG']) ? ! $site['DEBUG'] : false) {
        $containerBuilder->enableCompilation(__DIR__ . '/../var/cache');
    }
};
