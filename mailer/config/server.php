<?php

return [

    // バリデーションの言語設定
    'VALIDATION_LANG' => getenv('VALIDATION_LANG'),

    // SMTPサーバー
    'SMTP_HOST' => getenv('SMTP_HOST'),

    // メールユーザー名・アカウント名
    'SMTP_USERNAME' => getenv('SMTP_USERNAME'),

    // メールパスワード
    'SMTP_PASSWORD' => getenv('SMTP_PASSWORD'),

    // SMTPプロトコル(sslまたはtls)
    'SMTP_ENCRYPTION' => getenv('SMTP_ENCRYPTION'),

    // 送信ポート(ssl:465 => tls:587)
    'SMTP_PORT' => getenv('SMTP_PORT'),

    // 配信元の表示名
    'FROM_NAME' => getenv('FROM_SITE_NAME'),

    // 配信元のメールアドレス
    'FROM_MAIL' => getenv('SMTP_MAIL'),

    // 管理者メールアドレス
    'ADMIN_MAIL' => getenv('ADMIN_MAIL'),

    // 管理者メールCC
    'ADMIN_CC' => getenv('ADMIN_CC'),

    // 管理者メールBCC
    'ADMIN_BCC' => getenv('ADMIN_BCC'),

    // DBサーバーの情報
    'DB' => [

        'CONNECTION' => getenv('DB_CONNECTION'),

        'HOST' => getenv('DB_HOST'),

        'DATABASE' => getenv('DB_DATABASE'),

        'USERNAME' => getenv('DB_USERNAME'),

        'PASSWORD' => getenv('DB_PASSWORD'),

        'PREFIX' => getenv('DB_PREFIX'),

        'CHARSET' => getenv('DB_CHARSET'),

        'COLLATION' => getenv('DB_COLLATION'),
    ],
];
