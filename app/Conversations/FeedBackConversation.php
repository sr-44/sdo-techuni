<?php

namespace App\Conversations;

use App\Commands\StartCommand;
use App\Handlers\CancelHandler;
use App\Nutgram\Keyboards;
use Psr\SimpleCache\InvalidArgumentException;
use SergiX44\Nutgram\Conversations\Conversation;
use SergiX44\Nutgram\Nutgram;

class FeedBackConversation extends Conversation
{
    /**
     * @throws InvalidArgumentException
     */
    public function start(Nutgram $bot): void
    {
        $bot->sendMessage($bot->__('send_feedback'), reply_markup: Keyboards::cancelButton($bot));
        $this->next('secondStep');
    }

    /**
     * @throws InvalidArgumentException
     */
    public function secondStep(Nutgram $bot): void
    {
        if (!$bot->message()->text) {
            $this->start($bot);
            return;
        }
        if ($bot->message()->text === $bot->__('kbd.cancel')) {
            $this->end();
            (new CancelHandler())($bot);
            return;
        }
        $bot->forwardMessage(config('bot.owner'), $bot->chatId(), $bot->message()->message_id);
        $bot->sendMessage($bot->__('feedback_sent'));
        $this->end();
        (new StartCommand())($bot);
    }
}