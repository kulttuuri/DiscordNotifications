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
		global $wgDiscordNotificationWikiUrl, $wgDiscordNotificationWikiUrlEnding, $wgDiscordNotificationWikiUrlEndingUserPage,
			$wgDiscordNotificationWikiUrlEndingBlockUser, $wgDiscordNotificationWikiUrlEndingUserRights,
			$wgDiscordNotificationWikiUrlEndingUserTalkPage, $wgDiscordNotificationWikiUrlEndingUserContributions,
			$wgDiscordIncludeUserUrls;

		$userName = $user->getName();
		$user_url = str_replace( "&", "%26", $userName );
		if ( $wgDiscordIncludeUserUrls ) {
			return sprintf(
				"%s (%s | %s | %s | %s)",
				"<" . self::parseurl( $wgDiscordNotificationWikiUrl . $wgDiscordNotificationWikiUrlEnding . $wgDiscordNotificationWikiUrlEndingUserPage . $user_url ) . "|$userName>",
				"<" . self::parseurl( $wgDiscordNotificationWikiUrl . $wgDiscordNotificationWikiUrlEnding . $wgDiscordNotificationWikiUrlEndingBlockUser . $user_url ) . "|" . self::msg( 'discordnotifications-block' ) . ">",
				"<" . self::parseurl( $wgDiscordNotificationWikiUrl . $wgDiscordNotificationWikiUrlEnding . $wgDiscordNotificationWikiUrlEndingUserRights . $user_url ) . "|" . self::msg( 'discordnotifications-groups' ) . ">",
				"<" . self::parseurl( $wgDiscordNotificationWikiUrl . $wgDiscordNotificationWikiUrlEnding . $wgDiscordNotificationWikiUrlEndingUserTalkPage . $user_url ) . "|" . self::msg( 'discordnotifications-talk' ) . ">",
				"<" . self::parseurl( $wgDiscordNotificationWikiUrl . $wgDiscordNotificationWikiUrlEnding . $wgDiscordNotificationWikiUrlEndingUserContributions . $user_url ) . "|" . self::msg( 'discordnotifications-contribs' ) . ">" );
		} else {
			return "<" . self::parseurl( $wgDiscordNotificationWikiUrl . $wgDiscordNotificationWikiUrlEnding . $wgDiscordNotificationWikiUrlEndingUserPage . $user_url ) . "|$userName>";
		}
	}

	/**
	 * Gets nice HTML text for article containing the link to article page
	 * and also into edit, delete and article history pages.
	 */
	private static function getDiscordArticleText( WikiPage $article, $diff = false ) {
		global $wgDiscordNotificationWikiUrl, $wgDiscordNotificationWikiUrlEnding, $wgDiscordNotificationWikiUrlEndingEditArticle,
			$wgDiscordNotificationWikiUrlEndingDeleteArticle, $wgDiscordNotificationWikiUrlEndingHistory,
			$wgDiscordNotificationWikiUrlEndingDiff, $wgDiscordIncludePageUrls;

		$title = $article->getTitle()->getFullText();
		$title_url = str_replace( "&", "%26", $title );
		$prefix = "<" . $wgDiscordNotificationWikiUrl . $wgDiscordNotificationWikiUrlEnding . $title_url;
		if ( $wgDiscordIncludePageUrls ) {
			$out = sprintf(
				"%s (%s | %s | %s",
				self::parseurl( $prefix ) . "|" . $title . ">",
				self::parseurl( $prefix . "&" . $wgDiscordNotificationWikiUrlEndingEditArticle ) . "|" . self::msg( 'discordnotifications-edit' ) . ">",
				self::parseurl( $prefix . "&" . $wgDiscordNotificationWikiUrlEndingDeleteArticle ) . "|" . self::msg( 'discordnotifications-delete' ) . ">",
				self::parseurl( $prefix . "&" . $wgDiscordNotificationWikiUrlEndingHistory ) . "|" . self::msg( 'discordnotifications-history' ) . ">"/*,
					"move",
					"protect",
					"watch"*/ );
			if ( $diff ) {
				if ( defined( 'MW_VERSION' ) && version_compare( MW_VERSION, '1.31', '>=' ) ) { // Revision::getId was deprecated in MediaWiki 1.31
					$revisionId = $article->getRevisionRecord()->getId();
				} else {
					$revisionId = $article->getRevision()->getID();
				}
				$out .= " | " . self::parseurl( $prefix . "&" . $wgDiscordNotificationWikiUrlEndingDiff . $revisionId ) . "|" . self::msg( 'discordnotifications-diff' ) . ">)";
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
		global $wgDiscordNotificationWikiUrl, $wgDiscordNotificationWikiUrlEnding, $wgDiscordNotificationWikiUrlEndingEditArticle,
			$wgDiscordNotificationWikiUrlEndingDeleteArticle, $wgDiscordNotificationWikiUrlEndingHistory,
			$wgDiscordIncludePageUrls;

		$titleName = $title->getFullText();
		$title_url = str_replace( "&", "%26", $titleName );
		if ( $wgDiscordIncludePageUrls ) {
			return sprintf(
				"%s (%s | %s | %s)",
				"<" . self::parseurl( $wgDiscordNotificationWikiUrl . $wgDiscordNotificationWikiUrlEnding . $title_url ) . "|" . $titleName . ">",
				"<" . self::parseurl( $wgDiscordNotificationWikiUrl . $wgDiscordNotificationWikiUrlEnding . $title_url . "&" . $wgDiscordNotificationWikiUrlEndingEditArticle ) . "|" . self::msg( 'discordnotifications-edit' ) . ">",
				"<" . self::parseurl( $wgDiscordNotificationWikiUrl . $wgDiscordNotificationWikiUrlEnding . $title_url . "&" . $wgDiscordNotificationWikiUrlEndingDeleteArticle ) . "|" . self::msg( 'discordnotifications-delete' ) . ">",
				"<" . self::parseurl( $wgDiscordNotificationWikiUrl . $wgDiscordNotificationWikiUrlEnding . $title_url . "&" . $wgDiscordNotificationWikiUrlEndingHistory ) . "|" . self::msg( 'discordnotifications-history' ) . ">"/*,
						"move",
						"protect",
						"watch"*/ );
		} else {
			return "<" . self::parseurl( $wgDiscordNotificationWikiUrl . $wgDiscordNotificationWikiUrlEnding . $title_url ) . "|" . $titleName . ">";
		}
	}

	/**
	 * Returns whether the given title should be excluded
	 */
	private static function titleIsExcluded( $title ) {
		global $wgDiscordExcludeNotificationsFrom;
		if ( is_array( $wgDiscordExcludeNotificationsFrom ) && count( $wgDiscordExcludeNotificationsFrom ) > 0 ) {
			foreach ( $wgDiscordExcludeNotificationsFrom as &$currentExclude ) {
				if ( 0 === strpos( $title, $currentExclude ) ) return true;
			}
		}
		return false;
	}

	/**
	 * Register different hooks depending on MediaWiki version
	 */
	public static function registerExtraHooks() {
		global $wgHooks;
		if ( defined( 'MW_VERSION' ) && version_compare( MW_VERSION, '1.35', '>=' ) ) {
			$wgHooks['PageSaveComplete'][] = 'DiscordNotificationsCore::onDiscordPageSaveComplete';
		} else {
			$wgHooks['PageContentSaveComplete'][] = 'DiscordNotificationsCore::onDiscordArticleSaved';
			$wgHooks['PageContentInsertComplete'][] = 'DiscordNotificationsCore::onDiscordArticleInserted';
		}
	}

	/**
	 * Occurs after an article has been updated.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/PageSaveComplete
	 */
	public static function onDiscordPageSaveComplete( $wikiPage, $user, $summary, $flags, $revisionRecord, $editResult ) {
		global $wgDiscordNotificationEditedArticle, $wgDiscordIgnoreMinorEdits,
			$wgDiscordNotificationAddedArticle, $wgDiscordIncludeDiffSize;
		$isNew = (bool)( $flags & EDIT_NEW );

		if ( !$wgDiscordNotificationEditedArticle && !$isNew ) return true;
		if ( !$wgDiscordNotificationAddedArticle && $isNew ) return true;
		if ( self::titleIsExcluded( $wikiPage->getTitle() ) ) return true;

		// Do not announce newly added file uploads as articles...
		if ( $wikiPage->getTitle()->getNsText() && $wikiPage->getTitle()->getNsText() == self::msg( 'discordnotifications-file-namespace' ) ) return true;

		if ( $isNew ) {
			$message = self::msg( 'discordnotifications-article-created',
			self::getDiscordUserText( $user ),
			self::getDiscordArticleText( $wikiPage ),
			$summary == "" ? "" : wfMessage( 'discordnotifications-summary' )->plaintextParams( $summary ) );
			if ( $wgDiscordIncludeDiffSize ) {
				$message .= " (" . self::msg( 'discordnotifications-bytes', $revisionRecord->getSize() ) . ")";
			}
			self::pushDiscordNotify( $message, $user, 'article_inserted' );
		} else {
			$isMinor = (bool)( $flags & EDIT_MINOR );
			// Skip minor edits if user wanted to ignore them
			if ( $isMinor && $wgDiscordIgnoreMinorEdits ) return true;

			$message = self::msg(
				'discordnotifications-article-saved',
				self::getDiscordUserText( $user ),
				$isMinor == true ? self::msg( 'discordnotifications-article-saved-minor-edits' ) : self::msg( 'discordnotifications-article-saved-edit' ),
				self::getDiscordArticleText( $wikiPage, true ),
				$summary == "" ? "" : wfMessage( 'discordnotifications-summary' )->plaintextParams( $summary ) );
			if ( $wgDiscordIncludeDiffSize ) {
				$message .= ' (' . self::msg( 'discordnotifications-bytes',
					$revisionRecord->getSize() - MediaWiki\MediaWikiServices::getInstance()->getRevisionLookup()->getPreviousRevision( $revisionRecord )->getSize() ) . ')';
			}
			self::pushDiscordNotify( $message, $user, 'article_saved' );
		}
		return true;
	}

	/**
	 * Occurs after the save page request has been processed.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/PageContentSaveComplete
	 */
	public static function onDiscordArticleSaved( WikiPage $article, $user, $content, $summary, $isMinor, $isWatch, $section, $flags, $revision, $status, $baseRevId ) {
		global $wgDiscordNotificationEditedArticle;
		global $wgDiscordIgnoreMinorEdits, $wgDiscordIncludeDiffSize;
		if ( !$wgDiscordNotificationEditedArticle ) return;

		if ( self::titleIsExcluded( $article->getTitle() ) ) return;

		// Skip new articles that have view count below 1. Adding new articles is already handled in article_added function and
		// calling it also here would trigger two notifications!
		$isNew = $status->value['new']; // This is 1 if article is new
		if ( $isNew == 1 ) {
			return true;
		}

		// Skip minor edits if user wanted to ignore them
		if ( $isMinor && $wgDiscordIgnoreMinorEdits ) return;

		// Skip edits that are just refreshing the page
		if ( $article->getRevision()->getPrevious() == null || Revision::getRevisionStore()->getPreviousRevision() == null || !$revision || is_null( $status->getValue()['revision'] ) ) {
			return;
		}

		$message = wfMessage( 'discordnotifications-article-saved' )->plaintextParams(
			self::getDiscordUserText( $user ),
			$isMinor == true ? self::msg( 'discordnotifications-article-saved-minor-edits' ) : self::msg( 'discordnotifications-article-saved-edit' ),
			self::getDiscordArticleText( $article, true ),
			$summary == "" ? "" : wfMessage( 'discordnotifications-summary' )->plaintextParams( $summary )->inContentLanguage()->plain()
		)->inContentLanguage()->text();
		if ( $wgDiscordIncludeDiffSize ) {
			$message .= ' (' . self::msg( 'discordnotifications-bytes',
				$article->getRevision()->getSize() - $article->getRevision()->getPrevious()->getSize() ) . ')';
		}
		self::pushDiscordNotify( $message, $user, 'article_saved' );
		return true;
	}

	/**
	 * Occurs after a new article has been created.
	 * @see http://www.mediawiki.org/wiki/Manual:Hooks/ArticleInsertComplete
	 */
	public static function onDiscordArticleInserted( WikiPage $article, $user, $text, $summary, $isminor, $iswatch, $section, $flags, $revision ) {
		global $wgDiscordNotificationAddedArticle, $wgDiscordIncludeDiffSize;
		if ( !$wgDiscordNotificationAddedArticle ) return;

		if ( self::titleIsExcluded( $article->getTitle() ) ) return;

		// Do not announce newly added file uploads as articles...
		if ( $article->getTitle()->getNsText() == self::msg( 'discordnotifications-file-namespace' ) ) return true;

		$message = wfMessage( 'discordnotifications-article-created' )->plaintextParams(
			self::getDiscordUserText( $user ),
			self::getDiscordArticleText( $article ),
			$summary == "" ? "" : wfMessage( 'discordnotifications-summary' )->plaintextParams( $summary )->inContentLanguage()->plain()
		)->inContentLanguage()->text();
		if ( $wgDiscordIncludeDiffSize ) {
			if ( defined( 'MW_VERSION' ) && version_compare( MW_VERSION, '1.31', '>=' ) ) {
				// WikiPage::getRevision was deprecated in MediaWiki 1.35
				// Revision::getSize was deprecated in MediaWiki 1.31
				$size = $article->getRevisionRecord()->getSize();
			} else {
				$size = $article->getRevision()->getSize();
			}
			$message .= " (" . self::msg( 'discordnotifications-bytes', $size ) . ")";
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

		if ( self::titleIsExcluded( $article->getTitle() ) ) return;

		$message = wfMessage( 'discordnotifications-article-deleted' )->plaintextParams(
			self::getDiscordUserText( $user ),
			self::getDiscordArticleText( $article ),
			$reason
		)->inContentLanguage()->text();
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

		$message = self::msg( 'discordnotifications-article-moved',
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

		$message = self::msg( 'discordnotifications-article-protected',
			self::getDiscordUserText( $user ),
			$protect ? self::msg( 'discordnotifications-article-protected-change' ) : self::msg( 'discordnotifications-article-protected-remove' ),
			self::getDiscordArticleText( $article ),
			$reason );
		self::pushDiscordNotify( $message, $user, 'article_protected' );
		return true;
	}

	/**
	 * Occurs after page has been imported into wiki.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/AfterImportPage
	 */
	public static function onDiscordAfterImportPage( $title = null, $origTitle = null, $revCount = null, $sRevCount = null, $pageInfo = null ) {
		global $wgDiscordNotificationAfterImportPage;
		if ( !$wgDiscordNotificationAfterImportPage ) return;

		$message = self::msg( 'discordnotifications-import-complete',
			self::getDiscordTitleText( $title ) );
		self::pushDiscordNotify( $message, null, 'import_complete' );
		return true;
	}

	/**
	 * Called after a user account is created.
	 * @see http://www.mediawiki.org/wiki/Manual:Hooks/AddNewAccount
	 */
	public static function onDiscordNewUserAccount( $user, $byEmail ) {
		global $wgDiscordNotificationNewUser, $wgDiscordShowNewUserFullName;

		// Disable reporting of new user email and IP address
		//global $wgDiscordShowNewUserEmail, $wgDiscordShowNewUserIP;
		$wgDiscordShowNewUserEmail = false;
		$wgDiscordShowNewUserIP = false;
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

		$message = self::msg( 'discordnotifications-new-user',
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

		global $wgDiscordNotificationWikiUrl, $wgDiscordNotificationWikiUrlEnding;
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

		$user = RequestContext::getMain()->getUser();

		$message = self::msg( 'discordnotifications-file-uploaded',
			self::getDiscordUserText( $user ),
			self::parseurl( $wgDiscordNotificationWikiUrl . $wgDiscordNotificationWikiUrlEnding . $image->getLocalFile()->getTitle() ),
			$localFile->getTitle(),
			$localFile->getMimeType(),
			$fsize, $funits,
			$localFile->getDescription() );

		self::pushDiscordNotify( $message, $user, 'file_uploaded' );
		return true;
	}

	/**
	 * Occurs after the request to block an IP or user has been processed
	 * @see http://www.mediawiki.org/wiki/Manual:MediaWiki_hooks/BlockIpComplete
	 */
	public static function onDiscordUserBlocked( Block $block, $user ) {
		global $wgDiscordNotificationBlockedUser;
		if ( !$wgDiscordNotificationBlockedUser ) return;

		global $wgDiscordNotificationWikiUrl, $wgDiscordNotificationWikiUrlEnding, $wgDiscordNotificationWikiUrlEndingBlockList;
		$mReason = "";
		if ( defined( 'MW_VERSION' ) && version_compare( MW_VERSION, '1.35', '>=' ) ) {  // DatabaseBlock::$mReason was made protected in MW 1.35
			$mReason = $block->getReasonComment()->text;
		} else {
			$mReason = $block->mReason;
		}

		$message = self::msg( 'discordnotifications-block-user',
			self::getDiscordUserText( $user ),
			self::getDiscordUserText( $block->getTarget() ),
			$mReason == "" ? "" : self::msg( 'discordnotifications-block-user-reason' ) . " '" . $mReason . "'.",
			$block->mExpiry,
			"<" . self::parseurl( $wgDiscordNotificationWikiUrl . $wgDiscordNotificationWikiUrlEnding . $wgDiscordNotificationWikiUrlEndingBlockList ) . "|" . self::msg( 'discordnotifications-block-user-list' ) . ">." );
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

		global $wgDiscordNotificationWikiUrl, $wgDiscordNotificationWikiUrlEnding, $wgDiscordNotificationWikiUrlEndingUserRights;
		$message = self::msg( 'discordnotifications-change-user-groups-with-old',
			self::getDiscordUserText( $performer ),
			self::getDiscordUserText( $user ),
			implode( ", ", array_keys( $oldUGMs ) ),
			implode( ", ", $user->getGroups() ),
			"<" . self::parseurl( $wgDiscordNotificationWikiUrl . $wgDiscordNotificationWikiUrlEnding . $wgDiscordNotificationWikiUrlEndingUserRights . self::getDiscordUserText( $performer ) ) . "|" . self::msg( 'discordnotifications-view-user-rights' ) . ">." );
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

		if ( self::titleIsExcluded( $request['page'] ) ) return;

		global $wgDiscordNotificationWikiUrl, $wgDiscordNotificationWikiUrlEnding;

		$user = RequestContext::getMain()->getUser();

		switch ( $action ) {
			case 'edit-header':
				$message = self::msg( "discordnotifications-flow-edit-header",
					self::getDiscordUserText( $user ),
					"<" . self::parseurl( $wgDiscordNotificationWikiUrl . $wgDiscordNotificationWikiUrlEnding . $request['page'] ) . "|" . $request['page'] . ">" );
				break;
			case 'edit-post':
				$message = self::msg( "discordnotifications-flow-edit-post",
					self::getDiscordUserText( $user ),
					"<" . self::parseurl( $wgDiscordNotificationWikiUrl . $wgDiscordNotificationWikiUrlEnding . "Topic:" . $result['workflow'] ) . "|" . self::flowUUIDToTitleText( $result['workflow'] ) . ">" );
				break;
			case 'edit-title':
				$message = self::msg( "discordnotifications-flow-edit-title",
					self::getDiscordUserText( $user ),
					$request['etcontent'],
					"<" . self::parseurl( $wgDiscordNotificationWikiUrl . $wgDiscordNotificationWikiUrlEnding . 'Topic:' . $result['workflow'] ) . "|" . self::flowUUIDToTitleText( $result['workflow'] ) . ">" );
				break;
			case 'edit-topic-summary':
				$message = self::msg( "discordnotifications-flow-edit-topic-summary",
					self::getDiscordUserText( $user ),
					"<" . self::parseurl( $wgDiscordNotificationWikiUrl . $wgDiscordNotificationWikiUrlEnding . 'Topic:' . $result['workflow'] ) . "|" . self::flowUUIDToTitleText( $result['workflow'] ) . ">" );
				break;
			case 'lock-topic':
				$message = self::msg( "discordnotifications-flow-lock-topic",
					self::getDiscordUserText( $user ),
					// Messages that can be used here:
					// * discordnotifications-flow-lock-topic-lock
					// * discordnotifications-flow-lock-topic-unlock
					self::msg( "discordnotifications-flow-lock-topic-" . $request['cotmoderationState'] ),
					"<" . self::parseurl( $wgDiscordNotificationWikiUrl . $wgDiscordNotificationWikiUrlEnding . $request['page'] ) . "|" . self::flowUUIDToTitleText( $result['workflow'] ) . ">" );
				break;
			case 'moderate-post':
				$message = self::msg( "discordnotifications-flow-moderate-post",
					self::getDiscordUserText( $user ),
					// Messages that can be used here:
					// * discordnotifications-flow-moderate-hide
					// * discordnotifications-flow-moderate-unhide
					// * discordnotifications-flow-moderate-suppress
					// * discordnotifications-flow-moderate-unsuppress
					// * discordnotifications-flow-moderate-delete
					// * discordnotifications-flow-moderate-undelete
					self::msg( "discordnotifications-flow-moderate-" . $request['mpmoderationState'] ),
					"<" . self::parseurl( $wgDiscordNotificationWikiUrl . $wgDiscordNotificationWikiUrlEnding . $request['page'] ) . "|" . self::flowUUIDToTitleText( $result['workflow'] ) . ">" );
				break;
			case 'moderate-topic':
				$message = self::msg( "discordnotifications-flow-moderate-topic",
					self::getDiscordUserText( $user ),
					// Messages that can be used here:
					// * discordnotifications-flow-moderate-hide
					// * discordnotifications-flow-moderate-unhide
					// * discordnotifications-flow-moderate-suppress
					// * discordnotifications-flow-moderate-unsuppress
					// * discordnotifications-flow-moderate-delete
					// * discordnotifications-flow-moderate-undelete
					self::msg( "discordnotifications-flow-moderate-" . $request['mtmoderationState'] ),
					"<" . self::parseurl( $wgDiscordNotificationWikiUrl . $wgDiscordNotificationWikiUrlEnding . $request['page'] ) . "|" . self::flowUUIDToTitleText( $result['workflow'] ) . ">" );
				break;
			case 'new-topic':
				$message = self::msg( "discordnotifications-flow-new-topic",
					self::getDiscordUserText( $user ),
					"<" . self::parseurl( $wgDiscordNotificationWikiUrl . $wgDiscordNotificationWikiUrlEnding . "Topic:" . $result['committed']['topiclist']['topic-id'] ) . "|" . $request['nttopic'] . ">",
					"<" . self::parseurl( $wgDiscordNotificationWikiUrl . $wgDiscordNotificationWikiUrlEnding . $request['page'] ) . "|" . $request['page'] . ">" );
				break;
			case 'reply':
				$message = self::msg( "discordnotifications-flow-reply",
					self::getDiscordUserText( $user ),
					"<" . self::parseurl( $wgDiscordNotificationWikiUrl . $wgDiscordNotificationWikiUrlEnding . 'Topic:' . $result['workflow'] ) . "|" . self::flowUUIDToTitleText( $result['workflow'] ) . ">" );
				break;
			default:
				return;
		}
		self::pushDiscordNotify( $message, $user, 'flow' );
	}

	/**
	 * Sends the message into Discord room.
	 * @param $message Message to be sent.
	 * @see https://discordapp.com/developers/docs/resources/webhook#execute-webhook
	 */
	private static function pushDiscordNotify( $message, $user, $action ) {
		global $wgDiscordIncomingWebhookUrl, $wgDiscordFromName, $wgDiscordAvatarUrl, $wgDiscordSendMethod, $wgDiscordExcludedPermission, $wgSitename, $wgDiscordAdditionalIncomingWebhookUrls;

		if ( isset( $wgDiscordExcludedPermission ) && $wgDiscordExcludedPermission != "" ) {
			if ( $user && $user->isAllowed( $wgDiscordExcludedPermission ) ) {
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
			case 'import_complete':
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
		curl_setopt( $h, CURLOPT_HTTPHEADER, [
			'Content-Type: application/json',
			'Content-Length: ' . strlen( $postData )
		] ); // Set Content-Type to application/json
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

	private static function msg( $key, ...$params ) {
		if ( $params ) {
			return wfMessage( $key, ...$params )->inContentLanguage()->text();
		} else {
			return wfMessage( $key )->inContentLanguage()->text();
		}
	}

	private static function flowUUIDToTitleText( $UUID ) {
		$UUID = \Flow\Model\UUID::create( $UUID );
		$collection = \Flow\Collection\PostCollection::newFromId( $UUID );
		$revision = $collection->getLastRevision();
		return $revision->getContent( 'topic-title-plaintext' );
	}
}
