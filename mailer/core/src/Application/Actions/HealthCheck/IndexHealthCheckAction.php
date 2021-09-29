<?php
/**
 * Mailer | el.kulo v3.0.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2021 A.Sudo
 * Licensed under MIT (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

namespace App\Application\Actions\HealthCheck;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Routing\RouteContext;

/**
 * IndexHealthCheckAction
 */
class IndexHealthCheckAction extends HealthCheckAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        $repository = $this->healthCheckRepository->index();

        // 次のステップURL.
        $router['next'] = RouteContext::fromRequest($this->request)
            ->getRouteParser()
            ->fullUrlFor($this->request->getUri(), 'health-check.confirm');

        // bodyを生成
        $response = $this->view->render(
            $this->response,
            'health-check/' . $repository['template'],
            array_merge($repository['data'], ['router' => $router])
        );

        return $response;
    }
}
