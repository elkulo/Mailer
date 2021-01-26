<?php
declare(strict_types=1);

namespace App\Handler;

interface HandlerInterface
{

    /**
     * send
     *
     * @param  string $to
     * @param  string $subject
     * @param  string $body
     * @param  array $header
     * @return void
     */
    public function send(string $to, string $subject, string $body, array $header): void;
}
