<?php

namespace App\Commands;

use App\Models\User;
use Psr\SimpleCache\InvalidArgumentException;
use SergiX44\Nutgram\Nutgram;

class RegisterUserCommand
{

    /**
     * @throws InvalidArgumentException
     */
    public function __invoke(Nutgram $bot, $langCode): void
    {
        if (!in_array($langCode, ['en', 'ru', 'tg'])) {
            $bot->answerCallbackQuery(text: $bot->__('Language not available'));
            return;
        }
        $bot->answerCallbackQuery();
        $user = User::where('user_id', $bot->userId())->first();
        if (!$user) {
            User::create([
                'user_id' => $bot->userId(),
                'username' => $bot->user()->username,
                'language' => $langCode,
            ]);
            $bot->setUserData('lang_code', $langCode);
            $bot->sendMessage($bot->__('greeting_text', [':name' => $bot->user()->first_name]));
        } else {
            $user->language = $langCode;
            $user->save();
//            $bot->setUserData('lang_code', $lang_code);
        }
        $bot->setUserData('lang_code', $langCode);
        (new StartCommand())($bot);
        $bot->message()?->delete();

    }

}