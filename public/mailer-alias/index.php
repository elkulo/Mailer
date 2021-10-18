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
//define('ENV_DIR_PATH', __DIR__);

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
//define('SETTINGS_DIR_PATH', __DIR__);

// 任意のテンプレートディレクトリがある場所までのパス
//define('TEMPLATES_DIR_PATH', __DIR__);

/**
 * WordPressの連携
 *
 * !! WordPress と連携する場合のみ書き換える !!
 *
 * wp-load.php を読み込むことでWordPressの関数が使用可能。
 * envで MAILER_DRIVER='WordPress' に切り替えでメール送信を wp_mail() にする。
 * そのため、WordPressのSMTP等のプラグインとも連携ができます。
 * WordPressの設定を使用する場合はwp-load.phpをインクルードさせるとWP関数も使用可能。
 */
// WordPressがインストールされているディレクトリの wp-load.php を指定
//require_once __DIR__ . '/../../../../wp-load.php';

/**
 * 任意なファイル名
 * 
 * Mailerプログラムを公開ディレクトリ以外（httpでアクセスできない場所）に設置、
 * /mailer/bootstrap.php を require_once で読み込めば、任意のディレクトリやファイル名で実行できます。
 * <form action="..."> のactionに読み込みさせたファイルを指定する。
 */
// Mailerプログラムのロードファイル "/mailer/bootstrap.php" を指定
require_once __DIR__ . '/../../mailer/bootstrap.php';
