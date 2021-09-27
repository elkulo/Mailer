<?php
/**
 * Mailer | el.kulo v1.0.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2021 A.Sudo
 * Licensed under MIT (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

namespace App\Domain\Mailer;

use App\Application\Settings\SettingsInterface;
use Twig\Loader\FilesystemLoader as TwigFileLoader;
use Twig\Loader\ArrayLoader as TwigArrayLoader;
use Twig\Environment as TwigEnvironment;

class MailPost
{

    /**
     * 設定
     *
     * @var array
     */
    private array $server;

    /**
     * 設定
     *
     * @var array
     */
    private array $setting;

    /**
     * POSTデータ
     *
     * @var array
     */
    private array $post_data;

    /**
     * ユーザーメール格納先
     *
     * @var string
     */
    private string $user_mail = '';

    /**
     * ページリファラー
     *
     * @var string
     */
    private string $page_referer;

    /**
     * Twig ハンドラー
     *
     * @var object
     */
    private object $view;

    /**
     * コンストラクタ
     *
     * @param  array $posts
     * @param  SettingsInterface $settings
     * @return void
     */
    public function __construct(array $posts, SettingsInterface $settings)
    {
        $this->server = $settings->get('config.server');
        $this->setting = $settings->get('config.form');
        $app_path = $settings->get('app.path');

        // POSTデータから取得したデータを整形
        $sanitized = array();
        foreach ($posts as $name => $value) {
            $sanitized[$name] = trim(strip_tags(str_replace("\0", '', $value)));

            // フォームの設置ページを保存.
            if ($name === '_http_referer') {
                $this->page_referer = $this->kses($value);
            }
        }
        $this->post_data = $sanitized;

        // Twigの初期化
        $this->view = new TwigEnvironment(
            new TwigFileLoader(array(
                $app_path . '/../templates/mail',
                $app_path . '/src/Views/templates/mail',
            ))
        );
    }

    /**
     * サーバー設定情報の取得
     *
     * @return array
     */
    public function getServer(): array
    {
        return $this->server;
    }

    /**
     * アプリ設定情報の取得
     *
     * @return array
     */
    public function getSetting(): array
    {
        return $this->setting;
    }

    /**
     * POSTデータを取得
     *
     * @return array
     */
    public function getPost(): array
    {
        return $this->post_data;
    }

    /**
     * POSTデータを文字連結して取得
     *
     * @return string
     */
    public function getPostToString(): string
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
        }
        return $this->kses($response);
    }

    /**
     * Twigテンプレート用に{{name属性}}で置換.
     *
     * @return array
     */
    public function getPostToTwig(): array
    {
        $post_data = $this->post_data;
        $posts = array();
        foreach ($post_data as $key => $value) {
            // アンダースコアは除外.
            if (substr($key, 0, 1) !== '_') {
                $posts[$key] = $value;
            }
        }
        return $posts;
    }

    /**
     * ユーザーメールをセット
     *
     * @param  string $user_mail
     * @return void
     */
    public function setUserMail(string $user_mail): void
    {
        $this->user_mail = $user_mail;
    }

    /**
     * ユーザーメールを取得
     *
     * @return string
     */
    public function getUserMail(): string
    {
        return $this->user_mail;
    }

    /**
     * メール件名（共通）
     *
     * @return string
     */
    public function getMailSubject(): string
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
     * @return array
     */
    public function getMailBody(): array
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
            '__FROM_SITE_NAME' => $this->server['FROM_NAME'],
            '__POST_ALL' => $this->getPostToString(),
            '__DATE' => date('Y/m/d (D) H:i:s', time()),
            '__IP' => $_SERVER['REMOTE_ADDR'],
            '__HOST' => getHostByAddr($_SERVER['REMOTE_ADDR']),
            '__URL' => $this->page_referer,
        );

        return array_merge($posts, $value);
    }

    /**
     * 管理者メールヘッダ.
     *
     * @param  string $type
     * @return array
     */
    public function getMailAdminHeader(): array
    {
        $header = array();

        // 管理者宛送信メール.
        if (!empty($this->server['ADMIN_CC'])) {
            $header[] = 'Cc: ' . $this->server['ADMIN_CC'];
        }
        if (!empty($this->server['ADMIN_BCC'])) {
            $header[] = 'Bcc: ' . $this->server['ADMIN_BCC'];
        }
        if (!empty($this->setting['IS_FROM_USERMAIL'])) {
            $header[] = 'Reply-To: ' . $this->user_mail;
        }

        return $header;
    }


    /**
     * 管理者メールテンプレート
     *
     * @param  array $data
     * @return string
     */
    public function renderAdminMail(array $data): string
    {
        // 管理者宛送信メール.
        if (!empty($this->setting['TEMPLATE_MAIL_ADMIN'])) {
            return (new TwigEnvironment(
                new TwigArrayLoader(array(
                    'admin.mail.tpl' => $this->setting['TEMPLATE_MAIL_ADMIN']
                ))
            ))->render('admin.mail.tpl', $data);
        }
        return $this->view->render('admin.mail.twig', $data);
    }

    /**
     * ユーザーメールテンプレート
     *
     * @param  array $data
     * @return string
     */
    public function renderUserMail(array $data): string
    {
        // ユーザ宛送信メール.
        if (!empty($this->setting['TEMPLATE_MAIL_USER'])) {
            return (new TwigEnvironment(
                new TwigArrayLoader(array(
                    'user.mail.tpl' => $this->setting['TEMPLATE_MAIL_USER']
                ))
            ))->render('user.mail.tpl', $data);
        }
        return $this->view->render('user.mail.twig', $data);
    }

    /**
     * ページリファラーを取得
     *
     * @return string
     */
    public function getPageReferer(): string
    {
        return $this->page_referer;
    }

    /**
     * 確認画面の入力内容出力用関数
     *
     * @return string
     */
    public function getConfirmContent(): string
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
    public function getActionURL(): string
    {
        return $this->kses('http://localhost:8000/mailer-alias/complete');
    }

    /**
     * 完了後のリンク先
     *
     * @return string
     */
    public function getReturnURL(): string
    {
        return $this->kses($this->setting['RETURN_PAGE']);
    }

    /**
     * トークン出力
     *
     * @return string
     */
    public function getCreateNonce(): string
    {
        // セッションにNonceを保存
        $nonce                     = sha1(uniqid((string)mt_rand(), true));
        $_SESSION['mailerToken'] = $nonce;
        return sprintf(
            '<input type="hidden" name="_mailer_nonce" value="%1$s" />
            <input type="hidden" name="_http_referer" value="%2$s" />
            <input type="hidden" name="_confirm_submit" value="1" />' . PHP_EOL,
            $nonce,
            $this->kses($_SERVER['HTTP_REFERER'])
        );
    }

    /**
     * リファラチェック
     *
     * @return void
     */
    public function checkinReferer(): void
    {
        if (isset($_SERVER['HTTP_REFERER']) && isset($_SERVER['SERVER_NAME'])) {
            if (strpos($_SERVER['HTTP_REFERER'], $_SERVER['SERVER_NAME']) === false) {
                throw new \Exception('指定のページ以外から送信されています');
            }
        }
        if (empty($_SERVER['HTTP_REFERER']) || empty($_SERVER['SERVER_NAME'])) {
            throw new \Exception('指定のページ以外から送信されています');
        }
    }

    /**
     * トークンチェック
     *
     * @return void
     */
    public function checkinToken(): void
    {
        // TokenとNonceを照合
        if (empty($_SESSION['mailerToken']) || ($_SESSION['mailerToken'] !== $_POST['_mailer_nonce'])) {
            throw new \Exception('連続した投稿の可能性があるため送信できません');
        }
        // 連続投稿防止のためトークン削除
        if (isset($_SESSION['mailerToken'])) {
            unset($_SESSION['mailerToken']);
        }
    }

    /**
     * nameとラベルの属性の置き換え
     *
     * @param  string $name
     * @return string
     */
    public function nameToLabel(string $name): string
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
    public function changeHankaku(string $output, string $key): string
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
    public function changeJoin(array $items): string
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
    public function kses($content, string $encode = 'UTF-8')
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
