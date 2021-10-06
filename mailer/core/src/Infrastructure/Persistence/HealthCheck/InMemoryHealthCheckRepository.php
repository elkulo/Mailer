<?php
/**
 * Mailer | el.kulo v3.0.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2021 A.Sudo
 * Licensed under MIT (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

namespace App\Infrastructure\Persistence\HealthCheck;

use Slim\Csrf\Guard;
use Slim\Flash\Messages;
use Psr\Log\LoggerInterface;
use App\Domain\HealthCheck\HealthCheckRepository;
use App\Domain\HealthCheck\HealthPost;
use App\Application\Settings\SettingsInterface;
use App\Application\Handlers\Validate\ValidateHandlerInterface;
use App\Application\Handlers\Mail\MailHandlerInterface;
use App\Application\Handlers\DB\DBHandlerInterface;

class InMemoryHealthCheckRepository implements HealthCheckRepository
{

    /**
     * @var Guard
     */
    protected $csrf;

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
     * @var LoggerInterface
     */
    private $logger;

    /**
     * 設定
     *
     * @var SettingsInterface
     */
    private $settings;

    /**
     * バリデート
     *
     * @var ValidateHandlerInterface
     */
    private $validate;

    /**
     * メールハンドラー
     *
     * @var MailHandlerInterface
     */
    private $mail;

    /**
     * DBハンドラー
     *
     * @var DBHandlerInterface|null
     */
    private $db;

    /**
     * InMemoryHealthCheckRepository constructor.
     *
     * @param Guard $csrf,
     * @param Messages $messages
     * @param LoggerInterface $logger
     * @param SettingsInterface $settings
     * @param ValidateHandlerInterface $validate
     * @param MailHandlerInterface $mail
     * @param DBHandlerInterface $db
     */
    public function __construct(
        Guard $csrf,
        Messages $messages,
        LoggerInterface $logger,
        SettingsInterface $settings,
        ValidateHandlerInterface $validate,
        MailHandlerInterface $mail,
        DBHandlerInterface $db
    ) {

        // CSRF
        $this->csrf = $csrf;

        // フラッシュメッセージ
        $this->flash = $messages;

        // ロガーをセット
        $this->logger = $logger;

        // 設定
        $this->settings = $settings;

        // バリデーションアクションをセット
        $this->validate = $validate;

        // メールハンドラーをセット
        $this->mail = $mail;

        // データベースハンドラーをセット
        $this->db = $db;

        // POSTを格納
        $this->domain = new HealthPost($_POST, $settings);

        // バリデーション準備
        $this->validate->set($this->domain->getPosts());
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
                'sectionTitle' => '送受信テスト',
                'sectionDescription' => 'メールの送受信に問題がないかテストを行います。ヘルスチェックを開始するには、管理者のメールアドレス宛に確認コードが送信されます。',
                'csrf'   => [
                    'keys' => [
                        'name'  => $this->csrf->getTokenNameKey(),
                        'value' => $this->csrf->getTokenValueKey(),
                    ],
                    'name'  => $this->csrf->getTokenName(),
                    'value' => $this->csrf->getTokenValue(),
                ]
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
        $server = $this->domain->getServerSettings();
        $post_data = $this->domain->getPosts();

        try {
            // 管理者メールの比較
            $post_email = isset($post_data['email'])? $post_data['email']: '';
            if (!$this->validate->isCheckMailFormat($post_email) || $post_email !== $server['ADMIN_MAIL']) {
                throw new \Exception('入力内容に誤りがあります。入力内容を確認の上、再度お試しください。');
            }

            // 管理者メールの形式チェック
            $to = (array) $server['ADMIN_MAIL'];
            $cc = $server['ADMIN_CC'] ? explode(',', $server['ADMIN_CC']) : [];
            $bcc = $server['ADMIN_BCC'] ? explode(',', $server['ADMIN_BCC']) : [];
            foreach (array_merge($to, $cc, $bcc) as $admin_email) {
                if (!$this->validate->isCheckMailFormat($admin_email)) {
                    throw new \Exception('環境設定のメールアドレスに不備があります。設定を見直してください。');
                }
            }
        } catch (\Exception $e) {
            $this->flash->addMessage('warning', $e->getMessage());
            $redirect = '../health-check';
        }

        // パスコードの送信
        if (!$redirect) {
            $passcode = sprintf("%06d", mt_rand(1, 999999));
            $_SESSION['healthCheckPasscode'] = $passcode;

            // 管理者宛に届くメールをセット
            if ($this->settings->get('debug')) {
                console($passcode);
            } else {
                $success = $this->mail->send(
                    $server['ADMIN_MAIL'],
                    $this->domain->getMailSubject(),
                    $this->domain->renderAdminMail(['passcode' => $passcode])
                );
                if (!$success) {
                    $this->flash->addMessage('warning', '環境設定のSMTPに不備があります。設定を見直してください。');
                    $redirect = '../health-check';
                }
            }
        }

        return [
            'template' => 'confirm.twig',
            'data' => [
                'sectionTitle' => '確認コード',
                'sectionDescription' => '管理者のメールアドレス宛に確認コードを送信しました。受信された確認コードを入力してください。',
                'csrf'   => [
                    'keys' => [
                        'name'  => $this->csrf->getTokenNameKey(),
                        'value' => $this->csrf->getTokenValueKey(),
                    ],
                    'name'  => $this->csrf->getTokenName(),
                    'value' => $this->csrf->getTokenValue(),
                ]
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
        $server = $this->domain->getServerSettings();
        $post_data = $this->domain->getPosts();

        try {
            // パスコードの比較
            $passcode = isset($_SESSION['healthCheckPasscode']) ? $_SESSION['healthCheckPasscode'] : null;

            for ($i = 1; $i <= 6; $i++) {
                $var_passcode = 'passcode-' . $i;
                $post_passcode[] = isset($post_data[$var_passcode])? $post_data[$var_passcode]: null;
            }

            if (implode('', $post_passcode) !== $passcode) {
                throw new \Exception('確認コードが一致しませんでした。入力内容を確認の上、再度お試しください。');
            }

            // セッションを削除
            if (isset($_SESSION['healthCheckPasscode'])) {
                unset($_SESSION['healthCheckPasscode']);
            }
        } catch (\Exception $e) {
            $this->flash->addMessage('warning', $e->getMessage());
            $redirect = '../../health-check';
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
                    'description' => 'データベースに接続',
                    'answer' => $this->db->make()
                ],
                6 => [
                    'description' => 'データベースに履歴を保存',
                    'answer' => $this->db->test($server['ADMIN_MAIL'])
                ],
                7 => [
                    'description' => 'reCAPTCHA でBot対策',
                    'answer' => !empty($server['CAPTCHA']['SECRETKEY'])? true: false
                ],
                8 => [
                    'description' => 'デバッグモードが無効',
                    'answer' => $this->settings->get('debug')? false: true
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
                'sectionDescription' => 'メールの送受信は正常に行えました。検証内容は次の通りです。',
                'resultList' => $resultList,
                'totalResult' => $totalResult
            ],
            'redirect' => $redirect,
        ];
    }
}
