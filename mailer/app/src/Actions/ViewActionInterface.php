<?php
/**
 * Mailer | el.kulo v1.0.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2021 A.Sudo
 * Licensed under MIT (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

namespace App\Actions;

interface ViewActionInterface
{

  /**
   * バリデーションエラー画面テンプレート
   *
   * @param  array $data
   * @return void
   */
    public function displayValidate(array $data): void;

  /**
   * 確認画面テンプレート
   *
   * @param  array $data
   * @return void
   */
    public function displayConfirm(array $data): void;

  /**
   * 完了画面テンプレート
   *
   * @param  array $data
   * @return void
   */
    public function displayComplete(array $data): void;

  /**
   * 例外発生時の停止
   *
   * @param  string $error
   * @return void
   */
    public function displayExceptionExit(string $error): void;

  /**
   * 管理者メールテンプレート
   *
   * @param  array $data
   * @return string
   */
    public function renderAdminMail(array $data): string;

  /**
   * ユーザーメールテンプレート
   *
   * @param  array $data
   * @return string
   */
    public function renderUserMail(array $data): string;
}
