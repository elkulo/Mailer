<?php
/**
 * WordPressの連携
 *
 * !! WordPress と連携する場合のみ書き換える !!
 *
 * wp-load.php を読み込むことでWordPressの関数が使用可能。
 * envで MAILER_TYPE='WordPress' に切り替えでメール送信を wp_mail() にする。
 * そのため、WordPressのSMTP等のプラグインとも連携ができます。
 * WordPressの設定を使用する場合はwp-load.phpをインクルードさせるとWP関数も使用可能。
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

// 管理者メールアドレス
define('ADMIN_MAIL', getenv('ADMIN_MAIL'));

// 管理者メールCC
define('ADMIN_CC', getenv('ADMIN_CC'));

// 管理者メールBCC
define('ADMIN_BCC', getenv('ADMIN_BCC'));

// バリデーションの言語設定
define('VALIDATION_LANG', getenv('VALIDATION_LANG'));

// タイムゾーン
define('TIME_ZONE', getenv('TIME_ZONE'));
