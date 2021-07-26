<?php
/**
 * Mailer | el.kulo v1.0.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2021 A.Sudo
 * Licensed under MIT (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

namespace App\Application\Actions;

use Psr\Container\ContainerInterface;

abstract class Action
{
    /**
     * @var object
     */
    protected $logger;

    /**
     * コンストラクタ
     *
     * @param  ContainerInterface
     * @return void
     */
    public function __construct(ContainerInterface $container)
    {
        // ロガーをセット
        $this->logger = $container->get('logger');
    }

    /**
     * @return void
     */
    abstract protected function action(): void;

    /**
     * @return bool
     * @throws Exception
     */
    public function __invoke(): bool
    {
        try {
            $this->action();
            return true;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
            return false;
        }
    }
}
