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
     * @var HealthPost
     */
    private $healthPost;

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
     * @param SettingsInterface $settings
     * @param ValidateHandlerInterface $validate
     * @param MailHandlerInterface $mail
     * @param DBHandlerInterface $db
     */
    public function __construct(
        Guard $csrf,
        Messages $messages,
        SettingsInterface $settings,
        ValidateHandlerInterface $validate,
        MailHandlerInterface $mail,
        DBHandlerInterface $db
    ) {

        // CSRF
        $this->csrf = $csrf;

        // フラッシュメッセージ
        $this->flash = $messages;

        // 設定
        $this->settings = $settings;

        // バリデーションアクションをセット
        $this->validate = $validate;

        // メールハンドラーをセット
        $this->mail = $mail;

        // データベースハンドラーをセット
        $this->db = $db;

        // POSTを格納
        $this->healthPost = new HealthPost($_POST, $settings);

        // バリデーション準備
        $this->validate->set($_POST);
    }

    /**
     * 受付画面
     *
     * @return array
     */
    public function index(): array
    {
        $server = $this->healthPost->getServerSettings();
        $to = (array) $server['ADMIN_MAIL'];
        $cc = $server['ADMIN_CC']? explode(',', $server['ADMIN_CC']) : [];
        $bcc = $server['ADMIN_BCC']? explode(',', $server['ADMIN_BCC']) : [];

        try {
            // 環境設定のメールアドレスの形式に不備があれば警告.
            foreach (array_merge($to, $cc, $bcc) as $admin_email) {
                if (!$this->validate->isCheckMailFormat($admin_email)) {
                    throw new \Exception('環境設定のメールアドレスに不備があります。設定を見直してください。');
                }
            }
        } catch (\Exception $e) {
            $this->flash->clearMessages();
            $this->flash->addMessageNow('danger', $e->getMessage());
        }

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
        $server = $this->healthPost->getServerSettings();
        $post_data = $this->healthPost->getPosts();
        $post_email = isset($post_data['email'])? $post_data['email']: '';
        $passcode = '';
        $success = false;
        $to = (array) $server['ADMIN_MAIL'];
        $cc = $server['ADMIN_CC']? explode(',', $server['ADMIN_CC']) : [];
        $bcc = $server['ADMIN_BCC']? explode(',', $server['ADMIN_BCC']) : [];

        try {
            // 環境設定のメールアドレスの形式に不備がある場合は処理を中止.
            foreach (array_merge($to, $cc, $bcc) as $admin_email) {
                if (!$this->validate->isCheckMailFormat($admin_email)) {
                    return [
                        'redirect' => '../health-check'
                    ];
                }
            }

            // 管理者メールの比較
            if ($this->validate->isCheckMailFormat($post_email) && $post_email === $server['ADMIN_MAIL']) {
                // パスコードの送信
                $passcode = sprintf("%06d", mt_rand(1, 999999));
                $_SESSION['healthCheckPasscode'] = $passcode;

                // 管理者宛に届くメールをセット
                if ($this->settings->get('debug')) {
                    console($passcode);
                } else {
                    $success = $this->mail->send(
                        $server['ADMIN_MAIL'],
                        $this->healthPost->getMailSubject(),
                        $this->healthPost->renderAdminMail(['passcode' => $passcode])
                    );
                    if (!$success) {
                        throw new \Exception('環境設定のSMTPに不備があります。設定を見直してください。');
                    }
                }
            } else {
                throw new \Exception('入力内容に誤りがあります。入力内容を確認の上、再度お試しください。');
            }
        } catch (\Exception $e) {
            $this->flash->addMessage('warning', $e->getMessage());
            return [
                'redirect' => '../health-check'
            ];
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
        $server = $this->healthPost->getServerSettings();
        $post_data = $this->healthPost->getPosts();
        $resultList = [];
        $resultListCount = 0;
        $post_passcode = [];
        $passcode = null;

        try {
            // パスコードの比較
            $passcode = isset($_SESSION['healthCheckPasscode']) ? $_SESSION['healthCheckPasscode'] : null;

            for ($i = 1; $i <= 6; $i++) {
                $post_passcode[] = isset($post_data['passcode-' . $i])? $post_data['passcode-' . $i]: null;
            }

            if (implode('', $post_passcode) === $passcode) {
                $resultList = [
                    1 => [
                        'description' => 'SMTPでのメール送信',
                        'success' => true
                    ],
                    2 => [
                        'description' => 'PHPのバージョンがver7.4以上',
                        'success' => (version_compare(PHP_VERSION, '7.4.0') >= 0) ? true : false
                    ],
                    3 => [
                        'description' => 'HTTPSで暗号化されたサイト',
                        'success' => (isset($_SERVER['HTTPS'])) ? true : false
                    ],
                    4 => [
                        'description' => 'SSL/TLSで暗号化されたメール送信',
                        'success' => (in_array($server['SMTP_ENCRYPTION'], ['ssl', 'tls'])) ? true : false
                    ],
                    5 => [
                        'description' => 'データベースに接続',
                        'success' => $this->db->make()
                    ],
                    6 => [
                        'description' => 'データベースに履歴を保存',
                        'success' => $this->db->test($server['ADMIN_MAIL'])
                    ],
                    7 => [
                        'description' => 'reCAPTCHA でBot対策',
                        'success' => !empty($server['CAPTCHA']['SECRETKEY'])? true: false
                    ],
                    8 => [
                        'description' => 'デバッグモードが無効',
                        'success' => $this->settings->get('debug')? false: true
                    ],
                ];
    
                foreach ($resultList as $value) {
                    if ($value['success']) {
                        $resultListCount++;
                    }
                }
            } else {
                throw new \Exception('確認コードが一致しませんでした。入力内容を確認の上、再度お試しください。');
            }

            // セッションを削除
            if (isset($_SESSION['healthCheckPasscode'])) {
                unset($_SESSION['healthCheckPasscode']);
            }
        } catch (\Exception $e) {
            $this->flash->addMessage('warning', $e->getMessage());
            return [
                'redirect' => '../health-check'
            ];
        }

        return [
            'template' => 'result.twig',
            'data' => [
                'sectionTitle' => '結果',
                'sectionDescription' => 'メールの送受信は正常に行えました。検証内容は次の通りです。',
                'resultList' => $resultList,
                'resultListCount' => $resultListCount
            ]
        ];
    }
}
