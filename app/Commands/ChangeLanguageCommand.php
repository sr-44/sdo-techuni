<?php

namespace App\Commands;

use App\Nutgram\Keyboards;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;

class ChangeLanguageCommand
{
    public function __invoke(Nutgram $bot): void
    {
        $bot->sendMessage(
            $bot->__('choose_lang'),
            parse_mode: ParseMode::HTML,
            reply_markup: Keyboards::selectLanguage());
    }
}
