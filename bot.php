<?php


use App\Admin\AdminDashboardConversation;
use App\Commands\ChangeLanguageCommand;
use App\Commands\RegisterUserCommand;
use App\Conversations\FeedBackConversation;
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
if (config('debug')) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

require_once 'app/bootstrap.php';
try {
    Nutgram::mixin(new CustomMethods());
} catch (ReflectionException $e) {
}

$nutgramPsr16Cache = new Psr16Cache(new FilesystemAdapter(directory: config('dirs.bot_cache')));
$config = new Configuration(botName: config('bot.username'), cache: $nutgramPsr16Cache, logger: ConsoleLogger::class);

$bot = new Nutgram(config('bot.token'), $config);

if (config('bot.webhook')) {
    $bot->setRunningMode(Webhook::class);
}
$bot->middleware(SendLanguagesMiddleware::class);
$bot->middleware(SetLanguageMiddleware::class);


$bot->onCommand('start', CancelHandler::class);
$bot->onCommand('lang', ChangeLanguageCommand::class);
$bot->onCommand('login', StudentActionsConversation::class);

$bot->onMessageType(MessageType::TEXT, function (Nutgram $bot){
   switch ($bot->message()->text){
       case $bot->__('kbd.login'):
           (new StudentActionsConversation())($bot);
           break;
       case $bot->__('kbd.lang'):
           (new ChangeLanguageCommand())($bot);
           break;
       case $bot->__('kbd.about'):
           $bot->sendMessage($bot->__('about'));
           break;
       case $bot->__('kbd.feedback'):
           (new FeedBackConversation())($bot);
           break;
       default:
           (new CancelHandler())($bot);
   }
});

$bot->onCallbackQueryData('lang_{langCode}', RegisterUserCommand::class);

$bot->onCommand('admin', AdminDashboardConversation::class)->middleware(IsBotOwnerMiddleware::class);
$bot->fallback(CancelHandler::class);

try {
    $bot->run();
} catch (NotFoundExceptionInterface|ContainerExceptionInterface $e) {
}
