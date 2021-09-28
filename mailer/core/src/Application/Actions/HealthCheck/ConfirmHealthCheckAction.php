<?php
/**
 * Mailer | el.kulo v3.0.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2021 A.Sudo
 * Licensed under MIT (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

namespace App\Application\Actions\HealthCheck;

use Psr\Http\Message\ResponseInterface as Response;

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

        // POSTエラー時のリダイレクト.
        if (isset($repository['redirect']) && $repository['redirect']) {
            return $this->response->withHeader('Location', $repository['redirect'])->withStatus(303);
        }

        // bodyを生成
        $response = $this->view->render(
            $this->response,
            'health-check/' . $repository['template'],
            $repository['data']
        );

        return $response;
    }
}