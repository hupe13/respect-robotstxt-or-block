<?php
/**
 *  Respect the robots.txt or be blocked Settings
 *
 * @package respect-robotstxt-or-block
 **/

// Direktzugriff auf diese Datei verhindern.
defined( 'ABSPATH' ) || die();

// Init settings
function resprobots_init() {
	add_settings_section( 'resprobots_settings', '', '', 'resprobots_settings' );
	add_settings_field( 'resprobots_settings[rotate]', __( 'rotate the counts daily, weekly or monthly', 'respect-robotstxt-or-block' ), 'resprobots_form', 'resprobots_settings', 'resprobots_settings', 'rotate' );
	add_settings_field( 'resprobots_settings[logfile]', __( 'Path of log file', 'respect-robotstxt-or-block' ), 'resprobots_form', 'resprobots_settings', 'resprobots_settings', 'logfile' );
	add_settings_field( 'resprobots_settings[last_rotate]', __( 'Last rotation of values', 'respect-robotstxt-or-block' ), 'resprobots_form', 'resprobots_settings', 'resprobots_settings', 'last_rotate' );
	add_settings_field( 'resprobots_settings[next_rotate]', __( 'Next rotation of values', 'respect-robotstxt-or-block' ), 'resprobots_form', 'resprobots_settings', 'resprobots_settings', 'next_rotate' );
	register_setting( 'resprobots_settings', 'resprobots_settings', 'resprobots_validate' );
}
add_action( 'admin_init', 'resprobots_init' );

// Baue Abfrage der Params
function resprobots_form( $field ) {
	$options = resprobots_get_options();
	switch ( $field ) {
		case 'logfile':
			// var_dump($options);
			if ( isset( $options['logfile'] ) && $options['logfile'] !== '' && $options['logfile'] !== false ) {
				$value   = ' value="' . sanitize_text_field( $options['logfile'] ) . '" ';
				$setting = $options['logfile'];
			} else {
					$value = '';
				if ( true === WP_DEBUG && WP_DEBUG_LOG === true ) {
					$setting = 'WP_CONTENT_DIR/wp-content/debug.log';
				} elseif ( true === WP_DEBUG ) {
					$setting = WP_DEBUG_LOG . ' (WP_DEBUG_LOG)';
				} else {
					$setting = 'WP_DEBUG is false.';
				}
			}
			echo '<p><b>' . esc_html( __( 'Setting:', 'respect-robotstxt-or-block' ) ) . '</b> ' . esc_html( $setting ) . '</p>';
			echo '<input type="text" size="80" name="resprobots_settings[logfile]" ';
			echo 'placeholder="/path/to/logfile" ';
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ist schon escaped (Zeile 88)
			echo $value . ' />';
			break;
		case 'rotate':
			if ( ! isset( $options['rotate'] ) ) {
				$options['rotate'] = 'daily';
			}
			if ( ! current_user_can( 'manage_options' ) ) {
				$select_disabled = ' disabled ';
			} else {
				$select_disabled = '';
			}
			$times = array( 'daily', 'weekly', 'monthly' );
			echo '<select ' . esc_attr( $select_disabled ) . ' id="rotate" name="resprobots_settings[rotate]">' . "\r\n";
			foreach ( $times as $time ) {
				if ( $time === $options['rotate'] ) {
					echo '<option selected ';
				} else {
					echo '<option ';
				}
				echo 'value="' . esc_attr( $time ) . '">' . esc_attr( $time ) . '</option>' . "\r\n";
			}
			echo '</select>' . "\r\n";
			break;
		case 'next_rotate':
			echo '<input type="hidden" name="resprobots_settings[next_rotate]" value="' . esc_html( $options['next_rotate'] ) . '">';
			echo esc_html( $options['next_rotate'] );
			break;
		case 'last_rotate':
			$last_rotate = isset( $options['last_rotate'] ) ? $options['last_rotate'] : 0;
			echo '<input type="hidden" name="resprobots_settings[last_rotate]" value="' . esc_html( $last_rotate ) . '">';
			echo esc_html( $last_rotate );
	}
}

// Sanitize and validate input. Accepts an array, return a sanitized array.
function resprobots_validate( $options ) {
	if ( ! empty( $_POST ) && check_admin_referer( 'resprobots', 'resprobots_nonce' ) ) {
		if ( isset( $_POST['submit'] ) ) {
			if ( $options['logfile'] !== '' ) {
				if ( ! file_exists( dirname( $options['logfile'] ) ) ) {
					$options['logfile'] = '';
				}
			}
			delete_transient( 'resprobots_logfile' );
		}
		$options['next_rotate'] = resprobots_next_rotate( $options['rotate'] );
		$options['last_rotate'] = isset( $options['last_rotate'] ) ? $options['last_rotate'] : 0;
		return $options;
	}
	if ( isset( $_POST['delete'] ) ) {
		delete_option( 'resprobots_settings' );
		delete_transient( 'resprobots_logfile' );
	}
	return false;
}
