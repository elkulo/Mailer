<?php
declare(strict_types=1);

namespace App\Application\Router;

interface RouterInterface
{
    /**
     * @param string $urlName
     * @return void
     */
    public function set(string $urlName): void;

    /**
     * @param string $key
     * @return mixed
     */
    public function getUrl(string $key = '');
}
