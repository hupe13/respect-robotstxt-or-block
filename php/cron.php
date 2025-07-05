<?php
/**
 *  Respect the robots.txt or be blocked cron job
 *
 * @package respect-robotstxt-or-block
 **/

// Direktzugriff auf diese Datei verhindern.
defined( 'ABSPATH' ) || die();

function resprobots_rotate() {
	if ( ! is_multisite() ) {
		resprobots_rotate_on_site( get_current_blog_id() );
	} else {
		$domains = resprobots_get_domains();
		if ( count( $domains ) > 1 ) {
			foreach ( $domains as $site => $mydomain ) {
				resprobots_rotate_on_site( $site );
			}
		} else {
			resprobots_rotate_on_site( get_current_blog_id() );
		}
	}
}
add_action( 'resprobots_rotate', 'resprobots_rotate' );

function resprobots_rotate_on_site( $site ) {
	if ( get_current_blog_id() === $site ) {
		$switch = false;
	} else {
		$switch = true;
	}
	if ( $switch ) {
		switch_to_blog( $site );
	}
	$mgt_settings = resprobots_get_domains_mgt();
	$table_name   = $mgt_settings['table'];
	resprobots_rotate_table( $table_name );
	if ( $switch ) {
		restore_current_blog();
	}
}

function resprobots_rotate_table( $table_name ) {
	$resprobots_options = resprobots_get_options();
	resprobots_error_log( 'Checking - ' . $resprobots_options['rotate'] . ' - Next rotate: ' . $resprobots_options['next_rotate'] . ' ' . $table_name );
	if ( $resprobots_options['next_rotate'] <= current_time( 'Ymd' ) ) {
		global $wpdb;
		$entries = $wpdb->get_results(
			$wpdb->prepare(
				'UPDATE %i SET last=last, count_2=count_1, robots_2=robots_1, count_1=count, robots_1=robots, count=0, robots=0 WHERE 1',
				$table_name
			)
		);

		$resprobots_options['next_rotate'] = resprobots_next_rotate( $resprobots_options['rotate'] );
		$resprobots_options['last_rotate'] = current_time( 'Ymd' );

		if ( get_option( 'resprobots_settings' ) !== false ) {
			update_option( 'resprobots_settings', $resprobots_options );
		} else {
			add_option( 'resprobots_settings', $resprobots_options );
		}
		resprobots_error_log( 'Rotating - ' . $resprobots_options['rotate'] . ' - Next rotate: ' . $resprobots_options['next_rotate'] . ' ' . $table_name );
	}
}

if ( is_main_site() ) {
	if ( ! wp_next_scheduled( 'resprobots_rotate' ) ) {
		$datetime = new DateTime( 'tomorrow 00.05.00', new DateTimeZone( wp_timezone_string() ) );
		wp_schedule_event( $datetime->getTimestamp(), 'daily', 'resprobots_rotate' );
	}
}

function resprobots_next_rotate( $cycle ) {
	switch ( $cycle ) {
		case 'monthly':
			$datetime   = new DateTime( 'first day of next month midnight', new DateTimeZone( wp_timezone_string() ) );
			$nextrotate = $datetime->format( 'Ymd' );
			break;
		case 'weekly':
			$day        = (int) get_option( 'start_of_week' );
			$rotate_on  = $day === 0 ? 'Sunday' : 'Monday';
			$datetime   = new DateTime( $rotate_on . ' next week midnight', new DateTimeZone( wp_timezone_string() ) );
			$nextrotate = $datetime->format( 'Ymd' );
			break;
		case 'daily':
		default:
			$datetime   = new DateTime( 'tomorrow midnight', new DateTimeZone( wp_timezone_string() ) );
			$nextrotate = $datetime->format( 'Ymd' );
	}
	return $nextrotate;
}
