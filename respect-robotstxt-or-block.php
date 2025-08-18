<?php
/**
 * Plugin Name:       Respect the robots.txt or be blocked
 * Description:       Provide a robots.txt to forbid crawling and block the crawlers if they do it anyway
 * Plugin URI:        https://leafext.de/hp/categories/robots-txt/
 * Update URI:        https://github.com/hupe13/respect-robotstxt-or-block/
 * Version:           250818
 * Requires PHP:      8.1
 * Author:            hupe13
 * Author URI:        https://leafext.de/hp/
 * Network:           true
 * License:           GPL v2 or later
 *
 * @package respect-robotstxt-or-block
 **/

// Direktzugriff auf diese Datei verhindern.
defined( 'ABSPATH' ) || die();

define( 'RESPECT_ROBOTS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) ); // respect-robotstxt-or-block/respect-robotstxt-or-block.php

function resprobots_get_table_name() {
	global $wpdb;
	$table_name = $wpdb->base_prefix . 'badcrawler';
	return $table_name;
}

function resprobots_install() {
	global $wpdb;
	$table_name = resprobots_get_table_name();
	resprobots_table_domain_install( $table_name );
}
register_activation_hook( __FILE__, 'resprobots_install' );

/**
 * This function runs when WordPress completes its upgrade process
 * It iterates through each plugin updated to see if ours is included
 *
 * @param $upgrader_object Array
 * @param $options Array
 * https://gist.github.com/junaidtk/dca0f58b5621b87167a9cf344cf86148
 */
function resprobots_upgrade_completed( $upgrader_object, $options ) {
	// If an update has taken place and the updated type is plugins and the plugins element exists
	if ( $options['action'] === 'update' && $options['type'] === 'plugin' && isset( $options['plugins'] ) ) {
		// Iterate through the plugins being updated and check if ours is there
		foreach ( $options['plugins'] as $plugin ) {
			if ( $plugin === RESPECT_ROBOTS_PLUGIN_BASENAME ) {
				if ( ! is_multisite() ) {
					$table_name = resprobots_get_table_name();
					resprobots_table_domain_install( $table_name );
				} else {
					$domains = resprobots_get_domains();
					if ( count( $domains ) > 1 ) {
						foreach ( $domains as $site => $mydomain ) {
							if ( $site !== get_main_site_id() ) {
								$table_name = $wpdb->base_prefix . $site . '_badcrawler';
							} else {
								$table_name = $wpdb->base_prefix . 'badcrawler';
							}
							resprobots_table_domain_install( $table_name );
						}
					} else {
						$table_name = resprobots_get_table_name();
						resprobots_table_domain_install( $table_name );
					}
				}
			}
		}
	}
}
// add_action( 'upgrader_process_complete', 'resprobots_upgrade_completed', 10, 2 );

function resprobots_uninstall() {
	global $wpdb;
	$table_name = resprobots_get_table_name();
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->query(
		$wpdb->prepare(
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange
			'DROP TABLE IF EXISTS %i',
			$table_name
		)
	);
}
// register_uninstall_hook( __FILE__, 'resprobots_uninstall' );

// Add settings to plugin page
function resprobots_add_action_links( $actions ) {
	$actions[] = '<a href="' . esc_url( admin_url( 'options-general.php' ) . '?page=respect-robotstxt-or-block' ) . '">' . esc_html__( 'Settings', 'respect-robotstxt-or-block' ) . '</a>';
	return $actions;
}
add_filter( 'plugin_action_links_' . RESPECT_ROBOTS_PLUGIN_BASENAME, 'resprobots_add_action_links' );

// Add settings to network plugin page
function resprobots_network_add_action_links( $actions, $plugin ) {
	if ( $plugin === RESPECT_ROBOTS_PLUGIN_BASENAME ) {
		$actions[] = '<a href="' . esc_url( admin_url( 'options-general.php' ) . '?page=respect-robotstxt-or-block' ) . '">' . esc_html__( 'Settings', 'respect-robotstxt-or-block' ) . '</a>';
	}
	return $actions;
}
add_filter( 'network_admin_plugin_action_links', 'resprobots_network_add_action_links', 10, 4 );

require_once __DIR__ . '/php/block-the-bot.php';
require_once __DIR__ . '/php/cron.php';
require_once __DIR__ . '/php/multisite.php';

if ( is_admin() ) {
	require_once __DIR__ . '/admin.php';
	require_once __DIR__ . '/admin/resprobots-settings.php';
	require_once __DIR__ . '/admin/resprobots-multi-settings.php';
	require_once __DIR__ . '/github-respect-robotstxt.php';
}

function resprobots_error_log( $reason ) {
	$logfile = get_transient( 'resprobots_logfile' );
	if ( false === $logfile ) {
		$mgt_settings = resprobots_get_domains_mgt();
		$options      = resprobots_get_site_options( $mgt_settings['site'] );
		if ( isset( $options['logfile'] ) && $options['logfile'] !== '' && $options['logfile'] !== false ) {
			$logfile = $options['logfile'];
		} elseif ( true === WP_DEBUG && WP_DEBUG_LOG === true ) {
			$logfile = WP_CONTENT_DIR . '/wp-content/debug.log';
		} elseif ( true === WP_DEBUG ) {
			$logfile = WP_DEBUG_LOG;
		} else {
			$logfile = '';
		}
		set_transient( 'resprobots_logfile', $logfile, DAY_IN_SECONDS );
		resprobots_error_log( 'set transients logfile - ' . $logfile );
	}

	if ( $logfile !== '' ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( '[' . current_time( 'mysql' ) . '] ' . get_site_url() . ': ' . $reason . "\r\n", 3, $logfile );
	}
}

function resprobots_get_options() {
	$defaults           = array(
		'rotate'      => 'daily',
		'next_rotate' => current_time( 'Ymd' ),
		'last_rotate' => '00000000',
		'logfile'     => '',
	);
	$resprobots_options = get_option( 'resprobots_settings', $defaults );
	return $resprobots_options;
}

function resprobots_get_site_options( $site ) {
	$defaults = array(
		'rotate'      => 'daily',
		'next_rotate' => current_time( 'Ymd' ),
		'last_rotate' => '00000000',
		'logfile'     => '',
	);
	if ( get_current_blog_id() === $site ) {
		$switch = false;
	} else {
		$switch = true;
	}
	if ( $switch ) {
		switch_to_blog( $site );
	}
	$resprobots_options = get_option( 'resprobots_settings', $defaults );
	if ( $switch ) {
		restore_current_blog();
	}
	return $resprobots_options;
}
