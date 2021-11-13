<?php
/**
 * Mailer | el.kulo v3.2.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2022 A.Sudo
 * Licensed under LGPL-2.1-only (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

namespace App\Application\Actions\Dashboard;

use App\Application\Actions\Action;
use App\Domain\Dashboard\DashboardRepository;
use Psr\Log\LoggerInterface;
use Slim\Views\Twig;

/**
 * DashboardAction
 */
abstract class DashboardAction extends Action
{
    /**
     * @var DashboardRepository
     */
    protected $dashboardRepository;

   /**
     * @var Twig
     */
    protected $view;

    /**
     * @param LoggerInterface $logger
     * @param DashboardRepository $dashboardRepository
     * @param Twig $twig
     */
    public function __construct(
        LoggerInterface $logger,
        DashboardRepository $dashboardRepository,
        Twig $twig
    ) {
        parent::__construct($logger);
        $this->dashboardRepository = $dashboardRepository;
        $this->view = $twig;
    }
}
