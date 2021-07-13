<?php
/**
 * Mailer | el.kulo v1.0.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2021 A.Sudo
 * Licensed under MIT (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

namespace App\Application\Middleware;

/**
 * ReCAPTCHA
 */
class ReCAPTCHA
{

    /**
     * DBに保存
     *
     * @param  array $content
     * @return bool
     */
    public function save(array $content): bool
    {
        return true;
    }
}