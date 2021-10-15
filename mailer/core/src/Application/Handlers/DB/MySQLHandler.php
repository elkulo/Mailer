<?php
/**
 * Mailer | el.kulo v3.0.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2021 A.Sudo
 * Licensed under LGPL-2.1-only (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

namespace App\Application\Handlers\DB;

use App\Application\Settings\SettingsInterface;
use Psr\Log\LoggerInterface;
use Illuminate\Database\Capsule\Manager;

/**
 * MySQLHandler
 */
class MySQLHandler implements DBHandlerInterface
{

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * データーベース設定
     *
     * @var array
     */
    private $databaseSettings = [];

    /**
     * データベース
     *
     * @var Manager|null
     */
    public $db;

    /**
     * テーブル名
     *
     * @var string
     */
    public $tableName = '';

    /**
     * DBを作成
     *
     * @param  SettingsInterface $settings
     * @param  LoggerInterface $logger
     * @return void
     */
    public function __construct(SettingsInterface $settings, LoggerInterface $logger)
    {
        try {
            $this->logger = $logger;

            $this->databaseSettings = $settings->get('database');

            // DBテーブル名
            $prefix = $this->databaseSettings['DB_PREFIX'] ? strtolower($this->databaseSettings['DB_PREFIX']) : '';
            $this->tableName = $prefix . 'mailer';

            // DB設定
            $this->db = new Manager();

            // 接続情報
            $config = [
                'driver'    => 'mysql',
                'host'      => $this->databaseSettings['DB_HOST'],
                'database'  => $this->databaseSettings['DB_NAME'],
                'username'  => $this->databaseSettings['DB_USER'],
                'password'  => $this->databaseSettings['DB_PASSWORD'],
                'charset'   => $this->databaseSettings['DB_CHARSET'],
                'collation' => $this->databaseSettings['DB_COLLATE'],
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
            $this->db = null;
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
            'registry_datetime' => date('Y-m-d H:i:s'),
            'created_at' => time(),
            'updated_at' => time()
        ];

        try {
            if ($this->db) {
                $this->db->table('mailer')->insert($values); // prefixは省略
            }
        } catch (\Exception $e) {
            $this->logger->error('データベース接続エラー');
            return false;
        }
        return true;
    }

    /**
     * DBを作成
     *
     * @return bool
     * @throws Exception
     */
    final public function make(): bool
    {
        try {
            $db = $this->databaseSettings;

            $pdo = new \PDO(
                'mysql:host=' . $db['DB_HOST'] . ';dbname=' . $db['DB_NAME'] . ';charset=' . $db['DB_CHARSET'],
                $db['DB_USER'],
                $db['DB_PASSWORD']
            );

            // SQL実行時にもエラーの代わりに例外を投げるように設定
            // (毎回if文を書く必要がなくなる)
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            // デフォルトのフェッチモードを連想配列形式に設定
            // (毎回PDO::FETCH_ASSOCを指定する必要が無くなる)
            $pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

            // テーブル存在チェック
            $sql = "CREATE TABLE IF NOT EXISTS {$this->tableName} (
                    id INT(11) AUTO_INCREMENT PRIMARY KEY,
                    success VARCHAR(50),
                    email VARCHAR(256),
                    subject VARCHAR(78),
                    body VARCHAR(998),
                    date VARCHAR(50),
                    ip VARCHAR(50),
                    host VARCHAR(50),
                    referer VARCHAR(50),
                    registry_datetime DATETIME,
                    created_at INT(11),
                    updated_at INT(11)
                ) engine=innodb default charset={$db['DB_CHARSET']}";

            // テーブル作成
            $pdo->query($sql);

            // 一度閉じる.
            $pdo = null;
        } catch (\PDOException $e) {
            $this->logger->error($e->getMessage());
            return false;
        }
        return true;
    }

    /**
     * DBに保存をテスト
     *
     * @param  string $email
     * @return bool
     */
    final public function test(string $email): bool
    {
        return $this->save(
            array(
                'admin' => true,
                'user' => false
            ),
            $email,
            '[HEALTH CHECK] メールプログラムからの動作検証',
            '//------------ ヘルスチェックによりメールの送信履歴が正常に保存されることを確認しました。 ------------//',
            array(
                '_date' => date('Y/m/d (D) H:i:s', time()),
                '_ip' => $_SERVER['REMOTE_ADDR'],
                '_host' => getHostByAddr($_SERVER['REMOTE_ADDR']),
                '_url' => '',
            )
        );
    }
}
