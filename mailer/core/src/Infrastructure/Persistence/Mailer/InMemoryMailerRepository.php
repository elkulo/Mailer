<?php
/**
 * Mailer | el.kulo v3.0.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2021 A.Sudo
 * Licensed under LGPL-2.1-only (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

namespace App\Infrastructure\Persistence\Mailer;

use Slim\Csrf\Guard;
use Psr\Log\LoggerInterface;
use App\Domain\Mailer\MailerRepository;
use App\Domain\Mailer\MailerPostData;
use App\Application\Settings\SettingsInterface;
use App\Application\Router\RouterInterface;
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
     * @var MailerPostData
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
     * ルーター
     *
     * @var RouterInterface
     */
    protected $router;

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
    private $db = null;

    /**
     * InMemoryMailerRepository constructor.
     *
     * @param Guard $csrf,
     * @param LoggerInterface $logger,
     * @param SettingsInterface $settings
     * @param RouterInterface $router
     * @param ValidateHandlerInterface $validate,
     * @param MailHandlerInterface $mail,
     * @param DBHandlerInterface|null $db
     */
    public function __construct(
        Guard $csrf,
        LoggerInterface $logger,
        SettingsInterface $settings,
        RouterInterface $router,
        ValidateHandlerInterface $validate,
        MailHandlerInterface $mail,
        ?DBHandlerInterface $db
    ) {

        // CSRF
        $this->csrf = $csrf;

        // ロガーをセット
        $this->logger = $logger;

        // バリデーションアクションをセット
        $this->validate = $validate;

        // 設定値
        $this->settings = $settings;

        // ルーター
        $this->router = $router;

        // メールハンドラーをセット
        $this->mail = $mail;

        // データベースハンドラーをセット
        $this->db = $db;

        // POSTを格納
        $this->postData = new MailerPostData($_POST, $settings);

        // バリデーション準備
        $this->validate->set($_POST);

        // 設定値の取得
        $formSettings = $this->settings->get('form');

        // POSTデータ
        $postData = $this->postData->getPosts();

        // ユーザーメールを形式チェックして格納
        $emailAttr = isset($formSettings['EMAIL_ATTRIBUTE']) ? $formSettings['EMAIL_ATTRIBUTE'] : null;
        if (isset($postData[$emailAttr])) {
            if ($this->validate->isCheckMailFormat($postData[$emailAttr])) {
                $this->postData->setUserMail($postData[$emailAttr]);
            }
        }
    }

    /**
     * 入力画面
     *
     * @return array
     */
    public function index(): array
    {
        $formSettings = $this->settings->get('form');
        $mailSettings = $this->settings->get('mail');
        $adminEmail = isset($mailSettings['ADMIN_MAIL'])? $mailSettings['ADMIN_MAIL']: '';

        try {
            // 管理者メールチェック
            if (!$this->validate->isCheckMailFormat($adminEmail)) {
                throw new \Exception('メールプログラムは停止しています。');
            }

            // 入力画面を生成.
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
                    'reCAPTCHA' => $this->validate->getReCaptchaScript(),
                    'Action' => [
                        'url' => $this->router->getUrl(
                            empty($formSettings['IS_CONFIRM_SKIP'])? 'mailer.confirm' : 'mailer.complete'
                        )
                    ],
                ],
            ];
        } catch (\Exception $e) {
            return [
                'template' => 'exception.twig',
                'data' => [
                    'Message' => $e->getMessage()
                ]
            ];
        }
    }

    /**
     * 確認画面
     *
     * @return array
     */
    public function confirm(): array
    {
        $mailSettings = $this->settings->get('mail');
        $adminEmail = isset($mailSettings['ADMIN_MAIL'])? $mailSettings['ADMIN_MAIL']: '';

        try {
            // 管理者メールチェック
            if (!$this->validate->isCheckMailFormat($adminEmail)) {
                throw new \Exception('メールプログラムは停止しています。');
            }

            // バリデーションチェック
            if (!$this->validate->validateAll()) {
                throw new \Exception('バリデーションエラー', 400);
            }

            // 固有のメール送信のトークンを生成.
            $this->postData->createMailerToken();

            // 確認画面を生成.
            return [
                'template' => 'confirm.twig',
                'data' => array_merge(
                    $this->postData->getPosts(),
                    [
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
                        'reCAPTCHA' => $this->validate->getReCaptchaScript(),
                        'Action' => [
                            'url' => $this->router->getUrl('mailer.complete')
                        ],
                    ]
                ),
            ];
        } catch (\Exception $e) {
            if ($e->getCode() === 400) {
                return [
                    'template' => 'validate.twig',
                    'data' => [
                        'Messages' => array_map(fn($n) => $n[0], $this->validate->errors()),
                    ]
                ];
            } else {
                $this->logger->error($e->getMessage());
                return [
                    'template' => 'exception.twig',
                    'data' => [
                        'Message' => $e->getMessage()
                    ]
                ];
            }
        }
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
        $success = ['admin' => false, 'user' => false];
        $adminEmail = isset($mailSettings['ADMIN_MAIL'])? $mailSettings['ADMIN_MAIL']: '';

        try {
            // 管理者メールチェック
            if (!$this->validate->isCheckMailFormat($adminEmail)) {
                throw new \Exception('メールプログラムは停止しています。');
            }

            // 重複投稿をチェック
            if (empty($formSettings['IS_CONFIRM_SKIP'])) {
                // 確認画面経由の場合は固有のメールの送信トークンを削除
                $this->postData->checkinMailerToken();
            } else {
                // 確認画面スキップの場合はCSRFトークンを削除
                $this->csrf->removeTokenFromStorage($this->csrf->getTokenName());
            }

            // バリデーションチェック
            if (!$this->validate->validateAll()) {
                throw new \Exception('バリデーションエラー', 400);
            }

            // 管理者宛に届くメールをセット
            $success['admin'] = $this->mail->send(
                $mailSettings['ADMIN_MAIL'],
                $this->postData->getMailSubject(),
                $this->postData->renderAdminMail($this->postData->getMailBody()),
                $this->postData->getMailAdminHeader()
            );
            if (! $success['admin']) {
                throw new \Exception('メールの送信でエラーが起きました。別の方法でサイト管理者にお問い合わせください。');
            }

            // ユーザーに届くメールをセット
            if (!empty($formSettings['IS_REPLY_USERMAIL'])) {
                if ($this->postData->getUserMail()) {
                    $success['user'] = $this->mail->send(
                        $this->postData->getUserMail(),
                        $this->postData->getMailSubject(),
                        $this->postData->renderUserMail($this->postData->getMailBody())
                    );
                }
            }

            // DBに保存
            isset($this->db) && $this->db->save(
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

            // 完了画面を生成.
            return [
                'template' => 'complete.twig',
                'data' => array_merge(
                    $this->postData->getPosts(),
                    [
                        'Return' => [
                            'url' => $this->postData->getReturnURL(),
                        ]
                    ]
                ),
            ];
        } catch (\Exception $e) {
            if ($e->getCode() === 400) {
                return [
                    'template' => 'validate.twig',
                    'data' => [
                        'Messages' => array_map(fn($n) => $n[0], $this->validate->errors()),
                    ]
                ];
            } else {
                $this->logger->error($e->getMessage());
                return [
                    'template' => 'exception.twig',
                    'data' => [
                        'Message' => $e->getMessage()
                    ]
                ];
            }
        }
    }
}
