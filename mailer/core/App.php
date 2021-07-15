<?php
/**
 * Mailer | el.kulo v1.0.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2021 A.Sudo
 * Licensed under MIT (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

(function (string $env_path): void {

    try {
        // config ディレクトリまでのパス.
        $config_path = __DIR__ . '/../config';

        // ルートパス.
        // phpcs:disable
        define('APP_PATH', __DIR__ . '/../');
        // phpcs:enable

        // 設定定数を取得.
        \Dotenv\Dotenv::create($env_path)->load();
        if (file_exists($config_path . '/server.php') && file_exists($config_path . '/setting.php')) {
            require_once $config_path . '/server.php';
            $config = include $config_path . '/setting.php';
        } else {
            throw new \Exception('Mailer Error: Not Config.');
        }
        date_default_timezone_set(TIME_ZONE);

        // DIコンテナー.
        $builder = new \DI\ContainerBuilder();
        $builder->addDefinitions(__DIR__ . '/app/dependencies.php');
        $builder->addDefinitions(__DIR__ . '/app/application.php');
        $app = $builder->build();

        // Configのセット.
        $app->set('config', $config);

        // Whoopsの開始.
        $app->get('whoops')->register();

        // Actionの開始.
        $app->call('MailerAction');
    } catch (\Exception $e) {
        $app->get('logger')->error($e->getMessage());
        exit($e->getMessage());
    }
})(isset($ENV_PATH) ? $ENV_PATH : __DIR__ . '/../'); // .envまでのパス.
