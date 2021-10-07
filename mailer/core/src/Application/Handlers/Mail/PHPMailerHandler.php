<?php
/**
 * Mailer | el.kulo v3.0.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2021 A.Sudo
 * Licensed under MIT (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

namespace App\Application\Handlers\Mail;

use App\Application\Settings\SettingsInterface;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * PHPMailerHandler
 */
class PHPMailerHandler implements MailHandlerInterface
{

    /**
     * メールサーバー設定
     *
     * @var array
     */
    private array $mailSettings;

    /**
     * DBを作成
     *
     * @param  SettingsInterface $settings
     * @return void
     */
    public function __construct(SettingsInterface $settings)
    {
        $this->mailSettings = $settings->get('mail');
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
        $mailSettings = $this->mailSettings;

        // SMTP認証.
        $mailer = new PHPMailer;
        $mailer->isSMTP();
        $mailer->Host = $mailSettings['smtp.host'];
        $mailer->Port = $mailSettings['smtp.port'];

        // メーラー名を変更.
        $mailer->XMailer = 'PHPMailer';

        if (isset($mailSettings['smtp.username'], $mailSettings['smtp.password'])) {
            $mailer->SMTPAuth = true;
            $mailer->Username = $mailSettings['smtp.username'];
            $mailer->Password = $mailSettings['smtp.password'];
        } else {
            $mailer->SMTPAuth = false;
        }

        if (isset($mailSettings['smtp.encrypt'])) {
            $mailer->SMTPSecure = $mailSettings['smtp.encrypt'];
            $mailer->SMTPAutoTLS = true;
        } else {
            $mailer->SMTPSecure  = false;
            $mailer->SMTPAutoTLS = false;
        }

        // エンコード.
        $mailer->CharSet = 'ISO-2022-JP';
        $mailer->Encoding = 'base64';
        $subject = mb_encode_mimeheader($subject, 'ISO-2022-JP', 'UTF-8');
        $from_name = mb_encode_mimeheader($mailSettings['from.name'], 'ISO-2022-JP', 'UTF-8');
        $body = mb_convert_encoding($body, 'ISO-2022-JP', 'UTF-8');

        // 配信元.
        $mailer->setFrom($mailSettings['smtp.mailaddress'], $from_name);

        // 送信メール.
        $mailer->isHTML(false);
        $mailer->Subject = $subject;
        $mailer->Body = $body;

        // メールヘッダ.
        $mailer->addAddress($to);

        // 追加のメールヘッダ.
        if ($header) {
            $this->addMailHeader($mailer, $header);
        }

        // 受信失敗時のリターン先.
        $mailer->Sender = $mailSettings['smtp.mailaddress'];

        /**
         * デバックレベル 0 ~ 2
         * (0)デバッグを無効にします（これを完全に省略することもできます、0がデフォルト）
         * (1)クライアントから送信されたメッセージを出力
         * (2)1に加えて、サーバーから受信した応答
         * (3)2に加えて、初期接続についての詳細情報 - このレベルはSTARTTLSエラーの診断
         */
        // $mailer->SMTPDebug = 1;

        // メール送信の実行.
        try {
            if ($mailer->send()) {
                return true;
            } else {
                throw new Exception('PHPMailer Error');
            }
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * 追加のメールヘッダ
     *
     * @param  PHPMailer $phpmailer
     * @param  array $headers
     * @return void
     */
    private function addMailHeader(PHPMailer $phpmailer, array $headers): void
    {
        $cc       = array();
        $bcc      = array();
        $reply_to = array();

        // タイプ別の配列へ.
        foreach ((array) $headers as $header) {
            list($name, $content) = explode(':', trim($header), 2);

            // 前後の空白除去.
            $name    = trim($name);
            $content = trim($content);

            switch (strtolower($name)) {
                case 'cc':
                    $cc = array_merge((array) $cc, explode(',', $content));
                    break;
                case 'bcc':
                    $bcc = array_merge((array) $bcc, explode(',', $content));
                    break;
                case 'reply-to':
                    $reply_to = array_merge((array) $reply_to, explode(',', $content));
                    break;
                default:
                    // Add it to our grand headers array.
                    $headers[trim($name)] = trim($content);
                    break;
            }
        }

        // 配列にまとめる.
        $address_headers = compact('cc', 'bcc', 'reply_to');

        foreach ($address_headers as $address_header => $addresses) {
            if (empty($addresses)) {
                continue;
            }

            foreach ((array) $addresses as $address) {
                try {
                    $recipient_name = '';

                    // "Foo <mail@example.com>" を "Foo" と "mail@example.com" に分解.
                    if (preg_match('/(.*)<(.+)>/', $address, $matches)) {
                        if (count($matches) == 3) {
                            $recipient_name = $matches[1];
                            $address        = $matches[2];
                        }
                    }

                    // エンコード.
                    $recipient_name = mb_encode_mimeheader($recipient_name, 'ISO-2022-JP', 'UTF-8');

                    switch ($address_header) {
                        case 'cc':
                            $phpmailer->addCc($address, $recipient_name);
                            break;
                        case 'bcc':
                            $phpmailer->addBcc($address, $recipient_name);
                            break;
                        case 'reply_to':
                            $phpmailer->addReplyTo($address, $recipient_name);
                            break;
                    }
                } catch (Exception $e) {
                    continue;
                }
            }
        }
    }
}
