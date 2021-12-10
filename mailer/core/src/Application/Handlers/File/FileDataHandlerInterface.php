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
     * アップロード画像を保存
     *
     * @return void
     */
    public function init(bool $clear = false): void;

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
    public function getConfirmQuery(): array;

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
