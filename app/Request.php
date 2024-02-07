<?php

namespace App;

use App\Conversations\StudentActionsConversation;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\FileCookieJar;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\StreamInterface;
use SergiX44\Nutgram\Telegram\Types\Sticker\Sticker;

class Request
{
    private int $userID;

    public function __construct(int $userId)
    {
        $this->userID = $userId;
    }

    /**
     * @throws GuzzleException
     */
    public function loginToSite(string $login, string $password): bool
    {
        $uri = StudentActionsConversation::$uri;
        $data = ['login' => $login, 'password' => $password];

        $cookiePath = config('dirs.cookies') . '/' . $this->userID;

        $client = $this->getClient($cookiePath);
        $response = $client->request('POST', $uri . '/?option=auth', [
            'form_params' => $data,
            'allow_redirects' => true,
        ]);

        return str_contains($response->getBody(), 'Дарсҳои ман');

    }

    /**
     * @throws GuzzleException
     */
    public function getRatingTable(): StreamInterface
    {
        $cookiePath = config('dirs.cookies') . '/' . $this->userID;
        $uri = StudentActionsConversation::$uri;
        return $this->getClient($cookiePath)->get($uri . '/student/?option=study$action=list')->getBody();
    }

    private function getClient(?string $cookiePath): Client
    {
        $cookieJar = new FileCookieJar($cookiePath, true);
        return new Client([
            'cookies' => $cookieJar,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64; rv:109.0) Gecko/20100101 Firefox/119.0',
            ],
        ]);
    }
}