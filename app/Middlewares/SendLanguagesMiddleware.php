<?php

namespace App\Middlewares;

use App\Models\User;
use App\Nutgram\Keyboards;
use Psr\SimpleCache\InvalidArgumentException;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;

class SendLanguagesMiddleware
{
    /**
     * @throws InvalidArgumentException
     */
    public function __invoke(Nutgram $bot, $next): void
    {
        if ($bot->callbackQuery() === null) {
            if ($bot->getUserData('lang_code') === null) {
                $user = User::where('user_id', $bot->userId())->first();
                if (!$user) {
                    $bot->sendMessage(
                        $bot->__('choose_lang'),
                        parse_mode: ParseMode::HTML,
                        reply_markup: Keyboards::selectLanguage()
                    );
                    return;
                }
            }
        }
        $next($bot);
    }
}