<?php
/**
 * Mailer | el.kulo v3.6.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2024 A.Sudo
 * Licensed under LGPL-2.1-only (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

namespace App\Application\Handlers\Validate;

interface ValidateHandlerInterface
{

    /**
     * POSTデータをセット
     *
     * @param  array $posts
     * @return void
     */
    public function set(array $posts): void;

    /**
     * バリデーションチェック
     *
     * @return bool
     */
    public function validate(): bool;

    /**
     * バリデーションALLチェック
     *
     * @return bool
     */
    public function validateAll(): bool;

    /**
     * エラー内容
     *
     * @return array
     */
    public function errors(): array;

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
    public function checkinMultibyteWord(): void;

    /**
     * 禁止ワード
     *
     * @return void
     */
    public function checkinBlockNGWord(): void;

    /**
     * 禁止ドメイン
     *
     * @return void
     */
    public function checkinBlockDomain(): void;

    /**
     * メール文字判定
     *
     * @param  string $value
     * @return bool
     */
    public function isCheckMailFormat(string $value): bool;

    /**
     * リファラチェック
     *
     * @return bool
     */
    public function isCheckReferer(): bool;

    /**
     * BOT判定
     *
     * @return void
     */
    public function checkinHuman(): void;

    /**
     * Google reCAPTCHA
     *
     * @return array
     */
    public function getReCaptchaScript():array;

    /**
     * 伏字のEmail
     *
     * @param  string $email
     * @return string
     */
    public function getHiddenEmail(string $email): string;
}
