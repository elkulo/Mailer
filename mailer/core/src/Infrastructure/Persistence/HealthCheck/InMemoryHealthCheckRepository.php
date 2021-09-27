<?php
/**
 * Mailer | el.kulo v3.0.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2021 A.Sudo
 * Licensed under MIT (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

namespace App\Infrastructure\Persistence\HealthCheck;

use Slim\Flash\Messages;
use Psr\Log\LoggerInterface;
use App\Application\Settings\SettingsInterface;
use App\Domain\HealthCheck\HealthPost;
use App\Domain\HealthCheck\HealthCheckRepository;
use App\Application\Handlers\Mail\MailHandler;
use App\Application\Handlers\DB\DBHandler;
use App\Application\Handlers\Validate\ValidateHandler;

class InMemoryHealthCheckRepository implements HealthCheckRepository
{

    /**
     * @var Messages
     */
    protected $flash;

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
     * @param LoggerInterface $logger
     * @param SettingsInterface $settings
     * @param ValidateHandler $validate
     * @param MailHandler $mail
     * @param DBHandler $db
     * @param Messages $messages
     */
    public function __construct(
        LoggerInterface $logger,
        SettingsInterface $settings,
        ValidateHandler $validate,
        MailHandler $mail,
        DBHandler $db,
        Messages $messages
    ) {

        // フラッシュメッセージ
        $this->flash = $messages;

        // ロガーをセット
        $this->logger = $logger;

        // バリデーションアクションをセット
        $this->validate = $validate;

        // メールハンドラーをセット
        $this->mail = $mail;

        // データベースハンドラーをセット
        $this->db = $db;

        // POSTを格納
        $this->domain = new HealthPost($_POST, $settings);

        // バリデーション準備
        $post_data = $this->domain->getPost();
        $this->validate->set($post_data);
    }

    /**
     * 受付画面
     *
     * @return array
     */
    public function index(): array
    {
        return [
            'template' => 'index.twig',
            'data' => [
                'sectionTitle' => '送信テスト',
                'sectionDescription' => 'メールプログラムから問題なく送信ができるかテストを行います。
                ヘルスチェックを開始するには、管理者メールアドレスを入力して「検証」を押すと確認コードが送信されます。'
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
        $redirect = null;
        $server = $this->domain->getServer();
        $post_data = $this->domain->getPost();

        try {
            // 管理者メールの比較
            if (!$this->validate->isCheckMailFormat($post_data['email'])
                || $post_data['email'] !== $server['ADMIN_MAIL']
            ) {
                throw new \Exception('入力内容に誤りがあります。入力内容を確認の上、再度お試しください。');
            }

            // 管理者メールの形式チェック
            if (!$this->validate->isCheckMailFormat($server['ADMIN_MAIL'])) {
                throw new \Exception('環境設定のメールアドレスに不備があります。設定を見直してください。');
            }
        } catch (\Exception $e) {
            $this->flash->addMessage('warning', $e->getMessage());
            $redirect = '../health-check';
        }

        // パスコードの送信
        if (!$redirect) {
            $_SESSION['healthCheckPasscode'] = sprintf("%06d", mt_rand(1, 999999));
            console($_SESSION['healthCheckPasscode']);

            /*
            // 管理者宛に届くメールをセット
            $success = $this->mail->send(
                $server['ADMIN_MAIL'],
                $this->domain->getMailSubject(),
                $this->domain->renderAdminMail(['passcode' => '000000'])
            );
            if (!$success) {
                $this->flash->addMessage('warning', '環境設定のSMTPに不備があります。設定を見直してください。');
                $redirect = '../health-check';
            }
            */
        }

        return [
            'template' => 'confirm.twig',
            'data' => [
                'sectionTitle' => '確認コード',
                'sectionDescription' => '管理者のメールアドレス宛に確認コードを送信しました。受信された確認コードを入力してください。',
            ],
            'redirect' => $redirect,
        ];
    }

    /**
     * 結果画面
     *
     * @return array
     */
    public function result(): array
    {
        $resultList = [];
        $totalResult = 0;
        $redirect = null;
        $server = $this->domain->getServer();
        $post_data = $this->domain->getPost();

        try {
            // パスコードの比較
            if (empty($_SESSION['healthCheckPasscode'])
                || implode('', $post_data) !== $_SESSION['healthCheckPasscode']
            ) {
                throw new \Exception('確認コードが一致しませんでした。入力内容を確認の上、再度お試しください。');
            }
        } catch (\Exception $e) {
            $this->flash->addMessage('warning', $e->getMessage());
            $redirect = '../health-check';
        }

        // セッションを削除
        if (isset($_SESSION['healthCheckPasscode'])) {
            unset($_SESSION['healthCheckPasscode']);
        }

        // パスコード一致で検証
        if (!$redirect) {
            $resultList = [
                1 => [
                    'description' => 'SMTPでのメール送信',
                    'answer' => true
                ],
                2 => [
                    'description' => 'PHPのバージョンがver7.4以上',
                    'answer' => (version_compare(PHP_VERSION, '7.4.0') >= 0) ? true : false
                ],
                3 => [
                    'description' => 'HTTPSで暗号化されたサイト',
                    'answer' => (isset($_SERVER['HTTPS'])) ? true : false
                ],
                4 => [
                    'description' => 'SSL/TLSで暗号化されたメール送信',
                    'answer' => (in_array($server['SMTP_ENCRYPTION'], ['ssl', 'tls'])) ? true : false
                ],
                5 => [
                    'description' => 'データベースにテーブルを新規作成',
                    'answer' => true
                ],
                6 => [
                    'description' => 'データベースに履歴を保存',
                    'answer' => true
                ],
                7 => [
                    'description' => 'デバッグモード',
                    'answer' => true
                ],
                8 => [
                    'description' => 'デバッグモード',
                    'answer' => true
                ],
                9 => [
                    'description' => 'デバッグモード',
                    'answer' => true
                ],
                10 => [
                    'description' => 'デバッグモード',
                    'answer' => true
                ],
            ];

            foreach ($resultList as $value) {
                if ($value['answer']) {
                    $totalResult++;
                }
            }
        }

        return [
            'template' => 'result.twig',
            'data' => [
                'sectionTitle' => '結果',
                'sectionDescription' => 'メールプログラムの送受信は正常に行えました。実行結果は次の通りです。',
                'resultList' => $resultList,
                'totalResult' => $totalResult
            ],
            'redirect' => $redirect,
        ];
    }
}
