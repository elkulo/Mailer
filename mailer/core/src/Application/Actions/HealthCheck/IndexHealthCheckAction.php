<?php
/**
 * Mailer | el.kulo v3.3.1 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2022 A.Sudo
 * Licensed under LGPL-2.1-only (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

namespace App\Application\Actions\HealthCheck;

use Psr\Http\Message\ResponseInterface as Response;

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

        return $this->view->render(
            $this->response,
            'health-check/' . $repository['template'],
            $repository['data']
        );
    }
}
