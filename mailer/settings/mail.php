<?php

return [

    // メーラータイプ
    'mailer.driver' => env('MAILER_DRIVER'),

    // SMTPサーバー
    'smtp.host' => env('SMTP_HOST'),

    // SMTPメールアドレス(配信元)
    'smtp.mailaddress' => env('SMTP_MAILADDRESS'),

    // メールユーザー名(アカウント名)
    'smtp.username' => env('SMTP_USERNAME'),

    // メールパスワード
    'smtp.password' => env('SMTP_PASSWORD'),

    // SMTPプロトコル(sslまたはtls)
    'smtp.encrypt' => env('SMTP_ENCRYPT'),

    // 送信ポート(465 or 587)
    'smtp.port' => env('SMTP_PORT'),

    // 配信元の表示名
    'from.name' => env('FROM_NAME'),

    // 管理者メールアドレス
    'admin.mail' => env('ADMIN_MAIL'),

    // 管理者メールCC
    'admin.cc' => env('ADMIN_CC'),

    // 管理者メールBCC
    'admin.bcc' => env('ADMIN_BCC'),
];
