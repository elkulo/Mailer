<?php
declare(strict_types=1);

namespace App\Handler;

interface HandlerInterface
{
    public function sendMail(string $to, string $subject, string $body, string $header): void;
}
