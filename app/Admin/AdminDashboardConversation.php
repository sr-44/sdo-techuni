<?php

namespace App\Admin;

use App\Commands\StartCommand;
use App\Models\User;
use App\Nutgram\Keyboards;
use Psr\SimpleCache\InvalidArgumentException;
use SergiX44\Nutgram\Conversations\Conversation;
use SergiX44\Nutgram\Nutgram;

class AdminDashboardConversation extends Conversation
{

    /**
     * @throws InvalidArgumentException
     */
    public function start(Nutgram $bot): void
    {
        $usersCount = User::count();
        $loginedUsersCount = User::whereNotNull('encrypted_login')->count();
        $text = "Добро пожаловать в административный панель!\nВсего пользователей: $usersCount\nЗалогиненных пользователей: $loginedUsersCount\n\n\nВыберите действие:";
        $bot->sendMessage($text, reply_markup: Keyboards::adminDashboard());
        $this->next('second');
    }

    /**
     * @throws InvalidArgumentException
     */
    public function second(Nutgram $bot): void
    {
        if ($bot->callbackQuery()) {
            $bot->answerCallbackQuery();
        }
        if ($bot->callbackQuery()->data === 'cancel') {
            $this->cancel($bot);
            return;
        }
        if ($bot->callbackQuery()->data === 'bulk_message') {
            $bot->message()->delete();
            $bot->sendMessage('Отправьте сообщение для рассылки:');
            $this->next('setPost');
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    public function setPost(Nutgram $bot)
    {
        if (!$bot->message()) {
            return;
        }
        $bot->setUserData('post', $bot->message()->message_id);
        if ($bot->message()->reply_markup !== null) {
            $bot->setUserData('keyboards', $bot->message()->reply_markup->inline_keyboard);
        }

        $bot->sendMessage('Выберите метод отправки сообщения:', reply_markup: Keyboards::bulkMessage());
        $this->next('send');
    }

    /**
     * @throws InvalidArgumentException
     */
    public function send(Nutgram $bot): void
    {
        if ($bot->callbackQuery() === null) {
            return;
        }
        if ($bot->callbackQuery()->data === 'cancel') {
            $this->cancel($bot);
            return;
        }
        $bot->answerCallbackQuery();
        $bot->message()->delete();
        $method = $bot->callbackQuery()->data;
        $postId = $bot->getUserData('post');

        $php = config('php_path');
        $script = config('bulk_script');
        $count = User::count();
        exec("$php $script $method $postId {$bot->chatId()} > /dev/null &");
        $bot->sendMessage('Start sending to ' . $count . ' chats');
        $this->end();

    }


    /**
     * @throws InvalidArgumentException
     */
    private function cancel(Nutgram $bot): void
    {
        $bot->message()->delete();
        $this->end();
        (new StartCommand())($bot);
    }
}