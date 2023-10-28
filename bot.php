<?php


use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SergiX44\Nutgram\Configuration;
use SergiX44\Nutgram\Logger\ConsoleLogger;
use SergiX44\Nutgram\Nutgram;

require_once 'vendor/autoload.php';

if (config('debug') === 'true') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}


$config = new Configuration(botName: config('bot.username'), logger: ConsoleLogger::class);
$bot = new Nutgram(config('bot.token'), $config);

$bot->onCommand('start', function (Nutgram $bot) {
    $bot->sendMessage('hello world');
});




try {
    $bot->run();
} catch (NotFoundExceptionInterface|ContainerExceptionInterface $e) {
}