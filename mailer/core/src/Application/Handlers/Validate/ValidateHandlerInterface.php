<?php
/**
 * Mailer | el.kulo v1.0.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2021 A.Sudo
 * Licensed under MIT (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

namespace App\Application\Handlers\Validate;

interface ValidateHandlerInterface
{

    /**
     * POSTデータをセット
     *
     * @param  array $post_data
     * @return void
     */
    public function set(array $post_data): void;

    /**
     * バリデーションBoolType
     *
     * @return bool
     */
    public function validate(): bool;

    /**
     * エラー内容
     *
     * @return array
     */
    public function errors(): array;

    /**
     * バリデーションチェック
     *
     * @return void
     */
    public function checkinValidateAll(): void;

    /**
     * メール文字判定
     *
     * @param  string $value
     * @return bool
     */
    public function isCheckMailFormat(string $value): bool;
}
