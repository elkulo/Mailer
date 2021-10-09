<?php
/**
 * Mailer | el.kulo v3.0.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2021 A.Sudo
 * Licensed under MIT (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

namespace App\Application\Actions\Dashboard;

use Psr\Http\Message\ResponseInterface as Response;

/**
 * CsrfJavaScriptAction
 */
class CsrfJavaScriptAction extends DashboardAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        return $this->view->render(
            $this->response,
            'assets/csrf.min.js.twig'
        )->withHeader('Content-Type', 'text/javascript');
    }
}
