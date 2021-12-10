<?php
/**
 * Mailer | el.kulo v3.2.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2022 A.Sudo
 * Licensed under LGPL-2.1-only (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

namespace App\Application\Handlers\Media;

use App\Application\Settings\SettingsInterface;
use Psr\Log\LoggerInterface;

class MediaHandler implements MediaHandlerInterface
{

    /**
     * ロガー
     *
     * @var LoggerInterface
     */
    private $logger;

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
     * @param LoggerInterface $logger,
     * @param  SettingsInterface $settings
     * @return void
     */
    public function __construct(LoggerInterface $logger, SettingsInterface $settings)
    {
        $this->logger = $logger;
        $this->settings = $settings;
        $this->uploadDir = $this->settings->get('appPath') . '/var/tmp/';
        $this->clearOldFiles();
        $this->flashTmpFiles();
    }

    /**
     * アップロード画像を保存
     *
     * @return void
     * @throws \Exception  アップロードエラー
     */
    public function init(): void
    {
        $formSettings = $this->settings->get('form');
        $uploadDir = $this->uploadDir;
        $file = [];
        $fileNames = [];

        try {
            foreach ($formSettings['ATTACHMENT_ATTRIBUTES'] as $attributeName) {
                // 許可するファイルタイプ
                $accepts = (empty($formSettings['ATTACHMENT_ACCEPTS']))? [
                    'image/png',
                    'image/gif',
                    'image/jpeg'
                ]: $formSettings['ATTACHMENT_ACCEPTS'];

                if (isset($_FILES[$attributeName]) && !empty($_FILES[$attributeName]['tmp_name'])) {
                    $file = [
                        '_origin_tmp' => $_FILES[$attributeName]['tmp_name'],
                        'name' => $_FILES[$attributeName]['name'],
                        'type' => $_FILES[$attributeName]['type'],
                        'size' => $_FILES[$attributeName]['size'],
                        'ext' => pathinfo($_FILES[$attributeName]['name'], PATHINFO_EXTENSION),
                        'tmp' => '',
                    ];

                    // ファイル名が重複している場合はスキップ.
                    if (in_array($file['name'], $fileNames)) {
                        continue;
                    } else {
                        $fileNames[] = $file['name'];
                    }

                    // POSTされたファイルの判定.
                    if (is_writable($uploadDir) && is_uploaded_file($file['_origin_tmp'])) {
                        // 許可されたファイルか判定.
                        if (!in_array($file['type'], $accepts)) {
                            throw new \Exception('添付のファイルタイプでは送信できません。');
                        }

                        // エンコード可能か調べてユニークなファイル名に変換.
                        if (mb_check_encoding( $file['name'], 'ISO-2022-JP')) {
                            $hashFile = $uploadDir . md5(uniqid($file['name'], true)) . '.' . $file['ext'];
                        } else {
                            throw new \Exception('添付ファイルの名前では文字化けが起こります。ファイル名を変更してください。');
                        }

                        // ファイルがアップロード成功したかの判定.
                        if (move_uploaded_file($file['_origin_tmp'], $hashFile)) {
                            $file['tmp'] = $hashFile;
                        } else {
                            throw new \Exception('添付ファイルのアップロードに失敗しました。');
                        }
                    } else {
                        throw new \Exception('キャッシュディレクトリの書き込みが許可されていません。');
                    }
                    $this->files[$attributeName] = $file;
                }
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
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

            // ファイル名をエンコードしてリネーム.
            $attachmentFile = $uploadDir . mb_encode_mimeheader( $file['name'], 'ISO-2022-JP', 'UTF-8' );
            if ($file['tmp'] !== $attachmentFile && rename($file['tmp'], $attachmentFile)) {

                $attachments[] = $attachmentFile;

                // 一時ファイルへ格納
                $this->files[$key]['tmp'] = $attachmentFile;
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
            $this->logger->error($e->getMessage());
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
                        if (is_writable($filePath)) {
                            if (!unlink($filePath)) {
                                throw new \Exception($filePath . 'の削除に失敗しました');
                                break;
                            }
                        } else {
                            throw new \Exception('tmpディレクトリに書き込み権限がありません');
                            break;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
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
