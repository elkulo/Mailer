<?php
/**
 * Mailer | el.kulo v1.0.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2021 A.Sudo
 * Licensed under MIT (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 *
 * 
 * envまでのパスを変更することもできます。
 * デフォルトはMailerプログラム直下になります。
 * 変更がない場合はコメントアウトを解除する必要はありません。
 */
//$ENV_PATH = __DIR__;

/**
 * Mailerプログラムを公開ディレクトリ以外（httpでアクセスできない場所）に設置、
 * post.php を require_once で読み込めば、任意のディレクトリで実行できます。
 * <form action="./mailer.php"> のPOST先に読み込みさせたこのファイルを指定する。
 */
require_once __DIR__ . '/../mailer/post.php';
