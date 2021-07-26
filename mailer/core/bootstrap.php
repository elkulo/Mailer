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
        // 環境変数を取得.
        \Dotenv\Dotenv::create($env_path)->load();

        // タイムゾーン.
        if (getenv('TIME_ZONE')) {
            date_default_timezone_set(getenv('TIME_ZONE'));
        }

        // DIコンテナー.
        $builder = new \DI\ContainerBuilder();

        // config ディレクトリまでのパス.
        $config_path = __DIR__ . '/../config';
        if (file_exists($config_path . '/server.php') && file_exists($config_path . '/setting.php')) {
            // 定数.
            $config = [
                'config' => [
                    'server' => include $config_path . '/server.php',
                    'setting' => include $config_path . '/setting.php',
                    'app.path' => __DIR__, // ルートパス
                ]
            ];
        } else {
            throw new \Exception('Mailer Error: Not Config.');
        }
        $builder->addDefinitions($config);
        $builder->addDefinitions(__DIR__ . '/app/dependencies.php');
        $builder->addDefinitions(__DIR__ . '/app/handlers.php');
        $builder->addDefinitions(__DIR__ . '/app/middleware.php');
        $builder->addDefinitions(__DIR__ . '/app/repositories.php');
        $app = $builder->build();

        // Whoopsの開始.
        $app->get(Whoops\Run::class)->register();

        // Action の開始.
        $app->call(App\Application\Actions\MailerAction::class);
    } catch (\Exception $e) {
        $app->get('logger')->error($e->getMessage());
        exit($e->getMessage());
    }
})(isset($ENV_PATH) ? $ENV_PATH : __DIR__ . '/../'); // .envまでのパス.
