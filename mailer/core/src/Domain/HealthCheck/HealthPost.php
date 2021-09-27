<?php
/**
 * Mailer | el.kulo v3.0.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2021 A.Sudo
 * Licensed under MIT (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

namespace App\Domain\HealthCheck;

use App\Application\Settings\SettingsInterface;
use Twig\Loader\FilesystemLoader as TwigFileLoader;
use Twig\Environment as TwigEnvironment;

class HealthPost
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
        }
        $this->post_data = $sanitized;

        // Twigの初期化
        $this->view = new TwigEnvironment(
            new TwigFileLoader(array(
                $app_path . '/src/Views/health-check/mail',
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
     * メール件名（共通）
     *
     * @return string
     */
    public function getMailSubject(): string
    {
        $subject = 'HEALTH CHECKの確認コード';
        $before = isset($this->setting['SUBJECT_BEFORE']) ? $this->setting['SUBJECT_BEFORE'] : '';
        $after = isset($this->setting['SUBJECT_AFTER']) ? $this->setting['SUBJECT_AFTER'] : '';

        return str_replace(PHP_EOL, '', $this->kses($before . $subject . $after));
    }

    /**
     * 管理者メールテンプレート
     *
     * @param  array $data
     * @return string
     */
    public function renderAdminMail(array $data): string
    {
        return $this->view->render('passcode.mail.twig', $data);
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
