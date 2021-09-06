<?php
/**
 * Mailer | el.kulo v3.0.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2021 A.Sudo
 * Licensed under MIT (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

/**
 * 環境変数
 * 
 * envまでのパスを変更することができます。
 * デフォルトはMailerプログラム直下になります。
 * 変更がない場合はコメントアウトを解除する必要はありません。
 */
// $ENV_PATH = __DIR__;

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
// require_once __DIR__ . '/../../../../wp-load.php';

/**
 * 任意なファイル名
 * 
 * Mailerプログラムを公開ディレクトリ以外（httpでアクセスできない場所）に設置、
 * /mailer/index.php を require_once で読み込めば、任意のディレクトリやファイル名で実行できます。
 * <form action="..."> のactionに読み込みさせたファイルを指定する。
 */

// 任意のディレクトリ名
$BASE_PATH = '/mailer-alias';

// Mailerプログラムの /mailer/index.php を指定
require_once __DIR__ . '/../../mailer/index.php';
