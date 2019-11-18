<?php
if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'DiscordNotifications' );
	$wgMessagesDirs['DiscordNotifications'] = __DIR__ . '/i18n';
	wfWarn(
		'Deprecated PHP entry point used for DiscordNotifications extension. ' .
		'Please use wfLoadExtension instead, ' .
		'see https://www.mediawiki.org/wiki/Extension_registration for more details.'
	);
	return;
} else {
	die( 'This version of the DiscordNotifications extension requires MediaWiki 1.25+' );
}
