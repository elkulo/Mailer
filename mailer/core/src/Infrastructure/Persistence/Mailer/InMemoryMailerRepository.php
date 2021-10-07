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
use App\Domain\Mailer\MailPost;
use App\Application\Settings\SettingsInterface;
use App\Application\Handlers\Mail\MailHandlerInterface;
use App\Application\Handlers\DB\DBHandlerInterface;
use App\Application\Handlers\Validate\ValidateHandlerInterface;

class InMemoryMailerRepository implements MailerRepository
{

    /**
     * @var Guard
     */
    protected $csrf;

    /**
     * ロジック
     *
     * @var MailPost
     */
    private $mailPost;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
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
        $this->mailPost = new MailPost($_POST, $settings);

        // バリデーション準備
        $this->validate->set($_POST);

        // 設定値の取得
        $formSettings = $this->mailPost->getFormSettings();

        $post_data = $this->mailPost->getPosts();

        // ユーザーメールを形式チェックして格納
        $email_attr = isset($formSettings['EMAIL_ATTRIBUTE']) ? $formSettings['EMAIL_ATTRIBUTE'] : null;
        if (isset($post_data[$email_attr])) {
            if ($this->validate->isCheckMailFormat($post_data[$email_attr])) {
                $this->mailPost->setUserMail($post_data[$email_attr]);
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
                'csrf'   => sprintf(
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
            $this->validate->checkinValidateAll();
            if (!$this->validate->validate()) {
                return [
                    'template' => 'validate.twig',
                    'data' => [
                        'messages' => array_map(fn($n) => $n[0], $this->validate->errors())
                    ]
                ];
            }

            // 固有のメーラートークンを生成.
            $this->mailPost->createMailerToken();

            // 適正な$_POSTを取得.
            $posts = $this->mailPost->getPosts();

            $system = [
                'posts' => $this->mailPost->getConfirmQuery(),
                'csrf'   => sprintf(
                    '<div style="display:none">
                        <input type="hidden" name="%1$s" value="%2$s">
                        <input type="hidden" name="%3$s" value="%4$s">
                        <input type="hidden" name="_http_referer" value="%5$s" />
                     </div>',
                    $this->csrf->getTokenNameKey(),
                    $this->csrf->getTokenName(),
                    $this->csrf->getTokenValueKey(),
                    $this->csrf->getTokenValue(),
                    $this->mailPost->getPageReferer()
                ),
                'reCAPTCHA' => $this->validate->getCaptchaScript(),
            ];
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return [
                'template' => 'exception.twig',
                'data' => [
                    'message' => $e->getMessage()
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
        $mailSettings = $this->mailPost->getMailSettings();
        $formSettings = $this->mailPost->getFormSettings();
        $router = [];
        $posts = [];
        $mail_body = [];
        $success = [];

        try {
            // 固有のメーラートークンを削除（重複チェック）
            $this->mailPost->checkinMailerToken();

            // バリデーションチェック
            $this->validate->checkinValidateAll();
            if (!$this->validate->validate()) {
                return [
                    'template' => 'validate.twig',
                    'messages' => array_map(fn($n) => $n[0], $this->validate->errors()),
                ];
            }

            // Twigテンプレート用に{{name属性}}で置換.
            $posts = $this->mailPost->getPosts();

            // メールボディを取得
            $mail_body = $this->mailPost->getMailBody();

            // 管理者宛に届くメールをセット
            $success['admin'] = $this->mail->send(
                $mailSettings['admin.mail'],
                $this->mailPost->getMailSubject(),
                $this->mailPost->renderAdminMail($mail_body),
                $this->mailPost->getMailAdminHeader()
            );

            // ユーザーに届くメールをセット
            if (!empty($formSettings['IS_REPLY_USERMAIL'])) {
                if ($this->mailPost->getUserMail()) {
                    $success['user'] = $this->mail->send(
                        $this->mailPost->getUserMail(),
                        $this->mailPost->getMailSubject(),
                        $this->mailPost->renderUserMail($mail_body)
                    );
                }
            }

            // DBに保存
            $this->db->save(
                $success,
                $this->mailPost->getUserMail(),
                $this->mailPost->getMailSubject(),
                $this->mailPost->getPostToString(),
                array(
                    '_date' => date('Y/m/d (D) H:i:s', time()),
                    '_ip' => $_SERVER['REMOTE_ADDR'],
                    '_host' => getHostByAddr($_SERVER['REMOTE_ADDR']),
                    '_url' => $this->mailPost->getPageReferer(),
                )
            );

            if (array_search(false, $success)) {
                throw new \Exception('メールの送信でエラーが起きました。別の方法でサイト管理者にお問い合わせください。');
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return [
                'template' => 'exception.twig',
                'data' => array('message' => $e->getMessage())
            ];
        }

        // 完了画面を生成.
        $router['url'] = $this->mailPost->getReturnURL();
        return [
            'template' => 'complete.twig',
            'data' => array_merge($posts, ['return' => $router])
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
