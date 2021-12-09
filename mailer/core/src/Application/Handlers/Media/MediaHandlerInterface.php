<?php
/**
 * Mailer | el.kulo v3.2.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2022 A.Sudo
 * Licensed under LGPL-2.1-only (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

namespace App\Application\Handlers\Media;

interface MediaHandlerInterface
{

    /**
     * アップロード画像を保存
     *
     * @return void
     */
    public function init(): void;

    /**
     * アップロード画像を取得
     *
     * @return array|bool
     */
    public function get($key);

    /**
     * アップロード画像をすべて取得
     *
     * @return array
     */
    public function getAttachmentAll(): array;

    /**
     * アップロード画像を削除
     *
     * @return bool
     */
    public function destroy(): bool;
}
