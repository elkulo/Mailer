<?php
/**
 * SMTPサーバーの設定
 * 
 */
return [

    // メーラータイプ
    'MAILER_DRIVER' => env('MAILER_DRIVER'),

    // SMTPサーバー
    'SMTP_HOST' => env('SMTP_HOST'),

    // SMTPメールアドレス(配信元)
    'SMTP_MAILADDRESS' => env('SMTP_MAILADDRESS'),

    // メールユーザー名(アカウント名)
    'SMTP_USERNAME' => env('SMTP_USERNAME'),

    // メールパスワード
    'SMTP_PASSWORD' => env('SMTP_PASSWORD'),

    // SMTPプロトコル(sslまたはtls)
    'SMTP_ENCRYPT' => env('SMTP_ENCRYPT'),

    // 送信ポート(465 or 587)
    'SMTP_PORT' => env('SMTP_PORT'),

    // 配信元の表示名(サイト名)
    'FROM_NAME' => env('SITE_TITLE'),

    // 管理者メールアドレス
    'ADMIN_MAIL' => env('ADMIN_MAIL'),

    // 管理者メールCC
    'ADMIN_CC' => env('ADMIN_CC'),

    // 管理者メールBCC
    'ADMIN_BCC' => env('ADMIN_BCC'),
];
