<?php
declare(strict_types=1);

namespace App\Application;

use App\Handler\HandlerInterface;
use Twig\Loader\FilesystemLoader as TwigLoader;
use Twig\Environment as TwigEnvironment;

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
    private $setting = array(
        'FROM_NAME' => FROM_NAME, // 送信元の宛名
        'FROM_MAIL' => FROM_MAIL, // 送信元のメールアドレス(SMTPの設定で上書きされる)
        'ADMIN_NAME' => ADMIN_NAME, // 管理者の宛名
        'ADMIN_MAIL' => ADMIN_MAIL, // 管理者メールアドレス
    );

    /**
     * $_POST
     *
     * @var array
     */
    private $post_data;

    /**
     * エラーメッセージ
     *
     * @var mixed
     */
    private $error_massage;

    /**
     * フォームの設置ページの格納
     *
     * @var string
     */
    private $page_referer;

    /**
     * メールハンドラー
     *
     * @var object
     */
    protected $mail;

    /**
     * Twig ハンドラー
     *
     * @var object
     */
    protected $view;
    
    /**
     * Twig キャッシュディレクトリ
     *
     * @var string
     */
    protected $view_cache_dir = __DIR__ . '/../../cache';

    /**
     * __construct
     *
     * @param  HandlerInterface $handler
     * @param  array $config_setting
     * @return void
     */
    public function __construct(HandlerInterface $handler, array $config_setting)
    {
        try {
            // ハンドラーをセット
            $this->mail = $handler;

            // コンフィグをセット
            $this->setting = array_merge($this->setting, $config_setting);

            // Twigの初期化
            $loader = new TwigLoader(__DIR__ . '/../../../templates');
            $this->view = new TwigEnvironment(
                $loader,
                getenv('MAILER_DEBUG') ? array() : array('cache' => $this->view_cache_dir)
            );

            // 連続投稿防止
            if (empty($_SESSION)) {
                // トークンチェック用のセッション
                session_name('_mailer_tookun');
                session_start();

                // ワンタイムセッション
                $session_tmp = $_SESSION; // 退避
                session_destroy(); // 一度削除
                session_id(md5(uniqid((string)rand(), true))); // セッションID変更
                session_start(); // セッション再開
                $_SESSION = $session_tmp; // セッション変数値を引継ぎ
            }

            // NULLバイト除去して格納
            if (isset($_POST)) {
                $this->post_data = $this->ksesHTML($_POST);
            } else {
                throw new \Exception('Mailer Error: Not Post.');
            }
        } catch (\Exception $e) {
            exit($e->getMessage());
        }
    }

    /**
     * 基本機能
     *
     * @return void
     */
    public function init(): void
    {

        // リファラチェック
        $this->checkinReferer();

        // 日本語チェック
        $this->checkinMBWord();

        // NGワードチェック
        $this->checkinNGWord();

        // 必須項目チェックで $error_massage にエラー格納
        $this->checkinRequire();

        // エラー判定
        if ($this->isErrorMassage()) {
            $this->view->display('/error.twig', array(
                'theErrorMassage' => $this->error_massage,
            ));
            // エラーメッセージがある場合は処理を止める
            exit;
        }

        // 確認画面の判定
        if (!$this->isConfirmSubmit()) {
            // 確認画面から送信されていない場合
            $this->view->display('/confirm.twig', array(
                'theActionURL' => $this->getActionURL(),
                'theConfirmContent' => $this->getConfirm() . PHP_EOL . $this->getCreateNonce(),
            ));
        } else {
            // トークンチェック
            $this->checkinToken();

            // 管理者宛に届くメールをセット
            $this->mail->send(
                $this->setting['ADMIN_MAIL'],
                $this->getMailSubject(),
                $this->getMailBody('admin'),
                $this->getAdminHeader()
            );

            // ユーザーに届くメールをセット
            if (!empty($this->setting['REPLY_USERMAIL'])) {
                $this->mail->send(
                    $this->setting['USER_MAIL'],
                    $this->getMailSubject(),
                    $this->getMailBody('user'),
                    $this->getUserHeader()
                );
            }

            // 送信完了画面
            $this->view->display('/complete.twig', array(
                'theReturnURL' => $this->getReturnURL(),
            ));
        }
    }

    /**
     * 送信メール件名（共通）
     *
     * @return string
     */
    private function getMailSubject(): string
    {
        $subject = 'No Subject';
        $before = !empty($this->setting['SUBJECT_BEFORE']) ? $this->setting['SUBJECT_BEFORE'] : '';
        $after = !empty($this->setting['SUBJECT_AFTER']) ? $this->setting['SUBJECT_AFTER'] : '';
        foreach ($this->post_data as $key => $value) {
            if ($key === $this->setting['SUBJECT_ATTRIBUTE']) {
                $subject = $value;
            }
        }
        return $this->ksesRM($this->ksesESC($before . $subject . $after));
    }

    /**
     * 送信メールボディ（共通）
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
            '__TIME' => date('Y/m/d (D) H:i:s', time()),
            '__IP' => $_SERVER['REMOTE_ADDR'],
            '__HOST' => getHostByAddr($_SERVER['REMOTE_ADDR']),
            '__URL' => $this->page_referer,
        );

        if ($type === 'admin') {
            // 管理者宛送信メール.
            return $this->view->render('/mail/admin.mail.twig', array_merge($posts, $value));
        } else {
            // ユーザ宛送信メール.
            return $this->view->render('/mail/user.mail.twig', array_merge($posts, $value));
        }
    }

    /**
     * 管理者宛送信メールヘッダ
     *
     * @return string
     */
    private function getAdminHeader(): string
    {

        $header = array(
            'From: ' . $this->setting['FROM_NAME'] . ' <' . $this->setting['FROM_MAIL'] . '>',
        );

        if ($this->setting['IS_FROM_USERMAIL'] == 1) {
            $header[] = 'Reply-To: ' . $this->setting['USER_NAME'] . ' <' . $this->setting['USER_MAIL'] . '>';
        }
        if (!empty($this->setting['ADMIN_BCC'])) {
            $header[] = 'Bcc: ' . $this->setting['ADMIN_BCC'];
        }

        // FIXME BCCで送るメールアドレス
        //$this->setting['ADMIN_BCC'] = [];
        //return $header;
        return $this->setting['ADMIN_NAME'];
    }

    /**
     * ユーザ宛送信メールヘッダ
     *
     * @return string
     */
    private function getUserHeader(): string
    {

        $header = array(
            'From: ' . $this->setting['FROM_NAME'] . ' <' . $this->setting['FROM_MAIL'] . '>',
        );

        //return $header;
        return $this->setting['USER_NAME'];
    }

    /**
     * 送信メールにPOSTデータをセットする関数
     *
     * @return string
     */
    private function getPost(): string
    {
        $response = '';
        foreach ($this->post_data as $key => $val) {
            $output = '';
            if (is_array($val)) {
                foreach ($val as $item) {
                    // 連結項目の処理
                    if (is_array($item)) {
                        $output .= $this->changeJoin($item);
                    } else {
                        $output .= $item . ', ';
                    }
                }
                $output = rtrim($output, ', ');
            } else {
                $output = $val;
            }

            // 全角を半角へ変換.
            $output = $this->changeHankaku($output, $key);

            // アンダースコアで始まる文字は除外.
            if (substr($key, 0, 1) !== '_') {
                $response .= '【' . $this->ksesESC($key) . '】' . $this->ksesESC($output) . PHP_EOL;
            }

            // フォームの設置ページを保存.
            if ($key === '_http_referer') {
                $this->page_referer = $this->ksesESC($output);
            }
        }

        // 不要な改行を整形.
        return trim($response);
    }

    /**
     * 確認画面の入力内容出力用関数
     *
     * @return string
     */
    private function getConfirm(): string
    {
        $html = '';

        foreach ($this->post_data as $key => $val) {
            $output = '';

            // チェックボックス（配列）の結合
            if (is_array($val)) {
                foreach ($val as $item) {
                    if (is_array($item)) {
                        $output .= $this->changeJoin($item);
                    } else {
                        $output .= $item . ', ';
                    }
                }
                $output = rtrim($output, ', ');
            } else {
                $output = $val;
            }

            // 改行コードを変換
            $output = nl2br($this->ksesESC($output));
            $key = trim($this->ksesESC($key));
            $content = str_replace(array('<br />', '<br>'), '', $output);

            // 全角を半角へ変換
            $output = $this->changeHankaku($output, $key);

            // 確認をセット
            $html .= sprintf(
                '<tr><th>%1$s</th><td>%2$s<input type="hidden" name="%1$s" value="%3$s" /></td></tr>' . PHP_EOL,
                $key,
                $output,
                $content
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
        return $this->ksesESC($this->ksesHTML($_SERVER['SCRIPT_NAME']));
    }

    /**
     * 完了後のリンク先
     *
     * @return string
     */
    private function getReturnURL(): string
    {
        return $this->ksesESC($this->setting['RETURN_PAGE']);
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
            $this->ksesESC($_SERVER['HTTP_REFERER'])
        );
    }

    /**
     * 必須チェック
     *
     * @return void
     */
    private function checkinRequire(): void
    {
        $error = '';

        // 必須項目チェック
        if (!empty($this->setting['REQUIRED_ATTRIBUTE'])) {
            foreach ($this->setting['REQUIRED_ATTRIBUTE'] as $requireValue) {
                $existsFlag = '';
                foreach ($this->post_data as $key => $value) {
                    if ($key === $requireValue) {
                        // 連結指定の項目（配列）のための必須チェック
                        if (is_array($value)) {
                            $connectEmpty = 0;
                            foreach ($value as $vv) {
                                if (is_array($vv)) {
                                    foreach ($vv as $vv02) {
                                        if ($vv02 === '') {
                                            $connectEmpty++;
                                        }
                                    }
                                }
                            }
                            if ($connectEmpty > 0) {
                                $error .= '<p>【' . $this->ksesESC($key) . '】は必須項目です。</p>' . PHP_EOL;
                            }
                        } elseif ($value === '') {
                            // デフォルト必須チェック
                            $error .= '<p>【' . $this->ksesESC($key) . '】は必須項目です。</p>' . PHP_EOL;
                        }
                        $existsFlag = 1;
                        break;
                    }
                }
                if ($existsFlag !== 1) {
                    $error .= '<p>【' . $requireValue . '】が選択されていません。</p>' . PHP_EOL;
                }
            }
        }

        // メール形式チェック
        if (empty($error)) {
            foreach ($this->post_data as $key => $value) {
                if ($key === $this->setting['USERNAME_ATTRIBUTE']) {
                    $this->setting['USER_NAME'] = $this->ksesRM($this->ksesESC($value));
                }
                if ($key === $this->setting['EMAIL_ATTRIBUTE']) {
                    $this->setting['USER_MAIL'] = $this->ksesRM($this->ksesESC($value));
                }
                if ($key === $this->setting['EMAIL_ATTRIBUTE'] && !empty($value)) {
                    if (!$this->isCheckMailFormat($value)) {
                        $error .= '<p>【' . $key . '】はメールアドレスの形式が正しくありません。</p>' . PHP_EOL;
                    }
                }
            }
        }
        $this->error_massage = $error;
    }

    /**
     * メール文字判定
     *
     * @param  string $post
     * @return bool
     */
    private function isCheckMailFormat(string $post): bool
    {
        $post         = trim($post);
        $mail_address = explode('@', $post);
        $mail_match   = '/^[\.!#%&\-_0-9a-zA-Z\?\/\+]+\@[!#%&\-_0-9a-z]+(\.[!#%&\-_0-9a-z]+)+$/';

        // メールアドレス形式チェック＆複数メール防止
        if (preg_match($mail_match, $post) && count($mail_address) == 2) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * エラーメッセージの判定
     *
     * @return bool
     */
    private function isErrorMassage(): bool
    {
        return $this->error_massage ? true : false;
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
     * 禁止ワード
     *
     * @return void
     */
    private function checkinNGWord(): void
    {
        $ng_words = (array) explode(' ', $this->setting['NG_WORD']);

        if (isset($ng_words[0])) {
            foreach ($this->post_data as $value) {
                foreach ($ng_words as $word) {
                    if (mb_strpos($value, $word, 0, 'UTF-8') !== false) {
                        $this->addExceptionExit('"' . $word . '"を含む単語は送信できません');
                    }
                }
            }
        }
    }

    /**
     * 日本語チェック
     *
     * @return void
     */
    private function checkinMBWord(): void
    {
        $mb_word = (string) $this->setting['MB_WORD'];

        if ($mb_word) {
            foreach ($this->post_data as $key => $value) {
                if ($key === $mb_word) {
                    if (strlen($value) == mb_strlen($value, 'UTF-8')) {
                        $this->addExceptionExit('日本語を含まない文章は送信できません');
                    }
                }
            }
        }
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
        (new TwigEnvironment(
            new TwigLoader(__DIR__ . '/../View'),
            getenv('MAILER_DEBUG') ? array() : array('cache' => $this->view_cache_dir)
        ))->display('/Exception.twig', array(
            'theExceptionMassage' => $massage,
        ));
        exit;
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
     * 空白と改行を消すインジェクション対策
     *
     * @param  string $content
     * @return string
     */
    private function ksesRM(string $content): string
    {
        $content = str_replace(array("\r\n", "\r", "\n"), '', $content);
        return trim($content);
    }

    /**
     * エスケープ
     *
     * @param  mixed $content
     * @param  string $encode
     * @return mixed
     */
    private function ksesESC($content, string $encode = 'UTF-8')
    {
        if (is_array($content)) {
            return array_map(array($this, 'esc'), $content);
        }
        return htmlspecialchars($content, ENT_QUOTES, $encode);
    }

    /**
     * NULLバイトとHTMLタグ除去
     *
     * @param  mixed $content
     * @return mixed
     */
    private function ksesHTML($content)
    {
        $sanitized = array();
        if (is_array($content)) {
            foreach ($content as $key => $value) {
                $sanitized[$key] = strip_tags(str_replace("\0", '', $value));
            }
        } else {
            return strip_tags(str_replace("\0", '', $content));
        }
        return $sanitized;
    }
}
