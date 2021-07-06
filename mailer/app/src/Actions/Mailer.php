<?php
/**
 * Mailer | el.kulo v1.0.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2021 A.Sudo
 * Licensed under MIT (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

namespace App\Actions;

use App\Handlers\MailHandlerInterface;
use App\Handlers\DBHandlerInterface;
use Twig\Loader\FilesystemLoader as TwigFileLoader;
use Twig\Loader\ArrayLoader as TwigArrayLoader;
use Twig\Environment as TwigEnvironment;
use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\DNSCheckValidation;
use Egulias\EmailValidator\Validation\MultipleValidationWithAnd;
use Egulias\EmailValidator\Validation\RFCValidation;
use Valitron;

/**
 * Mailer
 */
class Mailer
{

    /**
     * 設定
     *
     * @var array
     */
    private array $setting = array(
        'FROM_NAME' => FROM_NAME, // 送信元の宛名
        'FROM_MAIL' => FROM_MAIL, // 送信元のメールアドレス
        'ADMIN_MAIL' => ADMIN_MAIL, // 管理者メールアドレス
        'ADMIN_CC' => ADMIN_CC, // 管理者CC
        'ADMIN_BCC' => ADMIN_BCC, // 管理者BCC
    );

    /**
     * $_POST
     *
     * @var array
     */
    private array $post_data;

    /**
     * バリデート
     *
     * @var object
     */
    private object $validation;

    /**
     * フォームの設置ページの格納
     *
     * @var string
     */
    private string $page_referer;

    /**
     * メールハンドラー
     *
     * @var object
     */
    protected object $mail;

    /**
     * Twig ハンドラー
     *
     * @var object
     */
    protected object $view;

    /**
     * Twig テンプレートディレクトリ
     *
     * @var array
     */
    protected array $view_tamplete_dir = array(
        __DIR__ . '/../../../templates',
        __DIR__ . '/../View',
    );

    /**
     * Twig キャッシュディレクトリ
     *
     * @var string
     */
    protected string $view_cache_dir = __DIR__ . '/../../cache';

    /**
     * コンストラクタ
     *
     * @param  MailHandlerInterface $mail_handler
     * @param  array $config
     * @return void
     */
    public function __construct(
        MailHandlerInterface $mail_handler,
        DBHandlerInterface $db_handler,
        array $config)
    {
        try {
            // ハンドラーをセット
            $this->mail = $mail_handler;

            // コンフィグをセット
            $this->setting = array_merge($this->setting, $config);

            // Twigの初期化
            $loader = new TwigFileLoader($this->view_tamplete_dir);
            $this->view = new TwigEnvironment(
                $loader,
                getenv('MAILER_DEBUG') ? array() : array('cache' => $this->view_cache_dir)
            );

            // 管理者メールの形式チェック
            $to = (array) $this->setting['ADMIN_MAIL'];
            $cc = $this->setting['ADMIN_CC']? explode(',', $this->setting['ADMIN_CC']): [];
            $bcc = $this->setting['ADMIN_BCC']? explode(',', $this->setting['ADMIN_BCC']): [];
            foreach (array_merge($to, $cc, $bcc) as $email) {
                if (! $this->isCheckMailFormat($email)) {
                    throw new \Exception('管理者メールアドレスに不備があります。設定を見直してください。');
                }
            }

            // 連続投稿防止
            $this->checkinSession();

            // NULLバイト除去して格納
            if (isset($_POST)) {
                // POSTを格納
                $this->setPost($_POST);

                // バリデーション準備
                Valitron\Validator::lang('ja');
                $this->validation = new Valitron\Validator($this->post_data);
                $this->validation->labels($this->setting['NAME_FOR_LABELS']);
            } else {
                throw new \Exception('何も送信されていません。');
            }
        } catch (\Exception $e) {
            logger($e->getMessage(), 'error');
            $this->addExceptionExit( $e->getMessage() );
        }
    }

    /**
     * 実行
     *
     * @return void
     */
    public function run(): void
    {

        // リファラチェック
        $this->checkinReferer();

        // バリデーションチェック
        $this->checkinValidation();

        // 入力エラーの判定
        if (! $this->validation->validate()) {
            $validate_massage = '';
            foreach ($this->validation->errors() as $error) {
                $validate_massage .= '<p>' . $error[0] . '</p>';
            }
            $this->view->display('/validate.twig', array(
                'theValidateMassage' => $validate_massage,
            ));
            // エラーメッセージがある場合は処理を止める
            exit;
        }

        // Twigテンプレート用に{{name属性}}で置換.
        $posts = array();
        foreach ($this->post_data as $key => $value) {
            // アンダースコアは除外.
            if (substr($key, 0, 1) !== '_') {
                $posts[$key] = $value;
            }
        }

        // 確認画面の判定
        if (! $this->isConfirmSubmit()) {
            // 確認画面から送信されていない場合
            $system = array(
                'theActionURL' => $this->getActionURL(),
                'theConfirmContent' => $this->getConfirm() . PHP_EOL . $this->getCreateNonce(),
            );
            $this->view->display('/confirm.twig', array_merge($posts, $system));
        } else {
            // トークンチェック
            $this->checkinToken();

            // 管理者宛に届くメールをセット
            $this->mail->send(
                $this->setting['ADMIN_MAIL'],
                $this->getMailSubject(),
                $this->getMailBody('admin'),
                $this->getMailAdminHeader()
            );

            // ユーザーに届くメールをセット
            if (!empty($this->setting['IS_REPLY_USERMAIL'])) {
                $this->mail->send(
                    $this->setting['USER_MAIL'],
                    $this->getMailSubject(),
                    $this->getMailBody('user')
                );
            }

            // 送信完了画面
            $system = array(
                'theReturnURL' => $this->getReturnURL(),
            );
            $this->view->display('/complete.twig', array_merge($posts, $system));
        }
    }

    /**
     * メール件名（共通）
     *
     * @return string
     */
    private function getMailSubject(): string
    {
        $subject = '';
        $before = isset($this->setting['SUBJECT_BEFORE']) ? $this->setting['SUBJECT_BEFORE'] : '';
        $after = isset($this->setting['SUBJECT_AFTER']) ? $this->setting['SUBJECT_AFTER'] : '';
        foreach ($this->post_data as $key => $value) {
            if ($key === $this->setting['SUBJECT_ATTRIBUTE']) {
                $subject = $value;
            }
        }

        return str_replace(PHP_EOL, '', $this->kses($before . $subject . $after));
    }

    /**
     * メールボディ（共通）
     *
     * @param  string $type
     * @return string
     */
    private function getMailBody(string $type): string
    {
        // {name属性}で置換.
        $posts = array();
        foreach ($this->post_data as $key => $value) {
            // アンダースコアは除外.
            if (substr($key, 0, 1) !== '_') {
                $posts[$key] = $value;
            }
        }

        // クライアント情報の置換.
        $value = array(
            '__FROM_SITE_NAME' => $this->setting['FROM_NAME'],
            '__POST_ALL' => $this->getPost(),
            '__DATE' => date('Y/m/d (D) H:i:s', time()),
            '__IP' => $_SERVER['REMOTE_ADDR'],
            '__HOST' => getHostByAddr($_SERVER['REMOTE_ADDR']),
            '__URL' => $this->page_referer,
        );

        if ($type === 'admin') {
            // 管理者宛送信メール.
            if (!empty($this->setting['TEMPLATE_MAIL_ADMIN'])) {
                return (new TwigEnvironment(
                    new TwigArrayLoader(array(
                        'admin.mail.tpl' => $this->setting['TEMPLATE_MAIL_ADMIN']
                    ))
                ))->render('admin.mail.tpl', array_merge($posts, $value));
            }

            return $this->view->render('/mail/admin.mail.twig', array_merge($posts, $value));
        } else {
            // ユーザ宛送信メール.
            if (!empty($this->setting['TEMPLATE_MAIL_USER'])) {
                return (new TwigEnvironment(
                    new TwigArrayLoader(array(
                        'user.mail.tpl' => $this->setting['TEMPLATE_MAIL_USER']
                    ))
                ))->render('user.mail.tpl', array_merge($posts, $value));
            }

            return $this->view->render('/mail/user.mail.twig', array_merge($posts, $value));
        }
    }

    /**
     * 管理者メールヘッダ.
     *
     * @param  string $type
     * @return array
     */
    private function getMailAdminHeader(): array
    {
        $header = array();

        // 管理者宛送信メール.
        if (!empty($this->setting['ADMIN_CC'])) {
            $header[] = 'Cc: ' . $this->setting['ADMIN_CC'];
        }
        if (!empty($this->setting['ADMIN_BCC'])) {
            $header[] = 'Bcc: ' . $this->setting['ADMIN_BCC'];
        }
        if (!empty($this->setting['IS_FROM_USERMAIL'])) {
            $header[] = 'Reply-To: ' . $this->setting['USER_MAIL'];
        }

        return $header;
    }

    /**
     * POSTデータから取得したデータを整形
     *
     * @param  array $posts
     * @return void
     */
    private function setPost($posts): void
    {
        $sanitized = array();
        foreach ($posts as $key => $value) {
            $sanitized[$key] = trim(strip_tags(str_replace("\0", '', $value)));
        }
        $this->post_data = $sanitized;
    }

    /**
     * POSTデータを文字連結して取得
     *
     * @return string
     */
    private function getPost(): string
    {
        $response = '';
        foreach ($this->post_data as $name => $value) {
            $output = '';
            if (is_array($value)) {
                foreach ($value as $item) {
                    // 連結項目の処理
                    if (is_array($item)) {
                        $output .= $this->changeJoin($item);
                    } else {
                        $output .= $item . ', ';
                    }
                }
                $output = rtrim($output, ', ');
            } else {
                $output = $value;
            }

            // 全角を半角へ変換.
            $output = $this->changeHankaku($output, $name);

            // アンダースコアで始まる文字は除外.
            if (substr($name, 0, 1) !== '_') {
                $response .= $this->nameToLabel($name) . ': ' . $output . PHP_EOL;
            }

            // フォームの設置ページを保存.
            if ($name === '_http_referer') {
                $this->page_referer = $this->kses($output);
            }
        }
        return $this->kses($response);
    }

    /**
     * 確認画面の入力内容出力用関数
     *
     * @return string
     */
    private function getConfirm(): string
    {
        $html = '';

        foreach ($this->post_data as $name => $value) {
            $output_value = '';

            // チェックボックス（配列）の結合
            if (is_array($value)) {
                foreach ($value as $item) {
                    if (is_array($item)) {
                        $output_value .= $this->changeJoin($item);
                    } else {
                        $output_value .= $item . ', ';
                    }
                }
                $output_value = rtrim($output_value, ', ');
            } else {
                $output_value = $value;
            }

            // 全角を半角へ変換
            $output_value = $this->changeHankaku($output_value, $name);

            // 確認をセット
            $name = $this->kses($name);
            $output_value = $this->kses($output_value);
            $html .= sprintf(
                '<tr><th>%1$s</th><td>%2$s<input type="hidden" name="%3$s" value="%4$s" /></td></tr>' . PHP_EOL,
                $this->nameToLabel($name),
                nl2br($output_value),
                $name,
                $output_value
            );
        }
        return '<table>' . $html . '</table>';
    }

    /**
     * 確認画面のフォームにアクション先出力
     *
     * @return string
     */
    private function getActionURL(): string
    {
        return $this->kses($_SERVER['SCRIPT_NAME']);
    }

    /**
     * 完了後のリンク先
     *
     * @return string
     */
    private function getReturnURL(): string
    {
        return $this->kses($this->setting['RETURN_PAGE']);
    }

    /**
     * トークン出力
     *
     * @return string
     */
    private function getCreateNonce(): string
    {
        $token                     = sha1(uniqid((string)mt_rand(), true));
        $_SESSION['_mailer_nonce'] = $token;
        return sprintf(
            '<input type="hidden" name="_mailer_nonce" value="%1$s" />
            <input type="hidden" name="_http_referer" value="%2$s" />
            <input type="hidden" name="_confirm_submit" value="1" />' . PHP_EOL,
            $token,
            $this->kses($_SERVER['HTTP_REFERER'])
        );
    }

    /**
     * バリデーションチェック
     *
     * @return void
     */
    private function checkinValidation(): void
    {
        // 必須項目チェック
        if (isset($this->setting['REQUIRED_ATTRIBUTE'])) {
            $this->validation->rule(
                'required',
                $this->setting['REQUIRED_ATTRIBUTE']
            );
        }
        // メール形式チェック
        if (isset($this->setting['EMAIL_ATTRIBUTE'])) {
            Valitron\Validator::addRule('EmailValidator', function ($field, $value) {
                return $this->isCheckMailFormat($value);
            });
            $this->validation->rule(
                'EmailValidator',
                $this->setting['EMAIL_ATTRIBUTE']
            )->message('メールアドレスの形式が正しくありません。');
        }

        // 日本語チェック
        if ( isset($this->setting['MB_WORD']) ) {
            Valitron\Validator::addRule('MBValidator', function ($field, $value) {
                if (strlen($value) === mb_strlen($value, 'UTF-8')) {
                    return false;
                }
                return true;
            });
            $this->validation->rule(
                'MBValidator',
                $this->setting['MB_WORD']
            )->message('日本語を含まない文章は送信できません。');
        }

        // 禁止ワード
        $ng_words = (array) explode(' ', $this->setting['NG_WORD']);
        if ( isset($ng_words[0]) ) {
            Valitron\Validator::addRule('NGValidator', function ($field, $value) use ($ng_words) {
                foreach ($ng_words as $word) {
                    if (mb_strpos($value, $word, 0, 'UTF-8') !== false) {
                        return false;
                    }
                }
                return true;
            });
            $this->validation->rule(
                'NGValidator',
                '*'
            )->message('禁止ワードが含まれているため送信できません');
        }
    }

    /**
     * メール文字判定
     *
     * @param  string $value
     * @return bool
     */
    private function isCheckMailFormat(string $value): bool
    {
        $validator = new EmailValidator();
        $multipleValidations = new MultipleValidationWithAnd([
            new RFCValidation(),
            new DNSCheckValidation()
        ]);
        //ietf.org has MX records signaling a server with email capabilites
        return $validator->isValid(trim($value), $multipleValidations); //true
    }

    /**
     * 送信画面判定
     *
     * @return bool
     */
    private function isConfirmSubmit(): bool
    {
        if (isset($this->post_data['_confirm_submit']) && $this->post_data['_confirm_submit'] === '1') {
            return true;
        }
        return false;
    }

    /**
     * リファラチェック
     *
     * @return void
     */
    private function checkinReferer(): void
    {
        if (isset($_SERVER['HTTP_REFERER']) && isset($_SERVER['SERVER_NAME'])) {
            if (strpos($_SERVER['HTTP_REFERER'], $_SERVER['SERVER_NAME']) === false) {
                $this->addExceptionExit('指定のページ以外から送信されています');
            }
        }
        if (empty($_SERVER['HTTP_REFERER']) || empty($_SERVER['SERVER_NAME'])) {
            $this->addExceptionExit('指定のページ以外から送信されています');
        }
    }

    /**
     * セッションチェック
     *
     * @return void
     */
    private function checkinSession(): void
    {
        if (empty($_SESSION)) {
            // トークンチェック用のセッション
            session_name('_mailer_token');
            session_start();

            // ワンタイムセッション
            $session_tmp = $_SESSION; // 退避
            session_destroy(); // 一度削除
            session_id(md5(uniqid((string)rand(), true))); // セッションID変更
            session_start(); // セッション再開
            $_SESSION = $session_tmp; // セッション変数値を引継ぎ
        }
    }

    /**
     * トークンチェック
     *
     * @return void
     */
    private function checkinToken(): void
    {
        if (empty($_SESSION['_mailer_nonce']) || ($_SESSION['_mailer_nonce'] !== $_POST['_mailer_nonce'])) {
            // 再読み込みで発行されたセッションを破壊
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params['path'],
                    $params['domain'],
                    $params['secure'],
                    $params['httponly']
                );
            }
            if (isset($_SESSION['_mailer_nonce'])) {
                session_destroy();
            }
            $this->addExceptionExit('連続した投稿の可能性があるため送信できません');
        } else {
            // 多重投稿を防ぐ
            // セッションを破壊してクッキーを削除
            if (isset($_SESSION['_mailer_nonce'])) {
                if (ini_get('session.use_cookies')) {
                    $params = session_get_cookie_params();
                    setcookie(
                        session_name(),
                        '',
                        time() - 42000,
                        $params['path'],
                        $params['domain'],
                        $params['secure'],
                        $params['httponly']
                    );
                }
                session_destroy();
            }
        }
    }

    /**
     * 例外発生時の停止
     *
     * @param  string $massage
     * @return void
     */
    private function addExceptionExit(string $massage): void
    {
        $this->view->display('/exception.twig', array(
            'theExceptionMassage' => $massage,
        ));
        exit;
    }

    /**
     * nameとラベルの属性の置き換え
     *
     * @param  string $name
     * @return string
     */
    private function nameToLabel(string $name): string
    {
        $label = $this->kses($name);
        if (isset($this->setting['NAME_FOR_LABELS'][$label])) {
            $label = $this->setting['NAME_FOR_LABELS'][$label];
        }
        return $label;
    }

    /**
     * 全角半角変換
     *
     * @param  string $output
     * @param  string $key
     * @return string
     */
    private function changeHankaku(string $output, string $key): string
    {
        if (empty($this->setting['HANKAKU_ATTRIBUTE']) || !function_exists('mb_convert_kana')) {
            return $output;
        }
        if (is_array($this->setting['HANKAKU_ATTRIBUTE'])) {
            foreach ($this->setting['HANKAKU_ATTRIBUTE'] as $val) {
                if ($key === $val) {
                    $output = mb_convert_kana($output, 'a', 'UTF-8');
                }
            }
        } else {
            $output = mb_convert_kana($output, 'a', 'UTF-8');
        }
        return $output;
    }

    /**
     * 配列連結の処理
     *
     * @param  array $items
     * @return string
     */
    private function changeJoin(array $items): string
    {
        $output = '';
        foreach ($items as $key => $val) {
            if ($key === 0 || $val == '') {
                // 配列が0、または内容が空の場合は連結文字を付加しない
                $key = '';
            } elseif (strpos($key, '円') !== false && $val != '' && preg_match('/^[0-9]+$/', $val)) {
                // 金額の場合には3桁ごとにカンマを追加
                $val = number_format($val);
            }
            $output .= $val . $key;
        }
        return $output;
    }

    /**
     * エスケープ
     *
     * @param  mixed $content
     * @param  string $encode
     * @return mixed
     */
    private function kses($content, string $encode = 'UTF-8')
    {
        $sanitized = array();
        if (is_array($content)) {
            foreach ($content as $key => $value) {
                $sanitized[$key] = trim(htmlspecialchars($value, ENT_QUOTES, $encode));
            }
        } else {
            return trim(htmlspecialchars($content, ENT_QUOTES, $encode));
        }
        return $sanitized;
    }
}
