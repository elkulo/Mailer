<?php
/**
 * Mailer | el.kulo v3.0.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2021 A.Sudo
 * Licensed under MIT (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

namespace App\Infrastructure\Persistence\Dashboard;

use App\Domain\Dashboard\DashboardRepository;
use App\Application\Settings\SettingsInterface;
use App\Application\Router\RouterInterface;
use Slim\Csrf\Guard;

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
     * CSRF対策
     *
     * @var Guard
     */
    protected $csrf;

    /**
     * InMemoryDashboardRepository constructor.
     *
     * @param SettingsInterface $settings
     * @param RouterInterface $router
     * @param Guard $csrf
     */
    public function __construct(
        SettingsInterface $settings,
        RouterInterface $router,
        Guard $csrf
    ) {
        $this->settings = $settings;
        $this->router = $router;
        $this->csrf = $csrf;
    }

    /**
     * インデックス
     *
     * @return array
     */
    public function index(): array
    {
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
