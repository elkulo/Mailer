<?php
/**
 * Mailer | el.kulo v1.0.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2021 A.Sudo
 * Licensed under MIT (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\MailerRepository;
use App\Domain\Mailer;
use App\Application\Handlers\Validate\ValidateHandler;
use App\Application\Handlers\View\ViewHandler;
use App\Application\Handlers\Mail\MailHandlerInterface as MailHandler;
use App\Application\Handlers\DB\DBHandlerInterface as DBHandler;
use Psr\Container\ContainerInterface;

class InMemoryMailerRepository implements MailerRepository
{

    /**
     * @var object
     */
    private $logger;

    /**
     * ロジック
     *
     * @var object
     */
    private $repository;

    /**
     * バリデート
     *
     * @var object
     */
    private $validate;

    /**
     * Twig ハンドラー
     *
     * @var object
     */
    private $view;

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
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {

        try {

            // ロガーをセット
            $this->logger = $container->get('logger');

            // ロジックをセット
            $this->domain = $container->get(Mailer::class);

            // バリデーションアクションをセット
            $this->validate = $container->get(ValidateHandler::class);

            // ビューアクションをセット
            $this->view = $container->get(ViewHandler::class);

            // メールハンドラーをセット
            $this->mail = $container->get(MailHandler::class);

            // データベースハンドラーをセット
            $this->db = $container->get(DBHandler::class);

            // 連続投稿防止
            $this->domain->checkinSession();

            // 設定値の取得
            $server = $this->domain->getServer();
            $setting = $this->domain->getSetting();

            // NULLバイト除去して格納
            if (isset($_POST)) {
                // POSTを格納
                $this->domain->setPost($_POST);
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
            } else {
                throw new \Exception('何も送信されていません。');
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            $this->view->displayExceptionExit($e->getMessage());
        }
    }

    /**
     * 実行
     *
     * @return void
     */
    public function submit(): void
    {

        try {
            $server = $this->domain->getServer();
            $setting = $this->domain->getSetting();

            // リファラチェック
            $this->domain->checkinReferer();

            // バリデーションチェック
            $this->validate->checkinValidateAll();

            // 入力エラーの判定
            if (!$this->validate->validate()) {
                $validate_massage = '';
                foreach ($this->validate->errors() as $error) {
                    $validate_massage .= '<p>' . $error[0] . '</p>';
                }
                $this->view->displayValidate(array('theValidateMassage' => $validate_massage));
                // エラーメッセージがある場合は処理を止める
                exit;
            }

            // Twigテンプレート用に{{name属性}}で置換.
            $post_data = $this->domain->getPost();
            $posts = array();
            foreach ($post_data as $key => $value) {
                // アンダースコアは除外.
                if (substr($key, 0, 1) !== '_') {
                    $posts[$key] = $value;
                }
            }

            // 確認画面の判定
            if (!$this->domain->isConfirmSubmit()) {
                // 確認画面から送信されていない場合
                $confirm = $this->domain->getConfirm() . PHP_EOL . $this->domain->getCreateNonce();
                $system = array(
                    'theActionURL' => $this->domain->getActionURL(),
                    'theConfirmContent' => $confirm,
                );
                $this->view->displayConfirm(array_merge($posts, $system));
            } else {
                // トークンチェック
                $this->domain->checkinToken();

                $success = array();

                $mail_body = $this->domain->getMailBody();

                // 管理者宛に届くメールをセット
                $success['admin'] = $this->mail->send(
                    $server['ADMIN_MAIL'],
                    $this->domain->getMailSubject(),
                    $this->view->renderAdminMail($mail_body),
                    $this->domain->getMailAdminHeader()
                );

                // ユーザーに届くメールをセット
                if (!empty($setting['IS_REPLY_USERMAIL'])) {
                    if ($this->domain->getUserMail()) {
                        $success['user'] = $this->mail->send(
                            $this->domain->getUserMail(),
                            $this->domain->getMailSubject(),
                            $this->view->renderUserMail($mail_body),
                            array()
                        );
                    }
                }

                // DBに保存
                if ($this->db) {
                    $db_insert_success = $this->db->save(
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
                    if (!$db_insert_success) {
                        $this->logger->error('データベース接続エラー');
                    }
                }

                if (!array_search(false, $success)) {
                    // 送信完了画面
                    $system = array(
                        'theReturnURL' => $this->domain->getReturnURL(),
                    );
                    $this->view->displayComplete(array_merge($posts, $system));
                } else {
                    throw new \Exception('メールの送信でエラーが起きました。別の方法でサイト管理者にお問い合わせください。');
                }
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            $this->view->displayExceptionExit($e->getMessage());
        }
    }
}
