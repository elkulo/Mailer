<?php
declare(strict_types=1);

namespace App\Handler;

abstract class WordPressHandler implements HandlerInterface
{
    final public function sendmail(string $to, string $header, string $subject, string $body): void
    {
        console($to, $header, $subject, $body);
    }
}
