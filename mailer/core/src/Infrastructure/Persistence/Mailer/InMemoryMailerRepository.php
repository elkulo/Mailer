<?php
/**
 * Mailer | el.kulo v3.0.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2021 A.Sudo
 * Licensed under MIT (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

namespace App\Infrastructure\Persistence\Mailer;

use Slim\Csrf\Guard;
use Psr\Log\LoggerInterface;
use App\Domain\Mailer\MailerRepository;
use App\Domain\Mailer\MailPostData;
use App\Application\Settings\SettingsInterface;
use App\Application\Handlers\Mail\MailHandlerInterface;
use App\Application\Handlers\DB\DBHandlerInterface;
use App\Application\Handlers\Validate\ValidateHandlerInterface;

class InMemoryMailerRepository implements MailerRepository
{

    /**
     * CSRF対策
     *
     * @var Guard
     */
    private $csrf;

    /**
     * ロジック
     *
     * @var MailPostData
     */
    private $postData;

    /**
     * ロガー
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * 設定値
     *
     * @var SettingsInterface
     */
    private $settings;

    /**
     * 検証ハンドラー
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
     * InMemoryMailerRepository constructor.
     *
     * @param Guard $csrf,
     * @param LoggerInterface $logger,
     * @param SettingsInterface $settings
     * @param ValidateHandlerInterface $validate,
     * @param MailHandlerInterface $mail,
     * @param DBHandlerInterface $db
     */
    public function __construct(
        Guard $csrf,
        LoggerInterface $logger,
        SettingsInterface $settings,
        ValidateHandlerInterface $validate,
        MailHandlerInterface $mail,
        DBHandlerInterface $db
    ) {

        // CSRF
        $this->csrf = $csrf;

        // ロガーをセット
        $this->logger = $logger;

        // バリデーションアクションをセット
        $this->validate = $validate;

        // 設定値
        $this->settings = $settings;

        // メールハンドラーをセット
        $this->mail = $mail;

        // データベースハンドラーをセット
        $this->db = $db;

        // POSTを格納
        $this->postData = new MailPostData($_POST, $settings);

        // バリデーション準備
        $this->validate->set($_POST);

        // 設定値の取得
        $formSettings = $this->settings->get('form');

        // POSTデータ
        $post_data = $this->postData->getPosts();

        // ユーザーメールを形式チェックして格納
        $email_attr = isset($formSettings['EMAIL_ATTRIBUTE']) ? $formSettings['EMAIL_ATTRIBUTE'] : null;
        if (isset($post_data[$email_attr])) {
            if ($this->validate->isCheckMailFormat($post_data[$email_attr])) {
                $this->postData->setUserMail($post_data[$email_attr]);
            }
        }
    }

    /**
     * インデックス
     *
     * @return array
     */
    public function index(): array
    {
        return [
            'template' => 'index.twig',
            'data' => [
                'CSRF'   => sprintf(
                    '<div style="display:none">
                        <input type="hidden" name="%1$s" value="%2$s">
                        <input type="hidden" name="%3$s" value="%4$s">
                     </div>',
                    $this->csrf->getTokenNameKey(),
                    $this->csrf->getTokenName(),
                    $this->csrf->getTokenValueKey(),
                    $this->csrf->getTokenValue()
                ),
                'reCAPTCHA' => $this->validate->getCaptchaScript(),
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
        $posts = [];
        $system = [];

        try {
            // バリデーションチェック
            if (!$this->validate->validateAll()) {
                return [
                    'template' => 'validate.twig',
                    'data' => [
                        'Messages' => array_map(fn($n) => $n[0], $this->validate->errors())
                    ]
                ];
            }

            // 固有のメーラートークンを生成.
            $this->postData->createMailerToken();

            // 適正な$_POSTを取得.
            $posts = $this->postData->getPosts();

            $system = [
                'Posts' => $this->postData->getConfirmQuery(),
                'CSRF'   => sprintf(
                    '<div style="display:none">
                        <input type="hidden" name="%1$s" value="%2$s">
                        <input type="hidden" name="%3$s" value="%4$s">
                        <input type="hidden" name="_http_referer" value="%5$s" />
                     </div>',
                    $this->csrf->getTokenNameKey(),
                    $this->csrf->getTokenName(),
                    $this->csrf->getTokenValueKey(),
                    $this->csrf->getTokenValue(),
                    $this->postData->getPageReferer()
                ),
                'reCAPTCHA' => $this->validate->getCaptchaScript(),
            ];
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return [
                'template' => 'exception.twig',
                'data' => [
                    'Message' => $e->getMessage()
                ]
            ];
        }

        // 確認画面を生成.
        return [
            'template' => 'confirm.twig',
            'data' => array_merge($posts, $system)
        ];
    }

    /**
     * 送信完了
     *
     * @return array
     */
    public function complete(): array
    {
        $mailSettings = $this->settings->get('mail');
        $formSettings = $this->settings->get('form');
        $router = [];
        $posts = [];
        $mailBody = [];
        $success = [];

        try {
            // 固有のメーラートークンを削除（重複チェック）
            $this->postData->checkinMailerToken();

            // バリデーションチェック
            if (!$this->validate->validateAll()) {
                return [
                    'template' => 'validate.twig',
                    'data' => [
                        'Messages' => array_map(fn($n) => $n[0], $this->validate->errors())
                    ]
                ];
            }

            // Twigテンプレート用に{{name属性}}で置換.
            $posts = $this->postData->getPosts();

            // メールボディを取得
            $mailBody = $this->postData->getMailBody();

            // 管理者宛に届くメールをセット
            $success['admin'] = $this->mail->send(
                $mailSettings['ADMIN_MAIL'],
                $this->postData->getMailSubject(),
                $this->postData->renderAdminMail($mailBody),
                $this->postData->getMailAdminHeader()
            );

            // ユーザーに届くメールをセット
            if (!empty($formSettings['IS_REPLY_USERMAIL'])) {
                if ($this->postData->getUserMail()) {
                    $success['user'] = $this->mail->send(
                        $this->postData->getUserMail(),
                        $this->postData->getMailSubject(),
                        $this->postData->renderUserMail($mailBody)
                    );
                }
            }

            // DBに保存
            $this->db->save(
                $success,
                $this->postData->getUserMail(),
                $this->postData->getMailSubject(),
                $this->postData->getPostToString(),
                array(
                    '_date' => date('Y/m/d (D) H:i:s', time()),
                    '_ip' => $_SERVER['REMOTE_ADDR'],
                    '_host' => getHostByAddr($_SERVER['REMOTE_ADDR']),
                    '_url' => $this->postData->getPageReferer(),
                )
            );

            if (array_search(false, $success)) {
                throw new \Exception('メールの送信でエラーが起きました。別の方法でサイト管理者にお問い合わせください。');
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return [
                'template' => 'exception.twig',
                'data' => [
                    'Message' => $e->getMessage()
                ]
            ];
        }

        // 完了画面を生成.
        $router['url'] = $this->postData->getReturnURL();
        return [
            'template' => 'complete.twig',
            'data' => array_merge($posts, ['Return' => $router])
        ];
    }

    /**
     * API
     *
     * @return array
     */
    public function api(): array
    {
        return [
            'csrf'   => [
                'keys' => [
                    'name'  => $this->csrf->getTokenNameKey(),
                    'value' => $this->csrf->getTokenValueKey(),
                ],
                'name'  => $this->csrf->getTokenName(),
                'value' => $this->csrf->getTokenValue(),
            ]
        ];
    }
}
