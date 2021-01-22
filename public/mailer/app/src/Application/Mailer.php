<?php
declare(strict_types=1);

namespace App\Application;

class Mailer
{

    // 設定
    private $setting = array(
        'FROM_NAME' => FROM_NAME, // 送信元の宛名
        'FROM_MAIL' => FROM_MAIL, // 送信元のメールアドレス(SMTPの設定で上書きされる)
        'ADMIN_NAME' => ADMIN_NAME, // 管理者の宛名
        'ADMIN_MAIL' => ADMIN_MAIL, // 管理者メールアドレス
    );

    private $post_data; // $_POST

    private $error_massage; // エラーメッセージ

    private $page_referer; // フォームの設置ページの格納

    protected $mail; // メールハンドラー

    protected $view; // Twigテンプレート

    public function __construct($handler, array $config_setting)
    {
        try {

            // ハンドラーをセット
            $this->mail = $handler;

            // コンフィグをセット
            $this->setting = array_merge($this->setting, $config_setting);

            // Twigの初期化
            $loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../../../templates');
            $this->view = new \Twig\Environment(
                $loader,
                getenv('MAILER_DEBUG') ? array() : array('cache' => __DIR__ . '/../../cache')
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

            // Bccで送るメールアドレス ADMIN_BCC
            $this->setting['ADMIN_BCC'] = [];
        } catch (\Exception $e) {
            exit($e->getMessage());
        }
    }

    // 基本機能
    public function init()
    {

        // リファラチェック
        $this->checkinReferer();

        // 日本語チェック
        $this->checkMBWord();

        // NGワードチェック
        $this->checkNGWord();

        // 必須項目チェックで $error_massage にエラー格納
        $this->checkinRequire();

        // 確認画面通過チェック
        if ($this->isCheckConfirm() && $this->isCheckRequire()) {
            // トークンチェック
            $this->checkinToken();

            // 管理者宛に届くメールをセット
            $this->mail->send(
                $this->setting['ADMIN_MAIL'],
                $this->getMailSubject(),
                $this->getAdminBody(),
                $this->getAdminHeader()
            );

            // ユーザーに届くメールをセット
            if ($this->setting['RETURN_USER'] == 1) {
                $this->mail->send(
                    $this->setting['USER_MAIL'],
                    $this->getMailSubject(),
                    $this->getUserBody(),
                    $this->getUserHeader()
                );
            }

            // 送信完了画面
            $this->view->display('/complet.twig', array(
                'theReturnURL' => $this->getReturnURL(),
            ));
        } else {
            // 確認画面とエラー画面の分岐
            if ($this->isCheckRequire()) {
                $this->view->display('/confirm.twig', array(
                    'theActionURL' => $this->getActionURL(),
                    'theConfirm' => $this->getConfirm() . $this->getCreateNonce(),
                ));
            } else {
                $this->view->display('/error.twig', array(
                    'theErrorMassage' => $this->error_massage,
                ));
            }
        }
    }

    // 件名（共通）
    private function getMailSubject()
    {
        $subject = 'No Subject';
        $before  = $after = '';
        if ($this->setting['SUBJECT_BEFORE']) {
            $before = $this->setting['SUBJECT_BEFORE'];
        }
        if ($this->setting['SUBJECT_AFTER']) {
            $after = $this->setting['SUBJECT_AFTER'];
        }

        foreach ($this->post_data as $key => $val) {
            if ($key === $this->setting['SUBJECT_ATTRIBUTE']) {
                $subject = $val;
            }
        }
        return $this->ksesRM($this->ksesESC($before . $subject . $after));
    }

    // 管理者宛送信メールヘッダ
    private function getAdminHeader()
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

        //return $header;
        return $this->setting['ADMIN_NAME'];
    }

    // 管理者宛送信メールボディ
    private function getAdminBody()
    {

        $body  = 'サイト「' . $this->setting['FROM_NAME'] . '」でお問い合わせがありました。';
        $body .= "\n------------------------------------------------------\n";
        $body .= $this->getPost();
        $body .= "\n------------------------------------------------------\n";
        $body .= "\n※ この通知は送信専用のメールアドレスから送られています。";
        $body .= "\n※ ご連絡の際はメールの送り先にご注意ください。\n\n";
        $body .= '送信された日時：' . date('Y/m/d (D) H:i:s', time()) . "\n";
        $body .= '送信者のIPアドレス：' . @$_SERVER['REMOTE_ADDR'] . "\n";
        $body .= '送信者のホスト名：' . getHostByAddr($_SERVER['REMOTE_ADDR']) . "\n";
        $body .= 'お問い合わせページURL：' . $this->page_referer . "\n";
        return $body;
    }

    // ユーザ宛送信メールヘッダ
    private function getUserHeader()
    {

        $header = array(
            'From: ' . $this->setting['FROM_NAME'] . ' <' . $this->setting['FROM_MAIL'] . '>',
        );

        //return $header;
        return $this->setting['USER_NAME'];
    }

    // ユーザ宛送信メールボディ
    private function getUserBody()
    {

        $body  = $this->replaceDisplayName($this->setting['BODY_BEGINNING']);
        $body .= "\n------------------------------------------------------\n";
        $body .= $this->getPost();
        $body .= "\n------------------------------------------------------\n";
        $body .= '送信日時：' . date('Y/m/d (D) H:i:s', time()) . "\n";
        $body .= "\n※ この通知は送信専用のメールアドレスから送られています。";
        $body .= "\n※ ご連絡の際はメールの送り先にご注意ください。\n\n";
        $body .= $this->replaceDisplayName($this->setting['BODY_SIGNATURE']);

        return $body;
    }

    // 送信メールにPOSTデータをセットする関数
    private function getPost()
    {
        $resArray = '';
        foreach ($this->post_data as $key => $val) {
            $out = '';
            if (is_array($val)) {
                foreach ($val as $key02 => $item) {
                    // 連結項目の処理
                    if (is_array($item)) {
                        $out .= $this->changeJoin($item);
                    } else {
                        $out .= $item . ', ';
                    }
                }
                $out = rtrim($out, ', ');
            } else {
                $out = $val;
            }

            // 全角を半角へ変換
            $out = $this->changeHankaku($out, $key);

            // アンダースコアで始まる文字は除外
            if (substr($key, 0, 1) !== '_') {
                $resArray .= '【 ' . $this->ksesESC($key) . ' 】 ' . $this->ksesESC($out) . PHP_EOL;
            }

            // フォームの設置ページを保存
            if ($key === '_http_referer') {
                $this->page_referer = $this->ksesESC($out);
            }
        }
        return $resArray;
    }

    // 確認画面の入力内容出力用関数
    private function getConfirm()
    {
        $html = '';

        foreach ($this->post_data as $key => $val) {
            $out = '';

            // チェックボックス（配列）の結合
            if (is_array($val)) {
                foreach ($val as $key02 => $item) {
                    if (is_array($item)) {
                        $out .= $this->changeJoin($item);
                    } else {
                        $out .= $item . ', ';
                    }
                }
                $out = rtrim($out, ', ');
            } else {
                $out = $val;
            }

            // 改行コードを変換
            $out = nl2br($this->ksesESC($out));
            $key = trim($this->ksesESC($key));
            $content = str_replace(array('<br />', '<br>'), '', $out);

            // 全角を半角へ変換
            $out = $this->changeHankaku($out, $key);

            $html .= '<tr><th>' . $key . '</th><td>' . $out;
            $html .= '<input type="hidden" name="' . $key . '" value="' . $content . '" />';
            $html .= '</td></tr>' . PHP_EOL;
        }
        return '<table>' . $html . '</table>';
    }

    // 確認画面のフォームにアクション先出力
    private function getActionURL()
    {
        return $this->ksesESC($this->ksesHTML($_SERVER['SCRIPT_NAME']));
    }

    // 完了後のリンク先
    private function getReturnURL()
    {
        return $this->ksesESC($this->setting['END_URL']);
    }

    // トークン出力
    private function getCreateNonce()
    {
        $token                     = sha1(uniqid((string)mt_rand(), true));
        $_SESSION['_mailer_nonce'] = $token;
        $referer = $this->ksesESC($_SERVER['HTTP_REFERER']);
        $html  = '<input type="hidden" name="_mailer_nonce" value="' . $token . '" />';
        $html .= '<input type="hidden" name="_http_referer" value="' . $referer . '" />';
        $html .= '<input type="hidden" name="_confirm_submit" value="1" />' . PHP_EOL;
        return $html;
    }

    // 必須チェック
    private function checkinRequire()
    {
        $error = '';

        // 必須項目チェック
        if (!empty($this->setting['MANDATORY_ATTRIBUTE'])) {
            foreach ($this->setting['MANDATORY_ATTRIBUTE'] as $requireVal) {
                $existsFalg = '';
                foreach ($this->post_data as $key => $val) {
                    if ($key === $requireVal) {
                        // 連結指定の項目（配列）のための必須チェック
                        if (is_array($val)) {
                            $connectEmpty = 0;
                            foreach ($val as $kk => $vv) {
                                if (is_array($vv)) {
                                    foreach ($vv as $kk02 => $vv02) {
                                        if ($vv02 === '') {
                                            $connectEmpty++;
                                        }
                                    }
                                }
                            }
                            if ($connectEmpty > 0) {
                                $error .= '<p>【' . $this->ksesESC($key) . '】は必須項目です。</p>' . PHP_EOL;
                            }
                        } elseif ($val === '') {
                            // デフォルト必須チェック
                            $error .= '<p>【' . $this->ksesESC($key) . '】は必須項目です。</p>' . PHP_EOL;
                        }
                        $existsFalg = 1;
                        break;
                    }
                }
                if ($existsFalg !== 1) {
                    $error .= '<p>【' . $requireVal . '】が選択されていません。</p>' . PHP_EOL;
                }
            }
        }

        // メール形式チェック
        if (empty($error)) {
            foreach ($this->post_data as $key => $val) {
                if ($key === $this->setting['DISPLAY_NAME']) {
                    $this->setting['USER_NAME'] = $this->ksesRM($this->ksesESC($val));
                }
                if ($key === $this->setting['EMAIL_ATTRIBUTE']) {
                    $this->setting['USER_MAIL'] = $this->ksesRM($this->ksesESC($val));
                }
                if ($key === $this->setting['EMAIL_ATTRIBUTE'] && !empty($val)) {
                    if (!$this->isCheckMailFormat($val)) {
                        $error .= '<p>【' . $key . '】はメールアドレスの形式が正しくありません。</p>' . PHP_EOL;
                    }
                }
            }
        }
        $this->error_massage = isset($error) ? $error : 0;
    }

    // 送信画面判定
    private function isCheckConfirm()
    {
        if (isset($this->post_data['_confirm_submit']) && $this->post_data['_confirm_submit'] === '1') {
            return true;
        } else {
            return false;
        }
    }

    // 必須エラー判定
    private function isCheckRequire()
    {
        return empty($this->error_massage) ? true : false;
    }

    // メール文字判定
    private function isCheckMailFormat($post)
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

    // 禁止ワード
    private function checkNGWord()
    {
        $ng_words = explode(',', $this->setting['NG_WORD']);

        if (empty($ng_words[0])) {
            return;
        } else {
            foreach ($this->post_data as $val) {
                foreach ($ng_words as $word) {
                    if (mb_strpos($val, $word, 0, 'UTF-8') !== false) {
                        return exit('「' . $word . '」を含む単語はブロックされています。');
                    }
                }
            }
        }
    }

    // 日本語チェック
    private function checkMBWord()
    {
        $mb_word = $this->setting['MB_WORD'];

        if (empty($mb_word)) {
            return;
        } else {
            foreach ($this->post_data as $key => $val) {
                if ($key === $mb_word) {
                    if (strlen($val) == mb_strlen($val, 'UTF-8')) {
                        return exit('日本語を含まない文章はブロックされています。');
                    }
                }
            }
        }
    }

    // リファラチェック
    private function checkinReferer()
    {
        if (empty($_SERVER['HTTP_REFERER']) || empty($_SERVER['SERVER_NAME'])) {
            return exit('リファラチェックエラー');
        }
        if (strpos($_SERVER['HTTP_REFERER'], $_SERVER['SERVER_NAME']) === false) {
            return exit('リファラチェックエラー');
        }
    }

    // トークンチェック
    private function checkinToken()
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
            return exit('タイムアウトエラー');
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

    // 全角→半角変換
    private function changeHankaku($out, $key)
    {
        if (empty($this->setting['HANKAKU_ATTRIBUTE']) || !function_exists('mb_convert_kana')) {
            return $out;
        }
        if (is_array($this->setting['HANKAKU_ATTRIBUTE'])) {
            foreach ($this->setting['HANKAKU_ATTRIBUTE'] as $val) {
                if ($key === $val) {
                    $out = mb_convert_kana($out, 'a', 'UTF-8');
                }
            }
        } else {
            $out = mb_convert_kana($out, 'a', 'UTF-8');
        }
        return $out;
    }

    // 配列連結の処理
    private function changeJoin($arr)
    {
        $out = '';
        foreach ($arr as $key => $val) {
            if ($key === 0 || $val == '') {
                // 配列が0、または内容が空の場合は連結文字を付加しない
                $key = '';
            } elseif (strpos($key, '円') !== false && $val != '' && preg_match('/^[0-9]+$/', $val)) {
                // 金額の場合には3桁ごとにカンマを追加
                $val = number_format($val);
            }
            $out .= $val . $key;
        }
        return $out;
    }

    // 名前置換え
    private function replaceDisplayName($rep)
    {
        $str = $this->setting['DISPLAY_NAME'];
        $pos = $this->post_data[$str];
        // {お名前}変換
        $name_a = array('{' . $str . '}', '｛' . $str . '｝', '{' . $str . '｝', '｛' . $str . '}');
        $name_b = $this->ksesESC($pos);
        // 置換
        $txt = str_replace($name_a, $name_b, $rep);
        return $txt;
    }

    // 空白と改行を消すインジェクション対策
    private function ksesRM($str)
    {
        $str = str_replace(array("\r\n", "\r", "\n"), '', $str);
        return trim($str);
    }

    // エスケープ
    private function ksesESC($value, $enc = 'UTF-8')
    {
        if (is_array($value)) {
            return array_map(array($this, 'esc'), $value);
        }
        return htmlspecialchars($value, ENT_QUOTES, $enc);
    }

    // NULLバイトとHTMLタグ除去
    private function ksesHTML($str)
    {
        $sanitized = array();
        if (is_array($str)) {
            foreach ($str as $key => $val) {
                $sanitized[$key] = strip_tags(str_replace("\0", '', $val));
            }
        } else {
            return strip_tags(str_replace("\0", '', $str));
        }
        return $sanitized;
    }
}
