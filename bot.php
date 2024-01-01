<?php


use App\Admin\AdminDashboardConversation;
use App\Commands\ChangeLanguageCommand;
use App\Commands\RegisterUserCommand;
use App\Conversations\StudentActionsConversation;
use App\Handlers\CancelHandler;
use App\Middlewares\IsBotOwnerMiddleware;
use App\Middlewares\SendLanguagesMiddleware;
use App\Middlewares\SetLanguageMiddleware;
use App\Nutgram\CustomMethods;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SergiX44\Nutgram\Configuration;
use SergiX44\Nutgram\Logger\ConsoleLogger;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\RunningMode\Webhook;
use SergiX44\Nutgram\Telegram\Properties\MessageType;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Psr16Cache;

require_once 'vendor/autoload.php';
if (config('debug') === true) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

require_once 'app/bootstrap.php';
try {
    Nutgram::mixin(new CustomMethods());
} catch (ReflectionException $e) {
}

$nutgramPsr6Cache = new FilesystemAdapter(directory: config('tmp_dir') . '/bot-cache');
$nutgramPsr16Cache = new Psr16Cache($nutgramPsr6Cache);
$config = new Configuration(botName: config('bot.username'), cache: $nutgramPsr16Cache, logger: ConsoleLogger::class);

$bot = new Nutgram(config('bot.token'), $config);

if (config('bot.webhook') === true) {
    $bot->setRunningMode(Webhook::class);
}
$bot->middleware(SendLanguagesMiddleware::class);
$bot->middleware(SetLanguageMiddleware::class);


$bot->onCommand('start', CancelHandler::class);
$bot->onCommand('lang', ChangeLanguageCommand::class);
$bot->onCommand('login', StudentActionsConversation::class);

$bot->onMessageType(MessageType::TEXT, function (Nutgram $bot) {
    if ($bot->message()->text === $bot->__('kbd.login')) {
        (new StudentActionsConversation())($bot);
    } elseif ($bot->message()->text === $bot->__('kbd.lang')) {
        (new ChangeLanguageCommand())($bot);
    } elseif ($bot->message()->text === $bot->__('kbd.about')) {
        $bot->sendMessage($bot->__('about'));
    }
});
$bot->onCallbackQueryData('lang_.*', RegisterUserCommand::class);

$bot->onCommand('admin', AdminDashboardConversation::class)->middleware(IsBotOwnerMiddleware::class);


try {
    $bot->run();
} catch (NotFoundExceptionInterface|ContainerExceptionInterface $e) {
}
