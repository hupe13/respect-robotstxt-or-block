<?php
/**
 * Documentation HELP
 *
 * @package respect-robotstxt-or-block
 **/

// Direktzugriff auf diese Datei verhindern.
defined( 'ABSPATH' ) || die();

// https://gist.github.com/timkinnane/364458b73b3ffaa9e73e
function resprobots_data_table( $db_data ) {
	if ( ! is_array( $db_data ) || empty( $db_data ) ) {
		return false;
	}

	$resprobots_options = resprobots_get_options();
	switch ( $resprobots_options['rotate'] ) {
		case 'monthly':
			$cycle = 'month';
			break;
		case 'weekly':
			$cycle = 'week';
			break;
		case 'daily':
		default:
			$cycle = 'day';
	}
	$tablehdr  = '<tr><th>&nbsp;</th><th>&nbsp;</th><th>&nbsp;</th>';
	$tablehdr .= '<th colspan=2>this ' . $cycle . '</th>';
	$tablehdr .= '<th colspan=2>' . $cycle . ' before</th>';
	$tablehdr .= '<th colspan=2>2 ' . $cycle . 's before</th></tr>';
	$tablehdr .= '<tr><th>Browser</th><th>Type</th><th>Last</th>';
	for ( $i = 1; $i <= 3; $i++ ) {
		$tablehdr .= '<th>blocked</th><th>robots.txt</th>';
	}
	$tablehdr .= '</tr>';
	$header    = '<thead>' . $tablehdr . '</thead>';

	// Make the data rows
	$rows      = array();
	$alternate = true;
	foreach ( $db_data as $row ) {
		$row_vals = array();
		foreach ( $row as $key => $value ) {

			// format any date values properly with WP date format
			if ( strpos( $key, 'date' ) !== false || strpos( $key, 'modified' ) !== false ) {
				$date_format = get_option( 'date_format' );
				$value       = mysql2date( $date_format, $value );
			}
			$row_vals[] = $value;
		}
		if ( $alternate ) {
			$alternate = false;
			$class     = ' class="quicktags"';
		} else {
			$alternate = true;
			$class     = '';
		}
		$rows[] = '<tr' . $class . '><td style="text-align: center;">' . join( '</td><td style="text-align: center;">', $row_vals ) . '</td></tr>';
	}

	// Put the table together and output
	return '<table border=1 class="widefat fixed>' . $header . '<tbody>' . join( $rows ) . '</tbody></table>';
}

function resprobots_help() {
	$text = '';
	if ( is_multisite() ) {
		$mgt_settings = resprobots_get_domains_mgt();
		$table_name   = $mgt_settings['table'];
		$text        .= '<h3>' . $table_name . '</h3>';

		$text .= '<p>';
		if ( resprobots_many_domains() ) {
			$text .= sprintf(
				/* translators: %s is a link. */
				__( 'This is the table for the domain %1$s.', 'respect-robotstxt-or-block' ),
				$mgt_settings['domain']
			);
		} else {
			$text .= __( 'This is the table for all domains on this multisite.', 'respect-robotstxt-or-block' );
		}
		$text   .= '</p>';
		$options = resprobots_get_site_options( $mgt_settings['site'] );
	} else {
		$table_name = resprobots_get_table_name();
		$text      .= '<h3>' . $table_name . '</h3>';
		$options    = resprobots_get_options();
	}

	$text       .= '<p>' . __( 'Last rotation:', 'respect-robotstxt-or-block' ) . ' ';
	$last_rotate = isset( $options['last_rotate'] ) ? $options['last_rotate'] : 0;
	$text       .= $last_rotate . '</p>';

	global $wpdb;
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$entries = $wpdb->get_results(
		$wpdb->prepare(
			'SELECT * FROM %i WHERE count+count_1+count_2+robots+robots_1+robots_2 > 0 ORDER BY last DESC',
			$table_name
		),
		ARRAY_A
	);
	$text   .= resprobots_data_table( $entries );

	$allowed_html = wp_kses_allowed_html( 'post' );
	echo wp_kses( $text, $allowed_html );
}
