<?php
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
// use App\Handler\WordPressHandler as MailerHandler;
use App\Handler\PHPMailerHandler as MailerHandler;

/************************************************************/

use App\Application\Mailer;
use Whoops\Run as Whoops;
use Whoops\Handler\Handler as WhoopsHandler;
use Whoops\Handler\PrettyPageHandler as WhoopsPageHandler;

(function () {

    // 設定ファイル(.env)までのパス.
    $env_path = __DIR__;

    // Config.
    Dotenv\Dotenv::create($env_path)->load();
    require_once  __DIR__ . '/config/server.php';
    $config = require_once __DIR__ . '/config/setting.php';
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

    // Mailer.
    $mailer = new Mailer(new MailerHandler, $config);
    $mailer->run();
})();
