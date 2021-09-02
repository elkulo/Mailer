<?php
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
