<?php
/**
 * Documentation HELP
 *
 * @package respect-robotstxt-or-block
 **/

// Direktzugriff auf diese Datei verhindern.
defined( 'ABSPATH' ) || die();

function resprobots_mgt_domains_help( $domains ) {
	$text          = '<h3>' . __( 'Domains', 'respect-robotstxt-or-block' ) . '</h3>';
	$count_domains = count( $domains );
	$text         .= '<p>' . sprintf(
		/* translators: %s is a count. */
		__( 'You have %s domains on your multisite.', 'respect-robotstxt-or-block' ),
		$count_domains
	) . '</p>';
	$text .= '<p><ul>';
	foreach ( $domains as $site => $mydomain ) {
		if ( resprobots_many_domains() ) {
			$mgturl = get_site_url( $site ) . '/wp-admin/options-general.php?page=respect-robotstxt-or-block&tab=settings';
			$text  .= '<li style="list-style-type:disc;margin-left: 1.5em;"> <a href="' . $mgturl . '">' . $mydomain . '</a></li>';
		} else {
			$text .= '<li style="list-style-type:disc;margin-left: 1.5em;">' . $mydomain . '</li>';
		}
	}
	$text .= '</ul></p>';
	$text .= '<p>' . __( 'You can have one table for all domains or one table for each domain.', 'respect-robotstxt-or-block' ) . '</p>';
	return $text;
}
