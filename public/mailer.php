<?php

/**
 * Mailerプログラムを公開ディレクトリ以外（httpでアクセスできない場所）に設置、
 * post.php を require_once で読み込めば、任意のディレクトリで実行できます。
 * <form action="./mailer.php"> のPOST先に読み込みさせたこのファイルを指定する。
 */
require_once __DIR__ . '/../mailer/post.php';
