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

class InMemoryDashboardRepository implements DashboardRepository
{
    /**
     * 設定
     *
     * @var SettingsInterface
     */
    private SettingsInterface $settings;

    /**
     * InMemoryDashboardRepository constructor.
     *
     * @param SettingsInterface $settings
     */
    public function __construct(
        SettingsInterface $settings
    ) {
        // 設定
        $this->settings = $settings;
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
                'Debug' => $this->settings->get('debug')
            ]
        ];
    }
}
