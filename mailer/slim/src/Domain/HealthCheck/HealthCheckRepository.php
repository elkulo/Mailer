<?php
/**
 * Mailer | el.kulo v1.0.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2021 A.Sudo
 * Licensed under MIT (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

namespace App\Domain\HealthCheck;

interface HealthCheckRepository
{

    /**
     * 確認画面
     *
     * @return array
     */
    public function index(): array;

    /**
     * 送信完了
     *
     * @return array
     */
    public function result(): array;
}