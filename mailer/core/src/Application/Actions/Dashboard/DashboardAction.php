<?php
/**
 * Mailer | el.kulo v3.0.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2021 A.Sudo
 * Licensed under MIT (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

namespace App\Application\Actions\Dashboard;

use App\Application\Actions\Action;
use App\Domain\Dashboard\DashboardRepository;
use Psr\Log\LoggerInterface;
use Slim\Views\Twig;
use Slim\Csrf\Guard;

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
     * CSRF対策
     *
     * @var Guard
     */
    protected $csrf;

    /**
     * @param LoggerInterface $logger
     * @param DashboardRepository $dashboardRepository
     * @param Twig $twig
     * @param Guard $csrf
     */
    public function __construct(
        LoggerInterface $logger,
        DashboardRepository $dashboardRepository,
        Twig $twig,
        Guard $csrf
    ) {
        parent::__construct($logger);
        $this->dashboardRepository = $dashboardRepository;
        $this->view = $twig;
        $this->csrf = $csrf;
    }
}
