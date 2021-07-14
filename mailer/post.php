<?php
/**
 * Mailer | el.kulo v1.0.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2021 A.Sudo
 * Licensed under MIT (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

require_once __DIR__ . '/app/vendor/autoload.php';

(function (string $env_path): void {

    try {

        // config ディレクトリまでのパス.
        $config_path = __DIR__ . '/config';

        // ルートパス.
        define('APP_PATH', __DIR__);

        // 設定定数を取得.
        Dotenv\Dotenv::create($env_path)->load();
        if (file_exists($config_path . '/server.php') && file_exists($config_path . '/setting.php')) {
            require_once $config_path . '/server.php';
            $config = include $config_path . '/setting.php';
        } else {
            throw new \Exception('Mailer Error: Not Config.');
        }
        date_default_timezone_set(TIME_ZONE);

        // DIコンテナー.
        $builder = new DI\ContainerBuilder();
        $builder->addDefinitions( APP_PATH . '/app/app/di-config.php' );
        $container = $builder->build();

        // Configのセット.
        $container->set('config', $config);

        // Whoopsの開始.
        $container->get('whoops')->register();

        // Actionの開始.
        $container->get('MailerAction')();
    } catch (\Exception $e) {
        $container->get('logger')->error($e->getMessage());
        exit($e->getMessage());
    }
})(isset($ENV_PATH) ? $ENV_PATH : __DIR__); // .envまでのパス.
