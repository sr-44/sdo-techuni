<?php

namespace App\Middlewares;

class IsBotOwnerMiddleware
{
    public function __invoke($bot, $next):void
    {
        if ($bot->userId() == config('bot.owner')) {
            $next($bot);
        }
    }
}