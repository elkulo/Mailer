<?php
/**
 * Mailer | el.kulo v1.0.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2021 A.Sudo
 * Licensed under MIT (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

namespace App\Application\Actions;

//use Psr\Http\Message\ResponseInterface as Response;
//use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

abstract class Action
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Response
     */
    protected $response;

    /**
     * @var array
     */
    protected $args;

    /**
     * @param LoggerInterface $logger
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return bool
     */
    abstract protected function action(): bool;

    /**
     * エスケープ
     *
     * @param  mixed $content
     * @param  string $encode
     * @return mixed
     */
    protected function kses($content, string $encode = 'UTF-8')
    {
        $sanitized = array();
        if (is_array($content)) {
            foreach ($content as $key => $value) {
                $sanitized[$key] = trim(htmlspecialchars($value, ENT_QUOTES, $encode));
            }
        } else {
            return trim(htmlspecialchars($content, ENT_QUOTES, $encode));
        }
        return $sanitized;
    }
}
