<?php

use Illuminate\Support\Arr;


if (!function_exists('config')) {
    function config(string $key = null): mixed
    {
        static $config;
        if (!$config) {
            $config = require('config.php');
        }
        return Arr::get($config, $key);
    }
}