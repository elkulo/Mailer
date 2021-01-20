<?php
/**
 * WordPress 連携
 *
 * !! WordPress と連携する場合のみ書き換える !!
 *
 * wp-load.php を読み込むことでWordPressの関数が使用可能になる。
 * さらに、app/src/Applecation/Mailer.php のハンドラーを WordPressHandler にすることで、
 * メール送信を wp_mail() に切り替えるためWordPressのSMTP等のプラグインとも連携できる。
 */
// require_once __DIR__ . '/../../../../../../../wp-load.php';

// SMTPサーバー
define('SMTP_HOST', getenv('SMTP_HOST'));

// メールユーザー名・アカウント名
define('SMTP_USERNAME', getenv('SMTP_USERNAME'));

// メールパスワード
define('SMTP_PASSWORD', getenv('SMTP_PASSWORD'));

// SMTPプロトコル(sslまたはtls)
define('SMTP_ENCRYPTION', getenv('SMTP_ENCRYPTION'));

// 送信ポート(ssl:465, tls:587)
define('SMTP_PORT', getenv('SMTP_PORT'));

// 配信元の表示名
define('FROM_NAME', getenv('FROM_SITE_NAME'));

// 配信元のメールアドレス
define('FROM_MAIL', getenv('SMTP_MAIL'));

// 管理者の宛名
define('ADMIN_NAME', getenv('ADMIN_NAME'));

// 管理者メールアドレス
define('ADMIN_MAIL', getenv('ADMIN_MAIL'));

// 管理者メールBCC
define('ADMIN_BCC', getenv('ADMIN_BCC'));

// タイムゾーン
define('TIME_ZONE', getenv('TIME_ZONE'));