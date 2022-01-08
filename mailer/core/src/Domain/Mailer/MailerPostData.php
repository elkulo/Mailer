<?php
/**
 * Mailer | el.kulo v3.2.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2022 A.Sudo
 * Licensed under LGPL-2.1-only (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

namespace App\Domain\Mailer;

use App\Application\Settings\SettingsInterface;
use Twig\Loader\FilesystemLoader as TwigFileLoader;
use Twig\Loader\ArrayLoader as TwigArrayLoader;
use Twig\Environment as TwigEnvironment;

class MailerPostData
{

    /**
     * 設定値
     *
     * @var SettingsInterface
     */
    private $settings;

    /**
     * メール設定値
     *
     * @var array
     */
    private $mailSettings = [];

    /**
     * フォーム設定値
     *
     * @var array
     */
    private $formSettings = [];

    /**
     * POSTデータ
     *
     * @var array
     */
    private $postData = [];

    /**
     * ユーザーメールの格納
     *
     * @var string
     */
    private $userMailAddress = '';

    /**
     * ページリファラー
     *
     * @var string
     */
    private $pageReferer = '';

    /**
     * Twig ハンドラー
     *
     * @var TwigEnvironment
     */
    private $view;

    /**
     * メールテンプレート用のファイル名
     *
     * @var array
     */
    private $mailFileNames = [];

    /**
     * 送信時の識別UUID
     *
     * @var string
     */
    private $uuid = '';

    /**
     * コンストラクタ
     *
     * @param  array $posts
     * @param  SettingsInterface $settings
     * @return void
     */
    public function __construct(array $posts, SettingsInterface $settings)
    {
        $this->settings = $settings;
        $this->mailSettings = $settings->get('mail');
        $this->formSettings = $settings->get('form');

        // POSTデータから取得したデータを整形
        $sanitized = [];
        foreach ($posts as $name => $value) {
            // アンダースコアは除外.
            if (substr($name, 0, 1) !== '_') {
                $sanitized[$name] = $this->kses($value);
            }

            // フォームの設置ページを保存.
            if ($name === '_http_referer') {
                $this->setPageReferer($value);
            }
        }
        $this->postData = $this->esc($sanitized);

        // Twigの初期化
        $mailTemplatePath = [
            $settings->get('appPath') . '/src/Views/mailer/templates/mail'
        ];
        if (file_exists($settings->get('templatesDirPath') . '/mail')) {
            array_unshift($mailTemplatePath, $settings->get('templatesDirPath') . '/mail');
        }
        $this->view = new TwigEnvironment(new TwigFileLoader($mailTemplatePath));
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

            // 結合.
            $response .= $this->nameToLabel($name) . ': ' . $output . PHP_EOL;
        }
        return $this->esc($response);
    }

    /**
     * POST元のステータス
     *
     * @param  string $format
     * @return array
     */
    public function getPostStatus(string $format = ''): array
    {
        $status = [
            'date' => date($this->settings->get('dateFormat'), time()),
            'ip' => $this->esc($_SERVER['REMOTE_ADDR']),
            'host' => $this->esc(getHostByAddr($_SERVER['REMOTE_ADDR'])),
            'referer' => $this->getPageReferer(),
            'ua' => $this->esc($_SERVER['HTTP_USER_AGENT']),
            'uuid' => $this->getGenerateUUID(),
        ];

        // Twig出力では[__KEY]の形式に変換.
        if ($format === 'twig') {
            foreach ($status as $key => $value) {
                $status[ '__' . strtoupper($key) ] = $value;
            }
        }
        return $status;
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
        return str_replace(PHP_EOL, '', $this->esc($before . $subject . $after));
    }

    /**
     * メールボディ（共通）
     *
     * @return array
     */
    public function getMailBody(): array
    {
        // Twig変数にクライアント情報の置換.
        return array_merge(
            $this->postData,
            $this->mailFileNames,
            $this->getPostStatus('twig'),
            [
                '__SITE_TITLE' => $this->settings->get('siteTitle'),
                '__SITE_URL' => $this->settings->get('siteUrl'),
                '__POST_ALL' => $this->getPostToString(),
            ]
        );
    }

    /**
     * メールテンプレート用にファイル名を格納
     *
     * @return void
     */
    public function setMailFileName(array $files): void
    {
        $this->mailFileNames = $files;
    }

    /**
     * 管理者メールヘッダ.
     *
     * @return array
     */
    public function getMailAdminHeader(): array
    {
        $header = [];

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
                new TwigArrayLoader([
                    'admin.mail.tpl' => $this->formSettings['TEMPLATE_ADMIN_MAIL']
                ])
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
                new TwigArrayLoader([
                    'user.mail.tpl' => $this->formSettings['TEMPLATE_USER_MAIL']
                ])
            ))->render('user.mail.tpl', $data);
        }
        return $this->view->render('user.mail.twig', $data);
    }

    /**
     * 確認画面の入力内容の表示の出力
     *
     * @return array
     */
    public function getDataQuery(): array
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
            $query[] = [
                'name' => $this->nameToLabel($name),
                'value' => nl2br($this->esc($output))
            ];
        }
        return $query;
    }

    /**
     * 確認画面の入力内容の隠し出力
     *
     * @return string
     */
    public function getTmpPosts(): string
    {
        $query = '';

        foreach ($this->postData as $name => $value) {
            $query .= sprintf(
                '<input type="hidden" name="%1$s" value="%2$s" />',
                $this->esc($name),
                $this->esc($value)
            );
        }

        // ページリファラーを継承.
        $query .= sprintf(
            '<input type="hidden" name="_http_referer" value="%1$s" />',
            $this->getPageReferer()
        );
        return $query;
    }

    /**
     * 完了後のリンク先
     *
     * @return string
     */
    public function getReturnURL(): string
    {
        return $this->esc($this->formSettings['RETURN_PAGE']);
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
            throw new \Exception('連続した投稿の可能性があるため送信できません。');
        }
    }

    /**
     * ページリファラーをセット
     *
     * @param  string $url
     * @return void
     */
    private function setPageReferer(string $url): void
    {
        $this->pageReferer = $this->esc($url);
    }

    /**
     * ページリファラーを取得
     *
     * @return string
     */
    private function getPageReferer(): string
    {
        if (!$this->pageReferer && isset($_SERVER['HTTP_REFERER'])) {
            return $this->esc($_SERVER['HTTP_REFERER']);
        }
        return $this->esc($this->pageReferer);
    }

    /**
     * nameとラベルの属性の置き換え
     *
     * @param  string $name
     * @return string
     */
    private function nameToLabel(string $name): string
    {
        $label = $this->esc($name);
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
    private function changeHankaku(string $output, string $key): string
    {
        if (empty($this->formSettings['HANKAKU_ATTRIBUTES']) || !function_exists('mb_convert_kana')) {
            return $output;
        }
        if (is_array($this->formSettings['HANKAKU_ATTRIBUTES'])) {
            foreach ($this->formSettings['HANKAKU_ATTRIBUTES'] as $value) {
                if ($key === $value) {
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
        foreach ($items as $key => $value) {
            if ($key === 0 || $value === '') {
                // 配列が0、または内容が空の場合は連結文字を付加しない
                $key = '';
            } elseif (strpos($key, '円') !== false && preg_match('/^[0-9]+$/', $value)) {
                // 金額の場合には3桁ごとにカンマを追加
                $value = number_format($value);
            }
            $output .= $value . $key;
        }
        return $output;
    }

    /**
     * UUIDを生成
     *
     * @return string
     */
    private function getGenerateUUID(): string
    {
        if (!$this->uuid) {
            $chars = str_split('XXXXXXXX-XXXX-4XXX-YXXX-XXXXXXXXXXXX');

            foreach ($chars as $i => $char) {
                if ('X' === $char) {
                    $chars[ $i ] = dechex(random_int(0, 15));
                } elseif ('Y' === $char) {
                    $chars[ $i ] = dechex(random_int(8, 11));
                }
            }
            $this->uuid = strtolower(implode('', $chars));
        }
        return $this->uuid;
    }

    /**
     * エスケープ
     *
     * @param  array|string $content
     * @param  string $encode
     * @return array|string
     */
    private function esc($content, string $encode = 'UTF-8')
    {
        $sanitized = [];
        if (is_array($content)) {
            foreach ($content as $key => $value) {
                $sanitized[$key] = trim(htmlspecialchars($value, ENT_QUOTES, $encode));
            }
        } else {
            return trim(htmlspecialchars($content, ENT_QUOTES, $encode));
        }
        return $sanitized;
    }

    /**
     * 除去
     *
     * @param  array|string $content
     * @return array|string
     */
    private function kses($content)
    {
        $sanitized = [];
        if (is_array($content)) {
            foreach ($content as $key => $value) {
                $sanitized[$key] = trim(strip_tags(str_replace("\0", '', $value)));
            }
        } else {
            return trim(strip_tags(str_replace("\0", '', $content)));
        }
        return $sanitized;
    }
}
