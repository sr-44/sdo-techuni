<?php

use Dcrypt\Aes;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\FileCookieJar;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Arr;
use Psr\Http\Message\ResponseInterface;


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


if (!function_exists('encryptData')) {
    function encryptData(string $data): string
    {
        return base64_encode(Aes::encrypt($data, config('encryption_key')));
    }
}

if (!function_exists('decryptData')) {
    /**
     * @throws Exception
     */
    function decryptData(string $data): string
    {
        return Aes::decrypt(base64_decode($data), config('encryption_key'));
    }
}

if (!function_exists('getRequest')) {
    /**
     * @throws GuzzleException
     */
    function getRequest(string $uri, string $cookiePath): ResponseInterface
    {
        $cookieJar = new FileCookieJar($cookiePath, true);
        return (new Client([
            'cookies' => $cookieJar,
            'timeout' => 10,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64; rv:109.0) Gecko/20100101 Firefox/119.0',
            ],
        ]))->request('GET', $uri);
    }
}


if (!function_exists('toBool')) {
    function toBool(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}