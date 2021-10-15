<?php
/**
 * Mailer | el.kulo v3.0.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2021 A.Sudo
 * Licensed under LGPL-2.1-only (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

namespace App\Application\Actions\HealthCheck;

use App\Application\Actions\Action;
use App\Domain\HealthCheck\HealthCheckRepository;
use Psr\Log\LoggerInterface;
use Slim\Views\Twig;

abstract class HealthCheckAction extends Action
{
    /**
     * @var HealthCheckRepository
     */
    protected $healthCheckRepository;

   /**
     * @var Twig
     */
    protected $view;

    /**
     * @param LoggerInterface $logger
     * @param HealthCheckRepository $healthCheckRepository
     * @param Twig $twig
     */
    public function __construct(
        LoggerInterface $logger,
        HealthCheckRepository $healthCheckRepository,
        Twig $twig
    ) {
        parent::__construct($logger);
        $this->healthCheckRepository = $healthCheckRepository;
        $this->view = $twig;
    }
}
