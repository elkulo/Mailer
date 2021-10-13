<?php
declare(strict_types=1);

namespace App\Application\Settings;

interface SettingsInterface
{
    /**
     * @param string $key
     * @return mixed
     */
    public function get(string $key = '');

    /**
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function set(string $key, $value): void;
}
