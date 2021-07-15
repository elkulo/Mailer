<?php
/**
 * Mailer | el.kulo v1.0.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2021 A.Sudo
 * Licensed under MIT (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

namespace App\Application\Actions;

abstract class Action
{
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
