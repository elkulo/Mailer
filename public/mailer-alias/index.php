<?php
/**
 * Mailer | el.kulo v3.0.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2021 A.Sudo
 * Licensed under MIT (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

/**
 * 環境変数: ENV_DIR_PATH
 * 
 * envまでのパスを変更することができます。
 * デフォルトはMailerプログラム直下になります。
 * 変更がない場合はコメントアウトを解除する必要はありません。
 */
// 任意の .env までのパス
//define('ENV_DIR_PATH', __DIR__);

/**
 * 設定とテンプレート: $TEMPLATES_DIR_PATH, $SETTINGS_DIR_PATH
 * 
 * 設定ディレクトリの "/settings" やテンプレートディレクトリの "/templates" までのパスを任意に指定すれば、
 * 一つのメールプログラムで複数のフォームの設定を分けられます。
 * 設定ディレクトリ名 "settings"、テンプレートディレクトリ名は "templates" である必要があります。
 * デフォルトはMailerプログラム直下になります。
 * 変更がない場合はコメントアウトを解除する必要はありません。
 */
// 任意の設定ディレクトリまでのパス
//define('SETTINGS_DIR_PATH', __DIR__);

// 任意のテンプレートディレクトリまでのパス
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
//require_once __DIR__ . '/../../../../wp-load.php';

/**
 * サブディレクトリに設置: BASE_PATH
 * 
 * このPHPファイルを置いているディレクトリまでのパスを、
 * 公開URLのルートからのパスで指定してください。
 * 例）https://example.com/path/to/mailer-alias/ なら '/path/to/mailer-alias'
 */
// 任意のディレクトリ名
define('BASE_PATH', '/mailer-alias');

/**
 * 任意なファイル名
 * 
 * Mailerプログラムを公開ディレクトリ以外（httpでアクセスできない場所）に設置、
 * /mailer/index.php を require_once で読み込めば、任意のディレクトリやファイル名で実行できます。
 * <form action="..."> のactionに読み込みさせたファイルを指定する。
 */
// Mailerプログラムのロードファイル "/mailer/index.php" を指定
require_once __DIR__ . '/../../mailer/index.php';
