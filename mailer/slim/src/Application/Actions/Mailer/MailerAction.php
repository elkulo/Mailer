<?php
declare(strict_types=1);

namespace App\Application\Actions\Mailer;

use App\Application\Actions\Action;
use App\Domain\Mailer\MailerRepository;
use Psr\Log\LoggerInterface;

abstract class MailerAction extends Action
{
    /**
     * @var MailerRepository
     */
    protected $mailerRepository;

    /**
     * @param LoggerInterface $logger
     * @param MailerRepository $userRepository
     */
    public function __construct(
        LoggerInterface $logger,
        MailerRepository $mailerRepository
    ) {
        parent::__construct($logger);
        $this->mailerRepository = $mailerRepository;
    }
}
