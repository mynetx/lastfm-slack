# Last.fm to Slack 🎶

This simple script let's you update your Slack status with the song you're currently listening to via Last.fm - for example using Spotify.

## Installation

1. Clone this repository.

```
git clone https://github.com/mpociot/lastfm-slack.git
```

2. Run `composer install`.

3. Copy `.env.example` to `.env` and configure your `LASTFM_KEY` key, `LASTFM_USER` and your `SLACK_TOKEN`.

4. Run the script `php statusUpdate.php`

5. Listen to some music :)

## Obtaining the Config tokens
> Please note: Please place the config options in "" quote marks.

### Getting the LastFM key
1. Go to this website [here](https://www.last.fm/api/account/create) and create a new API application
  a. Fill in the application. Call it "Last2Slack" or something similar - it doesn't really matter
2. On the completion screen, it will show the API Key - this is the LASTFM_KEY
3. There will also be a "registered to" field. This is the LASTFM_USER.
4. Record these details as you cannot currently view them again!

### Getting the Slack Token
1. Go to this page [here](https://api.slack.com/custom-integrations/legacy-tokens). Although Slack does not recommend using Legacy tokens, they are still heavily used and perfectly secure
2. Scroll down to the "Legacy token generator" area
3. Locate the Slack workspace you are looking to get a token for, click the "Request Token" button next to it (you may be prompted for a password)
4. The token will appear in an input field to the left of the button. Copy this and paste it in "" as the SLACK_TOKEN


## Options

### Slack Status Emoji
To change this, change the `SLACK_EMOJI` config option to a markdown emoji with the colons either side. e.g., `:tada:`
By default the emoji is :musical_note:

## License

Last.fm to Slack is free software distributed under the terms of the MIT license.
