<?php

use GuzzleHttp\Client;
use LastFmApi\Api\AuthApi;
use LastFmApi\Api\UserApi;

require_once 'vendor/autoload.php';
require_once './emojiPicker.php';

// Load .env file
if (file_exists(".env")) {
    (new Dotenv\Dotenv(__DIR__))->load();
} else if (!getenv('LASTFM_KEY')) {
    echo "No config setup." . PHP_EOL;
    die();
}


/**
 * Retrieve the last track listened the user listened to.
 *
 * @return array
 */
function getTrackInfo()
{
    try {
        $auth = new AuthApi('setsession', array('apiKey' => getenv('LASTFM_KEY')));
        $userAPI = new UserApi($auth);
        $trackInfo = $userAPI->getRecentTracks([
            'user' => getenv('LASTFM_USER'),
            'limit' => '1'
        ]);
        return $trackInfo[0];
    } catch (Exception $e) {
        echo 'Unable to authenticate against Last.fm API.', PHP_EOL;
        if (getenv('RESTART') == true) {
            echo 'Reinitializing program' . PHP_EOL;
            init();
        }
    }
}

/**
 * @param $status
 */
function updateSlackStatus($status, $trackName = '', $trackArtist = '')
{
    echo $status . PHP_EOL;
    $emoji = (new Emoji())->get($trackName, $trackArtist);
    $client = new Client();
    try {
        $response = $client->post('https://slack.com/api/users.profile.set', [
            'form_params' => [
                'token' => getenv('SLACK_TOKEN'),
                'profile' => json_encode([
                    'status_text' => $status,
                    'status_emoji' => $emoji
                ])
            ]
        ]);
        if ($response->getStatusCode() === 429) {
            echo "Rate Limited by Slack API. Sleeping for 30 seconds before restarting." . PHP_EOL;
            sleep(30);
            init();
        }
    } catch (Exception $e) {
        if ($e->getCode() === 429) {
            echo "Rate Limited by Slack API. Sleeping for 30 seconds before restarting." . PHP_EOL;
            sleep(30);
            init();
        }
    }
}

function getSlackStatus(&$currentStatus)
{
    $trackInfo = getTrackInfo();
    $status = $trackInfo['artist']['name'] . ' - ' . $trackInfo['name'];
    if (isset($trackInfo['nowplaying'])) {
        if ($trackInfo['nowplaying'] === true && $currentStatus !== $status) {
            updateSlackStatus($status, $trackInfo['name'], $trackInfo['artist']['name']);
            $currentStatus = $status;
        }
    } else {
        $status = 'Not currently playing';
        if ($currentStatus !== $status) {
            updateSlackStatus($status);
            $currentStatus = $status;
        }
    }
}

$currentStatus = '';

function init()
{
    getSlackStatus($currentStatus);

    $loop = React\EventLoop\Factory::create();

    if (defined('SIGINT')) {
        $loop->addSignal(SIGINT, function () {
            updateSlackStatus('Not currently playing');
            die();
        });
    }

    $loop->addPeriodicTimer(10, function () use (&$currentStatus) {
        getSlackStatus($currentStatus);
    });

    $loop->run();
}

init();
