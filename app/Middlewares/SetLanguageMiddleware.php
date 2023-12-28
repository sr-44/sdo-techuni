<?php

namespace App\Middlewares;

use App\Models\User;
use Psr\SimpleCache\InvalidArgumentException;
use SergiX44\Nutgram\Nutgram;

class SetLanguageMiddleware
{
    /**
     * @throws InvalidArgumentException
     */
    public function __invoke(Nutgram $bot, $next): void
    {
        if ($bot->getUserData('lang_code') === null) {
            $user = User::where('user_id', $bot->userId())->first();
            if ($user) {
                $bot->setUserData('lang_code', $user->language);
            }
        }
        $next($bot);
    }
}