<?php
/**
 * Mailer | el.kulo v1.0.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2021 A.Sudo
 * Licensed under MIT (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

namespace App\Application\Interfaces;

interface DBHandlerInterface
{

   /**
     * DBに保存
     *
     * @param  bool   $success
     * @param  string $email
     * @param  string $subject
     * @param  string $body
     * @param  array  $status
     * @return bool
     */
    public function save(bool $success, string $email, string $subject, string $body, array $status): bool;
}
