<?php
/**
 * Mailer | el.kulo v1.0.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2021 A.Sudo
 * Licensed under MIT (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

namespace App\Handlers;

use App\Interfaces\MailHandlerInterface;

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
     * @param  array $header
     * @return bool
     */
    final public function send(string $to, string $subject, string $body, array $header = array()): bool
    {
        try {
            if (defined('ABSPATH') && function_exists('wp_mail')) {
                // WordPress関数で送信
                if (wp_mail($to, $subject, $body, $header)) {
                    return true;
                } else {
                    throw new \Exception('Error wp_mail.');
                }
            } else {
                throw new \Exception('Error ABSPATH.');
            }
        } catch (\Exception $e) {
            return false;
        }
    }
}
