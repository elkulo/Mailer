<?php
/**
 * Mailer | el.kulo v1.0.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2021 A.Sudo
 * Licensed under MIT (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

namespace App\Application\Handlers\View;

use Twig\Loader\FilesystemLoader as TwigFileLoader;
use Twig\Loader\ArrayLoader as TwigArrayLoader;
use Twig\Environment as TwigEnvironment;

class ViewHandler implements ViewHandlerInterface
{

    /**
     * 設定
     *
     * @var array
     */
    private array $setting;

    /**
     * Twig ハンドラー
     *
     * @var object
     */
    private object $view;

    /**
     * Twig テンプレートディレクトリ
     *
     * 配列の若い順に優先されるオーバーライド
     *
     * @var array
     */
    private array $view_tamplete_dir;

    /**
     * Twig キャッシュディレクトリ
     *
     * @var string
     */
    private string $view_cache_dir;

    /**
     * コンストラクタ
     *
     * @param  array $config
     * @return void
     */
    public function __construct(array $config)
    {
        $this->setting = $config['setting'];

        $this->view_tamplete_dir = array(
            $config['app.path'] . '/../templates',
            $config['app.path'] . '/src/Infrastructure/templates',
        );

        $this->view_cache_dir = $config['app.path'] . '/var/cache/twig';

        // Twigの初期化
        $this->view = new TwigEnvironment(
            new TwigFileLoader($this->view_tamplete_dir),
            getenv('MAILER_DEBUG') ? array() : array('cache' => $this->view_cache_dir)
        );
    }

    /**
     * バリデーションエラー画面テンプレート
     *
     * @param  array $data
     * @return void
     */
    public function displayValidate(array $data): void
    {
        $this->view->display('/validate.twig', $data);
    }

    /**
     * 確認画面テンプレート
     *
     * @param  array $data
     * @return void
     */
    public function displayConfirm(array $data): void
    {
        $this->view->display('/confirm.twig', $data);
    }

    /**
     * 完了画面テンプレート
     *
     * @param  array $data
     * @return void
     */
    public function displayComplete(array $data): void
    {
        $this->view->display('/complete.twig', $data);
    }

    /**
     * 例外発生時の停止
     *
     * @param  string $error
     * @return void
     */
    public function displayExceptionExit(string $error): void
    {
        $this->view->display('/exception.twig', array('theExceptionMassage' => $error));
        exit;
    }

    /**
     * 管理者メールテンプレート
     *
     * @param  array $data
     * @return string
     */
    public function renderAdminMail(array $data): string
    {
        // 管理者宛送信メール.
        if (!empty($this->setting['TEMPLATE_MAIL_ADMIN'])) {
            return (new TwigEnvironment(
                new TwigArrayLoader(array(
                    'admin.mail.tpl' => $this->setting['TEMPLATE_MAIL_ADMIN']
                ))
            ))->render('admin.mail.tpl', $data);
        }
        return $this->view->render('/mail/admin.mail.twig', $data);
    }

    /**
     * ユーザーメールテンプレート
     *
     * @param  array $data
     * @return string
     */
    public function renderUserMail(array $data): string
    {
        // ユーザ宛送信メール.
        if (!empty($this->setting['TEMPLATE_MAIL_USER'])) {
            return (new TwigEnvironment(
                new TwigArrayLoader(array(
                    'user.mail.tpl' => $this->setting['TEMPLATE_MAIL_USER']
                ))
            ))->render('user.mail.tpl', $data);
        }
        return $this->view->render('/mail/user.mail.twig', $data);
    }
}
