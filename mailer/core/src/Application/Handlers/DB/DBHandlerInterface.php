<?php
/**
 * Mailer | el.kulo v3.0.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2021 A.Sudo
 * Licensed under MIT (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

namespace App\Application\Handlers\DB;

interface DBHandlerInterface
{

    /**
     * DBに保存
     *
     * @param  array   $success
     * @param  string $email
     * @param  string $subject
     * @param  string $body
     * @param  array  $status
     * @return bool
     */
    public function save(array $success, string $email, string $subject, string $body, array $status): bool;

    /**
     * DBを作成
     *
     * @return bool
     * @throws Exception
     */
    public function make(): bool;

    /**
     * DBに保存を検証
     *
     * @return bool
     */
    public function test(string $email): bool;
}
