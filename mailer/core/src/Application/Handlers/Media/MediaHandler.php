<?php
/**
 * Mailer | el.kulo v3.2.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2022 A.Sudo
 * Licensed under LGPL-2.1-only (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

namespace App\Application\Handlers\Media;

use App\Application\Settings\SettingsInterface;

class MediaHandler implements MediaHandlerInterface
{

    /**
     * 設定値
     *
     * @var SettingsInterface
     */
    private $settings;

    /**
     * アップロードファイルのディレクトリ
     *
     * @var string
     */
    private $uploadDir;

    /**
     * アップロードファイルの格納
     *
     * @var array
     */
    private $files = [];

    /**
     * コンストラクタ
     *
     * @param  SettingsInterface $settings
     * @return void
     */
    public function __construct(SettingsInterface $settings)
    {
        $this->settings = $settings;
        $this->uploadDir = $this->settings->get('appPath') . '/var/tmp/';
        $this->clearOldFiles();
        $this->flashTmpFiles();
    }

    /**
     * アップロード画像を保存
     *
     * @return void
     */
    public function init(): void
    {
        $formSettings = $this->settings->get('form');
        $uploadDir = $this->uploadDir;
        $file = [];

        try {
            foreach ($formSettings['ATTACHMENT_ATTRIBUTES'] as $key => $allowed) {
                if (isset($_FILES[$key]) && !empty($_FILES[$key]['tmp_name'])) {
                    $file = [
                        '_origin_tmp' => $_FILES[$key]['tmp_name'],
                        'name' => $_FILES[$key]['name'],
                        'type' => $_FILES[$key]['type'],
                        'size' => $_FILES[$key]['size'],
                        'ext' => pathinfo($_FILES[$key]['name'], PATHINFO_EXTENSION),
                        'tmp' => '',
                    ];

                    // POSTされたファイルの判定.
                    if (is_writable($uploadDir) && is_uploaded_file($file['_origin_tmp'])) {
                        // 許可されたファイルか判定.
                        if (!in_array($file['type'], $allowed)) {
                            throw new \Exception('許可されていない拡張子');
                        }

                        // ユニークなファイル名に変換.
                        $hashFile = $uploadDir . md5(uniqid($file['name'], true)) . '.' . $file['ext'];

                        // ファイルがアップロード成功したかの判定.
                        if (move_uploaded_file($file['_origin_tmp'], $hashFile)) {
                            $file['tmp'] = $hashFile;
                        } else {
                            throw new \Exception('画像ファイルのアップロードに失敗しました');
                        }
                    } else {
                        throw new \Exception('キャッシュディレクトリの書き込みが許可されていません');
                    }
                    $this->files[$key] = $file;
                }
            }
        } catch (\Exception $e) {
            console($e->getMessage());
        }
        $this->seveTmpFiles();
    }

    /**
     * アップロード画像をすべて取得
     *
     * @return array
     */
    public function getAttachmentAll(): array
    {
        $uploadDir = $this->uploadDir;
        $attachments = [];
        foreach ($this->files as $key => $file) {
            $attachmentPath = $uploadDir . $file['name'];

            // 一時ファイルをリネーム
            if ($file['tmp'] !== $attachmentPath && rename($file['tmp'], $attachmentPath)) {
                $attachments[] = $attachmentPath;

                // 一時ファイルへ格納
                $this->files[$key]['tmp'] = $attachmentPath;
            }
        }
        return $attachments;
    }

    /**
     * アップロード画像を削除
     *
     * @return void
     */
    public function destroy(): void
    {
        try {
            foreach ($this->files as $file) {
                if (!isset($file['tmp'])) {
                    continue;
                }
                if (is_writable($file['tmp'])) {
                    if (!unlink($file['tmp'])) {
                        throw new \Exception($file['tmp'] . 'の削除に失敗しました');
                    }
                } else {
                    throw new \Exception('tmpディレクトリに書き込み権限がありません');
                }
            }
        } catch (\Exception $e) {
            console($e->getMessage());
        }
    }

    /**
     * 古いアップロード画像を削除
     *
     * @return void
     */
    private function clearOldFiles(): void
    {
        try {
            $expire = strtotime('-1 hour');

            $listDirFiles = scandir($this->uploadDir);

            foreach ($listDirFiles as $file) {
                $filePath = $this->uploadDir . $file;
                // フォルダと隠しファイルは除外
                if (is_file($filePath) && substr($file, 0, 1) !== '.') {
                    $mod = filemtime($filePath);
                    if ($mod < $expire) {
                        unlink($filePath);
                    }
                }
            }
        } catch (\Exception $e) {
            console($e->getMessage());
        }
    }

    /**
     * セッションに一時保存
     *
     * @return void
     */
    private function seveTmpFiles(): void
    {
        $_SESSION['updateFiles'] = $this->files;
    }

    /**
     * セッションから取得して削除
     *
     * @return void
     */
    private function flashTmpFiles(): void
    {
        if (isset($_SESSION['updateFiles'])) {
            $this->files = $_SESSION['updateFiles'];
            unset($_SESSION['updateFiles']);
        }
    }
}
