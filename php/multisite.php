<?php
/**
 * Multisite functions
 *
 * @package respect-robotstxt-or-block
 **/

// Direktzugriff auf diese Datei verhindern.
defined( 'ABSPATH' ) || die();

function resprobots_multi_get_options() {
	$defaults                 = array(
		'sites' => get_main_site_id(),
	);
	$resprobots_multi_options = get_option( 'resprobots_multi_settings', $defaults );
	if ( ! is_main_site() && is_multisite() ) {
		switch_to_blog( get_main_site_id() );
		$resprobots_multi_options = get_option( 'resprobots_multi_settings', $defaults );
		restore_current_blog();
	}
	return $resprobots_multi_options;
}

function resprobots_get_domains_mgt() {
	global $wpdb;
	if ( ! is_multisite() ) {
		$domain = preg_replace( '|https?://|', '', get_option( 'siteurl' ) );
		$slash  = strpos( $domain, '/' );
		if ( $slash ) {
			$domain = substr( $domain, 0, $slash );
		}
		$table_name   = $wpdb->prefix . 'badcrawler';
		$mgt_site     = get_current_blog_id();
		$mgt_settings = array(
			'site'     => get_current_blog_id(),
			'table'    => $table_name,
			'domain'   => $domain,
			'mgt-site' => admin_url( 'options-general.php' ) . '?page=respect-robotstxt-or-block&tab=settings',
		);
	} elseif ( resprobots_many_domains() ) { // Multisite
		// every domain has its table
		$domains = resprobots_get_domains();

		$this_site = get_site();
		$mgt_id    = array_search( $this_site->domain, $domains, true );
		if ( $mgt_id !== get_main_site_id() ) {
			$table_name = $wpdb->base_prefix . $mgt_id . '_badcrawler';
		} else {
			$table_name = $wpdb->base_prefix . 'badcrawler';
		}
		$mgt_settings = array(
			'site'     => $mgt_id,
			'table'    => $table_name,
			'domain'   => $domains[ $mgt_id ],
			'mgt-site' => get_site_url( $mgt_id ) . '/wp-admin/options-general.php?page=respect-robotstxt-or-block&tab=settings',
		);
	} else { // one table for all
		$table_name   = $wpdb->base_prefix . 'badcrawler';
		$mgt_site     = get_site( get_main_site_id() );
		$mgt_settings = array(
			'site'     => get_main_site_id(),
			'table'    => $table_name,
			'domain'   => $mgt_site->domain,
			'mgt-site' => get_site_url( get_main_site_id() ) . '/wp-admin/options-general.php?page=respect-robotstxt-or-block&tab=settings',
		);
	}
	return $mgt_settings;
}

function resprobots_many_domains() {
	$multi_options = resprobots_multi_get_options();
	if ( strpos( $multi_options['sites'], ',' ) !== false ) {
		return true;
	}
	return false;
}

function resprobots_get_domains() {
	$domains = array();
	foreach ( get_sites() as $site ) {
		$domains[ $site->blog_id ] = $site->domain;
	}
	$domains = array_unique( $domains );
	return $domains;
}
