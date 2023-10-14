<?php
/**
 * Mailer | el.kulo v3.5.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2023 A.Sudo
 * Licensed under LGPL-2.1-only (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

namespace App\Application\Actions\HealthCheck;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Routing\RouteContext;

/**
 * ConfirmHealthCheckAction
 */
class ConfirmHealthCheckAction extends HealthCheckAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        $repository = $this->healthCheckRepository->confirm();

        if (isset($repository['redirect']) && $repository['redirect']) {
            return $this->response->withHeader('Location', $repository['redirect'])->withStatus(303);
        } else {
            return $this->view->render(
                $this->response,
                'health-check/' . $repository['template'],
                $repository['data']
            );
        }
    }
}
