<?php
/**
 * Mailer | el.kulo v1.0.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2021 A.Sudo
 * Licensed under MIT (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

namespace App\Application\Handlers\DB;

use Illuminate\Database\Capsule\Manager;

/**
 * SQLiteHandler
 */
class SQLiteHandler implements DBHandlerInterface
{

    /**
     * データベース
     *
     * @var object
     */
    public object $db;

    /**
     * テーブル名
     *
     * @var string
     */
    public string $table_name;

    /**
     * Database ディレクトリ
     *
     * @var string
     */
    private string $database_dir = APP_PATH . '/database';

    /**
     * DBを作成
     *
     * @return void
     */
    public function __construct()
    {
        $prefix = getenv('DB_PREFIX') ? strtolower(getenv('DB_PREFIX')) : '';

        $this->table_name = $prefix . 'mailer';

        // DB作成
        $sqlite_file = $this->make($this->database_dir . '/', getenv('DB_DATABASE'));

        // DB設定
        $this->db = new Manager();

        // 接続情報
        $config = [
            'driver'    => 'sqlite',
            'database'  => $sqlite_file,
            'prefix' => $prefix,
        ];

        // コネクションを追加
        $this->db->addConnection($config);

        // グローバルで(staticで)利用できるようにする宣言
        $this->db->setAsGlobal();

        // Eloquentを有効にする
        $this->db->bootEloquent();
    }

    /**
     * DBに保存
     *
     * @param  bool   $success
     * @param  string $email
     * @param  string $subject
     * @param  string $body
     * @param  array  $status
     * @return bool
     */
    final public function save(array $success, string $email, string $subject, string $body, array $status): bool
    {
        $values = [
            'email' => $email,
            'subject' => $subject,
            'body' => $body,
            '_success' => json_encode($success),
            '_date' => $status['_date'],
            '_ip' => $status['_ip'],
            '_host' => $status['_host'],
            '_url' => $status['_url'],
        ];
        $this->db->table('mailer')->insert($values);
        return true;
    }

    /**
     * DBを作成
     *
     * @param  string $dir_path
     * @param  string $file
     * @return string
     */
    public function make(string $dir_path, string $file): string
    {
        try {
            // DBディレクトリの確認
            if (!file_exists($dir_path)) {
                mkdir($dir_path, 0777);
            }

            // DBファイルの確認
            $sqlite_file = $dir_path . $file;
            if (!file_exists($sqlite_file)) {
                $pdo = new \PDO('sqlite:' . $sqlite_file);

                // SQL実行時にもエラーの代わりに例外を投げるように設定
                // (毎回if文を書く必要がなくなる)
                $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

                // デフォルトのフェッチモードを連想配列形式に設定
                // (毎回PDO::FETCH_ASSOCを指定する必要が無くなる)
                $pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

                // テーブル作成
                $pdo->exec("CREATE TABLE IF NOT EXISTS {$this->table_name} (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    email VARCHAR(50),
                    subject VARCHAR(50),
                    body VARCHAR(50),
                    _success VARCHAR(50),
                    _date VARCHAR(50),
                    _ip VARCHAR(50),
                    _host VARCHAR(50),
                    _url VARCHAR(50)
                )");

                // 一度閉じる.
                $pdo = null;
            }
        } catch (\Exception $e) {
            exit;
        }
        return $sqlite_file;
    }
}
