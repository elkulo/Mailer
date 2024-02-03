<?php
/**
 * Mailer | el.kulo v3.6.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2024 A.Sudo
 * Licensed under LGPL-2.1-only (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

namespace App\Domain\Dashboard;

interface DashboardRepository
{

    /**
     * ダッシュボード
     *
     * @return array
     */
    public function index(): array;

    /**
     * API
     *
     * @return array
     */
    public function api(): array;
}
