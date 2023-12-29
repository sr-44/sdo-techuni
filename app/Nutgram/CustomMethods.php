<?php

namespace App\Nutgram;

use Closure;
use Illuminate\Support\Arr;
use SergiX44\Nutgram\Nutgram;

class CustomMethods
{
    public function __(): Closure
    {
        return function (string $key = null, array $values = []) {
            /** @var Nutgram $this */
            $langCode = $this->getUserData('lang_code');
            if (($langCode !== null) && file_exists("lang/$langCode.php")) {
                $texts = require "lang/$langCode.php";
            } else {
                $texts = require 'lang/tg.php';
            }
            $text = Arr::get($texts, $key);
            return is_string($text) ? strtr($text, $values) : $key;
        };
    }
}