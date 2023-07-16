<?php
/**
 * Mailer | el.kulo v3.5.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2023 A.Sudo
 * Licensed under LGPL-2.1-only (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

namespace App\Application\Handlers\Mail;

/**
 * WordPressHandler
 */
class WordPressHandler implements MailHandlerInterface
{

    /**
     * メール送信
     *
     * @param  string $to
     * @param  string $subject
     * @param  string $body
     * @param  array  $header
     * @param  array  $attachments
     * @return bool
     */
    final public function send(
        string $to,
        string $subject,
        string $body,
        array $header = array(),
        array $attachments = array()
    ): bool {
        try {
            // WordPress関数で送信.
            if (function_exists('wp_mail')) {
                // phpcs:ignore @phpstan-ignore-next-line
                if (!\wp_mail($to, $subject, $body, $header, $attachments)) {
                    throw new \Exception('Error wp_mail.');
                }
            } else {
                throw new \Exception('Error wp_mail.');
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
