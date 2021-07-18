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
 * MySQLHandler
 */
class MySQLHandler implements DBHandlerInterface
{

    /**
     * サーバー設定
     *
     * @var array
     */
    private array $server;

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
     * DBを作成
     *
     * @param  array $config
     * @return void
     */
    public function __construct(array $config)
    {
        try {
            $this->server = $config['server'];

            // DBテーブル名
            $prefix = $this->server['DB']['PREFIX'] ? strtolower($this->server['DB']['PREFIX']) : '';
            $this->table_name = $prefix . 'mailer';

            // DBを作成
            $this->make();

            // DB設定
            $this->db = new Manager();

            // 接続情報
            $config = [
                'driver'    => 'mysql',
                'host'      => $this->server['DB']['HOST'],
                'database'  => $this->server['DB']['DATABASE'],
                'username'  => $this->server['DB']['USERNAME'],
                'password'  => $this->server['DB']['PASSWORD'],
                'charset'   => $this->server['DB']['CHARSET'],
                'collation' => $this->server['DB']['COLLATION'],
                'prefix' => $prefix,
            ];

            // コネクションを追加
            $this->db->addConnection($config);

            // グローバルで(staticで)利用できるようにする宣言
            $this->db->setAsGlobal();

            // Eloquentを有効にする
            $this->db->bootEloquent();
        } catch (\Exception $e) {
            // DBに接続が失敗した場合
            $this->db = new \stdClass();
        }
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
            'success' => json_encode($success),
            'email' => $email,
            'subject' => $subject,
            'body' => $body,
            'date' => $status['_date'],
            'ip' => $status['_ip'],
            'host' => $status['_host'],
            'referer' => $status['_url'],
            'registry_datetime' => date("Y-m-d H:i:s")
        ];

        if (!$this->db instanceof \stdClass) {
            // prefixは省略
            $this->db->table('mailer')->insert($values);
            return true;
        }
        return false;
    }

    /**
     * DBを作成
     *
     * @return void
     * @throws Exception
     */
    public function make(): void
    {
        try {
            $db = $this->server['DB'];

            $pdo = new \PDO(
                'mysql:host=' . $db['HOST'] . ';dbname=' . $db['DATABASE'] . ';charset=' . $db['CHARSET'],
                $db['USERNAME'],
                $db['PASSWORD']
            );

            // SQL実行時にもエラーの代わりに例外を投げるように設定
            // (毎回if文を書く必要がなくなる)
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            // デフォルトのフェッチモードを連想配列形式に設定
            // (毎回PDO::FETCH_ASSOCを指定する必要が無くなる)
            $pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

            // テーブル存在チェック
            $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
                    id INT(11) AUTO_INCREMENT PRIMARY KEY,
                    success VARCHAR(50),
                    email VARCHAR(255),
                    subject VARCHAR(255),
                    body VARCHAR(1000),
                    date VARCHAR(1000),
                    ip VARCHAR(50),
                    host VARCHAR(50),
                    referer VARCHAR(50),
                    registry_datetime DATETIME
                ) engine=innodb default charset={$db['CHARSET']}";

            // テーブル作成
            $pdo->query($sql);

            // 一度閉じる.
            $pdo = null;
        } catch (\PDOException $e) {
            throw new \Exception($e->getMessage());
        }
    }
}
