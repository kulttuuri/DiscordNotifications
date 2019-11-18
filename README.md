# Discord MediaWiki

This is a extension for [MediaWiki](https://www.mediawiki.org/wiki/MediaWiki) that sends notifications of actions in your Wiki like editing, adding or removing a page into [Discord](https://discordapp.com/) channel.

> Looking for extension that can send notifications to [HipChat](https://github.com/kulttuuri/hipchat_mediawiki) or [Slack](https://github.com/kulttuuri/slack_mediawiki)?

![Screenshot](https://github.com/kulttuuri/discord_mediawiki/blob/master/screenshot.jpg)

## Supported MediaWiki operations to send notifications

* Article is added, removed, moved or edited.
* Article protection settings are changed.
* New user is added.
* User is blocked.
* User groups are changed.
* File is uploaded.
* ... and each notification can be individually enabled or disabled :)

## Language Support

This extension supports *english*, *finnish*, *german*, *spanish* and *korean*. Notifications are being sent in the language set to your localSettings.php file in the variable wgLanguageCode.

Want to translate this extension to your language? Just clone this repository, make a copy of the i18n/en.json file to your language, make the translations and create a issue or pull request linking to your translation in your repository! :)

## Requirements

* [cURL](http://curl.haxx.se/) or ability to use PHP function `file_get_contents` for sending the data. Defaults to cURL. See the configuration parameter `$wgDiscordSendMethod` below to switch between cURL and file_get_contents.
* MediaWiki 1.25+
* Apache should have NE (NoEscape) flag on to prevent issues in URLs. By default you should have this enabled.

## How to install

1) Create a new Discord Webhook for your channel. You can create and manage webhooks for your channel by clicking the settings icon next to channel name in the Discord app. Read more from here: https://support.discordapp.com/hc/en-us/articles/228383668

2) After setting up the Webhook you will get a Webhook URL. Copy that URL as you will need it in step 4.

3) [Download latest release of this extension](https://github.com/kulttuuri/discord_mediawiki/archive/master.zip), uncompress the archive and move folder `DiscordNotifications` into your `mediawiki_installation/extensions` folder. (And instead of manually downloading the latest version, you could also just git clone this repository to that same extensions folder).

4) Add settings listed below in your `localSettings.php`. Note that it is mandatory to set these settings for this extension to work:

```php
require_once("$IP/extensions/DiscordNotifications/DiscordNotifications.php");
// Required. Your Discord webhook URL. Read more from here: https://support.discordapp.com/hc/en-us/articles/228383668
$wgDiscordIncomingWebhookUrl = "";
// Required. Name the message will appear to be sent from. Change this to whatever you wish it to be.
$wgDiscordFromName = $wgSitename;
// Avatar to use for messages. If blank, uses the webhook's default avatar.
$wgDiscordAvatarUrl = "";
// URL into your MediaWiki installation with the trailing /.
$wgWikiUrl = "http://your_wiki_url/";
// Wiki script name. Leave this to default one if you do not have URL rewriting enabled.
$wgWikiUrlEnding = "index.php?title=";
// What method will be used to send the data to Discord server. By default this is "curl" which only works if you have the curl extension enabled. There have been cases where VisualEditor extension does not work with the curl method, so in that case the recommended solution is to use the file_get_contents method. This can be: "curl" or "file_get_contents". Default: "curl".
$wgDiscordSendMethod = "curl";
```

5) Enjoy the notifications in your Discord room!

## Additional options

These options can be set after including your plugin in your `localSettings.php` file.

### Customize request call method (Fix extension not working with VisualEditor)

By default this extension uses curl to send the requests to slack's API. If you use VisualEditor and get unknown errors, do not have curl enabled on your server or notice other problems, the recommended solution is to change method to file_get_contents.

```php
$wgDiscordSendMethod = "file_get_contents";
```

### Send notifications to multiple channels

You can add more webhook urls that you want to send notifications to by adding them in this array:

```php
$wgDiscordAdditionalIncomingWebhookUrls = ["https://yourUrlOne.com", "https://yourUrlTwo..."];
```

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

### Disable notifications from certain pages / namespaces

You can exclude notifications from certain namespaces / articles by adding them into this array. Note: This targets all pages starting with the name.

```php
// Actions (add, edit, modify) won't be notified to Discord room from articles starting with these names
$wgDiscordExcludeNotificationsFrom = ["User:", "Weirdgroup"];
```

### Show non-public article deletions

By default we do not show non-public article deletion notifications. You can change this using the parameter below.

```php
$wgDiscordNotificationShowSuppressed = true;
```

### Actions to notify of

MediaWiki actions that will be sent notifications of into Discord. Set desired options to false to disable notifications of those actions.

```php
// New user added into MediaWiki
$wgDiscordNotificationNewUser = true;
// User or IP blocked in MediaWiki
$wgDiscordNotificationBlockedUser = true;
// User groups changed in MediaWiki
$wgDiscordNotificationUserGroupsChanged
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
// Article protection settings changed
$wgDiscordNotificationProtectedArticle = true;
// Action on Flow Boards (experimental)
$wgDiscordNotificationFlow = true;
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

## Contributors

[@innosflew](https://github.com/innosflew) [@uzalu](https://github.com/uzalu) [@DFelten](https://github.com/DFelten) [@lens0021](https://github.com/lens0021) [@The-Voidwalker](https://github.com/The-Voidwalker) [@GerbilSoft](https://github.com/GerbilSoft) [@Lens0021](https://github.com/Lens0021)

## License

[MIT License](http://en.wikipedia.org/wiki/MIT_License)

## Issues / Ideas / Comments

Feel free to use the [Issues](https://github.com/kulttuuri/discord_mediawiki/issues) section on Github for this project to submit any issues / ideas / comments! :)
