<?php
declare(strict_types=1);

/**
 * 環境変数: ENV_DIR_PATH
 * 
 * envまでのパスを変更することができます。
 * デフォルトはMailerプログラム直下になります。
 * 変更がない場合はコメントアウトを解除する必要はありません。
 */
// 任意の .env がある場所までのパス
//define('ENV_DIR_PATH', __DIR__ . '/../');

/**
 * 設定とテンプレート: SETTINGS_DIR_PATH, TEMPLATES_DIR_PATH
 * 
 * 設定ディレクトリの "/settings" やテンプレートディレクトリの "/templates" がある場所を任意に指定すれば、
 * 一つのメールプログラムで複数のフォームの設定を分けられます。
 * 設定ディレクトリ名 "settings"、テンプレートディレクトリ名は "templates" である必要があります。
 * デフォルトはMailerプログラム直下になります。
 * 変更がない場合はコメントアウトを解除する必要はありません。
 */
// 任意の設定ディレクトリがある場所までのパス
//define('SETTINGS_DIR_PATH', __DIR__ . '/../');

// 任意のテンプレートディレクトリがある場所までのパス
//define('TEMPLATES_DIR_PATH', __DIR__ . '/../');

/**
 * WordPressの連携
 *
 * !! WordPress と連携する場合のみ書き換える !!
 *
 * wp-load.php を読み込むことでWordPressの関数が使用できます。
 * settings 内の各設定ファイルをWP関数で置き換えると良いでしょう。
 * さらに、envで MAILER_DRIVER='WordPress' に切り替えでメール送信は wp_mail() 関数を使用します。
 * 本プログラムのSMTP機能は無視されますが、WordPress側でSMTP等のプラグインと連携ができます。
 */
// WordPressがインストールされているディレクトリの wp-load.php を指定
//require_once __DIR__ . '/../../../../wp-load.php';

/**
 * サブディレクトリに設置: BASE_URL_PATH
 * 
 * このPHPファイルを置いているディレクトリまでのパスを、
 * 公開URLのルートからのパスで指定してください。
 * 例）https://example.com/path/to/mailer-alias/ なら "/path/to/mailer-alias"
 */
// 任意のディレクトリ名
//define('BASE_URL_PATH', '/mailer-alias');

/**
 * 任意なファイル名
 * 
 * Mailerプログラムを公開ディレクトリ以外（httpでアクセスできない場所）に設置、
 * 本プログラム直下の bootstrap.php ファイルを require_once で読み込めば、任意のディレクトリやファイル名で実行ができます。
 */
// Mailerプログラムの bootstrap.php を指定
require_once __DIR__ . '/../bootstrap.php';
