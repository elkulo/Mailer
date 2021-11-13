<?php
/**
 * Mailer | el.kulo v3.2.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2022 A.Sudo
 * Licensed under LGPL-2.1-only (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

namespace App\Infrastructure\Persistence\Dashboard;

use Slim\Csrf\Guard;
use Slim\Flash\Messages;
use App\Domain\Dashboard\DashboardRepository;
use App\Application\Settings\SettingsInterface;
use App\Application\Router\RouterInterface;
use App\Application\Handlers\Validate\ValidateHandlerInterface;

class InMemoryDashboardRepository implements DashboardRepository
{
    /**
     * 設定
     *
     * @var SettingsInterface
     */
    private $settings;

    /**
     * ルーター
     *
     * @var RouterInterface
     */
    protected $router;

    /**
     * 検証ハンドラー
     *
     * @var ValidateHandlerInterface
     */
    private $validate;

    /**
     * CSRF対策
     *
     * @var Guard
     */
    protected $csrf;

    /**
     * フラッシュメッセージ
     *
     * @var Messages
     */
    private $flash;

    /**
     * InMemoryDashboardRepository constructor.
     *
     * @param SettingsInterface $settings
     * @param RouterInterface $router
     * @param ValidateHandlerInterface $validate
     * @param Guard $csrf
     * @param Messages $messages
     */
    public function __construct(
        SettingsInterface $settings,
        RouterInterface $router,
        ValidateHandlerInterface $validate,
        Guard $csrf,
        Messages $messages
    ) {
        // 設定
        $this->settings = $settings;

        // ルーター
        $this->router = $router;

        // バリデーションアクションをセット
        $this->validate = $validate;

        // CSRF
        $this->csrf = $csrf;

        // フラッシュメッセージ
        $this->flash = $messages;
    }

    /**
     * インデックス
     *
     * @return array
     */
    public function index(): array
    {
        $mailSettings = $this->settings->get('mail');
        $to = (array) $mailSettings['ADMIN_MAIL'];
        $cc = $mailSettings['ADMIN_CC']? explode(',', $mailSettings['ADMIN_CC']) : [];
        $bcc = $mailSettings['ADMIN_BCC']? explode(',', $mailSettings['ADMIN_BCC']) : [];

        try {
            // 環境設定のメールアドレスの形式に不備があれば警告.
            foreach (array_merge($to, $cc, $bcc) as $mailaddress) {
                if (!$this->validate->isCheckMailFormat($mailaddress)) {
                    throw new \Exception('環境設定のメールアドレスに不備があります。設定を見直してください。');
                }
            }
        } catch (\Exception $e) {
            $this->flash->clearMessages();
            $this->flash->addMessageNow('danger', $e->getMessage());
        }

        return [
            'template' => 'index.twig',
            'data' => [
                'Debug' => $this->settings->get('debug'),
                'Router' => [
                    'mailer' => $this->router->getUrl('mailer'),
                    'health_check' => $this->router->getUrl('health-check'),
                    'api' => [
                        'json' => $this->router->getUrl('api-json'),
                    ],
                    'csrf' => [
                        'js' => $this->router->getUrl('csrf.min.js'),
                    ],
                    'recaptcha' => [
                        'js' => $this->router->getUrl('recaptcha.min.js'),
                    ],
                    'bootstrap' => [
                        'css' => $this->router->getUrl('bootstrap.min.css'),
                        'js' => $this->router->getUrl('bootstrap.min.js'),
                    ],
                ],
            ]
        ];
    }

    /**
     * API
     *
     * @return array
     */
    public function api(): array
    {
        return [
            'csrf'   => [
                'keys' => [
                    'name'  => $this->csrf->getTokenNameKey(),
                    'value' => $this->csrf->getTokenValueKey(),
                ],
                'name'  => $this->csrf->getTokenName(),
                'value' => $this->csrf->getTokenValue(),
            ]
        ];
    }
}
