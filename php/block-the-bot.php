<?php
/**
 * Function resprobots_badcrawler
 *
 * @package respect-robotstxt-or-block
 */

// Direktzugriff auf diese Datei verhindern.
defined( 'ABSPATH' ) || die();

function resprobots_get_robots_txt( $posts ) {
	global $wp;
	global $wpdb;
	$resprobots_robots_slug = 'robots-check'; // URL slug of the robots.txt fake page
	if ( ( strtolower( $wp->request ) === $resprobots_robots_slug || strtolower( $wp->request ) === 'robots.txt' ) ) {
		$agent      = getenv( 'HTTP_USER_AGENT' );
		$table_name = resprobots_transient_table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$badbot = $wpdb->query(
			$wpdb->prepare(
				"UPDATE %i SET robots = robots + 1 WHERE type = 'bot' AND INSTR('" . esc_sql( $agent ) . "', browser)",
				$table_name
			)
		);
		if ( $badbot > 0 ) {
			header( 'Content-Type: text/plain; charset=UTF-8' );
			echo "User-agent: *\r\n" .
			'Disallow: /' . "\r\n";
			exit;
		}

		$ip   = getenv( 'REMOTE_ADDR' );
		$host = gethostbyaddr( $ip );
		if ( $host !== $ip && $host !== false ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$badbot = $wpdb->query(
				$wpdb->prepare(
					"UPDATE %i SET robots = robots + 1 WHERE type = 'name' AND INSTR('" . esc_sql( $host ) . "', browser)",
					$table_name
				)
			);

			if ( $badbot > 0 ) {
				header( 'Content-Type: text/plain; charset=UTF-8' );
				echo "User-agent: *\r\n" .
				'Disallow: /' . "\r\n";
				exit;
			}
		}

		// Default 'WordPress/' . get_bloginfo( 'version' ) . ‘; ‘ . get_bloginfo( 'url' ).
		$response = wp_remote_get( 'https://' . getenv( 'HTTP_HOST' ) . '/robots.txt' );
		if ( is_array( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
			header( 'Content-Type: text/plain; charset=UTF-8' );
			echo esc_html( $response['body'] ); // use the content
			exit;
		}

		header( 'Content-Type: text/plain; charset=UTF-8' );
		do_robots();
		exit;
	}
	return $posts;
}
add_filter( 'the_posts', 'resprobots_get_robots_txt', -10 );

// resprobots_badcrawler
function resprobots_badcrawler() {
	global $user_login;
	global $wpdb;

	$agent = getenv( 'HTTP_USER_AGENT' );
	$ip    = getenv( 'REMOTE_ADDR' );
	$file  = getenv( 'REQUEST_URI' );

	if ( ! is_admin()
	&& $ip !== '127.0.0.1'
	&& $ip !== false
	&& $user_login === ''
	&& $agent !== ''
	&& strpos( $agent, 'WordPress' ) === false
	&& strpos( $agent, get_site_url() ) === false
	&& strpos( $file, 'robots.txt' ) === false
	&& strpos( $file, 'robots-check' ) === false
	) {
		$table_name = resprobots_transient_table();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$badbot = $wpdb->query(
			$wpdb->prepare(
				"UPDATE %i SET count = count + 1 WHERE type = 'bot' AND INSTR('" . esc_sql( $agent ) . "', browser)",
				$table_name
			)
		);
		if ( $badbot > 0 ) {
			resprobots_error_log( 'BadCrawler: ' . $ip . ' - ' . $agent );
			status_header( 403 );
			exit();
		}

		$host = gethostbyaddr( $ip );
		if ( $host !== $ip && $host !== false ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$badbot = $wpdb->query(
				$wpdb->prepare(
					"UPDATE %i SET count = count + 1 WHERE type = 'name' AND INSTR('" . esc_sql( $host ) . "', browser)",
					$table_name
				)
			);
			if ( $badbot > 0 ) {
				resprobots_error_log( 'BadHostname: ' . $ip . ' - ' . $host );
				status_header( 403 );
				exit();
			}
		}
	}
}
add_action( 'init', 'resprobots_badcrawler', 8 );

function resprobots_transient_table() {
	$table_name = get_transient( 'resprobots_badcrawler_table' );
	if ( false === $table_name ) {
		$mgt_settings = resprobots_get_domains_mgt();
		$table_name   = $mgt_settings['table'];
		set_transient( 'resprobots_badcrawler_table', $table_name, DAY_IN_SECONDS );
		resprobots_error_log( 'set transients tablename - ' . $table_name );
	}
	return $table_name;
}
