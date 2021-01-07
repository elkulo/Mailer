<?php
namespace Pidgeot\Core;

class Mailer extends SMTP
{

    private $settings = array(); // 設定

    private $post_data; // $_POST

    private $error_msg; // エラーメッセージ

    private $user_name; // ユーザーネーム

    private $user_mail; // ユーザーメール

    private $page_referer; // フォームの設置ページの格納

    public function __construct($draft = null)
    {
        if ($draft) {
            $this->settings = $draft;
        } else {
            exit('Mail Error: Email settings do not exist.');
        }

        // トークンチェック用のセッションスタート
        if (! isset($_SESSION)) {
            session_name(MD5('SENDMAIL'));
            session_start();
        }

        // NULLバイト除去して格納
        if (isset($_POST)) {
            $this->post_data = $this->sanitize($_POST);
        }
    }

    // 基本機能
    public function init()
    {

        // リファラチェック
        $this->checkin_referer();

        // 日本語チェック
        $this->checkin_MB_word();

        // NGワードチェック
        $this->checkin_NG_word();

        // 必須項目チェックで $error_msg にエラー格納
        $this->checkin_require();

        // 管理画面通過チェック
        if ($this->is_check_sendmail() && $this->is_check_require()) {
            // トークンチェック
            $this->checkin_token();

            // 管理者宛に届くメールをセット
            $this->sendmail(
                $this->settings['ADMIN_MAIL'],
                $this->settings['ADMIN_NAME'], //$this->get_admin_header(),
                $this->get_mail_subject(),
                $this->get_admin_body()
            );

            // ユーザーに届くメールをセット
            if ($this->settings['RETURN_USER'] == 1) {
                $this->sendmail(
                    $this->settings['USER_MAIL'],
                    $this->settings['USER_NAME'], //$this->get_user_header(),
                    $this->get_mail_subject(),
                    $this->get_user_body()
                );
            }

            // 送信完了画面
            require dirname(__DIR__) . '/template/completion.php';
        } else {
            // 確認画面とエラー画面の分岐
            if ($this->is_check_require()) {
                require dirname(__DIR__) . '/template/confirm.php';
            } else {
                require dirname(__DIR__) . '/template/error.php';
            }
        }
    }

    // 件名（共通）
    private function get_mail_subject()
    {
        $subject = 'No Subject';
        $before  = $after = '';
        if ($this->settings['Before_SUBJECT']) {
            $before = $this->settings['Before_SUBJECT'];
        }
        if ($this->settings['After_SUBJECT']) {
            $after = $this->settings['After_SUBJECT'];
        }

        foreach ($this->post_data as $key => $val) {
            if ($key === $this->settings['Subject_ATTRIBUTE']) {
                $subject = $val;
            }
        }
        return $this->rm($this->esc($before . $subject . $after));
    }

    // 管理者宛送信メールヘッダ
    private function get_admin_header()
    {

        $header = array(
            'From: ' . $this->settings['FROM_NAME'] . ' <' . $this->settings['FROM_MAIL'] . '>',
        );

        if ($this->settings['IS_FROM_USERMAIL'] == 1) {
            $header[] = 'Reply-To: ' . $this->settings['USER_NAME'] . ' <' . $this->settings['USER_MAIL'] . '>';
        }
        if ($this->settings['ADMIN_MAIL_BCC'] !== '') {
            $header[] = 'Bcc: ' . $this->settings['ADMIN_MAIL_BCC'];
        }
        return $header;
    }

    // 管理者宛送信メールボディ
    private function get_admin_body()
    {

        $body  = 'サイト「' . $this->settings['FROM_NAME'] . '」でお問い合わせがありました。';
        $body .= "\n------------------------------------------------------\n";
        $body .= $this->get_post();
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
    private function get_user_header()
    {

        $header = array(
            'From: ' . $this->settings['FROM_NAME'] . ' <' . $this->settings['FROM_MAIL'] . '>',
        );
        return $header;
    }

    // ユーザ宛送信メールボディ
    private function get_user_body()
    {

        $body  = $this->replace_display_name($this->settings['BODY_BEGINNING']);
        $body .= "\n------------------------------------------------------\n";
        $body .= $this->get_post();
        $body .= "\n------------------------------------------------------\n";
        $body .= '送信日時：' . date('Y/m/d (D) H:i:s', time()) . "\n";
        $body .= "\n※ この通知は送信専用のメールアドレスから送られています。";
        $body .= "\n※ ご連絡の際はメールの送り先にご注意ください。\n\n";
        $body .= $this->replace_display_name($this->settings['BODY_SIGNATURE']);

        return $body;
    }

    // 送信メールにPOSTデータをセットする関数
    private function get_post()
    {
        $resArray = '';
        foreach ($this->post_data as $key => $val) {
            $out = '';
            if (is_array($val)) {
                foreach ($val as $key02 => $item) {
                    // 連結項目の処理
                    if (is_array($item)) {
                        $out .= $this->change_join($item);
                    } else {
                        $out .= $item . ', ';
                    }
                }
                $out = rtrim($out, ', ');
            } else {
                $out = $val;
            }

            // 全角を半角へ変換
            $out = $this->change_hankaku($out, $key);

            // アンダースコアで始まる文字は除外
            if (substr($key, 0, 1) !== '_') {
                $resArray .= '【 ' . $this->esc($key) . ' 】 ' . $this->esc($out) . PHP_EOL;
            }

            // フォームの設置ページを保存
            if ($key === '_http_referer') {
                $this->page_referer = $this->esc($out);
            }
        }
        return $resArray;
    }

    // 確認画面の入力内容出力用関数
    private function the_confirm()
    {
        $html = '';

        foreach ($this->post_data as $key => $val) {
            $out = '';

            // チェックボックス（配列）の結合
            if (is_array($val)) {
                foreach ($val as $key02 => $item) {
                    if (is_array($item)) {
                        $out .= $this->change_join($item);
                    } else {
                        $out .= $item . ', ';
                    }
                }
                $out = rtrim($out, ', ');
            } else {
                $out = $val;
            }

            // 改行コードを変換
            $out = nl2br($this->esc($out));
            $key = trim($this->esc($key));

            // 全角を半角へ変換
            $out = $this->change_hankaku($out, $key);

            $html .= '<tr><th>' . $key . '</th><td>' . $out;
            $html .= '<input type="hidden" name="' . $key . '" value="' . str_replace(array( '<br />', '<br>' ), '', $out) . '" />';
            $html .= '</td></tr>' . PHP_EOL;
        }
        echo $html;
    }

    // 完了後のリンク先
    private function the_return()
    {
        echo $this->esc($this->settings['END_URL']);
    }

    // 必須チェック
    private function checkin_require()
    {
        $error = '';

        // 必須項目チェック
        if (! empty($this->settings['MANDATORY'])) {
            foreach ($this->settings['MANDATORY'] as $requireVal) {
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
                                $error .= '<p class="error_messe">【' . $this->esc($key) . "】は必須項目です。</p>\n";
                            }
                        } elseif ($val === '') {
                            // デフォルト必須チェック
                            $error .= '<p class="error_messe">【' . $this->esc($key) . "】は必須項目です。</p>\n";
                        }
                        $existsFalg = 1;
                        break;
                    }
                }
                if ($existsFalg !== 1) {
                    $error .= '<p class="error_messe">【' . $requireVal . "】が選択されていません。</p>\n";
                }
            }
        }

        // メール形式チェック
        if (empty($error)) {
            foreach ($this->post_data as $key => $val) {
                if ($key === $this->settings['DISPLAY_NAME']) {
                    $this->settings['USER_NAME'] = $this->rm($this->esc($val));
                }
                if ($key === $this->settings['Email_ATTRIBUTE']) {
                    $this->settings['USER_MAIL'] = $this->rm($this->esc($val));
                }
                if ($key === $this->settings['Email_ATTRIBUTE'] && ! empty($val)) {
                    if (! $this->check_mail_format($val)) {
                        $error .= '<p class="error_messe">【' . $key . "】はメールアドレスの形式が正しくありません。</p>\n";
                    }
                }
            }
        }
        $this->error_msg = isset($error) ? $error : 0;
    }

    // 送信画面判定
    private function is_check_sendmail()
    {
        return ( isset($this->post_data['_confirm_submit']) && $this->post_data['_confirm_submit'] === '1' ) ? true : false;
    }

    // 必須エラー判定
    private function is_check_require()
    {
        return empty($this->error_msg) ? true : false;
    }

    // メール文字判定
    private function check_mail_format($str)
    {
        $str               = trim($str);
        $mailaddress_array = explode('@', $str);
        if (preg_match('/^[\.!#%&\-_0-9a-zA-Z\?\/\+]+\@[!#%&\-_0-9a-z]+(\.[!#%&\-_0-9a-z]+)+$/', "$str") && count($mailaddress_array) == 2) {
            return true;
        } else {
            return false;
        }
    }

    // リファラチェック
    private function checkin_referer()
    {
        if (empty($_SERVER['HTTP_REFERER']) || empty($_SERVER['SERVER_NAME'])) {
            return exit('リファラチェックエラー');
        }
        if (strpos($_SERVER['HTTP_REFERER'], $_SERVER['SERVER_NAME']) === false) {
            return exit('リファラチェックエラー');
        }
    }

    // トークンチェック
    private function checkin_token()
    {
        if (empty($_SESSION['_mailer_nonce']) || ( $_SESSION['_mailer_nonce'] !== $_POST['_mailer_nonce'] )) {
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

    // トークン出力
    private function the_create_nonce()
    {
        $token                     = sha1(uniqid(mt_rand(), true));
        $_SESSION['_mailer_nonce'] = $token;
        echo '<input type="hidden" name="_mailer_nonce" value="' . $token . '" />';
        echo '<input type="hidden" name="_http_referer" value="' . $this->esc($_SERVER['HTTP_REFERER']) . '" />';
        echo '<input type="hidden" name="_confirm_submit" value="1">' . PHP_EOL;
    }

    // 空白と改行を消すインジェクション対策
    private function rm($str)
    {
        $str = str_replace(array( "\r\n", "\r", "\n" ), '', $str);
        return trim($str);
    }

    // エスケープ
    private function esc($value, $enc = 'UTF-8')
    {
        if (is_array($value)) {
            return array_map(array( $this, 'esc' ), $value);
        }
        return htmlspecialchars($value, ENT_QUOTES, $enc);
    }

    // NULLバイトとHTMLタグ除去
    private function sanitize($str)
    {
        $sanitized = array();
        if (is_array($str)) {
            foreach ($str as $key => $val) {
                $sanitized[ $key ] = strip_tags(str_replace("\0", '', $val));
            }
        } else {
            return strip_tags(str_replace("\0", '', $str));
        }
        return $sanitized;
    }

    // 全角→半角変換
    private function change_hankaku($out, $key)
    {
        if (empty($this->settings['HANKAKU']) || ! function_exists('mb_convert_kana')) {
            return $out;
        }
        if (is_array($this->settings['HANKAKU'])) {
            foreach ($this->settings['HANKAKU'] as $val) {
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
    private function change_join($arr)
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
    private function replace_display_name($rep)
    {
        $str = $this->settings['DISPLAY_NAME'];
        $pos = $this->post_data[ $str ];
        // {お名前}変換
        $name_a = array( '{' . $str . '}', '｛' . $str . '｝', '{' . $str . '｝', '｛' . $str . '}' );
        $name_b = $this->esc($pos);
        // 置換
        $txt = str_replace($name_a, $name_b, $rep);
        return $txt;
    }

    // 確認画面のフォームにアクション先出力
    private function the_action()
    {
        echo $this->esc($this->sanitize($_SERVER['SCRIPT_NAME']));
    }

    // 禁止ワード
    private function checkin_NG_word()
    {
        $str      = $this->settings['NG_WORD'];
        $NG_words = explode(',', $str);

        if (empty($NG_words[0])) {
            return;
        } else {
            foreach ($this->post_data as $key => $val) {
                foreach ($NG_words as $NG) {
                    if (mb_strpos($val, $NG, 0, 'UTF-8') !== false) {
                        return exit('「' . $NG . '」を含む単語はブロックされています。');
                    }
                }
            }
        }
    }

    // 日本語チェック
    private function checkin_MB_word()
    {
        $MB_word = $this->settings['MB_JAPANESE'];

        if (empty($MB_word)) {
            return;
        } else {
            foreach ($this->post_data as $key => $val) {
                if ($key === $MB_word) {
                    if (strlen($val) == mb_strlen($val, 'UTF-8')) {
                        return exit('日本語を含まない文章はブロックされています。');
                    }
                }
            }
        }
    }
}
