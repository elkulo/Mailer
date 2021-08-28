<?php
declare(strict_types=1);

namespace App\Domain\Mailer;

use App\Domain\DomainException\DomainRecordNotFoundException;

class MailNotFoundException extends DomainRecordNotFoundException
{
    public $message = 'The user you requested does not exist.';
}
