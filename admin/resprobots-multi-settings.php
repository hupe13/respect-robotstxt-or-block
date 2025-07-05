<?php
/**
 *  Respect the robots.txt or be blocked Settings
 *
 * @package respect-robotstxt-or-block
 **/

// Direktzugriff auf diese Datei verhindern.
defined( 'ABSPATH' ) || die();

// Init settings
function resprobots_multi_init() {
	add_settings_section( 'resprobots_multi_settings', '', '', 'resprobots_multi_settings' );
	add_settings_field( 'resprobots_multi_settings[sites]', __( 'manage one table for all domains or one table for each domain', 'respect-robotstxt-or-block' ), 'resprobots_multi_form', 'resprobots_multi_settings', 'resprobots_multi_settings', 'sites' );
	register_setting( 'resprobots_multi_settings', 'resprobots_multi_settings', 'resprobots_multi_validate' );
}
add_action( 'admin_init', 'resprobots_multi_init' );

// Baue Abfrage der Params
function resprobots_multi_form( $field ) {
	switch ( $field ) {
		case 'sites':
			$multi_options = resprobots_multi_get_options();
			$falsevalue    = (string) get_main_site_id();
			if ( strpos( $multi_options['sites'], ',' ) !== false ) {
				$multi     = true;
				$truevalue = $multi_options['sites'];
			} else {
				$multi     = false;
				$domains   = resprobots_get_domains();
				$truevalue = implode( ',', array_keys( $domains ) );
			}
			echo '<input type="radio" name="resprobots_multi_settings[sites]" value="' . esc_html( $falsevalue ) . '"';
			checked( $multi === false );
			echo '> ' . esc_html__( 'one table for all', 'respect-robotstxt-or-block' ) . ' &nbsp;&nbsp; ';

			echo '<input type="radio" name="resprobots_multi_settings[sites]" value="' . esc_html( $truevalue ) . '" ';
			checked( $multi === true );
			echo '> ' . esc_html__( 'multiple tables', 'respect-robotstxt-or-block' );
			break;
		default:
			// var_dump( $field );
	}
}

// Sanitize and validate input. Accepts an array, return a sanitized array.
function resprobots_multi_validate( $options ) {
	if ( ! empty( $_POST ) && check_admin_referer( 'resprobots', 'resprobots_nonce' ) ) {
		if ( isset( $_POST['submit'] ) ) {
			global $wpdb;
			foreach ( explode( ',', $options ['sites'] ) as $table_id ) {
				if ( (int) $table_id !== get_main_site_id() ) {
					$table_name = $wpdb->base_prefix . $table_id . '_badcrawler';
					resprobots_table_domain_install( $table_name );
				}
			}
			$sites = get_sites();
			foreach ( $sites as $site ) {
				switch_to_blog( $site->blog_id );
				delete_transient( 'resprobots_badcrawler_table' );
				delete_transient( 'resprobots_logfile' );
				restore_current_blog();
			}
			return $options;
		}
		if ( isset( $_POST['delete'] ) ) {
			delete_option( 'resprobots_multi_settings' );
			$sites = get_sites();
			foreach ( $sites as $site ) {
				switch_to_blog( $site->blog_id );
				delete_transient( 'resprobots_badcrawler_table' );
				delete_transient( 'resprobots_logfile' );
				restore_current_blog();
			}
		}
		return false;
	}
}

function resprobots_table_domain_install( $table_name ) {
	global $wpdb;
	$charset_collate = $wpdb->get_charset_collate();

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$sql = "CREATE TABLE IF NOT EXISTS $table_name (
		browser varchar(60) NOT NULL,
		type ENUM ('bot','name') DEFAULT 'bot',
		last timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
		count int(11) NOT NULL DEFAULT 0,
		robots int(11) NOT NULL DEFAULT 0,
		count_1 int(11) NOT NULL DEFAULT 0,
		robots_1 int(11) NOT NULL DEFAULT 0,
		count_2 int(11) NOT NULL DEFAULT 0,
		robots_2 int(11) NOT NULL DEFAULT 0,
		PRIMARY KEY (browser)
	) $charset_collate;";

	//maybe_create_table( $table_name, $sql );

	dbDelta( $sql );
}
//register_activation_hook( __FILE__, 'resprobots_tables_domain_install' );
