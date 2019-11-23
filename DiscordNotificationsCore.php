<?php
class DiscordNotificationsCore {
	/**
	 * Replaces some special characters on urls. This has to be done as Discord webhook api does not accept urlencoded text.
	 */
	private static function parseurl( $url ) {
		$url = str_replace( " ", "%20", $url );
		$url = str_replace( "(", "%28", $url );
		$url = str_replace( ")", "%29", $url );
		return $url;
	}

	/**
	 * Gets nice HTML text for user containing the link to user page
	 * and also links to user site, groups editing, talk and contribs pages.
	 */
	private static function getDiscordUserText( $user ) {
		global $wgWikiUrl, $wgWikiUrlEnding, $wgWikiUrlEndingUserPage,
			$wgWikiUrlEndingBlockUser, $wgWikiUrlEndingUserRights,
			$wgWikiUrlEndingUserTalkPage, $wgWikiUrlEndingUserContributions,
			$wgDiscordIncludeUserUrls;

		$user_url = str_replace( "&", "%26", $user );
		if ( $wgDiscordIncludeUserUrls ) {
			return sprintf(
				"%s (%s | %s | %s | %s)",
				"<" . self::parseurl( $wgWikiUrl . $wgWikiUrlEnding . $wgWikiUrlEndingUserPage . $user_url ) . "|$user>",
				"<" . self::parseurl( $wgWikiUrl . $wgWikiUrlEnding . $wgWikiUrlEndingBlockUser . $user_url ) . "|" . self::getMessage( 'discordnotifications-block' ) . ">",
				"<" . self::parseurl( $wgWikiUrl . $wgWikiUrlEnding . $wgWikiUrlEndingUserRights . $user_url ) . "|" . self::getMessage( 'discordnotifications-groups' ) . ">",
				"<" . self::parseurl( $wgWikiUrl . $wgWikiUrlEnding . $wgWikiUrlEndingUserTalkPage . $user_url ) . "|" . self::getMessage( 'discordnotifications-talk' ) . ">",
				"<" . self::parseurl( $wgWikiUrl . $wgWikiUrlEnding . $wgWikiUrlEndingUserContributions . $user_url ) . "|" . self::getMessage( 'discordnotifications-contribs' ) . ">" );
		} else {
			return "<" . self::parseurl( $wgWikiUrl . $wgWikiUrlEnding . $wgWikiUrlEndingUserPage . $user_url ) . "|$user>";
		}
	}

	/**
	 * Gets nice HTML text for article containing the link to article page
	 * and also into edit, delete and article history pages.
	 */
	private static function getDiscordArticleText( WikiPage $article, $diff = false ) {
		global $wgWikiUrl, $wgWikiUrlEnding, $wgWikiUrlEndingEditArticle,
			$wgWikiUrlEndingDeleteArticle, $wgWikiUrlEndingHistory,
			$wgWikiUrlEndingDiff, $wgDiscordIncludePageUrls;

		$title = $article->getTitle()->getFullText();
		$title_url = str_replace( "&", "%26", $title );
		$prefix = "<" . $wgWikiUrl . $wgWikiUrlEnding . $title_url;
		if ( $wgDiscordIncludePageUrls ) {
			$out = sprintf(
				"%s (%s | %s | %s",
				self::parseurl( $prefix ) . "|" . $title . ">",
				self::parseurl( $prefix . "&" . $wgWikiUrlEndingEditArticle ) . "|" . self::getMessage( 'discordnotifications-edit' ) . ">",
				self::parseurl( $prefix . "&" . $wgWikiUrlEndingDeleteArticle ) . "|" . self::getMessage( 'discordnotifications-delete' ) . ">",
				self::parseurl( $prefix . "&" . $wgWikiUrlEndingHistory ) . "|" . self::getMessage( 'discordnotifications-history' ) . ">"/*,
					"move",
					"protect",
					"watch"*/ );
			if ( $diff ) {
				$out .= " | " . self::parseurl( $prefix . "&" . $wgWikiUrlEndingDiff . $article->getRevision()->getID() ) . "|" . self::getMessage( 'discordnotifications-diff' ) . ">)";
			} else {
				$out .= ")";
			}
			return $out . "\n";
		} else {
			return self::parseurl( $prefix ) . "|" . $title . ">";
		}
	}

	/**
	 * Gets nice HTML text for title object containing the link to article page
	 * and also into edit, delete and article history pages.
	 */
	private static function getDiscordTitleText( Title $title ) {
		global $wgWikiUrl, $wgWikiUrlEnding, $wgWikiUrlEndingEditArticle,
			$wgWikiUrlEndingDeleteArticle, $wgWikiUrlEndingHistory,
			$wgDiscordIncludePageUrls;

		$titleName = $title->getFullText();
		$title_url = str_replace( "&", "%26", $titleName );
		if ( $wgDiscordIncludePageUrls ) {
			return sprintf(
				"%s (%s | %s | %s)",
				"<" . self::parseurl( $wgWikiUrl . $wgWikiUrlEnding . $title_url ) . "|" . $titleName . ">",
				"<" . self::parseurl( $wgWikiUrl . $wgWikiUrlEnding . $title_url . "&" . $wgWikiUrlEndingEditArticle ) . "|" . self::getMessage( 'discordnotifications-edit' ) . ">",
				"<" . self::parseurl( $wgWikiUrl . $wgWikiUrlEnding . $title_url . "&" . $wgWikiUrlEndingDeleteArticle ) . "|" . self::getMessage( 'discordnotifications-delete' ) . ">",
				"<" . self::parseurl( $wgWikiUrl . $wgWikiUrlEnding . $title_url . "&" . $wgWikiUrlEndingHistory ) . "|" . self::getMessage( 'discordnotifications-history' ) . ">"/*,
						"move",
						"protect",
						"watch"*/ );
		} else {
			return "<" . self::parseurl( $wgWikiUrl . $wgWikiUrlEnding . $title_url ) . "|" . $titleName . ">";
		}
	}

	/**
	 * Occurs after the save page request has been processed.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/PageContentSaveComplete
	 */
	public static function onDiscordArticleSaved( WikiPage $article, $user, $content, $summary, $isMinor, $isWatch, $section, $flags, $revision, $status, $baseRevId ) {
		global $wgDiscordNotificationEditedArticle;
		global $wgDiscordIgnoreMinorEdits, $wgDiscordIncludeDiffSize;
		if ( !$wgDiscordNotificationEditedArticle ) return;

		// Discard notifications from excluded pages
		global $wgDiscordExcludeNotificationsFrom;
		if ( is_array( $wgDiscordExcludeNotificationsFrom ) && count( $wgDiscordExcludeNotificationsFrom ) > 0 ) {
			foreach ( $wgDiscordExcludeNotificationsFrom as &$currentExclude ) {
				if ( 0 === strpos( $article->getTitle(), $currentExclude ) ) return;
			}
		}

		// Skip new articles that have view count below 1. Adding new articles is already handled in article_added function and
		// calling it also here would trigger two notifications!
		$isNew = $status->value['new']; // This is 1 if article is new
		if ( $isNew == 1 ) {
			return true;
		}

		// Skip minor edits if user wanted to ignore them
		if ( $isMinor && $wgDiscordIgnoreMinorEdits ) return;

		// Skip edits that are just refreshing the page
		if ( $article->getRevision()->getPrevious() == null || $revision->getPrevious() == null || !$revision || is_null( $status->getValue()['revision'] ) ) {
			return;
		}

		$message = sprintf(
			self::getMessage( 'discordnotifications-article-saved' ),
			self::getDiscordUserText( $user ),
			$isMinor == true ? self::getMessage( 'discordnotifications-article-saved-minor-edits' ) : self::getMessage( 'discordnotifications-article-saved-edit' ),
			self::getDiscordArticleText( $article, true ),
			$summary == "" ? "" : self::getMessage( 'discordnotifications-summary' ) . $summary );
		if ( $wgDiscordIncludeDiffSize ) {
			$message .= sprintf(
				" (%+d " . self::getMessage( 'discordnotifications-bytes' ) . ")",
				$article->getRevision()->getSize() - $article->getRevision()->getPrevious()->getSize() );
		}
		self::pushDiscordNotify( $message, $user, 'article_saved' );
		return true;
	}

	/**
	 * Occurs after a new article has been created.
	 * @see http://www.mediawiki.org/wiki/Manual:Hooks/ArticleInsertComplete
	 */
	public function onDiscordArticleInserted( WikiPage $article, $user, $text, $summary, $isminor, $iswatch, $section, $flags, $revision ) {
		global $wgDiscordNotificationAddedArticle, $wgDiscordIncludeDiffSize;
		if ( !$wgDiscordNotificationAddedArticle ) return;

		// Discard notifications from excluded pages
		global $wgDiscordExcludeNotificationsFrom;
		if ( is_array( $wgDiscordExcludeNotificationsFrom ) && count( $wgDiscordExcludeNotificationsFrom ) > 0 ) {
			foreach ( $wgDiscordExcludeNotificationsFrom as &$currentExclude ) {
				if ( 0 === strpos( $article->getTitle(), $currentExclude ) ) return;
			}
		}

		// Do not announce newly added file uploads as articles...
		if ( $article->getTitle()->getNsText() == self::getMessage( 'discordnotifications-file-namespace' ) ) return true;

		$message = sprintf(
			self::getMessage( 'discordnotifications-article-created' ),
			self::getDiscordUserText( $user ),
			self::getDiscordArticleText( $article ),
			$summary == "" ? "" : self::getMessage( 'discordnotifications-summary' ) . $summary );
		if ( $wgDiscordIncludeDiffSize ) {
			$message .= sprintf(
				" (%d " . self::getMessage( 'discordnotifications-bytes' ) . ")",
				$article->getRevision()->getSize() );
		}
		self::pushDiscordNotify( $message, $user, 'article_inserted' );
		return true;
	}

	/**
	 * Occurs after the delete article request has been processed.
	 * @see http://www.mediawiki.org/wiki/Manual:Hooks/ArticleDeleteComplete
	 */
	public static function onDiscordArticleDeleted( WikiPage $article, User $user, $reason, $id, $content, ManualLogEntry $logEntry ) {
		global $wgDiscordNotificationRemovedArticle;
		if ( !$wgDiscordNotificationRemovedArticle ) return;

		global $wgDiscordNotificationShowSuppressed;
		if ( !$wgDiscordNotificationShowSuppressed && $logEntry->getType() != 'delete' ) return;

		// Discard notifications from excluded pages
		global $wgDiscordExcludeNotificationsFrom;
		if ( is_array( $wgDiscordExcludeNotificationsFrom ) && count( $wgDiscordExcludeNotificationsFrom ) > 0 ) {
			foreach ( $wgDiscordExcludeNotificationsFrom as &$currentExclude ) {
				if ( 0 === strpos( $article->getTitle(), $currentExclude ) ) return;
			}
		}

		$message = sprintf(
			self::getMessage( 'discordnotifications-article-deleted' ),
			self::getDiscordUserText( $user ),
			self::getDiscordArticleText( $article ),
			$reason );
		self::pushDiscordNotify( $message, $user, 'article_deleted' );
		return true;
	}

	/**
	 * Occurs after a page has been moved.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/TitleMoveComplete
	 */
	public static function onDiscordArticleMoved( $title, $newtitle, $user, $oldid, $newid, $reason = null ) {
		global $wgDiscordNotificationMovedArticle;
		if ( !$wgDiscordNotificationMovedArticle ) return;

		$message = sprintf(
			self::getMessage( 'discordnotifications-article-moved' ),
			self::getDiscordUserText( $user ),
			self::getDiscordTitleText( $title ),
			self::getDiscordTitleText( $newtitle ),
			$reason );
		self::pushDiscordNotify( $message, $user, 'article_moved' );
		return true;
	}

	/**
	 * Occurs after the protect article request has been processed.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ArticleProtectComplete
	 */
	public static function onDiscordArticleProtected( $article = null, $user = null, $protect = false, $reason = "", $moveonly = false ) {
		global $wgDiscordNotificationProtectedArticle;
		if ( !$wgDiscordNotificationProtectedArticle ) return;

		$message = sprintf(
			self::getMessage( 'discordnotifications-article-protected' ),
			self::getDiscordUserText( $user ),
			$protect ? self::getMessage( 'discordnotifications-article-protected-change' ) : self::getMessage( 'discordnotifications-article-protected-remove' ),
			self::getDiscordArticleText( $article ),
			$reason );
		self::pushDiscordNotify( $message, $user, 'article_protected' );
		return true;
	}

	/**
	 * Called after a user account is created.
	 * @see http://www.mediawiki.org/wiki/Manual:Hooks/AddNewAccount
	 */
	public static function onDiscordNewUserAccount( $user, $byEmail ) {
		global $wgDiscordNotificationNewUser, $wgDiscordShowNewUserEmail, $wgDiscordShowNewUserFullName, $wgDiscordShowNewUserIP;
		if ( !$wgDiscordNotificationNewUser ) return;

		$email = "";
		$realname = "";
		$ipaddress = "";
		try { $email = $user->getEmail();
  } catch ( Exception $e ) {
  }
		try { $realname = $user->getRealName();
  } catch ( Exception $e ) {
  }
		try { $ipaddress = $user->getRequest()->getIP();
  } catch ( Exception $e ) {
  }

		$messageExtra = "";
		if ( $wgDiscordShowNewUserEmail || $wgDiscordShowNewUserFullName || $wgDiscordShowNewUserIP ) {
			$messageExtra = "(";
			if ( $wgDiscordShowNewUserEmail ) $messageExtra .= $email . ", ";
			if ( $wgDiscordShowNewUserFullName ) $messageExtra .= $realname . ", ";
			if ( $wgDiscordShowNewUserIP ) $messageExtra .= $ipaddress . ", ";
			$messageExtra = substr( $messageExtra, 0, -2 ); // Remove trailing ,
			$messageExtra .= ")";
		}

		$message = sprintf(
			self::getMessage( 'discordnotifications-new-user' ),
			self::getDiscordUserText( $user ),
			$messageExtra );
		self::pushDiscordNotify( $message, $user, 'new_user_account' );
		return true;
	}

	/**
	 * Called when a file upload has completed.
	 * @see http://www.mediawiki.org/wiki/Manual:Hooks/UploadComplete
	 */
	public static function onDiscordFileUploaded( $image ) {
		global $wgDiscordNotificationFileUpload;
		if ( !$wgDiscordNotificationFileUpload ) return;

		global $wgWikiUrl, $wgWikiUrlEnding, $wgUser;
		$localFile = $image->getLocalFile();

		# Use bytes, KiB, and MiB, rounded to two decimal places.
		$fsize = $localFile->size;
		$funits = '';
		if ( $localFile->size < 2048 ) {
			$funits = 'bytes';
		} elseif ( $localFile->size < 2048 * 1024 ) {
			$fsize /= 1024;
			$fsize = round( $fsize, 2 );
			$funits = 'KiB';
		} else {
			$fsize /= 1024 * 1024;
			$fsize = round( $fsize, 2 );
			$funits = 'MiB';
		}

		$message = sprintf(
			self::getMessage( 'discordnotifications-file-uploaded' ),
			self::getDiscordUserText( $wgUser->mName ),
			self::parseurl( $wgWikiUrl . $wgWikiUrlEnding . $image->getLocalFile()->getTitle() ),
			$localFile->getTitle(),
			$localFile->getMimeType(),
			$fsize, $funits,
			$localFile->getDescription() );

		self::pushDiscordNotify( $message, $wgUser, 'file_uploaded' );
		return true;
	}

	/**
	 * Occurs after the request to block an IP or user has been processed
	 * @see http://www.mediawiki.org/wiki/Manual:MediaWiki_hooks/BlockIpComplete
	 */
	public static function onDiscordUserBlocked( Block $block, $user ) {
		global $wgDiscordNotificationBlockedUser;
		if ( !$wgDiscordNotificationBlockedUser ) return;

		global $wgWikiUrl, $wgWikiUrlEnding, $wgWikiUrlEndingBlockList;
		$message = sprintf(
			self::getMessage( 'discordnotifications-block-user' ),
			self::getDiscordUserText( $user ),
			self::getDiscordUserText( $block->getTarget() ),
			$block->mReason == "" ? "" : self::getMessage( 'discordnotifications-block-user-reason' ) . " '" . $block->mReason . "'.",
			$block->mExpiry,
			"<" . self::parseurl( $wgWikiUrl . $wgWikiUrlEnding . $wgWikiUrlEndingBlockList ) . "|" . self::getMessage( 'discordnotifications-block-user-list' ) . ">." );
		self::pushDiscordNotify( $message, $user, 'user_blocked' );
		return true;
	}

	/**
	 * Occurs after the user groups (rights) have been changed
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/UserGroupsChanged
	 */
	public static function onDiscordUserGroupsChanged( $user, array $added, array $removed, $performer, $reason, $oldUGMs, $newUGMs ) {
		global $wgDiscordNotificationUserGroupsChanged;
		if ( !$wgDiscordNotificationUserGroupsChanged ) return;

		global $wgWikiUrl, $wgWikiUrlEnding, $wgWikiUrlEndingUserRights;
		$message = sprintf(
			self::getMessage( 'discordnotifications-change-user-groups-with-old' ),
			self::getDiscordUserText( $performer ),
			self::getDiscordUserText( $user->getName() ),
			implode(", ", array_keys($oldUGMs)),
			implode( ", ", $user->getGroups() ),
			"<" . self::parseurl( $wgWikiUrl . $wgWikiUrlEnding . $wgWikiUrlEndingUserRights . self::getDiscordUserText( $performer ) ) . "|" . self::getMessage( 'discordnotifications-view-user-rights' ) . ">." );
		self::pushDiscordNotify( $message, $user, 'user_groups_changed' );
		return true;
	}

	/**
	 * Occurs after the execute() method of an Flow API module
	 */
	public static function onDiscordApiFlowAfterExecute( APIBase $module ) {
		global $wgDiscordNotificationFlow;
		if ( !$wgDiscordNotificationFlow || !ExtensionRegistry::getInstance()->isLoaded( 'Flow' ) ) return;

		global $wgRequest;
		$action = $module->getModuleName();
		$request = $wgRequest->getValues();
		$result = $module->getResult()->getResultData()['flow'][$action];
		if ( $result['status'] != 'ok' ) return;

		// Discard notifications from excluded pages
		global $wgDiscordExcludeNotificationsFrom;
		if ( count( $wgDiscordExcludeNotificationsFrom ) > 0 ) {
			foreach ( $wgDiscordExcludeNotificationsFrom as &$currentExclude ) {
				if ( 0 === strpos( $request['page'], $currentExclude ) ) return;
			}
		}

		global $wgWikiUrl, $wgWikiUrlEnding, $wgUser;
		switch ( $action ) {
			case 'edit-header':
				$message = sprintf(
					self::getMessage( "discordnotifications-flow-edit-header" ),
					self::getDiscordUserText( $wgUser ),
					"<" . self::parseurl( $wgWikiUrl . $wgWikiUrlEnding . $request['page'] ) . "|" . $request['page'] . ">" );
				break;
			case 'edit-post':
				$message = sprintf(
					self::getMessage( "discordnotifications-flow-edit-post" ),
					self::getDiscordUserText( $wgUser ),
					"<" . self::parseurl( $wgWikiUrl . $wgWikiUrlEnding . "Topic:" . $result['workflow'] ) . "|" . self::flowUUIDToTitleText( $result['workflow'] ) . ">" );
				break;
			case 'edit-title':
				$message = sprintf(
					self::getMessage( "discordnotifications-flow-edit-title" ),
					self::getDiscordUserText( $wgUser ),
					$request['etcontent'],
					"<" . self::parseurl( $wgWikiUrl . $wgWikiUrlEnding . 'Topic:' . $result['workflow'] ) . "|" . self::flowUUIDToTitleText( $result['workflow'] ) . ">" );
				break;
			case 'edit-topic-summary':
				$message = sprintf(
					self::getMessage( "discordnotifications-flow-edit-topic-summary" ),
					self::getDiscordUserText( $wgUser ),
					"<" . self::parseurl( $wgWikiUrl . $wgWikiUrlEnding . 'Topic:' . $result['workflow'] ) . "|" . self::flowUUIDToTitleText( $result['workflow'] ) . ">" );
				break;
			case 'lock-topic':
				$message = sprintf(
					self::getMessage( "discordnotifications-flow-lock-topic" ),
					self::getDiscordUserText( $wgUser ),
					self::getMessage( "discordnotifications-flow-lock-topic-" . $request['cotmoderationState'] ),
					"<" . self::parseurl( $wgWikiUrl . $wgWikiUrlEnding . $request['page'] ) . "|" . self::flowUUIDToTitleText( $result['workflow'] ) . ">" );
				break;
			case 'moderate-post':
				$message = sprintf(
					self::getMessage( "discordnotifications-flow-moderate-post" ),
					self::getDiscordUserText( $wgUser ),
					self::getMessage( "discordnotifications-flow-moderate-" . $request['mpmoderationState'] ),
					"<" . self::parseurl( $wgWikiUrl . $wgWikiUrlEnding . $request['page'] ) . "|" . self::flowUUIDToTitleText( $result['workflow'] ) . ">" );
				break;
			case 'moderate-topic':
				$message = sprintf(
					self::getMessage( "discordnotifications-flow-moderate-topic" ),
					self::getDiscordUserText( $wgUser ),
					self::getMessage( "discordnotifications-flow-moderate-" . $request['mtmoderationState'] ),
					"<" . self::parseurl( $wgWikiUrl . $wgWikiUrlEnding . $request['page'] ) . "|" . self::flowUUIDToTitleText( $result['workflow'] ) . ">" );
				break;
			case 'new-topic':
				$message = sprintf(
					self::getMessage( "discordnotifications-flow-new-topic" ),
					self::getDiscordUserText( $wgUser ),
					"<" . self::parseurl( $wgWikiUrl . $wgWikiUrlEnding . "Topic:" . $result['committed']['topiclist']['topic-id'] ) . "|" . $request['nttopic'] . ">",
					"<" . self::parseurl( $wgWikiUrl . $wgWikiUrlEnding . $request['page'] ) . "|" . $request['page'] . ">" );
				break;
			case 'reply':
				$message = sprintf(
					self::getMessage( "discordnotifications-flow-reply" ),
					self::getDiscordUserText( $wgUser ),
					"<" . self::parseurl( $wgWikiUrl . $wgWikiUrlEnding . 'Topic:' . $result['workflow'] ) . "|" . self::flowUUIDToTitleText( $result['workflow'] ) . ">" );
				break;
			default:
				return;
		}
		self::pushDiscordNotify( $message, $wgUser, 'flow' );
	}

	/**
	 * Sends the message into Discord room.
	 * @param $message Message to be sent.
	 * @see https://discordapp.com/developers/docs/resources/webhook#execute-webhook
	 */
	private static function pushDiscordNotify( $message, $user, $action ) {
		global $wgDiscordIncomingWebhookUrl, $wgDiscordFromName, $wgDiscordAvatarUrl, $wgDiscordSendMethod, $wgExcludedPermission, $wgSitename, $wgDiscordAdditionalIncomingWebhookUrls;

		if ( $wgExcludedPermission != "" ) {
			if ( $user->isAllowed( $wgExcludedPermission ) ) {
				return; // Users with the permission suppress notifications
			}
		}

		// Convert " to ' in the message to be sent as otherwise JSON formatting would break.
		$message = str_replace( '"', "'", $message );

		$discordFromName = $wgDiscordFromName;
		if ( $discordFromName == "" ) {
			$discordFromName = $wgSitename;
		}

		$message = preg_replace( "~(<)(http)([^|]*)(\|)([^\>]*)(>)~", "[$5]($2$3)", $message );
		$message = str_replace( [ "\r", "\n" ], '', $message );

		$colour = 11777212;
		switch ( $action ) {
			case 'article_saved':
				$colour = 2993970;
				break;
			case 'user_groups_changed':
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
			case 'flow':
				$colour = 2993970;
				break;
			default:
				$colour = 11777212;
			break;
		}

		$post = sprintf( '{"embeds": [{ "color" : "' . $colour . '" ,"description" : "%s"}], "username": "%s"',
		$message,
		$discordFromName );
		if ( isset( $wgDiscordAvatarUrl ) && !empty( $wgDiscordAvatarUrl ) ) {
			$post .= ', "avatar_url": "' . $wgDiscordAvatarUrl . '"';
		}
		$post .= '}';

		// Use file_get_contents to send the data. Note that you will need to have allow_url_fopen enabled in php.ini for this to work.
		if ( $wgDiscordSendMethod == "file_get_contents" ) {
			self::sendHttpRequest( $wgDiscordIncomingWebhookUrl, $post );
			if ( $wgDiscordAdditionalIncomingWebhookUrls && is_array( $wgDiscordAdditionalIncomingWebhookUrls ) ) {
				for ( $i = 0; $i < count( $wgDiscordAdditionalIncomingWebhookUrls ); ++$i ) {
					self::sendHttpRequest( $wgDiscordAdditionalIncomingWebhookUrls[$i], $post );
				}
			}
		} else {
			// Call the Discord API through cURL (default way). Note that you will need to have cURL enabled for this to work.
			self::sendCurlRequest( $wgDiscordIncomingWebhookUrl, $post );
			if ( $wgDiscordAdditionalIncomingWebhookUrls && is_array( $wgDiscordAdditionalIncomingWebhookUrls ) ) {
				for ( $i = 0; $i < count( $wgDiscordAdditionalIncomingWebhookUrls ); ++$i ) {
					self::sendCurlRequest( $wgDiscordAdditionalIncomingWebhookUrls[$i], $post );
				}
			}
		}
	}

	private static function sendCurlRequest( $url, $postData ) {
		$h = curl_init();
		curl_setopt( $h, CURLOPT_URL, $url );
		curl_setopt( $h, CURLOPT_POST, 1 );
		curl_setopt( $h, CURLOPT_POSTFIELDS, $postData );
		curl_setopt( $h, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $h, CURLOPT_CONNECTTIMEOUT, 10 ); // Set 10 second timeout to connection
		curl_setopt( $h, CURLOPT_TIMEOUT, 10 ); // Set global 10 second timeout to handle all data
		// Commented out lines below. Using default curl settings for host and peer verification.
		//curl_setopt ($h, CURLOPT_SSL_VERIFYHOST, 0);
		//curl_setopt ($h, CURLOPT_SSL_VERIFYPEER, 0);
		// ... Aaand execute the curl script!
		$curl_output = curl_exec( $h );
		curl_close( $h );
	}

	private static function sendHttpRequest( $url, $postData ) {
		$extradata = [
			'http' => [
			'header'  => "Content-type: application/json",
			'method'  => 'POST',
			'content' => $postData,
			],
		];
		$context = stream_context_create( $extradata );
		$result = file_get_contents( $url, false, $context );
	}

	private static function getMessage( $key ) {
		return wfMessage( $key )->inContentLanguage()->text();
	}

	private static function flowUUIDToTitleText( $UUID ) {
		// TODO: Not implemented yet.
		return 'Topic:' . $UUID;
	}
}
