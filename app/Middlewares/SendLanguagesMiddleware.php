<?php

namespace App\Middlewares;

use App\Models\User;
use App\Nutgram\Keyboards;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;

class SendLanguagesMiddleware
{
    public function __invoke(Nutgram $bot, $next): void
    {
        $user = User::where('user_id', $bot->userId())->first();
        if (!$user && $bot->callbackQuery() === null) {
            $bot->sendMessage(
                $bot->__('choose_lang'),
                parse_mode: ParseMode::HTML,
                reply_markup: Keyboards::selectLanguage()
            );
            return;
        }
        $next($bot);

    }
}