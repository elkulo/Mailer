<?php
declare(strict_types=1);

namespace App\Domain\HealthCheck;

use App\Domain\DomainException\DomainRecordNotFoundException;

class HealthCheckNotFoundException extends DomainRecordNotFoundException
{
    public $message = 'The user you requested does not exist.';
}
