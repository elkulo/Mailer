<?php
/**
 * Mailer | el.kulo v3.0.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2021 A.Sudo
 * Licensed under MIT (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

namespace App\Application\Handlers\DB;

use App\Application\Settings\SettingsInterface;
use Psr\Log\LoggerInterface;
use Illuminate\Database\Capsule\Manager;

/**
 * SQLiteHandler
 */
class SQLiteHandler implements DBHandler
{

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * サーバー設定
     *
     * @var array
     */
    private array $server;

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
    public string $table_name;

    /**
     * DBディレクトリ
     *
     * @var string
     */
    public string $db_dir;

    /**
     * DBファイル
     *
     * @var string
     */
    public string $sqlite_file;

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

            $app_path = $settings->get('app.path');
            $this->server = $settings->get('config.server');

            // DBテーブル名
            $prefix = $this->server['DB']['PREFIX'] ? strtolower($this->server['DB']['PREFIX']) : '';
            $this->table_name = $prefix . 'mailer';

            // DBの場所
            $this->db_dir = $app_path . '/../database/';
            $this->sqlite_file = $this->db_dir . $this->server['DB']['NAME'];

            // DB設定
            $this->db = new Manager();

            // 接続情報
            $config = [
                'driver'    => 'sqlite',
                'database'  => $this->sqlite_file,
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
        try {
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
            // DBディレクトリの確認
            if (!file_exists($this->db_dir)) {
                mkdir($this->db_dir, 0777);
            }

            // DBファイルの確認
            $sqlite_file = $this->db_dir . $this->server['DB']['NAME'];
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
                    success VARCHAR(50),
                    email VARCHAR(256),
                    subject VARCHAR(78),
                    body VARCHAR(998),
                    date VARCHAR(50),
                    ip VARCHAR(50),
                    host VARCHAR(50),
                    referer VARCHAR(50),
                    registry_datetime DATETIME,
                    created_at INTEGER,
                    updated_at INTEGER
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
     * @param  string $email
     * @return bool
     */
    final public function test(string $email): bool
    {
        return false;//$this->seve();
    }
}
