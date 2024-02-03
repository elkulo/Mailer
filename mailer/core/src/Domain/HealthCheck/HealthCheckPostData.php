<?php
/**
 * Mailer | el.kulo v3.6.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2024 A.Sudo
 * Licensed under LGPL-2.1-only (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

namespace App\Domain\HealthCheck;

use App\Application\Settings\SettingsInterface;
use Twig\Loader\FilesystemLoader as TwigFileLoader;
use Twig\Environment as TwigEnvironment;

class HealthCheckPostData
{

    /**
     * 設定値
     *
     * @var SettingsInterface
     */
    private $settings;

    /**
     * POSTデータ
     *
     * @var array
     */
    private $postData = [];

    /**
     * Twig ハンドラー
     *
     * @var TwigEnvironment
     */
    private $view;

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
        $appPath = $settings->get('appPath');

        // POSTデータから取得したデータを整形
        $this->postData = $this->esc($this->kses($posts));

        // Twigの初期化
        $this->view = new TwigEnvironment(
            new TwigFileLoader([
                $appPath . '/src/Views/health-check/mail',
            ])
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
     * メール件名
     *
     * @return string
     */
    public function getMailSubject(): string
    {
        $subject = $this->esc('ヘルスチェックの確認コード - ' . $this->settings->get('siteTitle'));
        return str_replace(PHP_EOL, '', $subject);
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
    private function esc($content, string $encode = 'UTF-8')
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

    /**
     * 除去
     *
     * @param  mixed $content
     * @return mixed
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
