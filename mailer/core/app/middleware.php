<?php
/**
 * Mailer | el.kulo v1.0.0 (https://github.com/elkulo/Mailer/)
 * Copyright 2020-2021 A.Sudo
 * Licensed under MIT (https://github.com/elkulo/Mailer/blob/main/LICENSE)
 */
declare(strict_types=1);

use function DI\Factory;
use Psr\Container\ContainerInterface;
use App\Application\Middleware\ReCAPTCHA;

return [
    'reCAPTCHA' => Factory(function (ContainerInterface $container) {
        return new ReCAPTCHA($container->get('ViewHandler'));
    }),
];