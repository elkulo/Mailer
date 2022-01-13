<?php
/**
 * Mailer | el.kulo v3.3.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2022 A.Sudo
 * Licensed under LGPL-2.1-only (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

namespace App\Application\Handlers\DB;

use App\Application\Settings\SettingsInterface;
use Psr\Log\LoggerInterface;
use Illuminate\Database\Capsule\Manager;

/**
 * SQLiteHandler
 */
class SQLiteHandler implements DBHandlerInterface
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
    public $tableName;

    /**
     * DBディレクトリ
     *
     * @var string
     */
    public $dbDirectory;

    /**
     * DBファイル
     *
     * @var string
     */
    public $sqliteFile;

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

            $appPath = $settings->get('appPath');
            $this->databaseSettings = $settings->get('database');

            // DBテーブル名
            $prefix = $this->databaseSettings['DB_PREFIX'] ? strtolower($this->databaseSettings['DB_PREFIX']) : '';
            $this->tableName = $prefix . 'mailer';

            // DBの場所
            $this->dbDirectory = $appPath . '/../database/';
            $this->sqliteFile = $this->dbDirectory . $this->databaseSettings['DB_NAME'];

            // DB設定
            $this->db = new Manager();

            // 接続情報
            $config = [
                'driver'    => 'sqlite',
                'database'  => $this->sqliteFile,
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
     * @param  array  $success
     * @param  string $email
     * @param  string $subject
     * @param  string $body
     * @param  string $attachment
     * @param  array  $status
     * @return bool
     */
    final public function save(
        array  $success,
        string $email,
        string $subject,
        string $body,
        string $attachment,
        array  $status
    ): bool {
        try {
            $values = [
                'success' => json_encode($success),
                'email' => $email,
                'subject' => $subject,
                'body' => $body,
                'attachment' => $attachment,
                'date' => $status['date'],
                'ip' => $status['ip'],
                'host' => $status['host'],
                'referer' => $status['referer'],
                'uuid' => $status['uuid'],
                'ua' => $status['ua'],
                'registry_datetime' => date('Y-m-d H:i:s'),
                'created_at' => time(),
                'updated_at' => time()
            ];

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
     */
    final public function make(): bool
    {
        try {
            // DBディレクトリの確認
            if (!file_exists($this->dbDirectory)) {
                mkdir($this->dbDirectory, 0777);
            }

            // DBファイルの確認
            $sqliteFile = $this->dbDirectory . $this->databaseSettings['DB_NAME'];
            if (!file_exists($sqliteFile)) {
                $pdo = new \PDO('sqlite:' . $sqliteFile);

                // SQL実行時にもエラーの代わりに例外を投げるように設定
                // (毎回if文を書く必要がなくなる)
                $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

                // デフォルトのフェッチモードを連想配列形式に設定
                // (毎回PDO::FETCH_ASSOCを指定する必要が無くなる)
                $pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

                // テーブル作成
                $pdo->exec("CREATE TABLE IF NOT EXISTS {$this->tableName} (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    success VARCHAR(50),
                    email VARCHAR(256),
                    subject VARCHAR(78),
                    body VARCHAR(3998),
                    attachment VARCHAR(50),
                    date VARCHAR(50),
                    ip VARCHAR(50),
                    host VARCHAR(50),
                    referer VARCHAR(50),
                    uuid VARCHAR(36),
                    ua VARCHAR(256),
                    registry_datetime DATETIME,
                    created_at INTEGER,
                    updated_at INTEGER
                )");

                // メタテーブル存在チェック
                $metaTable = $this->tableName.'meta';
                $pdo->exec("CREATE TABLE IF NOT EXISTS {$metaTable} (
                    meta_id INTEGER PRIMARY KEY AUTOINCREMENT,
                    meta_key VARCHAR(50),
                    meta_value VARCHAR(256)
                )");

                // 一度閉じる.
                $pdo = null;
            }
        } catch (\PDOException $e) {
            $this->logger->error($e->getMessage());
            return false;
        }
        return true;
    }

    /**
     * DBに保存をテスト
     *
     * @return bool
     */
    final public function test(): bool
    {
        try {
            if ($this->db) {
                $this->db->table('mailermeta')->updateOrInsert(
                    [
                        'meta_id' => 1,
                    ],
                    [
                        'meta_key' => 'health_check_ok',
                        'meta_value' => '1'
                    ]
                );
            }
        } catch (\Exception $e) {
            $this->logger->error('データベース接続エラー');
            return false;
        }
        return true;
    }
}
