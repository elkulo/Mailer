<?php
/**
 * Mailer | el.kulo v1.0.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2021 A.Sudo
 * Licensed under MIT (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

namespace App\Domain;

interface MailerInterface
{

    /**
     * サーバー設定情報の取得
     *
     * @return array
     */
    public function getServer(): array;

    /**
     * アプリ設定情報の取得
     *
     * @return array
     */
    public function getSetting(): array;

    /**
     * POSTデータから取得したデータを整形
     *
     * @param  array $posts
     * @return void
     */
    public function setPost($posts): void;

    /**
     * POSTデータを取得
     *
     * @return array
     */
    public function getPost(): array;

    /**
     * POSTデータを文字連結して取得
     *
     * @return string
     */
    public function getPostToString(): string;

    /**
     * ユーザーメールをセット
     *
     * @param  string $user_mail
     * @return void
     */
    public function setUserMail(string $user_mail): void;

    /**
     * ユーザーメールを取得
     *
     * @return string
     */
    public function getUserMail(): string;

    /**
     * メール件名（共通）
     *
     * @return string
     */
    public function getMailSubject(): string;

    /**
     * メールボディ（共通）
     *
     * @return array
     */
    public function getMailBody(): array;

    /**
     * 管理者メールヘッダ.
     *
     * @param  string $type
     * @return array
     */
    public function getMailAdminHeader(): array;

    /**
     * ページリファラーを取得
     *
     * @return string
     */
    public function getPageReferer(): string;

    /**
     * 確認画面の入力内容出力用関数
     *
     * @return string
     */
    public function getConfirm(): string;

    /**
     * 確認画面のフォームにアクション先出力
     *
     * @return string
     */
    public function getActionURL(): string;

    /**
     * 完了後のリンク先
     *
     * @return string
     */
    public function getReturnURL(): string;

    /**
     * トークン出力
     *
     * @return string
     */
    public function getCreateNonce(): string;

    /**
     * 送信画面判定
     *
     * @return bool
     */
    public function isConfirmSubmit(): bool;

    /**
     * リファラチェック
     *
     * @return void
     */
    public function checkinReferer(): void;

    /**
     * セッションチェック
     *
     * @return void
     */
    public function checkinSession(): void;

    /**
     * トークンチェック
     *
     * @return void
     */
    public function checkinToken(): void;

    /**
     * nameとラベルの属性の置き換え
     *
     * @param  string $name
     * @return string
     */
    public function nameToLabel(string $name): string;

    /**
     * 全角半角変換
     *
     * @param  string $output
     * @param  string $key
     * @return string
     */
    public function changeHankaku(string $output, string $key): string;

    /**
     * 配列連結の処理
     *
     * @param  array $items
     * @return string
     */
    public function changeJoin(array $items): string;

    /**
     * エスケープ
     *
     * @param  mixed $content
     * @param  string $encode
     * @return mixed
     */
    public function kses($content, string $encode);
}
