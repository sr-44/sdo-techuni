<?php
namespace App\Commands;


use App\Nutgram\Keyboards;
use Psr\SimpleCache\InvalidArgumentException;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;

class StartCommand
{
    /**
     * @throws InvalidArgumentException
     */
    public function __invoke(Nutgram $bot): void
    {
        $bot->sendMessage($bot->__('main_menu'), parse_mode: ParseMode::HTML, reply_markup: Keyboards::removeKeyboards());
        $bot->endConversation();
        $bot->clear();
    }

}