<?php
/**
 * Mailer | el.kulo v1.0.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2021 A.Sudo
 * Licensed under MIT (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

namespace App\Handlers;

use Illuminate\Database\Capsule\Manager;
use App\Interfaces\DBHandlerInterface;

/**
 * SQLiteHandler
 */
class SQLiteHandler implements DBHandlerInterface
{

    public $db;

    public function __construct()
    {
        //\ORM::configure('sqlite:' . __DIR__ . '/database/example.db');
        //\ORM::configure('id_column', getenv('DB_PREFIX'));
        //$db = \ORM::get_db();
        //dump($db);

        /*
        // DB設定
        $this->db = new Manager();

        // 接続情報
        $config = [
            'driver'    => strtolower( getenv('DB_CONNECTION') ),   // mysql, pgsql, sqlite, sqlsrv から選択可
            'host'      => getenv('DB_HOST'),         // ホスト名
            'database'  => getenv('DB_DATABASE'),     // データベース名
            'username'  => getenv('DB_USERNAME'),     // ユーザー名
            'password'  => getenv('DB_PASSWORD'),     // パスワード
            'prefix'    => getenv('DB_PREFIX'),
            'charset'   => 'utf8mb4',                 // 文字セット
            'collation' => 'utf8mb4_general_ci',      // コレーション
        ];
        // コネクションを追加
        $this->db->addConnection($config);

        // グローバルで(staticで)利用できるようにする宣言
        $this->db->setAsGlobal();

        // Eloquentを有効にする
        $this->db->bootEloquent();
        */
    }

    /**
     * DBに保存
     *
     * @param  array $content
     * @return bool
     */
    final public function save(array $content): bool
    {
        //$this->db::table('users')->insert($content);
        return true;
    }
}
