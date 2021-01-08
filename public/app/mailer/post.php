<?php
require_once __DIR__.'/vendor/autoload.php';

use Dotenv\Dotenv;
use App\Mailer\Core\Mailer;

(function () {

    // Config
    Dotenv::create(__DIR__)->load();
    date_default_timezone_set(getenv('TIME_ZONE'));
    require_once  __DIR__.'/config/server.php';
    require_once  __DIR__.'/config/draft.php';
    require_once  __DIR__.'/config/setting.php';

    // Core
    $mailer = new Mailer($setting);
    $mailer->init();
})();
