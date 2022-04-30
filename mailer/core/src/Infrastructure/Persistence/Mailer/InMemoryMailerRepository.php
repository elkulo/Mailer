<?php
/**
 * Mailer | el.kulo v3.3.1 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2022 A.Sudo
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
use App\Application\Handlers\File\FileDataHandlerInterface;

class InMemoryMailerRepository implements MailerRepository
{

    /**
     * CSRF対策
     *
     * @var Guard
     */
    private $guard;

    /**
     * POSTロジック
     *
     * @var MailerPostData
     */
    private $postData;

    /**
     * 画像アップロードハンドラー
     *
     * @var FileDataHandlerInterface
     */
    private $fileData;

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
     * @param Guard $guard,
     * @param LoggerInterface $logger,
     * @param SettingsInterface $settings
     * @param RouterInterface $router
     * @param ValidateHandlerInterface $validate,
     * @param MailHandlerInterface $mail,
     * @param FileDataHandlerInterface $fileData,
     * @param DBHandlerInterface|null $db
     */
    public function __construct(
        Guard $guard,
        LoggerInterface $logger,
        SettingsInterface $settings,
        RouterInterface $router,
        ValidateHandlerInterface $validate,
        MailHandlerInterface $mail,
        FileDataHandlerInterface $fileData,
        ?DBHandlerInterface $db
    ) {

        // CSRF
        $this->guard = $guard;

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

        // POSTデータを取得
        $posts = filter_input_array(INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? [];

        // POSTデータをサニタイズして格納
        $this->postData = new MailerPostData($posts, $settings);

        // 画像アップロードハンドラーをセット
        $this->fileData = $fileData;
        $this->fileData->set(filter_var_array($_FILES, FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? []);

        // POSTされたFILE変数を取得
        $files = $this->fileData->getPostedFiles();

        // メールテンプレート用にファイル名を事前に格納
        $this->postData->setMailFileName($this->fileData->getFileNames());

        // POSTデータとFILEデータを統合してバリデーションに格納
        $this->validate->set(array_merge($posts, $files));

        // サニタイズしたPOSTデータを取得
        $postData = $this->postData->getPosts();

        // 設定値の取得
        $formSettings = $this->settings->get('form');

        // ユーザーメールを形式チェックして格納
        $emailAttr = isset($formSettings['EMAIL_ATTRIBUTE']) ? $formSettings['EMAIL_ATTRIBUTE'] : null;
        if (isset($postData[$emailAttr]) && $this->validate->isCheckMailFormat($postData[$emailAttr])) {
            $this->postData->setUserMail($postData[$emailAttr]);
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
        $adminEmail = isset($mailSettings['ADMIN_MAIL']) ? $mailSettings['ADMIN_MAIL'] : '';

        try {
            // 管理者メールチェック
            if (!$this->validate->isCheckMailFormat($adminEmail)) {
                throw new \Exception('メールプログラムは停止しています。');
            }

            // 入力画面を生成.
            return [
                'template' => 'index.twig',
                'data' => [
                    'Guard'   => sprintf(
                        '<div style="display:none">
                            <input type="hidden" name="%1$s" value="%2$s">
                            <input type="hidden" name="%3$s" value="%4$s">
                         </div>',
                        $this->guard->getTokenNameKey(),
                        $this->guard->getTokenName(),
                        $this->guard->getTokenValueKey(),
                        $this->guard->getTokenValue()
                    ),
                    'reCAPTCHA' => $this->validate->getReCaptchaScript(),
                    'Action' => [
                        'url' => $this->router->getUrl(
                            empty($formSettings['IS_CONFIRM_SKIP']) ? 'mailer.confirm' : 'mailer.complete'
                        )
                    ],
                ],
            ];
        } catch (\Exception $e) {
            return [
                'template' => 'exception.twig',
                'data' => [
                    'ExceptionMessage' => $e->getMessage()
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
        $adminEmail = isset($mailSettings['ADMIN_MAIL']) ? $mailSettings['ADMIN_MAIL'] : '';

        try {
            // 管理者メールチェック
            if (!$this->validate->isCheckMailFormat($adminEmail)) {
                throw new \Exception('メールプログラムは停止しています。');
            }

            // リファラチェック
            if (!$this->validate->isCheckReferer()) {
                throw new \Exception('指定のページ以外から送信されています。');
            }

            // バリデーションチェック
            if (!$this->validate->validateAll()) {
                throw new \Exception('バリデーションエラー', 400);
            }

            // 固有のメール送信のトークンを生成.
            $this->postData->createMailerToken();

            // 画像のアップロード
            // NOTE: エラーの場合は例外をスローします。
            $this->fileData->run();

            // 確認画面を生成.
            return [
                'template' => 'confirm.twig',
                'data' => array_merge(
                    $this->postData->getPosts(),
                    $this->fileData->getFiles(),
                    [
                        'Posts' => $this->postData->getDataQuery(),
                        'Files' => $this->fileData->getDataQuery(),
                        'Guard'   => sprintf(
                            '<div style="display:none">
                                <input type="hidden" name="%1$s" value="%2$s">
                                <input type="hidden" name="%3$s" value="%4$s">
                                %5$s
                                %6$s
                             </div>',
                            $this->guard->getTokenNameKey(),
                            $this->guard->getTokenName(),
                            $this->guard->getTokenValueKey(),
                            $this->guard->getTokenValue(),
                            $this->postData->getTmpPosts(),
                            $this->fileData->getTmpFiles()
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
                        'Errors' => array_map(fn ($n) => $n[0], $this->validate->errors()),
                    ]
                ];
            } else {
                $this->logger->error($e->getMessage());
                return [
                    'template' => 'exception.twig',
                    'data' => [
                        'ExceptionMessage' => $e->getMessage()
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
        $adminEmail = isset($mailSettings['ADMIN_MAIL']) ? $mailSettings['ADMIN_MAIL'] : '';

        try {
            // 管理者メールチェック
            if (!$this->validate->isCheckMailFormat($adminEmail)) {
                throw new \Exception('メールプログラムは停止しています。');
            }

            // リファラチェック
            if (!$this->validate->isCheckReferer()) {
                throw new \Exception('指定のページ以外から送信されています。');
            }

            // 画像のアップロード
            // NOTE: エラーの場合は例外をスローします。
            $this->fileData->run();

            // 確認画面スキップ分岐
            if (empty($formSettings['IS_CONFIRM_SKIP'])) {
                // 重複投稿をチェックは固有のメールの送信トークンを削除
                $this->postData->checkinMailerToken();
            } else {
                // 重複投稿をチェックはNonceトークンを削除
                $this->guard->removeTokenFromStorage($this->guard->getTokenName());
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
                $this->postData->getMailAdminHeader(),
                $this->fileData->getAdminMailAttachment()
            );
            if (!$success['admin']) {
                // SMTPサーバー障害や設定ミスによる送信失敗時の致命的なエラーメッセージ.
                throw new \Exception($this->settings->get('validate')['MESSAGE_FATAL_ERROR']);
            }

            // ユーザーに届くメールをセット
            if (!empty($formSettings['IS_REPLY_USERMAIL'])) {
                if ($this->postData->getUserMail()) {
                    $success['user'] = $this->mail->send(
                        $this->postData->getUserMail(),
                        $this->postData->getMailSubject(),
                        $this->postData->renderUserMail($this->postData->getMailBody()),
                        [],
                        $this->fileData->getUserMailAttachment()
                    );
                }
            }

            // DBに保存
            isset($this->db) && $this->db->save(
                $success,
                $this->postData->getUserMail(),
                $this->postData->getMailSubject(),
                $this->postData->getPostToString(),
                $this->fileData->getFileCSV(),
                $this->postData->getPostStatus()
            );

            // 添付ファイルを削除.
            $this->fileData->destroy();

            // 完了画面を生成.
            return [
                'template' => 'complete.twig',
                'data' => array_merge(
                    $this->postData->getPosts(),
                    $this->postData->getPostStatus('twig'),
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
                        'Errors' => array_map(fn ($n) => $n[0], $this->validate->errors()),
                    ]
                ];
            } else {
                $this->logger->error($e->getMessage());
                return [
                    'template' => 'exception.twig',
                    'data' => [
                        'ExceptionMessage' => $e->getMessage()
                    ]
                ];
            }
        }
    }
}
