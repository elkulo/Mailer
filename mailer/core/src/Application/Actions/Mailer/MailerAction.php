<?php
/**
 * Mailer | el.kulo v3.0.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2021 A.Sudo
 * Licensed under LGPL-2.1-only (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

namespace App\Application\Actions\Mailer;

use App\Application\Actions\Action;
use App\Domain\Mailer\MailerRepository;
use Psr\Log\LoggerInterface;
use Slim\Views\Twig;

abstract class MailerAction extends Action
{
    /**
     * @var MailerRepository
     */
    protected $mailerRepository;

   /**
     * @var Twig
     */
    protected $view;

    /**
     * @param LoggerInterface $logger
     * @param MailerRepository $userRepository
     * @param Twig $twig
     */
    public function __construct(
        LoggerInterface $logger,
        MailerRepository $mailerRepository,
        Twig $twig
    ) {
        parent::__construct($logger);
        $this->mailerRepository = $mailerRepository;
        $this->view = $twig;
    }
}
