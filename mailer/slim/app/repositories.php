<?php
declare(strict_types=1);

use App\Domain\User\UserRepository;
use App\Infrastructure\Persistence\User\InMemoryUserRepository;
use DI\ContainerBuilder;

use App\Domain\Mailer\MailerRepository;
use App\Infrastructure\Persistence\Mailer\InMemoryMailerRepository;

return function (ContainerBuilder $containerBuilder) {
    // Here we map our UserRepository interface to its in memory implementation
    $containerBuilder->addDefinitions([
        UserRepository::class => \DI\autowire(InMemoryUserRepository::class),
        MailerRepository::class => \DI\autowire(InMemoryMailerRepository::class), // Repository
    ]);
};
