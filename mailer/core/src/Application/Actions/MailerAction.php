<?php
/**
 * Mailer | el.kulo v1.0.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2021 A.Sudo
 * Licensed under MIT (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

namespace App\Application\Actions;

use App\Application\Actions\Action;
use Psr\Container\ContainerInterface;

/**
 * MailerAction
 */
class MailerAction extends Action
{
    /**
     * コンストラクタ
     *
     * @param  ContainerInterface
     * @return void
     */
    public function __construct(ContainerInterface $container)
    {
        try {
            parent::__construct($container);

            // 連続投稿防止
            $this->repository->checkinSession();

            // 設定値の取得
            $server = $this->repository->getServer();
            $setting = $this->repository->getSetting();

            // NULLバイト除去して格納
            if (isset($_POST)) {
                // POSTを格納
                $this->repository->setPost($_POST);
                $post_data = $this->repository->getPost();

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
                        $this->repository->setUserMail($post_data[$email_attr]);
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
    public function action(): void
    {

        try {
            $server = $this->repository->getServer();
            $setting = $this->repository->getSetting();

            // リファラチェック
            $this->repository->checkinReferer();

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
            $post_data = $this->repository->getPost();
            $posts = array();
            foreach ($post_data as $key => $value) {
                // アンダースコアは除外.
                if (substr($key, 0, 1) !== '_') {
                    $posts[$key] = $value;
                }
            }

            // 確認画面の判定
            if (!$this->repository->isConfirmSubmit()) {
                // 確認画面から送信されていない場合
                $confirm = $this->repository->getConfirm() . PHP_EOL . $this->repository->getCreateNonce();
                $system = array(
                    'theActionURL' => $this->repository->getActionURL(),
                    'theConfirmContent' => $confirm,
                );
                $this->view->displayConfirm(array_merge($posts, $system));
            } else {
                // トークンチェック
                $this->repository->checkinToken();

                $success = array();

                $mail_body = $this->repository->getMailBody();

                // 管理者宛に届くメールをセット
                $success['admin'] = $this->mail->send(
                    $server['ADMIN_MAIL'],
                    $this->repository->getMailSubject(),
                    $this->view->renderAdminMail($mail_body),
                    $this->repository->getMailAdminHeader()
                );

                // ユーザーに届くメールをセット
                if (!empty($setting['IS_REPLY_USERMAIL'])) {
                    if ($this->repository->getUserMail()) {
                        $success['user'] = $this->mail->send(
                            $this->repository->getUserMail(),
                            $this->repository->getMailSubject(),
                            $this->view->renderUserMail($mail_body),
                            array()
                        );
                    }
                }

                // DBに保存
                if ($this->db) {
                    $db_insert_success = $this->db->save(
                        $success,
                        $this->repository->getUserMail(),
                        $this->repository->getMailSubject(),
                        $this->repository->getPostToString(),
                        array(
                            '_date' => date('Y/m/d (D) H:i:s', time()),
                            '_ip' => $_SERVER['REMOTE_ADDR'],
                            '_host' => getHostByAddr($_SERVER['REMOTE_ADDR']),
                            '_url' => $this->repository->getPageReferer(),
                        )
                    );
                    if (!$db_insert_success) {
                        $this->logger->error('データベース接続エラー');
                    }
                }

                if (!array_search(false, $success)) {
                    // 送信完了画面
                    $system = array(
                        'theReturnURL' => $this->repository->getReturnURL(),
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
