# Last.fm to Slack 🎶

This simple script let's you update your Slack status with the song you're currently listening to via Last.fm - for example using Spotify.

## Installation

1. Clone this repository.

```
git clone https://github.com/joshghent/lastfm-slack.git
```

2. Run `composer install`.

3. Copy `.env.example` to `.env` and configure your `LASTFM_KEY` key, `LASTFM_USER` and your `SLACK_TOKEN`.

4. Run the script `php statusUpdate.php`

5. Listen to some music :)

### Installation via Docker
After configuring a `.env` file with the required parameters, run the following command.
Please note: Do not wrap your environment variables in quote " tags as this will mean the program does not work!
```bash
docker run -d --restart always --name lastfm2slack --env-file .env joshghent/lastfm2slack
```

## Obtaining the Config tokens
> Please note: Please place the config options in "" quote marks.

### Getting the LastFM key
1. Go to this website [here](https://www.last.fm/api/account/create) and create a new API application
  a. Fill in the application. Call it "Last2Slack" or something similar - it doesn't really matter
2. On the completion screen, it will show the API Key - this is the LASTFM_KEY
3. There will also be a "registered to" field. This is the LASTFM_USER.
4. Record these details as you cannot currently view them again!

### Getting the Slack Token
1. Get your Slack app credentials from https://api.slack.com/apps.
2. Call https://slack.com/oauth/v2/authorize?user_scope=users.profile:write&client_id=<CLIENT ID>&redirect_uri=http://www.example.com/
3. with the result, call `curl -F code=<CALLBACK CODE> -F client_id=<CLIENT ID> -F client_secret=<CLIENT SECRET> https://slack.com/api/oauth.v2.access`

## Options

### Slack Status Emoji
To change this, change the `SLACK_EMOJI` config option to a markdown emoji with the colons either side. e.g., `:tada:`
By default the emoji is :musical_note:

## License

Last.fm to Slack is free software distributed under the terms of the MIT license.
