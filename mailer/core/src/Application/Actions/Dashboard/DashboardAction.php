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
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Log\LoggerInterface;
use Slim\Views\Twig;

/**
 * DashboardAction
 */
class DashboardAction extends Action
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

    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        $repository = $this->dashboardRepository->index();

        // bodyを生成
        $response = $this->view->render(
            $this->response,
            'dashboard/' . $repository['template'],
            $repository['data']
        );

        return $response;
    }
}
