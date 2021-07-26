<?php
/**
 * Mailer | el.kulo v1.0.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2021 A.Sudo
 * Licensed under MIT (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

namespace App\Application\Actions;

use App\Application\Actions\Action;
use App\Domain\MailerRepository;
use Psr\Container\ContainerInterface;

/**
 * MailerAction
 */
class MailerAction extends Action
{

    /**
     * @var MailerRepository
     */
    protected $mailerRepository;

    /**
     * コンストラクタ
     *
     * @param  ContainerInterface
     * @return void
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->mailerRepository = $container->get(MailerRepository::class);
    }

    /**
     * 実行
     *
     * @return void
     */
    public function action(): void
    {
        $this->mailerRepository->submit();
        $this->logger->info('Send Mail.');
    }
}
