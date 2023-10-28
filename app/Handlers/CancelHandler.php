<?php

namespace App\Handlers;

use App\Commands\StartCommand;
use Exception;
use Psr\SimpleCache\InvalidArgumentException;
use SergiX44\Nutgram\Nutgram;

class CancelHandler
{
    /**
     * @throws InvalidArgumentException
     */
    public function __invoke(Nutgram $bot): void
    {
        try {
            if ($bot->callbackQuery() !== null) {
                $bot->answerCallbackQuery();
                $bot->callbackQuery()->message?->delete();
            }
            $bot->message()?->delete();
        } catch (Exception) {
        }
        (new StartCommand())($bot);
    }
}