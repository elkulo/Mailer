<?php
/**
 * Mailer | el.kulo v1.0.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2021 A.Sudo
 * Licensed under MIT (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

use App\Domain\MailerRepository;
use App\Infrastructure\Persistence\InMemoryMailerRepository;

return [
    MailerRepository::class => \DI\autowire(InMemoryMailerRepository::class), // Repository
];
