<?php
/**
 * Mailer | el.kulo v1.0.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2021 A.Sudo
 * Licensed under MIT (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

namespace App\Application\Actions;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use App\Application\Interfaces\ValidateHandlerInterface;
use App\Application\Interfaces\ViewHandlerInterface;
use App\Application\Interfaces\MailHandlerInterface;
use App\Application\Interfaces\DBHandlerInterface;

abstract class Action
{
    /**
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * バリデート
     *
     * @var object
     */
    protected ValidateHandlerInterface $validate;

    /**
     * Twig ハンドラー
     *
     * @var object
     */
    protected ViewHandlerInterface $view;

    /**
     * メールハンドラー
     *
     * @var object
     */
    protected MailHandlerInterface $mail;

    /**
     * DBハンドラー
     *
     * @var object
     */
    protected DBHandlerInterface $db;

    /**
     * コンストラクタ
     *
     * @param  ContainerInterface $container
     * @return void
     */
    public function __construct(ContainerInterface $container)
    {

        // ロガーをセット
        $this->logger = $container->get('logger');

        // バリデーションアクションをセット
        $this->validate = $container->get('ValidateHandler');

        // ビューアクションをセット
        $this->view = $container->get('ViewHandler');

        // メールハンドラーをセット
        $this->mail = $container->get('MailHandler');

        // データベースハンドラーをセット
        $this->db = $container->has('DBHandler')? $container->get('DBHandler'): null;
    }

    /**
     * @return void
     */
    abstract protected function action(): void;

    /**
     * @return bool
     * @throws Exception
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
