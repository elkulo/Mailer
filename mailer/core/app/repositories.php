<?php
declare(strict_types=1);

use DI\ContainerBuilder;
use App\Domain\Mailer\MailerRepository;
use App\Infrastructure\Persistence\Mailer\InMemoryMailerRepository;
use App\Domain\HealthCheck\HealthCheckRepository;
use App\Infrastructure\Persistence\HealthCheck\InMemoryHealthCheckRepository;
use App\Domain\Dashboard\DashboardRepository;
use App\Infrastructure\Persistence\Dashboard\InMemoryDashboardRepository;

return function (ContainerBuilder $containerBuilder) {
    // Here we map our UserRepository interface to its in memory implementation
    $containerBuilder->addDefinitions([
        MailerRepository::class => \DI\autowire(InMemoryMailerRepository::class), // Repository
        HealthCheckRepository::class => \DI\autowire(InMemoryHealthCheckRepository::class), // Repository
        DashboardRepository::class => \DI\autowire(InMemoryDashboardRepository::class), // Repository
    ]);
};
