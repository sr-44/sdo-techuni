<?php


use App\Models\User;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SergiX44\Nutgram\Configuration;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardButton;
use SergiX44\Nutgram\Telegram\Types\Keyboard\InlineKeyboardMarkup;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Psr16Cache;

require_once 'vendor/autoload.php';
require_once 'app/bootstrap.php';


$method = $argv[1];
$postId = (int)$argv[2];
$fromChatId = (int)$argv[3];

$nutgramPsr6Cache = new FilesystemAdapter(directory: config('tmp_dir') . '/bot-cache');
$nutgramPsr16Cache = new Psr16Cache($nutgramPsr6Cache);
$config = new Configuration(botName: config('bot.username'), cache: $nutgramPsr16Cache);

$bot = new Nutgram(config('bot.token'), $config);

$chats = User::all()->pluck('user_id')->toArray();
$end = end($chats);
$status = [
    'success' => 0,
    'failed' => 0,
];

try {
    $bot->getBulkMessenger()
        ->setChats($chats)
        ->using(function (Nutgram $bot, int $chatId) use ($method, $postId, $fromChatId, $end, &$status) {
            try {
                if ($method === 'copy') {
                    if ($bot->getUserData('keyboards', $fromChatId) !== null) {
                        $keyboardArr = $bot->getUserData('keyboards', $fromChatId);
                    }

                    $keyboards = InlineKeyboardMarkup::make();
                    if (isset($keyboardArr)) {
                        foreach ($keyboardArr as $keyboard) {
                            $keyboards->addRow(
                                InlineKeyboardButton::make($keyboard[0]->text, $keyboard[0]->url)
                            );
                        }
                    }
                    $bot->copyMessage($chatId, $fromChatId, $postId, reply_markup: $keyboards);

                } elseif ($method === 'forward') {
                    $bot->forwardMessage($chatId, $fromChatId, $postId);
                }
                $status['success']++;

            } catch (Throwable $th) {
                $status['failed']++;
                if (str_contains($th->getMessage(), 'bot was blocked by the user') || str_contains($th->getMessage(), 'chat not found')) {
                    User::where('user_id', $chatId)->delete();
                }
            }
            if ($chatId === $end) {
                $bot->sendMessage(
                    "Sending finished. Success: {$status['success']}  Failed: {$status['failed']}", chat_id: config('bot.owner'));
                if ($bot->getUserData('keyboards', $fromChatId)) {
                    $bot->deleteUserData('keyboards', $fromChatId);
                }
            }
        }
        )->startSync();
} catch (NotFoundExceptionInterface|ContainerExceptionInterface $e) {
}
