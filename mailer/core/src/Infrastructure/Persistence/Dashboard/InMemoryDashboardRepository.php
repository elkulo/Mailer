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
     * InMemoryDashboardRepository constructor.
     *
     * @param SettingsInterface $settings
     * @param RouterInterface $router
     */
    public function __construct(
        SettingsInterface $settings,
        RouterInterface $router
    ) {
        $this->settings = $settings;
        $this->router = $router;
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
                    'csrf' => [
                        'js' => $this->router->getUrl('assets-csrf-js'),
                        'json' => $this->router->getUrl('api-csrf-json'),
                    ],
                    'recaptcha' => [
                        'js' => $this->router->getUrl('assets-recaptcha-js'),
                    ],
                ],
            ]
        ];
    }
}
