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
use App\Application\Settings\SettingsInterface;
use App\Domain\Mailer\MailPost;
use App\Domain\Mailer\MailerRepository;
use App\Application\Handlers\Mail\MailHandler;
use App\Application\Handlers\DB\DBHandler;
use App\Application\Handlers\Validate\ValidateHandler;

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
    private $domain;

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
     * @var ValidateHandler
     */
    private $validate;

    /**
     * メールハンドラー
     *
     * @var MailHandler
     */
    private $mail;

    /**
     * DBハンドラー
     *
     * @var DBHandler|null
     */
    private $db;

    /**
     * InMemoryMailerRepository constructor.
     *
     * @param Guard $csrf,
     * @param LoggerInterface $logger,
     * @param SettingsInterface $settings
     * @param ValidateHandler $validate,
     * @param MailHandler $mail,
     * @param DBHandler $db
     */
    public function __construct(
        Guard $csrf,
        LoggerInterface $logger,
        SettingsInterface $settings,
        ValidateHandler $validate,
        MailHandler $mail,
        DBHandler $db
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
        $this->domain = new MailPost($_POST, $settings);

        // 設定値の取得
        $form = $this->domain->getFormSettings();

        $post_data = $this->domain->getPosts();

        // バリデーション準備
        $this->validate->set($post_data);

        // ユーザーメールを形式チェックして格納
        $email_attr = isset($form['EMAIL_ATTRIBUTE']) ? $form['EMAIL_ATTRIBUTE'] : null;
        if (isset($post_data[$email_attr])) {
            if ($this->validate->isCheckMailFormat($post_data[$email_attr])) {
                $this->domain->setUserMail($post_data[$email_attr]);
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
                    '<input type="hidden" name="%1$s" value="%2$s">
                    <input type="hidden" name="%3$s" value="%4$s">',
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
        try {
            // バリデーションチェック
            $this->validate->checkinValidateAll();

            // 入力エラーの判定
            if (!$this->validate->validate()) {
                return [
                    'template' => 'validate.twig',
                    'data' => array(
                        'messages' => array_map(fn($n) => $n[0], $this->validate->errors())
                    )
                ];
            }

            // 固有のメーラートークンを生成.
            $this->domain->createMailerToken();

            // 適正な$_POSTを取得.
            $posts = $this->domain->getPosts();

            // 確認画面を生成.
            $system = array(
                'posts' => $this->domain->getConfirmQuery(),
                'csrf'   => sprintf(
                    '<input type="hidden" name="%1$s" value="%2$s">
                    <input type="hidden" name="%3$s" value="%4$s">
                    <input type="hidden" name="_http_referer" value="%5$s" />',
                    $this->csrf->getTokenNameKey(),
                    $this->csrf->getTokenName(),
                    $this->csrf->getTokenValueKey(),
                    $this->csrf->getTokenValue(),
                    $this->domain->getPageReferer()
                ),
                'reCAPTCHA' => $this->validate->getCaptchaScript(),
            );
            return [
                'template' => 'confirm.twig',
                'data' => array_merge($posts, $system)
            ];
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return [
                'template' => 'exception.twig',
                'data' => array('message' => $e->getMessage())
            ];
        }
    }

    /**
     * 送信完了
     *
     * @return array
     */
    public function complete(): array
    {
        try {
            $server = $this->domain->getServerSettings();
            $form = $this->domain->getFormSettings();

            // 固有のメーラートークンを削除（重複チェック）
            $this->domain->checkinMailerToken();

            //
            /*
            $post_data = $this->domain->getPosts();
            if ( $this->validate->isHuman($post_data['g-recaptcha-response'], $post_data['g-recaptcha-action']) ) {
                console('reCAPTCHA OK');
            } else {
                console('reCAPTCHA NG');
            }
            */

            // バリデーションチェック
            $this->validate->checkinValidateAll();

            // 入力エラーの判定
            if (!$this->validate->validate()) {
                return [
                    'template' => 'validate.twig',
                    'messages' => array_map(fn($n) => $n[0], $this->validate->errors()),
                ];
            }

            // Twigテンプレート用に{{name属性}}で置換.
            $posts = $this->domain->getPosts();

            // メールボディを取得
            $mail_body = $this->domain->getMailBody();

            // 管理者宛に届くメールをセット
            $success['admin'] = $this->mail->send(
                $server['ADMIN_MAIL'],
                $this->domain->getMailSubject(),
                $this->domain->renderAdminMail($mail_body),
                $this->domain->getMailAdminHeader()
            );

            // ユーザーに届くメールをセット
            if (!empty($form['IS_REPLY_USERMAIL'])) {
                if ($this->domain->getUserMail()) {
                    $success['user'] = $this->mail->send(
                        $this->domain->getUserMail(),
                        $this->domain->getMailSubject(),
                        $this->domain->renderUserMail($mail_body)
                    );
                }
            }

            // DBに保存
            $this->db->save(
                $success,
                $this->domain->getUserMail(),
                $this->domain->getMailSubject(),
                $this->domain->getPostToString(),
                array(
                    '_date' => date('Y/m/d (D) H:i:s', time()),
                    '_ip' => $_SERVER['REMOTE_ADDR'],
                    '_host' => getHostByAddr($_SERVER['REMOTE_ADDR']),
                    '_url' => $this->domain->getPageReferer(),
                )
            );

            if (!array_search(false, $success)) {
                // 完了画面を生成.
                $router['url'] = $this->domain->getReturnURL();
                return [
                    'template' => 'complete.twig',
                    'data' => array_merge($posts, ['return' => $router])
                ];
            } else {
                throw new \Exception('メールの送信でエラーが起きました。別の方法でサイト管理者にお問い合わせください。');
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return [
                'template' => 'exception.twig',
                'data' => array('message' => $e->getMessage())
            ];
        }
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
