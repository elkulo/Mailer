<?php
declare(strict_types=1);

namespace App\Handler;

interface HandlerInterface
{
    public function sendmail(string $to, string $header, string $subject, string $body): void;
}
