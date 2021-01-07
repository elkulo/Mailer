<?php
require_once __DIR__.'/vendor/autoload.php';

use Dotenv\Dotenv;
use Pidgeot\Core\Mailer;

(function () {

    // Config
    Dotenv::create(__DIR__)->load();
    date_default_timezone_set(getenv('TIME_ZONE'));
    require_once  __DIR__.'/config/server.php';
    require_once  __DIR__.'/config/draft.php';

    // Core
    $mailer = new Mailer($draft);
    $mailer->init();
})();
