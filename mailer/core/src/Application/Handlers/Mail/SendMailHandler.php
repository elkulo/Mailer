<?php
/**
 * Mailer | el.kulo v1.0.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2021 A.Sudo
 * Licensed under MIT (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

namespace App\Application\Handlers\Mail;

/**
 * SendMailHandler
 */
class SendMailHandler implements MailHandlerInterface
{

    /**
     * サーバー設定
     *
     * @var array
     */
    private array $server;

    /**
     * DBを作成
     *
     * @param  array $config
     * @return void
     */
    public function __construct(array $config)
    {
        $this->server = $config['server'];
    }

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
        $server = $this->server;

        mb_language('ja');

        $from_email = $server['SMTP_MAIL'];
        $from_name = mb_encode_mimeheader($server['FROM_NAME'], 'ISO-2022-JP', 'UTF-8');
        $subject = mb_encode_mimeheader('[致命的な送信エラー]'.$subject, 'ISO-2022-JP', 'UTF-8');
        $body = mb_convert_encoding($body, 'ISO-2022-JP', 'UTF-8');

        $headers  = 'MIME-Version: 1.0 \n';
        $headers .= 'From: ' .
            '' . mb_encode_mimeheader(mb_convert_encoding($from_name, 'ISO-2022-JP', 'AUTO')) . '' .
            '<' . $from_email . '> \n';
        $headers .= 'Reply-To: ' .
            '' . mb_encode_mimeheader(mb_convert_encoding($from_name, 'ISO-2022-JP', 'AUTO')) . '' .
            '<' . $from_email . '> \n';

        $headers .= 'Content-Type: text/plain;charset=ISO-2022-JP \n';

        /* Mail, optional paramiters. */
        $sendmail_params  = '-f '.$from_email;

        // 送信
        $result = mb_send_mail($to, $subject, $body, $headers);

        // メール送信の実行.
        try {
            if ($result) {
                return true;
            } else {
                throw new \Exception('Mailer Error');
            }
        } catch (\Exception $e) {
            return false;
        }
    }
}
