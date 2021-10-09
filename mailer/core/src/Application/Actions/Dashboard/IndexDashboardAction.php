<?php
/**
 * Mailer | el.kulo v3.0.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2021 A.Sudo
 * Licensed under MIT (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

namespace App\Application\Actions\Dashboard;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Routing\RouteContext;

/**
 * IndexDashboardAction
 */
class IndexDashboardAction extends DashboardAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        $repository = $this->dashboardRepository->index();

        // 次のステップURL.
        $router = [
            'mailer' => RouteContext::fromRequest($this->request)
                ->getRouteParser()
                ->fullUrlFor($this->request->getUri(), 'mailer'),
            'health_check' => RouteContext::fromRequest($this->request)
                ->getRouteParser()
                ->fullUrlFor($this->request->getUri(), 'health-check'),
            'csrf' => [
                'js' => RouteContext::fromRequest($this->request)
                    ->getRouteParser()
                    ->fullUrlFor($this->request->getUri(), 'assets-csrf-js'),
                'json' => RouteContext::fromRequest($this->request)
                    ->getRouteParser()
                    ->fullUrlFor($this->request->getUri(), 'api-csrf-json'),
            ],
        ];

        // bodyを生成
        $response = $this->view->render(
            $this->response,
            'dashboard/' . $repository['template'],
            array_merge($repository['data'], ['Router' => $router])
        );

        return $response;
    }
}
