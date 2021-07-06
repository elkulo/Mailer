<?php
/**
 * Mailer | el.kulo v1.0.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2021 A.Sudo
 * Licensed under MIT (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);
require_once __DIR__ . '/app/vendor/autoload.php';

/************************************************************/

/**
 * WordPressの連携
 *
 * !! WordPress と連携する場合のみ書き換える !!
 *
 * wp-load.php を読み込むことでWordPressの関数が使用可能。
 * WordPressHandler に切り替えでメール送信を wp_mail() にする。
 * そのため、WordPressのSMTP等のプラグインとも連携が可能。
 */
// require_once __DIR__ . '/../../../../../../../wp-load.php';

/**
 * メーラーハンドラーを選択
 *
 * 例）WordPressのハンドラーに切り替える
 * PHPMailerHandler -> WordPressHandler
 */
// use App\Handlers\WordPressHandler as MailerHandler;
use App\Handlers\PHPMailerHandler as MailerHandler;

/**
 * DBハンドラーを選択
 *
 * 例）MySQLのハンドラーに切り替える
 * SQLiteHandler -> MySQLHandler
 */
// use App\Handlers\MySQLHandler as DBHandler;
use App\Handlers\SQLiteHandler as DBHandler;

/************************************************************/

use App\Actions\Mailer;
use Pimple\Container;
use Whoops\Run as Whoops;
use Whoops\Handler\Handler as WhoopsHandler;
use Whoops\Handler\PrettyPageHandler as WhoopsPageHandler;

(function () {

    try {

        // 設定ファイル(.env)までのパス.
        $env_path = __DIR__;

        // config ディレクトリまでのパス.
        $config_path = __DIR__ . '/config';

        // Config.
        Dotenv\Dotenv::create($env_path)->load();
        if (file_exists($config_path . '/server.php') && file_exists($config_path . '/setting.php')) {
            require_once $config_path . '/server.php';
            $config = include $config_path . '/setting.php';
        } else {
            throw new \Exception('Mailer Error: Not Config.');
        }
        date_default_timezone_set(TIME_ZONE);

        // Whoops.
        $debug_mode = getenv('MAILER_DEBUG') ? getenv('MAILER_DEBUG') : false;
        $whoops     = new Whoops();
        if ($debug_mode) {
            $whoops->pushHandler(new WhoopsPageHandler());
        } else {
            $whoops->pushHandler(
                function ($exception, $inspector, $run) {
                    // Error Log.
                    logger($exception->getMessage());
                    return WhoopsHandler::DONE;
                }
            );
        }
        $whoops->register();

        // DIコンテナー.
        $container = new Container();
        $container['MailerHandler'] = function () {
            return new MailerHandler;
        };
        $container['DBHandler'] = function () {
            return new DBHandler;
        };

        // Mailer.
        if ( isset($config) ) {
            $container['Mailer'] = function ($call) use ($config) {
                return new Mailer(
                    $call['MailerHandler'],
                    $call['DBHandler'],
                    $config
                );
            };
            $container['Mailer']->run();
        }
    } catch (\Exception $e) {
        exit($e->getMessage());
    }
})();
