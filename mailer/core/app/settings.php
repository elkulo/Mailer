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

            // 定数を取得.
            $templatesDirPath = rtrim(TEMPLATES_DIR_PATH, '/'); /* @phpstan-ignore-line */
            $settingsDirPath = rtrim(SETTINGS_DIR_PATH, '/'); /* @phpstan-ignore-line */

            // サイト設定値.
            $site = include $settingsDirPath . '/site.php';

            // ログファイル.
            $logFile = isset($_ENV['docker']) ? 'php://stdout' : __DIR__ . '/../logs/app-' . date('Y-m-d') . '.log';

            return new Settings([
                'phpMinSupport' => '7.4.0',
                'appPath' => rtrim(__DIR__ . '/../', '/'),
                'siteTitle' => isset($site['SITE_TITLE'])? $site['SITE_TITLE']: 'Nameless',
                'siteUrl' => (empty($_SERVER['HTTPS']) ? 'http://' : 'https://') . rtrim($site['SITE_DOMAIN'], '/'),
                'siteLang' => isset($site['SITE_LANG'])? $site['SITE_LANG']: 'ja',
                'timeZone' => isset($site['TIME_ZONE'])? $site['TIME_ZONE']: 'asia/tokyo',
                'dateFormat' => isset($site['DATE_FORMAT'])? $site['DATE_FORMAT'] : 'Y/m/d (D) H:i:s',
                'templatesDirPath' => $templatesDirPath,
                'settingsDirPath' => $settingsDirPath,
                'debug' => isset($site['DEBUG']) ? $site['DEBUG'] : false,
                'displayErrorDetails' => isset($site['DEBUG']) ? $site['DEBUG'] : false,
                'logError'            => isset($site['DEBUG']) ? ! $site['DEBUG'] : true,
                'logErrorDetails'     => isset($site['DEBUG']) ? ! $site['DEBUG'] : true,
                'logger' => [
                    'name' => 'mailer',
                    'path' => $logFile,
                    'level' => Logger::DEBUG,
                ],
                'twig' => [
                    'debug' => isset($site['DEBUG']) ? $site['DEBUG'] === 'true' : false,
                    'auto_reload' => isset($site['DEBUG']) ? $site['DEBUG'] === 'true' : false,
                    'strict_variables' => isset($site['DEBUG']) ? $site['DEBUG'] === 'true' : false,
                    //'cache' => __DIR__ . '/../var/cache/twig',
                    'cache' => false,
                ],
                'database' => include $settingsDirPath . '/database.php',
                'form' => include $settingsDirPath . '/form.php',
                'mail' => include $settingsDirPath . '/mail.php',
                'validate' => include $settingsDirPath . '/validate.php',
            ]);
        }
    ]);
};
