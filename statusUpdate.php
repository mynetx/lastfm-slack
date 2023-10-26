<?php

use GuzzleHttp\Client;
use LastFmApi\Api\AuthApi;
use LastFmApi\Api\UserApi;
use Dotenv\Dotenv;

require_once 'vendor/autoload.php';
require_once './emojiPicker.php';

// Load .env file using Dotenv
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Function to authenticate and get the session key
function authenticateAndGetSessionKey()
{
    $sessionKey = $_ENV['LASTFM_SESSION_KEY'] ?? null;

    if (empty($sessionKey)) {
        // Get the Last.fm API key and secret from the environment
        $apiKey = $_ENV['LASTFM_KEY'];
        $apiSecret = $_ENV['LASTFM_SECRET'];

        // Step 1: Get a token
        $client = new Client();
        $response = $client->get("http://ws.audioscrobbler.com/2.0/?method=auth.gettoken&api_key=$apiKey&format=json");
        $data = json_decode($response->getBody());
        $token = $data->token;

        // Step 2: Open the authorization URL in your browser to authorize the application
        $authUrl = "http://www.last.fm/api/auth/?api_key=$apiKey&token=$token";
        echo "Visit this URL in your browser and authorize the application: $authUrl" . PHP_EOL;

        // Step 3: After authorization, enter the token you received to get the session key
        echo "Enter the token you received after authorizing the application: ";
        $enteredToken = trim(fgets(STDIN));

        if ($enteredToken === $token) {
            $client = new Client();
            $data = [
                "api_key=$apiKey",
                "method=auth.getSession",
                "token=$token"
            ];
            $data[] = "api_sig=" . md5(str_replace('=', '', implode('', $data)) . $apiSecret);
            $data[] = "format=json";
            $response = $client->get("http://ws.audioscrobbler.com/2.0/?" . implode("&", $data));

            if ($response->getStatusCode() === 200) {
                $body = $response->getBody()->getContents();
                $data = json_decode($body, true);

                if (isset($data['session']['key'])) {
                    $sessionKey = $data['session']['key'];
                    file_put_contents('.env', "LASTFM_SESSION_KEY=$sessionKey" . PHP_EOL, FILE_APPEND);
                    echo "Your session key has been stored in the .env file." . PHP_EOL;
                    return $data['session']['key'];
                } else {
                    echo "Failed to obtain a session key. Authentication failed." . PHP_EOL;
                    exit(1);
                }
            } else {
                echo "Failed to obtain a session key. Authentication failed." . PHP_EOL;
                exit(1);
            }
        } else {
            echo "Tokens do not match. Authentication failed." . PHP_EOL;
            exit(1);
        }
    }

    return $sessionKey;
}

// Function to retrieve the last track listened to by the user
function getTrackInfo()
{
    try {
        $sessionKey = authenticateAndGetSessionKey();
        $auth = new AuthApi('setsession', [
            'apiKey' => $_ENV['LASTFM_KEY'],
            'sessionKey' => $sessionKey,
        ]);
        $userAPI = new UserApi($auth);
        $trackInfo = $userAPI->getRecentTracks([
            'user' => $_ENV['LASTFM_USER'],
            'limit' => '1',
        ]);

        if (empty($trackInfo)) {
            echo 'No recent tracks found.' . PHP_EOL;
            exit(1);
        }

        return $trackInfo[0];
    } catch (Exception $e) {
        echo 'Unable to authenticate against Last.fm API or retrieve track info.' . PHP_EOL;
        exit(1);
    }
}

// Function to update Slack status
function updateSlackStatus($status, $trackName = '', $trackArtist = '')
{
    echo $status . PHP_EOL;
    $emoji = $status !== '' ? (new Emoji())->get($trackName, $trackArtist) : '';

    $slackTokens = [
        $_ENV['SLACK_TOKEN_1'],
        $_ENV['SLACK_TOKEN_2']
    ];

    $client = new Client();
    try {
        foreach ($slackTokens as $token) {
            $response = $client->post('https://slack.com/api/users.profile.set', [
                'form_params' => [
                    'token' => $token,
                    'profile' => json_encode([
                        'status_text' => $status,
                        'status_emoji' => $emoji,
                    ]),
                ],
            ]);

            if ($response->getStatusCode() === 429) {
                echo "Rate Limited by Slack API. Sleeping for 30 seconds before restarting." . PHP_EOL;
                sleep(30);
                init();
            }
        }
    } catch (Exception $e) {
        if ($e->getCode() === 429) {
            echo "Rate Limited by Slack API. Sleeping for 30 seconds before restarting." . PHP_EOL;
            sleep(30);
            init();
        }
    }
}

// Function to get Slack status
function getSlackStatus(&$currentStatus)
{
    try {
        $trackInfo = getTrackInfo();
    } catch (Exception $e) {
        exit(1);
    }

    $status = $trackInfo['artist']['name'] . ' – ' . $trackInfo['name'];

    if (isset($trackInfo['nowplaying'])) {
        if ($trackInfo['nowplaying'] === true && $currentStatus !== $status) {
            updateSlackStatus($status, $trackInfo['name'], $trackInfo['artist']['name']);
            $currentStatus = $status;
        }
    } else {
        $status = '';
        if ($currentStatus !== $status) {
            updateSlackStatus($status);
            $currentStatus = $status;
        }
    }
}

$currentStatus = '';

// Initialize the script
function init()
{
    global $currentStatus;

    getSlackStatus($currentStatus);
}

// Main loop
while (true) {
    if ($_ENV['RESTART'] === 'true') {
        // Initialize the script
        init();
        echo "Restarted." . PHP_EOL;
    }
    sleep(10); // Sleep for 10 seconds
}
