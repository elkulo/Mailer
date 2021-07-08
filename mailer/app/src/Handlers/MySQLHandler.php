<?php
/**
 * Mailer | el.kulo v1.0.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2021 A.Sudo
 * Licensed under MIT (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

namespace App\Handlers;

use Illuminate\Database\Capsule\Manager;
use App\Interfaces\DBHandlerInterface;

/**
 * MySQLHandler
 */
class MySQLHandler implements DBHandlerInterface
{

    /**
     * DBに保存
     *
     * @param  array $content
     * @return bool
     */
    final public function save(array $content): bool
    {
        return true;
    }
}
