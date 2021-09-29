<?php

return [

    // メーラータイプ
    'MAILER_DRIVER' => env('MAILER_DRIVER'),

    // バリデーションの言語設定
    'VALIDATION_LANG' => env('VALIDATION_LANG'),

    // SMTPサーバー
    'SMTP_HOST' => env('SMTP_HOST'),

    // SMTPメールアドレス(配信元)
    'SMTP_MAIL' => env('SMTP_MAIL'),

    // メールユーザー名(アカウント名)
    'SMTP_USERNAME' => env('SMTP_USERNAME'),

    // メールパスワード
    'SMTP_PASSWORD' => env('SMTP_PASSWORD'),

    // SMTPプロトコル(sslまたはtls)
    'SMTP_ENCRYPTION' => env('SMTP_ENCRYPTION'),

    // 送信ポート(465 or 587)
    'SMTP_PORT' => env('SMTP_PORT'),

    // 配信元の表示名
    'FROM_NAME' => env('FROM_SITE_NAME'),

    // 管理者メールアドレス
    'ADMIN_MAIL' => env('ADMIN_MAIL'),

    // 管理者メールCC
    'ADMIN_CC' => env('ADMIN_CC'),

    // 管理者メールBCC
    'ADMIN_BCC' => env('ADMIN_BCC'),

    // DBサーバーの情報
    'DB' => [

        'DRIVER' => env('DB_DRIVER'),

        'HOST' => env('DB_HOST'),

        'NAME' => env('DB_NAME'),

        'USER' => env('DB_USER'),

        'PASSWORD' => env('DB_PASSWORD'),

        'PREFIX' => env('DB_PREFIX'),

        'CHARSET' => env('DB_CHARSET'),

        'COLLATE' => env('DB_COLLATE'),
    ],

    // Google reCAPTCHA
    'CAPTCHA' => [

        'SECRETKEY' => env('CAPTCHA_SECRETKEY'),

        'SITEKEY' => env('CAPTCHA_SITEKEY'),
    ]
];
