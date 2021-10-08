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

class HealthCheckPostData
{

    /**
     * POSTデータ
     *
     * @var array
     */
    private array $postData = [];

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
        $app_path = $settings->get('appPath');

        // POSTデータから取得したデータを整形
        $sanitized = array();
        foreach ($posts as $name => $value) {
            $sanitized[$name] = trim(strip_tags(str_replace("\0", '', $value)));
        }
        $this->postData = $this->kses($sanitized);

        // Twigの初期化
        $this->view = new TwigEnvironment(
            new TwigFileLoader(array(
                $app_path . '/src/Views/health-check/mail',
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
     * メール件名（共通）
     *
     * @return string
     */
    public function getMailSubject(): string
    {
        return str_replace(PHP_EOL, '', $this->kses('HEALTH CHECKの確認コード'));
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
