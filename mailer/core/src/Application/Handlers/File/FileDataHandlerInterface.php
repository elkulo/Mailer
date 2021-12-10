<?php
/**
 * Mailer | el.kulo v3.2.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2022 A.Sudo
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
    public function run(bool $clear = false): void;

    /**
     * POSTされたFILE変数の取得
     *
     * @return array
     */
    public function getPostFiles(): array;

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
     * アップロード画像をすべて取得
     *
     * @return array
     */
    public function getAttachmentAll(): array;

    /**
     * アップロード画像を削除
     *
     * @return void
     */
    public function destroy(): void;
}
