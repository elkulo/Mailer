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
                    wp_die('システムエラー: 申し訳御座いません。お問い合わせの送信に失敗しました。');
                }
            } else {
                throw new \Exception('WordPress Mail Error');
            }
        } catch (\Exception $e) {
            exit($e->getMessage());
        }
    }
}
