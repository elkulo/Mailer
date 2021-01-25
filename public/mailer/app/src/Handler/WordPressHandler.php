<?php
declare(strict_types=1);

namespace App\Handler;

/**
 * WordPressHandler
 */
class WordPressHandler implements HandlerInterface
{

    /**
     * send
     *
     * @param  string $to
     * @param  string $subject
     * @param  string $body
     * @param  string $header
     * @return void
     */
    final public function send(string $to, string $subject, string $body, string $header): void
    {
        try {
            if (defined('ABSPATH')) {
                // WordPress関数で送信
                if (!wp_mail($to, $subject, $body, $header)) {
                    throw new \Exception('Error wp_mail.');
                }
            } else {
                throw new \Exception('Error ABSPATH.');
            }
        } catch (\Exception $e) {
            logger($e->getMessage(), 'error');
            exit($e->getMessage());
        }
    }
}
