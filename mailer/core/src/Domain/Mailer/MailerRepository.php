<?php
/**
 * Mailer | el.kulo v3.4.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2023 A.Sudo
 * Licensed under LGPL-2.1-only (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

namespace App\Domain\Mailer;

interface MailerRepository
{

    /**
     * 入力画面
     *
     * @return array
     */
    public function index(): array;

    /**
     * 確認画面
     *
     * @return array
     */
    public function confirm(): array;

    /**
     * 送信完了
     *
     * @return array
     */
    public function complete(): array;
}
