<?php
/**
 * Mailer | el.kulo v1.0.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2021 A.Sudo
 * Licensed under MIT (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

namespace App\Application\Actions;

use Psr\Container\ContainerInterface;
use App\Domain\Mailer;
use App\Application\Handlers\Validate\ValidateHandler;
use App\Application\Handlers\View\ViewHandler;
use App\Application\Handlers\Mail\MailHandlerInterface as MailHandler;
use App\Application\Handlers\DB\DBHandlerInterface as DBHandler;

abstract class Action
{
    /**
     * @var object
     */
    protected $logger;

    /**
     * ロジック
     *
     * @var object
     */
    protected $repository;

    /**
     * バリデート
     *
     * @var object
     */
    protected $validate;

    /**
     * Twig ハンドラー
     *
     * @var object
     */
    protected $view;

    /**
     * メールハンドラー
     *
     * @var object
     */
    protected $mail;

    /**
     * DBハンドラー
     *
     * @var object|null
     */
    protected $db;

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

        // ロジックをセット
        $this->repository = $container->get(Mailer::class);

        // バリデーションアクションをセット
        $this->validate = $container->get(ValidateHandler::class);

        // ビューアクションをセット
        $this->view = $container->get(ViewHandler::class);

        // メールハンドラーをセット
        $this->mail = $container->get(MailHandler::class);

        // データベースハンドラーをセット
        $this->db = $container->get(DBHandler::class);
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
