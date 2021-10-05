<?php
/**
 * Mailer | el.kulo v3.0.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2021 A.Sudo
 * Licensed under MIT (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

namespace App\Application\Actions\Mailer;

use Psr\Http\Message\ResponseInterface as Response;
use Slim\Routing\RouteContext;

/**
 * IndexMailerAction
 */
class IndexMailerAction extends MailerAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        $repository = $this->mailerRepository->index();

        // 次のステップURL.
        $router['url'] = RouteContext::fromRequest($this->request)
            ->getRouteParser()
            ->fullUrlFor($this->request->getUri(), 'mailer.confirm');

        // bodyを生成
        $response = $this->view->render(
            $this->response,
            'templates/' . $repository['template'],
            array_merge($repository['data'], ['action' => $router])
        );

        return $response;
    }
}
