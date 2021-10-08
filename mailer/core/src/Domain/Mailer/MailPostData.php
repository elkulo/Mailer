<?php
/**
 * Mailer | el.kulo v3.0.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2021 A.Sudo
 * Licensed under MIT (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

namespace App\Domain\Mailer;

use App\Application\Settings\SettingsInterface;
use Twig\Loader\FilesystemLoader as TwigFileLoader;
use Twig\Loader\ArrayLoader as TwigArrayLoader;
use Twig\Environment as TwigEnvironment;

class MailPostData
{

    /**
     * メール設定値
     *
     * @var array
     */
    private array $mailSettings = [];

    /**
     * フォーム設定値
     *
     * @var array
     */
    private array $formSettings = [];

    /**
     * POSTデータ
     *
     * @var array
     */
    private array $postData = [];

    /**
     * ユーザーメールの格納
     *
     * @var string
     */
    private string $userMailAddress = '';

    /**
     * ページリファラー
     *
     * @var string
     */
    private string $pageReferer = '';

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
        $this->mailSettings = $settings->get('mail');
        $this->formSettings = $settings->get('form');
        $appPath = $settings->get('appPath');

        // POSTデータから取得したデータを整形
        $sanitized = array();
        foreach ($posts as $name => $value) {
            // アンダースコアは除外.
            if (substr($name, 0, 1) !== '_') {
                $sanitized[$name] = trim(strip_tags(str_replace("\0", '', $value)));
            }

            // フォームの設置ページを保存.
            if ($name === '_http_referer') {
                $this->setPageReferer($value);
            }
        }
        $this->postData = $sanitized;

        // Twigの初期化
        $this->view = new TwigEnvironment(
            new TwigFileLoader(array(
                $appPath . '/../templates/mail',
                $appPath . '/src/Views/templates/mail',
            ))
        );
    }

    /**
     * POSTデータを取得
     *
     * @return array
     */
    public function getPosts(): array
    {
        return $this->postData;
    }

    /**
     * POSTデータを文字連結して取得
     *
     * @return string
     */
    public function getPostToString(): string
    {
        $response = '';
        foreach ($this->postData as $name => $value) {
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
        $post_data = $this->postData;
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
     * @param  string $email
     * @return void
     */
    public function setUserMail(string $email): void
    {
        $this->userMailAddress = $email;
    }

    /**
     * ユーザーメールを取得
     *
     * @return string
     */
    public function getUserMail(): string
    {
        return $this->userMailAddress;
    }

    /**
     * メール件名（共通）
     *
     * @return string
     */
    public function getMailSubject(): string
    {
        $subject = '';
        $before = isset($this->formSettings['SUBJECT_BEFORE']) ? $this->formSettings['SUBJECT_BEFORE'] : '';
        $after = isset($this->formSettings['SUBJECT_AFTER']) ? $this->formSettings['SUBJECT_AFTER'] : '';
        foreach ($this->postData as $key => $value) {
            if ($key === $this->formSettings['SUBJECT_ATTRIBUTE']) {
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
        foreach ($this->postData as $key => $value) {
            // アンダースコアは除外.
            if (substr($key, 0, 1) !== '_') {
                $posts[$key] = $value;
            }
        }

        // クライアント情報の置換.
        $value = array(
            '__FROM_NAME' => $this->mailSettings['FROM_NAME'],
            '__POST_ALL' => $this->getPostToString(),
            '__DATE' => date('Y/m/d (D) H:i:s', time()),
            '__IP' => $_SERVER['REMOTE_ADDR'],
            '__HOST' => getHostByAddr($_SERVER['REMOTE_ADDR']),
            '__URL' => $this->getPageReferer(),
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
        if (!empty($this->mailSettings['ADMIN_CC'])) {
            $header[] = 'Cc: ' . $this->mailSettings['ADMIN_CC'];
        }
        if (!empty($this->mailSettings['ADMIN_BCC'])) {
            $header[] = 'Bcc: ' . $this->mailSettings['ADMIN_BCC'];
        }
        if (!empty($this->formSettings['IS_FROM_USERMAIL'])) {
            $header[] = 'Reply-To: ' . $this->userMailAddress;
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
        if (!empty($this->formSettings['TEMPLATE_ADMIN_MAIL'])) {
            return (new TwigEnvironment(
                new TwigArrayLoader(array(
                    'admin.mail.tpl' => $this->formSettings['TEMPLATE_ADMIN_MAIL']
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
        if (!empty($this->formSettings['TEMPLATE_USER_MAIL'])) {
            return (new TwigEnvironment(
                new TwigArrayLoader(array(
                    'user.mail.tpl' => $this->formSettings['TEMPLATE_USER_MAIL']
                ))
            ))->render('user.mail.tpl', $data);
        }
        return $this->view->render('user.mail.twig', $data);
    }

    /**
     * 確認画面の入力内容の出力
     *
     * @return array
     */
    public function getConfirmQuery(): array
    {
        $query = [];

        foreach ($this->postData as $name => $value) {
            $output = '';

            // チェックボックス（配列）の結合
            if (is_array($value)) {
                foreach ($value as $item) {
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

            // 全角を半角へ変換
            $output = $this->changeHankaku($output, $name);

            // 確認をセット
            $query[]= [
                'name' => $this->nameToLabel($name) . sprintf(
                    '<input type="hidden" name="%1$s" value="%2$s" />',
                    $this->kses($name),
                    $this->kses($output)
                ),
                'value' => nl2br($this->kses($output))
            ];
        }
        return $query;
    }

    /**
     * 完了後のリンク先
     *
     * @return string
     */
    public function getReturnURL(): string
    {
        return $this->kses($this->formSettings['RETURN_PAGE']);
    }


    /**
     * 固有のトークン生成
     *
     * @return void
     */
    public function createMailerToken(): void
    {
        // セッションにNonceを保存
        $_SESSION['mailerToken'] = sha1(uniqid((string)mt_rand(), true));
    }

    /**
     * 固有のメールトークンで重複チェック
     *
     * @return void
     */
    public function checkinMailerToken(): void
    {
        // 連続投稿防止のためトークン削除
        if (isset($_SESSION['mailerToken'])) {
            unset($_SESSION['mailerToken']);
        } else {
            throw new \Exception('連続した投稿の可能性があるため送信できません');
        }
    }

    /**
     * ページリファラーをセット
     *
     * @return void
     */
    public function setPageReferer($value): void
    {
        $this->pageReferer = $this->kses($value);
    }

    /**
     * ページリファラーを取得
     *
     * @return string
     */
    public function getPageReferer(): string
    {
        if (! $this->pageReferer && isset($_SERVER['HTTP_REFERER'])) {
            return $this->kses($_SERVER['HTTP_REFERER']);
            ;
        }
        return $this->pageReferer;
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
        if (isset($this->formSettings['NAME_FOR_LABELS'][$label])) {
            $label = $this->formSettings['NAME_FOR_LABELS'][$label];
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
        if (empty($this->formSettings['HANKAKU_ATTRIBUTES']) || !function_exists('mb_convert_kana')) {
            return $output;
        }
        if (is_array($this->formSettings['HANKAKU_ATTRIBUTES'])) {
            foreach ($this->formSettings['HANKAKU_ATTRIBUTES'] as $val) {
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
