<?php

namespace App\Conversations;

use App\Commands\StartCommand;
use App\Models\User;
use App\Nutgram\Keyboards;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\FileCookieJar;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Str;
use Knp\Snappy\Image;
use Psr\SimpleCache\InvalidArgumentException;
use SergiX44\Nutgram\Conversations\Conversation;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;
use SergiX44\Nutgram\Telegram\Types\Internal\InputFile;

class StudentActionsConversation extends Conversation
{
    private string $uri = 'http://sdo.techuni.tj';

    /**
     * @throws InvalidArgumentException|GuzzleException
     */
    public function start(Nutgram $bot): void
    {
        $user = User::where('user_id', $bot->userId())->first();
        if ($user->encrypted_login && $user->encrypted_password) {
            $bot->setUserData('login', $user->encrypted_login);
            $bot->setUserData('password', $user->encrypted_password);
            $this->sendRequest($bot);
            return;
        }
        $this->firstStep($bot);

    }


    /**
     * @throws InvalidArgumentException
     */
    public function firstStep(Nutgram $bot): void
    {
        $bot->sendMessage($bot->__('send_login'));
        $this->next('secondStep');
    }


    /**
     * @throws InvalidArgumentException|GuzzleException
     */
    public function secondStep(Nutgram $bot): void
    {
        if (!$bot->message()->text) {
            $this->start($bot);
            return;
        }
        $bot->setUserData('login', encryptData($bot->message()->text));
        $bot->sendMessage($bot->__('send_password'));
        $this->next('setPassword');
    }

    /**
     * @throws InvalidArgumentException|GuzzleException
     */
    public function setPassword(Nutgram $bot): void
    {
        if (!$bot->message()->text) {
            $this->start($bot);
            return;
        }

        $bot->setUserData('password', encryptData($bot->message()->text));
        $this->sendRequest($bot);
    }


    /**
     * @throws GuzzleException|InvalidArgumentException|Exception
     */
    private function sendRequest(Nutgram $bot): void
    {
        $wait = $bot->sendMessage($bot->__('please_wait'))->message_id;
        $data = [
            'login' => decryptData($bot->getUserData('login')),
            'password' => decryptData($bot->getUserData('password')),
        ];

        $cookieFile = 'tmp/' . $bot->userId();
        $cookieJar = new FileCookieJar($cookieFile, true);
        $client = new Client([
            'cookies' => $cookieJar,
        ]);

        $response = $client->request('POST', $this->uri . '/?option=auth', [
            'form_params' => $data,
            'allow_redirects' => true,
        ]);
        $bot->deleteMessage($bot->chatId(), $wait);
        if (str_contains($response->getBody(), 'Дарсҳои ман')) {
            $user = User::where('user_id', $bot->userId())->first();
            $user->encrypted_login = $bot->getUserData('login');
            $user->encrypted_password = $bot->getUserData('password');
            $user->save();
            $this->actionsMenu($bot);
            return;
        }
        $bot->sendMessage($bot->__('wrong_datas'));
        //(new StartCommand())($bot);
        $this->firstStep($bot);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function actionsMenu(Nutgram $bot): void
    {
        $bot->sendMessage($bot->__('choose_action'), parse_mode: ParseMode::HTML, reply_markup: Keyboards::actionsKeyboards($bot));
        $this->next('actions');
    }


    /**
     * @throws GuzzleException|InvalidArgumentException
     */
    public function actions(Nutgram $bot): void
    {
        if ($bot->message()->text === $bot->__('kbd.show.rating')) {
            $this->showRating($bot);
        } elseif ($bot->message()->text === $bot->__('kbd.show.info')) {
            $this->showInfo($bot);
        } elseif ($bot->message()->text === $bot->__('kbd.logout')) {
            $this->logOut($bot);
        }
    }


    /**
     * @throws GuzzleException
     */
    protected function showRating(Nutgram $bot): void
    {
        $wait = $bot->sendMessage($bot->__('please_wait'))->message_id;
        $cookieFile = 'tmp/' . $bot->userId();
        $response = getRequest($this->uri . '/student/?option=study&action=list', $cookieFile);
        $snappy = new Image('/bin/wkhtmltoimage');
        $imagePath = 'tmp/screens/' . $bot->userId() . '_' . Str::random() . '.jpg';
        $snappy->generateFromHtml($response->getBody(), $imagePath);

        $bot->sendPhoto(InputFile::make(fopen($imagePath, 'rb+')));
        $bot->deleteMessage($bot->chatId(), $wait);
        unlink($imagePath);
    }


    /**
     * @throws GuzzleException
     */
    protected function showInfo(Nutgram $bot): void
    {
        $wait = $bot->sendMessage($bot->__('please_wait'))->message_id;
        $cookieFile = 'tmp/' . $bot->userId();
        $response = getRequest($this->uri . '/student/?option=study&action=myinfo', $cookieFile);
        $imagePath = 'tmp/screens/' . $bot->userId() . '_' . Str::random() . '.jpg';
        $lines = explode("\n", $response->getBody());
        $studentId = 0;
        foreach ($lines as $line) {
            if (stripos($line, 'var id_student')) {
                preg_match('/\d+/', $line, $matches);
                $studentId = $matches[0];
            }
        }

        $client = new Client();
        $response = $client->request('POST', $this->uri . '/modules/students/students_ajax.php?option=getstudentinfo', [
            'form_params' => [
                'id_student' => $studentId,
                'my_url' => $this->uri,
            ],
        ]);

        $snappy = new Image('/bin/wkhtmltoimage', ['encoding' => 'utf-8']);
        $snappy->generateFromHtml(self::parsInfoPage($response->getBody()), $imagePath);
        $bot->sendPhoto(InputFile::make(fopen($imagePath, 'rb+')));
        $bot->deleteMessage($bot->chatId(), $wait);
        unlink($imagePath);
    }

    /**
     * @throws GuzzleException|InvalidArgumentException
     */
    protected function logOut(Nutgram $bot): void
    {
        $user = User::where('user_id', $bot->userId())->get()->first();
        $user->encrypted_login = '';
        $user->encrypted_password = '';
        $user->save();
        unlink('tmp/' . $bot->userId());
        (new StartCommand())($bot);
        $this->start($bot);
    }


    private static function parsInfoPage(string $html): string
    {
        $lines = explode("\n", $html);
        foreach ($lines as $i => $value) {
            if ($i > 1 && $i < 15) {
                unset($lines[$i]);
            }
        }
        return implode("\n", $lines);
    }

    private static function parsRaitingPage(string $html)
    {
        $lines = explode("\n", $html);
        foreach ($lines as $i => $value) {
            if ($i > 1 && $i < 15) {
                unset($lines[$i]);
            }
        }
        return implode("\n", $lines);
    }
}