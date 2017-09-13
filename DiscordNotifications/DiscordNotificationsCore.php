<?php
class DiscordNotifications
{
	/**
	 * Replaces some special characters on urls. This has to be done as Discord webhook api does not accept urlencoded text.
	 */
	private static function parseurl($url)
	{
		$url = str_replace(" ", "%20", $url);
		$url = str_replace("(", "%28", $url);
		$url = str_replace(")", "%29", $url);
		return $url;
	}
	/**
	 * Gets nice HTML text for user containing the link to user page
	 * and also links to user site, groups editing, talk and contribs pages.
	 */
	static function getDiscordUserText($user)
	{
		global $wgWikiUrl, $wgWikiUrlEnding, $wgWikiUrlEndingUserPage,
			$wgWikiUrlEndingBlockUser, $wgWikiUrlEndingUserRights, 
			$wgWikiUrlEndingUserTalkPage, $wgWikiUrlEndingUserContributions,
			$wgDiscordIncludeUserUrls;
		
		if ($wgDiscordIncludeUserUrls)
		{
			return sprintf(
				"%s (%s | %s | %s | %s)",
				"<".self::parseurl($wgWikiUrl.$wgWikiUrlEnding.$wgWikiUrlEndingUserPage.$user)."|$user>",
				"<".self::parseurl($wgWikiUrl.$wgWikiUrlEnding.$wgWikiUrlEndingBlockUser.$user)."|block>",
				"<".self::parseurl($wgWikiUrl.$wgWikiUrlEnding.$wgWikiUrlEndingUserRights.$user)."|groups>",
				"<".self::parseurl($wgWikiUrl.$wgWikiUrlEnding.$wgWikiUrlEndingUserTalkPage.$user)."|talk>",
				"<".self::parseurl($wgWikiUrl.$wgWikiUrlEnding.$wgWikiUrlEndingUserContributions.$user)."|contribs>");
		}
		else
		{
			return "<".self::parseurl($wgWikiUrl.$wgWikiUrlEnding.$wgWikiUrlEndingUserPage.$user)."|$user>";
		}
	}

	/**
	 * Gets nice HTML text for article containing the link to article page
	 * and also into edit, delete and article history pages.
	 */
	static function getDiscordArticleText(WikiPage $article, $diff = false)
	{
		global $wgWikiUrl, $wgWikiUrlEnding, $wgWikiUrlEndingEditArticle,
			$wgWikiUrlEndingDeleteArticle, $wgWikiUrlEndingHistory,
			$wgWikiUrlEndingDiff, $wgDiscordIncludePageUrls;

		$prefix = "<".$wgWikiUrl.$wgWikiUrlEnding.$article->getTitle()->getFullText();
		if ($wgDiscordIncludePageUrls)
		{
			$out = sprintf(
				"%s (%s | %s | %s",
				self::parseurl($prefix)."|".$article->getTitle()->getFullText().">",
				self::parseurl($prefix."&".$wgWikiUrlEndingEditArticle)."|edit>",
				self::parseurl($prefix."&".$wgWikiUrlEndingDeleteArticle)."|delete>",
				self::parseurl($prefix."&".$wgWikiUrlEndingHistory)."|history>"/*,
					"move",
					"protect",
					"watch"*/);
			if ($diff)
			{
				$out .= " | ".self::parseurl($prefix."&".$wgWikiUrlEndingDiff.$article->getRevision()->getID())."|diff>)";
			}
			else
			{
				$out .= ")";
			}
			return $out."\n";
		}
		else
		{
			return self::parseurl($prefix)."|".$article->getTitle()->getFullText().">";
		}
	}

	/**
	 * Gets nice HTML text for title object containing the link to article page
	 * and also into edit, delete and article history pages.
	 */
	static function getDiscordTitleText(Title $title)
	{
		global $wgWikiUrl, $wgWikiUrlEnding, $wgWikiUrlEndingEditArticle,
			$wgWikiUrlEndingDeleteArticle, $wgWikiUrlEndingHistory,
			$wgDiscordIncludePageUrls;

		$titleName = $title->getFullText();
		if ($wgDiscordIncludePageUrls)
		{
			return sprintf(
				"%s (%s | %s | %s)",
				"<".self::parseurl($wgWikiUrl.$wgWikiUrlEnding.$titleName)."|".$titleName.">",
				"<".self::parseurl($wgWikiUrl.$wgWikiUrlEnding.$titleName."&".$wgWikiUrlEndingEditArticle)."|edit>",
				"<".self::parseurl($wgWikiUrl.$wgWikiUrlEnding.$titleName."&".$wgWikiUrlEndingDeleteArticle)."|delete>",
				"<".self::parseurl($wgWikiUrl.$wgWikiUrlEnding.$titleName."&".$wgWikiUrlEndingHistory)."|history>"/*,
						"move",
						"protect",
						"watch"*/);
		}
		else
		{
			return "<".self::parseurl($wgWikiUrl.$wgWikiUrlEnding.$titleName)."|".$titleName.">";
		}
	}

	/**
	 * Occurs after the save page request has been processed.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/PageContentSaveComplete
	 */
	static function discord_article_saved(WikiPage $article, $user, $content, $summary, $isMinor, $isWatch, $section, $flags, $revision, $status, $baseRevId)
	{
		global $wgDiscordNotificationEditedArticle;
		global $wgDiscordIgnoreMinorEdits, $wgDiscordIncludeDiffSize;
		if (!$wgDiscordNotificationEditedArticle) return;

		// Skip new articles that have view count below 1. Adding new articles is already handled in article_added function and
		// calling it also here would trigger two notifications!
		$isNew = $status->value['new']; // This is 1 if article is new
		if ($isNew == 1) {
			return true;
		}

		// Skip minor edits if user wanted to ignore them
		if ($isMinor && $wgDiscordIgnoreMinorEdits) return;
		
		// Skip edits that are just refreshing the page
		if ($article->getRevision()->getPrevious() == NULL) {
			return;
		}

		$message = sprintf(
			"📝 %s has %s article %s %s",
			self::getDiscordUserText($user),
			$isMinor == true ? "made minor edit to" : "edited",
			self::getDiscordArticleText($article, true),
			$summary == "" ? "" : "Summary: $summary");
		if ($wgDiscordIncludeDiffSize)
		{		
			$message .= sprintf(
				" (%+d bytes)",
				$article->getRevision()->getSize() - $article->getRevision()->getPrevious()->getSize());
		}
		self::push_discord_notify($message, $user, 'article_saved');
		return true;
	}

	/**
	 * Occurs after a new article has been created.
	 * @see http://www.mediawiki.org/wiki/Manual:Hooks/ArticleInsertComplete
	 */
	static function discord_article_inserted(WikiPage $article, $user, $text, $summary, $isminor, $iswatch, $section, $flags, $revision)
	{
		global $wgDiscordNotificationAddedArticle, $wgDiscordIncludeDiffSize;
		if (!$wgDiscordNotificationAddedArticle) return;

		// Do not announce newly added file uploads as articles...
		if ($article->getTitle()->getNsText() == "File") return true;
		
		$message = sprintf(
			"📄 %s has created article %s %s",
			self::getDiscordUserText($user),
			self::getDiscordArticleText($article),
			$summary == "" ? "" : "Summary: $summary");
		if ($wgDiscordIncludeDiffSize)
		{		
			$message .= sprintf(
				" (%d bytes)",
				$article->getRevision()->getSize());
		}
		self::push_discord_notify($message, $user, 'article_inserted');
		return true;
	}

	/**
	 * Occurs after the delete article request has been processed.
	 * @see http://www.mediawiki.org/wiki/Manual:Hooks/ArticleDeleteComplete
	 */
	static function discord_article_deleted(WikiPage $article, $user, $reason, $id)
	{
		global $wgDiscordNotificationRemovedArticle;
		if (!$wgDiscordNotificationRemovedArticle) return;

		$message = sprintf(
			"❌ %s has deleted article %s Reason: %s",
			self::getDiscordUserText($user),
			self::getDiscordArticleText($article),
			$reason);
		self::push_discord_notify($message, $user, 'article_deleted');
		return true;
	}

	/**
	 * Occurs after a page has been moved.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/TitleMoveComplete
	 */
	static function discord_article_moved($title, $newtitle, $user, $oldid, $newid, $reason = null)
	{
		global $wgDiscordNotificationMovedArticle;
		if (!$wgDiscordNotificationMovedArticle) return;

		$message = sprintf(
			"➡ %s has moved article %s to %s. Reason: %s",
			self::getDiscordUserText($user),
			self::getDiscordTitleText($title),
			self::getDiscordTitleText($newtitle),
			$reason);
		self::push_discord_notify($message, $user, 'article_moved');
		return true;
	}

	/**
	 * Occurs after the protect article request has been processed.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ArticleProtectComplete
	 */
	static function discord_article_protected($article = null, $user = null, $protect = false, $reason = "", $moveonly = false)
	{
		global $wgDiscordNotificationProtectedArticle;
		if (!$wgDiscordNotificationProtectedArticle) return;

		$message = sprintf(
			"🔒 %s has %s article %s. Reason: %s",
			self::getDiscordUserText($user),
			$protect ? "changed protection of" : "removed protection of",
			self::getDiscordArticleText($article),
			$reason);
		self::push_discord_notify($message, $user, 'article_protected');
		return true;
	}

	/**
	 * Called after a user account is created.
	 * @see http://www.mediawiki.org/wiki/Manual:Hooks/AddNewAccount
	 */
	static function discord_new_user_account($user, $byEmail)
	{
		global $wgDiscordNotificationNewUser, $wgDiscordShowNewUserEmail, $wgDiscordShowNewUserFullName, $wgDiscordShowNewUserIP;
		if (!$wgDiscordNotificationNewUser) return;

		$email = "";
		$realname = "";
		$ipaddress = "";
		try { $email = $user->getEmail(); } catch (Exception $e) {}
		try { $realname = $user->getRealName(); } catch (Exception $e) {}
		try { $ipaddress = $user->getRequest()->getIP(); } catch (Exception $e) {}

		$messageExtra = "";
		if ($wgDiscordShowNewUserEmail || $wgDiscordShowNewUserFullName || $wgDiscordShowNewUserIP) {
			$messageExtra = "(";
			if ($wgDiscordShowNewUserEmail) $messageExtra .= $email . ", ";
			if ($wgDiscordShowNewUserFullName) $messageExtra .= $realname . ", ";
			if ($wgDiscordShowNewUserIP) $messageExtra .= $ipaddress . ", ";
			$messageExtra = substr($messageExtra, 0, -2); // Remove trailing , 
			$messageExtra .= ")";
		}

		$message = sprintf(
			"👥 New user account %s was just created %s",
			self::getDiscordUserText($user),
			$messageExtra);
		self::push_discord_notify($message, $user, 'new_user_account');
		return true;
	}

	/**
	 * Called when a file upload has completed.
	 * @see http://www.mediawiki.org/wiki/Manual:Hooks/UploadComplete
	 */
	static function discord_file_uploaded($image)
	{
		global $wgDiscordNotificationFileUpload;
		if (!$wgDiscordNotificationFileUpload) return;

		global $wgWikiUrl, $wgWikiUrlEnding, $wgUser;
		$message = sprintf(
			"📤 %s has uploaded file <%s|%s> (format: %s, size: %s MB, summary: %s)",
			self::getDiscordUserText($wgUser->mName),
			self::parseurl($wgWikiUrl . $wgWikiUrlEnding . $image->getLocalFile()->getTitle()),
			$image->getLocalFile()->getTitle(),
			$image->getLocalFile()->getMimeType(),
			round($image->getLocalFile()->size / 1024 / 1024, 3),
			$image->getLocalFile()->getDescription());

		self::push_discord_notify($message, $wgUser, 'file_uploaded');
		return true;
	}

	/**
	 * Occurs after the request to block an IP or user has been processed
	 * @see http://www.mediawiki.org/wiki/Manual:MediaWiki_hooks/BlockIpComplete
	 */
	static function discord_user_blocked(Block $block, $user)
	{
		global $wgDiscordNotificationBlockedUser;
		if (!$wgDiscordNotificationBlockedUser) return;

		global $wgWikiUrl, $wgWikiUrlEnding, $wgWikiUrlEndingBlockList;
		$message = sprintf(
			"🚫 %s has blocked %s %s Block expiration: %s. %s",
			self::getDiscordUserText($user),
			self::getDiscordUserText($block->getTarget()),
			$block->mReason == "" ? "" : "with reason '".$block->mReason."'.",
			$block->mExpiry,
			"<".self::parseurl($wgWikiUrl.$wgWikiUrlEnding.$wgWikiUrlEndingBlockList)."|List of all blocks>.");
		self::push_discord_notify($message, $user, 'user_blocked');
		return true;
	}

	/**
	 * Sends the message into Discord room.
	 * @param message Message to be sent.
	 * @see https://discordapp.com/developers/docs/resources/webhook#execute-webhook
	 */
	static function push_discord_notify($message, $user, $action)
	{
		global $wgDiscordIncomingWebhookUrl, $wgDiscordFromName, $wgDiscordSendMethod, $wgExcludedPermission, $wgSitename;
		
		if ( $wgExcludedPermission != "" ) {
			if ( $user->isAllowed( $wgExcludedPermission ) )
			{
				return; // Users with the permission suppress notifications
			}
		}

		// Convert " to ' in the message to be sent as otherwise JSON formatting would break.
		$message = str_replace('"', "'", $message);

		$discordFromName = $wgDiscordFromName;
		if ( $discordFromName == "" )
		{
			$discordFromName = $wgSitename;
		}

		$message = preg_replace("~(<)(http)([^|]*)(\|)([^\>]*)(>)~", "[$5]($2$3)", $message);
		$message = str_replace(array("\r", "\n"), '', $message);
    
    $colour = 11777212;
    switch($action){
      case 'article_saved':
        $colour = 2993970;
        break;
      case 'article_inserted':
        $colour = 3580392;
        break;
      case 'article_deleted':
        $colour = 15217973;
        break;
      case 'article_moved':
        $colour = 14038504;
        break;
      case 'article_protected':
        $colour = 3493864;
        break;
      case 'new_user_account':
        $colour = 3580392;
        break;
      case 'file_uploaded':
        $colour = 3580392;
        break;
      case 'user_blocked':
        $colour = 15217973;
        break;
      default:
        $colour = 11777212;
        break;
    }

		$post = sprintf('{"embeds": [{ "color" : "'.$colour.'" ,"description" : "%s"}], "username": "%s"',
		$message,
		$discordFromName);
		$post .= '}';

		// Use file_get_contents to send the data. Note that you will need to have allow_url_fopen enabled in php.ini for this to work.
		if ($wgDiscordSendMethod == "file_get_contents") {
			$extradata = array(
				'http' => array(
				'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
				'method'  => 'POST',
				'content' => $post,
				),
			);
			$context = stream_context_create($extradata);
			$result = file_get_contents($wgDiscordIncomingWebhookUrl, false, $context);
		}
		// Call the Discord API through cURL (default way). Note that you will need to have cURL enabled for this to work.
		else {
			$h = curl_init();
			curl_setopt($h, CURLOPT_URL, $wgDiscordIncomingWebhookUrl);
			curl_setopt($h, CURLOPT_POST, 1);
			curl_setopt($h, CURLOPT_POSTFIELDS, $post);
			// I know this shouldn't be done, but because it wouldn't otherwise work because of SSL...
			curl_setopt ($h, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt ($h, CURLOPT_SSL_VERIFYPEER, 0);
			// ... Aaand execute the curl script!
			curl_exec($h);
			curl_close($h);
		}
	}
}

?>
