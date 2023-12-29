<?php

namespace App\Conversations;

use App\Commands\StartCommand;
use App\ImageFromHtml;
use App\Models\User;
use App\Nutgram\Keyboards;
use App\ParseHtml;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\FileCookieJar;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Str;
use Psr\SimpleCache\InvalidArgumentException;
use SergiX44\Nutgram\Conversations\Conversation;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;
use SergiX44\Nutgram\Telegram\Types\Internal\InputFile;
use Throwable;

class StudentActionsConversation extends Conversation
{
    private string $uri = 'http://sdo.techuni.tj';

    /**
     * @throws InvalidArgumentException|GuzzleException
     */
    public function start(Nutgram $bot): void
    {
        try {
            $bot->message()->delete();
        } catch (Throwable) {
        }

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
        try {
            $bot->message()->delete();
        } catch (Throwable) {
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
        try {
            $bot->message()->delete();
        } catch (Throwable) {
        }

        $bot->setUserData('password', encryptData($bot->message()->text));
        $this->sendRequest($bot);
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
        } elseif ($bot->message()->text === $bot->__('kbd.show.sessions')) {
            $this->showExams($bot);
        } else {
            $bot->sendMessage($bot->__('choose_action'), parse_mode: ParseMode::HTML, reply_markup: Keyboards::actionsKeyboards($bot));
        }
    }


    /**
     * @throws GuzzleException
     * @throws InvalidArgumentException
     */
    protected function showRating(Nutgram $bot): void
    {
        try {
            $bot->message()->delete();
        } catch (Throwable) {
        }

        $wait = $bot->sendMessage($bot->__('please_wait'), reply_markup: Keyboards::removeKeyboards());
        $cookieFile = 'tmp/' . $bot->userId();
        $response = getRequest($this->uri . '/student/?option=study&action=list', $cookieFile);
        $body = $response->getBody();
        $pars = new ParseHtml($body);
        $threadText = [
            $bot->__('rating_table.subject_name'),
            $bot->__('rating_table.credits'),
            $bot->__('rating_table.rating_1'),
            $bot->__('rating_table.rating_2'),
            $bot->__('rating_table.exam'),
            $bot->__('rating_table.sum'),
            $bot->__('rating_table.mark'),
            $bot->__('rating_table.teacher'),
        ];
        $html = $pars->parseSubjectsTable()->arrayToHtmlTable($threadText)->getHtmlTable();
        echo $html;
        $imagePath = 'tmp/screens/' . $bot->userId() . '_' . Str::random() . '.jpg';
        ImageFromHtml::generate($html, $imagePath);

        $bot->sendPhoto(InputFile::make(fopen($imagePath, 'rb+')), parse_mode: ParseMode::HTML, reply_markup: Keyboards::actionsKeyboards($bot));
        $wait->delete();
    }


    /**
     * @throws GuzzleException
     * @throws Exception
     */
    protected function showInfo(Nutgram $bot): void
    {
        try {
            $bot->message()->delete();
        } catch (Throwable) {
        }

        $wait = $bot->sendMessage($bot->__('please_wait'), reply_markup: Keyboards::removeKeyboards());
        $cookieFile = 'tmp/' . $bot->userId();
        $response = getRequest($this->uri . '/student/?option=study&action=myinfo', $cookieFile);

        $body = $response->getBody()->__toString();
        $parsed = new ParseHtml($body);
        $studentId = $parsed->parseStudentId();
        $client = new Client();
        $response = $client->post($this->uri . '/modules/students/students_ajax.php?option=getstudentinfo', [
            'form_params' => [
                'id_student' => $studentId,
                'my_url' => $this->uri,
            ],
        ]);
        $parsed = new ParseHtml($response->getBody());
        $studentInfo = $parsed->parseStudentInfo();
        $login = decryptData($bot->getUserData('login'));
        if ($studentInfo['image']) {
            $headers = get_headers($this->uri . $studentInfo['image']);
            if (str_contains($headers[0], '200')) {
                $photo = fopen($this->uri . $studentInfo['image'], 'r');
            } else {
                $photo = fopen('http://sdo.techuni.tj/userfiles/man.png', 'r');
            }

            $wait->delete();
            $caption = $bot->__('student_info', [
                ':login' => $login,
                ':name' => $studentInfo['name'],
            ]);
            $bot->sendPhoto(InputFile::make($photo), caption: $caption, parse_mode: ParseMode::HTML, reply_markup: Keyboards::actionsKeyboards($bot));
        }

    }

    /**
     * @throws GuzzleException
     * @throws InvalidArgumentException
     */
    public function showExams(Nutgram $bot): void
    {
        try {
            $bot->message()->delete();
        } catch (Throwable) {
        }

        $wait = $bot->sendMessage($bot->__('please_wait'), reply_markup: Keyboards::removeKeyboards());
        $cookieFile = 'tmp/' . $bot->userId();
        $response = getRequest($this->uri . '/student/?option=sessions&action=sessions_list', $cookieFile);
        $body = $response->getBody();
        $pars = new ParseHtml($body);
        $threadText = [
            $bot->__('exam_table.subject_name'),
            $bot->__('exam_table.exam_type'),
            $bot->__('exam_table.date'),
            $bot->__('exam_table.teachers'),
            $bot->__('exam_table.question_qty'),
        ];
        $html = $pars->parseExamsTable()->arrayToHtmlTable($threadText)->getHtmlTable();
        $imagePath = 'tmp/screens/' . $bot->userId() . '_' . Str::random() . '.jpg';
        ImageFromHtml::generate($html, $imagePath);
        $bot->sendPhoto(InputFile::make(fopen($imagePath, 'rb+')), parse_mode: ParseMode::HTML, reply_markup: Keyboards::actionsKeyboards($bot));
        $wait->delete();
    }

    /**
     * @throws GuzzleException|InvalidArgumentException
     */
    protected function logOut(Nutgram $bot): void
    {
        $user = User::where('user_id', $bot->userId())->first();
        $user->encrypted_login = '';
        $user->encrypted_password = '';
        $user->save();
        unlink('tmp/' . $bot->userId());
        (new StartCommand())($bot);
//        $this->start($bot);
    }


    /**
     * @throws GuzzleException
     * @throws InvalidArgumentException
     * @throws Exception
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
            if (str_contains($response->getBody(), 'Руйхати дарсҳо')) {
                $bot->setUserData('sessions', true);
            }
            $this->actionsMenu($bot);
            return;
        }
        $bot->sendMessage($bot->__('wrong_datas'));
        //(new StartCommand())($bot);
        $this->firstStep($bot);
    }
}