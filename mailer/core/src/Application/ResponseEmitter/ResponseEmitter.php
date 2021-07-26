<?php
/**
 * Mailer | el.kulo v1.0.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2021 A.Sudo
 * Licensed under MIT (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

namespace App\Application\ResponseEmitter;

use Psr\Http\Message\ResponseInterface;

class ResponseEmitter
{
    /**
     * Emit
     * 
     * @var ResponseInterface $response
     * @return void
     */
    public function emit(ResponseInterface $response): void
    {
        if (ob_get_contents()) {
            ob_clean();
        }
    }
}