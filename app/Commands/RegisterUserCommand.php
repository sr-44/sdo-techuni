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
    public function __invoke(Nutgram $bot): void
    {
        $lang_code = explode('lang_', $bot->callbackQuery()->data, limit: 2)[1];
        if (!in_array($lang_code, ['en', 'ru', 'tg'])) {
            $bot->answerCallbackQuery(text: $bot->__('Language not available'));
            return;
        }
        $bot->answerCallbackQuery();
        $user = User::where('user_id', $bot->userId())->first();
        if (!$user) {
            User::create([
                'user_id' => $bot->userId(),
                'username' => $bot->user()->username,
                'language' => $lang_code,
            ]);
            $bot->set('lang_code', $lang_code);
            $bot->sendMessage($bot->__('greeting_text', [':name' => $bot->user()->first_name]));
        } else {
            $user->language = $lang_code;
            $user->save();
//            $bot->setUserData('lang_code', $lang_code);
        }
        $bot->set('lang_code', $lang_code);
        (new StartCommand())($bot);
        $bot->message()?->delete();

    }

}