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
    public function __invoke(Nutgram $bot, $lang_code): void
    {
        $bot->answerCallbackQuery();
        $user = User::where('user_id', $bot->userId())->first();
        if (!$user) {
            User::create([
                'user_id' => $bot->userId(),
                'username' => $bot->user()->username,
                'language' => $lang_code,
            ]);
            $bot->setUserData('lang_code', $lang_code);
            $bot->sendMessage($bot->__('greeting_text', [':name' => $bot->user()->first_name]));
        } else {
            $user->language = $lang_code;
            $user->save();
            $bot->setUserData('lang_code', $lang_code);
        }
        (new StartCommand())($bot);
        $bot->message()?->delete();

    }

}