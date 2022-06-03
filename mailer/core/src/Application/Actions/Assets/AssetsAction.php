<?php
/**
 * Mailer | el.kulo v3.3.2 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2022 A.Sudo
 * Licensed under LGPL-2.1-only (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

namespace App\Application\Actions\Assets;

use App\Application\Actions\Action;
use Psr\Log\LoggerInterface;
use Slim\Views\Twig;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

/**
 * AssetsAction
 */
class AssetsAction extends Action
{
   /**
     * @var Twig
     */
    protected $view;

    /**
     * @param LoggerInterface $logger
     * @param Twig $twig
     */
    public function __construct(
        LoggerInterface $logger,
        Twig $twig
    ) {
        parent::__construct($logger);
        $this->view = $twig;
    }

    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        return $this->response;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function guardJavaScript(Request $request, Response $response, array $args): Response
    {
        return $this->view->render(
            $response,
            'assets/guard.min.js.twig'
        )->withHeader('Content-Type', 'text/javascript');
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function recaptchaJavaScript(Request $request, Response $response, array $args): Response
    {
        return $this->view->render(
            $response,
            'assets/recaptcha.min.js.twig'
        )->withHeader('Content-Type', 'text/javascript');
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function bootstrapStyle(Request $request, Response $response, array $args): Response
    {
        return $this->view->render(
            $response,
            'assets/bootstrap.min.css.twig'
        )->withHeader('Content-Type', 'text/css');
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     * @return Response
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function bootstrapJavaScript(Request $request, Response $response, array $args): Response
    {
        return $this->view->render(
            $response,
            'assets/bootstrap.min.js.twig'
        )->withHeader('Content-Type', 'text/javascript');
    }
}
