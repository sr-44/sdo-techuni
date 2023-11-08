<?php


use App\Commands\ChangeLanguageCommand;
use App\Commands\RegisterUserCommand;
use App\Conversations\StudentActionsConversation;
use App\Handlers\CancelHandler;
use App\Middlewares\SendLanguagesMiddleware;
use App\Middlewares\SetLanguageMiddleware;
use App\Nutgram\CustomMethods;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SergiX44\Nutgram\Configuration;
use SergiX44\Nutgram\Logger\ConsoleLogger;
use SergiX44\Nutgram\Nutgram;

require_once 'vendor/autoload.php';
require_once 'app/helpers/create_tables.php';
if (config('debug') === 'true') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

try {
    Nutgram::mixin(new CustomMethods());
} catch (ReflectionException $e) {
}

$config = new Configuration(botName: config('bot.username'), logger: ConsoleLogger::class);
$bot = new Nutgram(config('bot.token'), $config);

$bot->middleware(SendLanguagesMiddleware::class);
$bot->middleware(SetLanguageMiddleware::class);


$bot->onCommand('start', CancelHandler::class);
$bot->onCommand('lang', ChangeLanguageCommand::class);
$bot->onCommand('login', StudentActionsConversation::class);
$bot->onCallbackQueryData('lang_{lang_code}', RegisterUserCommand::class);




try {
    $bot->run();
} catch (NotFoundExceptionInterface | ContainerExceptionInterface $e) {
}
