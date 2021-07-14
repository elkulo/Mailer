<?php
/**
 * Mailer | el.kulo v1.0.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2021 A.Sudo
 * Licensed under MIT (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

/**
 * envまでのパスを変更することができます。
 * デフォルトはMailerプログラム直下になります。
 * 変更がない場合はコメントアウトを解除する必要はありません。
 */
//$ENV_PATH = __DIR__;

/**
 * Mailerプログラムを公開ディレクトリ以外（httpでアクセスできない場所）に設置、
 * core/App.php を require_once で読み込めば、任意のディレクトリやファイル名で実行できます。
 * <form action="..."> のactionに読み込みさせたファイルを指定する。
 */
require_once __DIR__ . '/core/App.php';
