# Discord MediaWiki

This is a extension for [MediaWiki](https://www.mediawiki.org/wiki/MediaWiki) that sends notifications of actions in your Wiki like editing, adding or removing a page into [Discord](https://discordapp.com/) channel.

> Looking for extension that can send notifications to [HipChat](https://github.com/kulttuuri/hipchat_mediawiki) or [Slack](https://github.com/kulttuuri/slack_mediawiki)?

![Screenshot](http://i.imgur.com/ZL46PJO.jpg)

## Supported MediaWiki operations to send notifications

* Article is added, removed, moved or edited.
* New user is added.
* User is blocked.
* File is uploaded.
* ... and each notification can be individually enabled or disabled :)

## Requirements

* [cURL](http://curl.haxx.se/). This extension also supports using `file_get_content` for sending the data. See the configuration parameter `$wgDiscordSendMethod` below to change this.
* MediaWiki 1.25+
* Apache should have NE (NoEscape) flag on to prevent issues in URLs. By default you should have this enabled.

## How to install

1) Create a new Discord Webhook for your channel. You can create and manage webhooks for your channel by clicking the settings icon next to channel name in the Discord app. Read more from here: https://support.discordapp.com/hc/en-us/articles/228383668

2) After setting up the Webhook you will get a Webhook URL. Copy that URL as you will need it in step 4.

3) [Download latest release of this extension](https://github.com/kulttuuri/discord_mediawiki/archive/master.zip), uncompress the archive and move folder `DiscordNotifications` into your `mediawiki_installation/extensions` folder.

4) Add settings listed below in your `localSettings.php`. Note that it is mandatory to set these settings for this extension to work:

```php
require_once("$IP/extensions/DiscordNotifications/DiscordNotifications.php");
// Required. Your Discord webhook URL. Read more from here: https://support.discordapp.com/hc/en-us/articles/228383668
$wgDiscordIncomingWebhookUrl = "";
// Required. Name the message will appear to be sent from. Change this to whatever you wish it to be.
$wgDiscordFromName = $wgSitename;
// URL into your MediaWiki installation with the trailing /.
$wgWikiUrl		= "http://your_wiki_url/";
// Wiki script name. Leave this to default one if you do not have URL rewriting enabled.
$wgWikiUrlEnding = "index.php?title=";
// What method will be used to send the data to Discord server. By default this is "curl" which only works if you have the curl extension enabled. This can be: "curl" or "file_get_contents". Default: "curl".
$wgDiscordSendMethod = "curl";
```

5) Enjoy the notifications in your Discord room!
	
## Additional options

These options can be set after including your plugin in your `localSettings.php` file.

### Remove additional links from user and article pages

By default user and article links in the nofication message will get additional links for ex. to block user, view article history etc. You can disable either one of those by setting settings below to false.

```php
// If this is true, pages will get additional links in the notification message (edit | delete | history).
$wgDiscordIncludePageUrls = true;
// If this is true, users will get additional links in the notification message (block | groups | talk | contribs).
$wgDiscordIncludeUserUrls = true;
// If this is true, all minor edits made to articles will not be submitted to Discord.
$wgDiscordIgnoreMinorEdits = false;
```

### Disable new user extra information

By default we show full name, email and IP address of newly created user in the notification. You can individually disable each of these using the settings below. This is helpful for example in situation where you do not want to expose this information for users in your Discord channel.

```php
// If this is true, newly created user email address is added to notification.
$wgDiscordShowNewUserEmail = true;
// If this is true, newly created user full name is added to notification.
$wgDiscordShowNewUserFullName = true;
// If this is true, newly created user IP address is added to notification.
$wgDiscordShowNewUserIP = true;
```

### Show edit size

By default we show size of the edit. You can hide this information with the setting below.

```php
$wgDiscordIncludeDiffSize = false;
```

### Disable notifications from certain user roles

By default notifications from all users will be sent to your Discord room. If you wish to exclude users in certain group to not send notification of any actions, you can set the group with the setting below.

```php
// If this is set, actions by users with this permission won't cause alerts
$wgExcludedPermission = "";
```

### Actions to notify of

MediaWiki actions that will be sent notifications of into Discord. Set desired options to false to disable notifications of those actions.

```php
// New user added into MediaWiki
$wgDiscordNotificationNewUser = true;
// User or IP blocked in MediaWiki
$wgDiscordNotificationBlockedUser = true;
// Article added to MediaWiki
$wgDiscordNotificationAddedArticle = true;
// Article removed from MediaWiki
$wgDiscordNotificationRemovedArticle = true;
// Article moved under new title in MediaWiki
$wgDiscordNotificationMovedArticle = true;
// Article edited in MediaWiki
$wgDiscordNotificationEditedArticle = true;
// File uploaded
$wgDiscordNotificationFileUpload = true;
```
	
## Additional MediaWiki URL Settings

Should any of these default MediaWiki system page URLs differ in your installation, change them here.

```php
$wgWikiUrlEndingUserRights          = "Special%3AUserRights&user=";
$wgWikiUrlEndingBlockUser           = "Special:Block/";
$wgWikiUrlEndingUserPage            = "User:";
$wgWikiUrlEndingUserTalkPage        = "User_talk:";
$wgWikiUrlEndingUserContributions   = "Special:Contributions/";
$wgWikiUrlEndingBlockList           = "Special:BlockList";
$wgWikiUrlEndingEditArticle         = "action=edit";
$wgWikiUrlEndingDeleteArticle       = "action=delete";
$wgWikiUrlEndingHistory             = "action=history";
$wgWikiUrlEndingDiff                = "diff=prev&oldid=";
```

## License

[MIT License](http://en.wikipedia.org/wiki/MIT_License)

## Issues / Ideas / Comments

Feel free to use the [Issues](https://github.com/kulttuuri/discord_mediawiki/issues) section on Github for this project to submit any issues / ideas / comments! :)
