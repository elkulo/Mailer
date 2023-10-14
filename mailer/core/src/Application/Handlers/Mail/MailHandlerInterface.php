<?php
/**
 * Mailer | el.kulo v3.5.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2023 A.Sudo
 * Licensed under LGPL-2.1-only (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

namespace App\Application\Handlers\Mail;

interface MailHandlerInterface
{

    /**
     * メール送信
     *
     * @param  string $to
     * @param  string $subject
     * @param  string $body
     * @param  array $header
     * @param  array $attachments
     * @return bool
     */
    public function send(
        string $to,
        string $subject,
        string $body,
        array $header = array(),
        array $attachments = array()
    ): bool;
}
