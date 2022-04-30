<?php
/**
 * Mailer | el.kulo v3.3.1 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2022 A.Sudo
 * Licensed under LGPL-2.1-only (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

namespace App\Application\Actions\Mailer;

use Psr\Http\Message\ResponseInterface as Response;

/**
 * CompleteMailerAction
 */
class CompleteMailerAction extends MailerAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        $repository = $this->mailerRepository->complete();

        return $this->view->render(
            $this->response,
            $repository['template'],
            $repository['data']
        );
    }
}
