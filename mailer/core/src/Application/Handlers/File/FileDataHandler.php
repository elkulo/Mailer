<?php
/**
 * Mailer | el.kulo v3.2.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2022 A.Sudo
 * Licensed under LGPL-2.1-only (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

namespace App\Application\Handlers\File;

use App\Application\Settings\SettingsInterface;
use Psr\Log\LoggerInterface;

class FileDataHandler implements FileDataHandlerInterface
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
     * $_FILESの格納
     *
     * @var array
     */
    private $tmpFiles = [];

    /**
     * アップロードファイルの格納
     *
     * @var array
     */
    private $fileData = [];

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
        $this->destroyOldFiles();
    }

    /**
     * $_FILESをセット
     *
     * @param  array $files
     */
    public function set(array $files): void
    {
        $this->tmpFiles = $files;
    }

    /**
     * アップロードを実行
     *
     * @param  bool $clear 一時ファイルを読み込み削除
     * @return void
     * @throws \Exception  アップロードエラー
     */
    public function run(bool $clear = false): void
    {
        $formSettings = $this->settings->get('form');
        $uploadDir = $this->uploadDir;
        $file = [];
        $fileNames = [];

        try {
            // 一時ファイルを読み込み.
            if ($clear) {
                $this->flashTmpFiles();
            }

            foreach ($formSettings['ATTACHMENT_ATTRIBUTES'] as $attr) {
                // 許可するファイルタイプ
                $accepts = (empty($formSettings['ATTACHMENT_ACCEPTS']))? [
                    'image/png',
                    'image/gif',
                    'image/jpeg',
                    'image/jpg',
                ]: $formSettings['ATTACHMENT_ACCEPTS'];

                if (isset($this->tmpFiles[$attr]) && !empty($this->tmpFiles[$attr]['tmp_name'])) {
                    $file = [
                        '_origin_tmp' => $this->tmpFiles[$attr]['tmp_name'],
                        'name' => $this->tmpFiles[$attr]['name'],
                        'type' => $this->tmpFiles[$attr]['type'],
                        'size' => $this->tmpFiles[$attr]['size'],
                        'ext' => pathinfo($this->tmpFiles[$attr]['name'], PATHINFO_EXTENSION),
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

                        // アップロードサイズを制限.
                        if ($formSettings['ATTACHMENT_MAXSIZE'] < ceil($file['size'] / 1024)) {
                            throw new \Exception('添付ファイルのサイズが大き過ぎます。');
                        }

                        // エンコード可能か調べてユニークなファイル名に変換.
                        $hashFile = $uploadDir . md5(uniqid($file['name'], true)) . '.' . $file['ext'];

                        // ファイルがアップロード成功したかの判定.
                        if (move_uploaded_file($file['_origin_tmp'], $hashFile)) {
                            $file['tmp'] = $hashFile;
                        } else {
                            throw new \Exception('添付ファイルのアップロードに失敗しました。');
                        }
                    } else {
                        throw new \Exception('キャッシュディレクトリの書き込みが許可されていません。');
                    }
                    $this->fileData[$attr] = $file;
                }
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
        $this->seveTmpFiles();
    }

    /**
     * POSTされたFILE変数の取得
     *
     * @return array
     */
    public function getPostFiles(): array
    {
        $formSettings = $this->settings->get('form');
        $fileStatus = [];

        // セッションから取得
        $sessionData = isset($_SESSION['updateFiles'])? $_SESSION['updateFiles']: [];

        foreach ($formSettings['ATTACHMENT_ATTRIBUTES'] as $attr) {
            $fileStatus[$attr] = isset($this->tmpFiles[$attr]['name']) ? $this->tmpFiles[$attr]['name'] : '';

            // セッションからの取得を試みる.
            if (!$fileStatus[$attr]) {
                $fileStatus[$attr] = isset($sessionData[$attr]['name']) ? $sessionData[$attr]['name'] : '';
            }
        }
        return $fileStatus;
    }

    /**
     * Twig変数の取得
     *
     * @return array
     */
    public function getFiles(): array
    {
        $fileStatus = [];
        foreach ($this->fileData as $key => $file) {
            $fileStatus[$key] = $file['name'];
        }
        return $fileStatus;
    }

    /**
     * Twig変数のすべて取得
     *
     * @return array
     */
    public function getDataQuery(): array
    {
        $formSettings = $this->settings->get('form');
        $query = [];

        foreach ($this->fileData as $name => $file) {
            // ラベル変換.
            $label = $name;
            if (isset($formSettings['NAME_FOR_LABELS'][$name])) {
                $label = $formSettings['NAME_FOR_LABELS'][$name];
            }

            // ファイルサイズ
            $bytes = (string) $this->prettyBytes((int) $file['size'], 2);

            // 確認をセット
            $query[] = [
                'name' => $this->esc($label),
                'value' => $this->esc($file['name']),
                'size' => $this->esc($bytes),
            ];
        }
        return $query;
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
        foreach ($this->fileData as $key => $file) {
            // ファイル名をエンコードしてリネーム.
            $attachmentFile = $uploadDir . mb_encode_mimeheader($file['name'], 'ISO-2022-JP', 'UTF-8');
            if ($file['tmp'] !== $attachmentFile && rename($file['tmp'], $attachmentFile)) {
                $attachments[] = $attachmentFile;

                // 一時ファイルへ格納
                $this->fileData[$key]['tmp'] = $attachmentFile;
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
            foreach ($this->fileData as $file) {
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
    private function destroyOldFiles(): void
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
        $_SESSION['updateFiles'] = $this->fileData;
    }

    /**
     * セッションから取得して削除
     *
     * @return void
     */
    private function flashTmpFiles(): void
    {
        if (isset($_SESSION['updateFiles'])) {
            $this->fileData = $_SESSION['updateFiles'];
            unset($_SESSION['updateFiles']);
        }
    }

    /**
     * 単位変換
     *
     * @param  int  $bytes     バイト数
     * @param  int  $dec       小数点の省略する桁
     * @param  bool $separate  桁区切り
     * @return int
     */
    private function prettyBytes(int $bytes, int $dec = -1, bool $separate = false)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $digits = ($bytes == 0) ? 0 : floor(log($bytes, 1024));
         
        $over = false;
        $maxDigit = count($units) -1 ;
     
        if ($digits == 0) {
            $num = $bytes;
        } elseif (!isset($units[$digits])) {
            $num = $bytes / (pow(1024, $maxDigit));
            $over = true;
        } else {
            $num = $bytes / (pow(1024, $digits));
        }
         
        if ($dec > -1 && $digits > 0) {
            $num = sprintf("%.{$dec}f", $num);
        }
        if ($separate && $digits > 0) {
            $num = number_format($num, $dec);
        }
         
        return ($over) ? $num . $units[$maxDigit] : $num . $units[$digits];
    }

    /**
     * エスケープ
     *
     * @param  array|string $content
     * @param  string $encode
     * @return array|string
     */
    private function esc($content, string $encode = 'UTF-8')
    {
        $sanitized = [];
        if (is_array($content)) {
            foreach ($content as $key => $value) {
                $sanitized[$key] = trim(htmlspecialchars($value, ENT_QUOTES, $encode));
            }
        } else {
            return trim(htmlspecialchars($content, ENT_QUOTES, $encode));
        }
        return $sanitized;
    }
}
