<?php
declare(strict_types=1);

namespace App\Handler;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * PHPMailerHandler
 */
class PHPMailerHandler implements HandlerInterface
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
        // SMTP認証.
        $mailer = new PHPMailer;
        $mailer->isSMTP();
        $mailer->Host = SMTP_HOST;
        $mailer->Port = SMTP_PORT;

        // メーラー名を変更.
        $mailer->XMailer = 'PHPMailer';

        if (defined('SMTP_USERNAME') && defined('SMTP_PASSWORD')) {
            $mailer->SMTPAuth = true;
            $mailer->Username = SMTP_USERNAME;
            $mailer->Password = SMTP_PASSWORD;
        } else {
            $mailer->SMTPAuth = false;
        }

        if (defined('SMTP_ENCRYPTION')) {
            $mailer->SMTPSecure = SMTP_ENCRYPTION;
            $mailer->SMTPAutoTLS = true;
        }

        // エンコード.
        $mailer->CharSet = 'ISO-2022-JP';
        $mailer->Encoding = 'base64';
        $subject = mb_encode_mimeheader($subject, 'ISO-2022-JP', 'UTF-8');
        $header = mb_encode_mimeheader($header, 'ISO-2022-JP', 'UTF-8');
        $from_name = mb_encode_mimeheader(FROM_NAME, 'ISO-2022-JP', 'UTF-8');
        $body = mb_convert_encoding($body, 'ISO-2022-JP', 'UTF-8');

        // 配信元.
        $mailer->setFrom(FROM_MAIL, $from_name);
        /** $mailer->addReplyTo(FROM_MAIL, $from_name); */

        // 送信メール.
        $mailer->isHTML(false);
        $mailer->Subject = $subject;
        $mailer->addAddress($to, $header);
        $mailer->Body = $body;

        /**
         * デバックレベル 0 ~ 2
         * (0)デバッグを無効にします（これを完全に省略することもできます、0がデフォルト）
         * (1)クライアントから送信されたメッセージを出力
         * (2)1に加えて、サーバーから受信した応答
         * (3)2に加えて、初期接続についての詳細情報 - このレベルはSTARTTLSエラーの診断
         */
        /** $mailer->SMTPDebug = 0; */

        // メール送信の実行.
        try {
            if (!$mailer->send()) {
                logger('Failed', 'error');
                throw new Exception('Mailer Error');
            }
        } catch (Exception $e) {
            exit($e->getMessage());
        }
    }
}
