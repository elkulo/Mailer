<?php
declare(strict_types=1);

namespace App\Domain\Dashboard;

use App\Domain\DomainException\DomainRecordNotFoundException;

class DashboardNotFoundException extends DomainRecordNotFoundException
{
    public $message = 'The user you requested does not exist.';
}
