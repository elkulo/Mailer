<?php
/**
 * Mailer | el.kulo v3.0.0 (https://github.com/elkulo/Mailer/)
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
     * 必須項目チェック
     *
     * @return void
     */
    public function checkinRequired(): void;

    /**
     * メール形式チェック
     *
     * @return void
     */
    public function checkinEmail(): void;

    /**
     * 日本語チェック
     *
     * @return void
     */
    public function checkinMBWord(): void;

    /**
     * 禁止ワード
     *
     * @return void
     */
    public function checkinNGWord(): void;

    /**
     * メール文字判定
     *
     * @param  string $value
     * @return bool
     */
    public function isCheckMailFormat(string $value): bool;

    /**
     * Google reCAPTCHA
     *
     * @param  string $token
     * @param  string $action
     * @return void
     */
    public function checkinHuman(): void;

    /**
     * Google reCAPTCHA
     *
     * @param  string $token
     * @param  string $action
     * @return array
     */
    public function getCaptchaScript():array;
}
