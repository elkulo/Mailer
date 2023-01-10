<?php
/**
 * Mailer | el.kulo v3.4.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2023 A.Sudo
 * Licensed under LGPL-2.1-only (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

namespace App\Application\Handlers\File;

interface FileDataHandlerInterface
{

    /**
     * $_FILESの格納
     *
     * @param  array $files
     */
    public function set(array $files): void;

    /**
     * アップロードを実行
     *
     * @return void
     */
    public function run(): void;

    /**
     * POSTされたFILE変数の取得
     *
     * @return array
     */
    public function getPostedFiles(): array;

    /**
     * Twig変数の取得
     *
     * @return array
     */
    public function getFiles(): array;

    /**
     * Twig変数のすべて取得
     *
     * @return array
     */
    public function getDataQuery(): array;

    /**
     * メールテンプレート用にファイル名を取得
     *
     * @return array
     */
    public function getFileNames(): array;

    /**
     * ファイル名をCSV形式で取得
     *
     * @return string
     */
    public function getFileCSV(): string;

    /**
     * 管理者へのアップロード画像を取得
     *
     * @return array
     */
    public function getAdminMailAttachment(): array;

    /**
     * ユーザーへのアップロード画像を取得
     *
     * @return array
     */
    public function getUserMailAttachment(): array;

    /**
     * 確認画面の入力内容の隠しにアップロードIDを出力
     *
     * @return string
     */
    public function getTmpFiles(): string;

    /**
     * アップロード画像を削除
     *
     * @return void
     */
    public function destroy(): void;
}
