<?php
/**
 * Mailer | el.kulo v3.6.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2024 A.Sudo
 * Licensed under LGPL-2.1-only (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

namespace App\Application\Handlers\DB;

interface DBHandlerInterface
{

    /**
     * DBに保存
     *
     * @param  array  $success
     * @param  string $email
     * @param  string $subject
     * @param  string $body
     * @param  string $attachment
     * @param  array  $status
     * @return bool
     */
    public function save(
        array  $success,
        string $email,
        string $subject,
        string $body,
        string $attachment,
        array  $status
    ): bool;

    /**
     * DBを作成
     *
     * @return bool
     */
    public function make(): bool;

    /**
     * DBに保存を検証
     *
     * @return bool
     */
    public function test(): bool;
}
