<?php
/**
 * Mailer | el.kulo v3.0.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2021 A.Sudo
 * Licensed under LGPL-2.1-only (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

/**
 * Locale.
 *
 * @param  string $lang
 * @param  string $domain
 * @return void
 */
function locale($lang, $domain = 'mailer'): void
{
    $locator = function () use ($lang) {
        switch ($lang) {
            case 'ja':
                return 'ja_JP.UTF-8';
                break;
            default:
                return 'en_US.UTF-8';
        }
    };

    // 翻訳ファイル.
    setlocale(LC_ALL, $locator()); // ロケールを指定.
    bindtextdomain($domain, __DIR__ . '/../languages'); // 翻訳ファイルのパス.
    textdomain($domain); // 翻訳ファイル名のドメイン指定.
    bind_textdomain_codeset($domain, 'UTF-8'); // エンコード.
}
