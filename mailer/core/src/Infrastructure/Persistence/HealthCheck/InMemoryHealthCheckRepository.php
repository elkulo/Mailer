<?php

/**
 * Mailer | el.kulo v1.0.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2021 A.Sudo
 * Licensed under MIT (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */

declare(strict_types=1);

namespace App\Infrastructure\Persistence\HealthCheck;

use App\Domain\Mailer\MailPost;
use App\Domain\HealthCheck\HealthCheckRepository;
use App\Application\Handlers\Mail\MailHandler;
use App\Application\Handlers\DB\DBHandler;
use App\Application\Handlers\Validate\ValidateHandler;

use Psr\Log\LoggerInterface;
use App\Application\Settings\SettingsInterface;

class InMemoryHealthCheckRepository implements HealthCheckRepository
{

    /**
     * ロジック
     *
     * @var MailPost
     */
    private $domain;

    /**
     * @var object
     */
    private $logger;

    /**
     * バリデート
     *
     * @var object
     */
    private $validate;

    /**
     * メールハンドラー
     *
     * @var object
     */
    private $mail;

    /**
     * DBハンドラー
     *
     * @var object|null
     */
    private $db;

    /**
     * InMemoryMailerRepository constructor.
     *
     * @param LoggerInterface $logger,
     * @param SettingsInterface $settings
     * @param ValidateHandler $validate,
     * @param MailHandler $mail,
     * @param DBHandler $db
     */
    public function __construct(
        LoggerInterface $logger,
        SettingsInterface $settings,
        ValidateHandler $validate,
        MailHandler $mail,
        DBHandler $db
    ) {

        // ロガーをセット
        $this->logger = $logger;

        // バリデーションアクションをセット
        $this->validate = $validate;

        // メールハンドラーをセット
        $this->mail = $mail;

        // データベースハンドラーをセット
        $this->db = $db;

        // POSTを格納
        $this->domain = new MailPost($_POST, $settings);

        // 連続投稿防止
        $this->domain->checkinSession();

        // 設定値の取得
        $server = $this->domain->getServer();
        $setting = $this->domain->getSetting();

        $post_data = $this->domain->getPost();

        // バリデーション準備
        $this->validate->set($post_data);

        // 管理者メールの形式チェック
        $to = (array) $server['ADMIN_MAIL'];
        $cc = $server['ADMIN_CC'] ? explode(',', $server['ADMIN_CC']) : [];
        $bcc = $server['ADMIN_BCC'] ? explode(',', $server['ADMIN_BCC']) : [];
        foreach (array_merge($to, $cc, $bcc) as $email) {
            if (!$this->validate->isCheckMailFormat($email)) {
                throw new \Exception('管理者メールアドレスに不備があります。設定を見直してください。');
            }
        }

        // ユーザーメールを形式チェックして格納
        $email_attr = isset($setting['EMAIL_ATTRIBUTE']) ? $setting['EMAIL_ATTRIBUTE'] : null;
        if (isset($post_data[$email_attr])) {
            if ($this->validate->isCheckMailFormat($post_data[$email_attr])) {
                $this->domain->setUserMail($post_data[$email_attr]);
            }
        }
    }

    /**
     * 受付画面
     *
     * @return array
     */
    public function index(): array
    {

        $flash = [
            'level' => 'warning',
            'title' => 'Failed...',
            'message' => '設定されている管理者のメールアドレスではありません。入力内容を確認の上、再度お試しください。',
        ];

        $flash = [
            'level' => 'warning',
            'title' => 'Failed...',
            'message' => '送信された確認コードが一致しませんでした。入力内容を確認の上、再度お試しください。',
        ];

        return [
            'template' => 'index.twig',
            'data' => [
                'sectionTitle' => '送信テスト',
                'sectionDescription' => 'メールプログラムから問題なく送信ができるかテストを行います。
                ヘルスチェックを開始するには、管理者メールアドレスを入力して「検証」を押すと確認コードが送信されます。',
                'flashMessage' => isset($flash)? $flash :null,
            ]
        ];
    }

    /**
     * 確認画面
     *
     * @return array
     */
    public function confirm(): array
    {
        return [
            'template' => 'confirm.twig',
            'data' => [
                'sectionTitle' => '確認コード',
                'sectionDescription' => '管理者のメールアドレス宛に確認コードを送信しました。受信された確認コードを入力してください。',
            ]
        ];
    }

    /**
     * 結果画面
     *
     * @return array
     */
    public function result(): array
    {
        $this->db->make();

        return [
            'template' => 'result.twig',
            'data' => [
                'sectionTitle' => '結果',
                'sectionDescription' => 'メールプログラムの送受信は正常に行えました。実行結果は次の通りです。',
            ]
        ];
    }
}
