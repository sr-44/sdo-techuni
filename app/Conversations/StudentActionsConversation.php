<?php

namespace App\Conversations;

use App\Commands\StartCommand;
use App\ImageFromHtml;
use App\Models\User;
use App\Nutgram\Keyboards;
use App\ParseHtml;
use App\Request;
use Exception;
use GuzzleHttp\Client;
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
    public static string $uri = 'http://sdo.techuni.tj';

    /**
     * @throws InvalidArgumentException|GuzzleException
     */
    public function start(Nutgram $bot): void
    {
        $this->delMessage();

        $user = User::where('user_id', $bot->userId())->first();
        if ($user->encrypted_login && $user->encrypted_password) {
            $bot->setUserData('login', $user->encrypted_login);
            $bot->setUserData('password', $user->encrypted_password);
            $this->sendRequest();
            return;
        }
        $this->firstStep($bot);

    }

    /**
     * @throws InvalidArgumentException
     */
    public function firstStep(Nutgram $bot): void
    {
        $bot->sendMessage($bot->__('send_login'), reply_markup: Keyboards::removeKeyboards());
        $this->next('secondStep');
    }


    /**
     * @throws InvalidArgumentException|GuzzleException
     * @throws Exception
     */
    public function secondStep(Nutgram $bot): void
    {
        if (!$bot->message()->text) {
            $this->start($bot);
            return;
        }

        $this->delMessage();
        $bot->setUserData('login', encryptData($bot->message()->text));
        $bot->sendMessage($bot->__('send_password'));
        $this->next('setPassword');
    }

    /**
     * @throws InvalidArgumentException|GuzzleException
     * @throws Exception
     */
    public function setPassword(Nutgram $bot): void
    {
        if (!$bot->message()->text) {
            $this->start($bot);
            return;
        }
        $this->delMessage();

        $bot->setUserData('password', encryptData($bot->message()->text));
        $this->sendRequest();
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
        switch ($bot->message()->text) {
            case $bot->__('kbd.show.rating'):
                $this->showRating();
                break;
            case $bot->__('kbd.show.info'):
                $this->showInfo();
                break;
            case $bot->__('kbd.logout'):
                $this->logOut();
                break;
            case $bot->__('kbd.show.sessions'):
                $this->showExams();
                break;
            default:
                $bot->sendMessage($bot->__('choose_action'), parse_mode: ParseMode::HTML, reply_markup: Keyboards::actionsKeyboards($bot));
        }
    }


    /**
     * @throws GuzzleException
     * @throws InvalidArgumentException
     */
    private function showRating(): void
    {
        $bot = $this->bot;
        $this->delMessage();
        $wait = $bot->sendMessage($bot->__('please_wait'), reply_markup: Keyboards::removeKeyboards());
        $request = new Request($bot->userId());
        $body = $request->getRatingTable();
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
        $imagePath = config('dirs.screens') . '/' . $bot->userId() . '_' . Str::random() . '.jpg';
        ImageFromHtml::generate($html, $imagePath);

        $bot->sendPhoto(InputFile::make(fopen($imagePath, 'rb+')), parse_mode: ParseMode::HTML, reply_markup: Keyboards::actionsKeyboards($bot));
        unlink($imagePath);
        $wait?->delete();
    }


    /**
     * @throws GuzzleException
     * @throws Exception
     */
    private function showInfo(): void
    {
        $bot = $this->bot;
        $this->delMessage();
        $wait = $bot->sendMessage($bot->__('please_wait'), reply_markup: Keyboards::removeKeyboards());
        $cookieFile = config('dirs.cookies') . '/' . $bot->userId();
        $response = getRequest(self::$uri . '/student/?option=study&action=myinfo', $cookieFile);

        $body = $response->getBody()->__toString();
        $parsed = new ParseHtml($body);
        $studentId = $parsed->parseStudentId();
        $client = new Client();
        $response = $client->post(self::$uri . '/modules/students/students_ajax.php?option=getstudentinfo', [
            'form_params' => [
                'id_student' => $studentId,
                'my_url' => self::$uri,
            ],
        ]);
        $parsed = new ParseHtml($response->getBody());
        $studentInfo = $parsed->parseStudentInfo();
        $login = decryptData($bot->getUserData('login'));
        if ($studentInfo['image']) {
            $headers = get_headers($studentInfo['image']);
            if (str_contains($headers[0], '200')) {
                $photo = fopen($studentInfo['image'], 'rb');
            } else {
                $photo = fopen('http://sdo.techuni.tj/userfiles/man.png', 'rb');
            }

            $caption = $bot->__('student_info', [
                ':login' => $login,
                ':name' => $studentInfo['name'],
            ]);
            $bot->sendPhoto(InputFile::make($photo), caption: $caption, parse_mode: ParseMode::HTML, reply_markup: Keyboards::actionsKeyboards($bot));
            $wait?->delete();
        }

    }

    /**
     * @throws GuzzleException
     * @throws InvalidArgumentException
     */
    private function showExams(): void
    {
        $bot = $this->bot;
        $this->delMessage();
        $wait = $bot->sendMessage($bot->__('please_wait'), reply_markup: Keyboards::removeKeyboards());
        $cookieFile = config('dirs.cookies') . '/' . $bot->userId();
        $response = getRequest(self::$uri . '/student/?option=sessions&action=sessions_list', $cookieFile);
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
        $imagePath = config('dirs.screens') . '/' . $bot->userId() . '_' . Str::random() . '.jpg';
        ImageFromHtml::generate($html, $imagePath);
        $bot->sendPhoto(InputFile::make(fopen($imagePath, 'rb+')), parse_mode: ParseMode::HTML, reply_markup: Keyboards::actionsKeyboards($bot));
        $wait?->delete();
        unlink($imagePath);
    }

    /**
     * @throws InvalidArgumentException
     */
    private function logOut(): void
    {
        $bot = $this->bot;
        $user = User::where('user_id', $bot->userId())->first();
        $user->encrypted_login = null;
        $user->encrypted_password = null;
        $user->save();
        unlink(config('dirs.cookies') . '/' . $bot->userId());
        (new StartCommand())($bot);
//        $this->start($bot);
    }


    /**
     * @throws GuzzleException
     * @throws InvalidArgumentException
     * @throws Exception
     */
    private function sendRequest(): void
    {
        $bot = $this->bot;
        $wait = $bot->sendMessage($bot->__('please_wait'));

        $login = decryptData($bot->getUserData('login'));
        $password = decryptData($bot->getUserData('password'));
        $request = new Request($bot->userId());
        $wait?->delete();
        if ($request->loginToSite($login, $password)) {
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


    private function delMessage(): void
    {
        try {
            $this->bot->message()?->delete();
        } catch (Throwable) {
        }
    }
}