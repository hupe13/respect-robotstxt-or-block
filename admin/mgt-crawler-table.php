<?php
/**
 * Table wp_badcrawler delete and add entries
 *
 * @package respect-robotstxt-or-block
 **/

// Direktzugriff auf diese Datei verhindern.
defined( 'ABSPATH' ) || die();

function resprobots_get_table_options() {
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
	return array( $table_name, $text, $options );
}

function resprobots_display_table_with_delete( $db_data ) {
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
			$row_vals[] = $value;
		}
		$class = '';
		if ( $row_vals[3] === '0' && $row_vals[5] === '0' && $row_vals[7] === '0' ) {
			if ( $alternate ) {
				$alternate = false;
				$class     = ' class="red04"';
			} else {
				$alternate = true;
				$class     = ' class="red02"';
			}
		} elseif ( $row_vals[4] === '0' && $row_vals[6] === '0' && $row_vals[8] === '0' ) {
			if ( $alternate ) {
				$alternate = false;
				$class     = ' class="green04"';
			} else {
				$alternate = true;
				$class     = ' class="green02"';
			}
		} elseif ( $alternate ) {
				$alternate = false;
				$class     = ' class="orange04"';
		} else {
			$alternate = true;
			$class     = ' class="orange02"';
		}

		$table  = '<tr' . $class . '>
		<td style="text-align: center;">' . join( '</td><td style="text-align: center;">', $row_vals ) . '</td>';
		$table .= '<td style="text-align: center;"><input type="checkbox" name="' . $row_vals[0] . '"/></td>';
		$table .= '</tr>';
		$rows[] = $table;
	}

	$tablebegin = '<fieldset><legend><h3>' . __( 'Select to delete', 'respect-robotstxt-or-block' ) . ':</h3></legend>';
	$tableend   = '</fieldset>';
	// Put the table together and output
	return $tablebegin . '<table border=1>' . $header . '<tbody>' . join( $rows ) . '</tbody></table>' . $tableend;
}

function resprobots_table_delete_form( $table_name, $all_rows ) {
	global $wpdb;
	$bots = array();
	$text = '';

	if ( $all_rows === false ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$entries = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM %i WHERE count+count_1+count_2+robots+robots_1+robots_2 = 0 ORDER BY last DESC',
				$table_name
			),
			ARRAY_A
		);
	} else {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$entries = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM %i ORDER BY last DESC',
				$table_name
			),
			ARRAY_A
		);
	}

	$text .= resprobots_display_table_with_delete( $entries );

	echo '<form method="post" action="options-general.php?page=respect-robotstxt-or-block&tab=wp_badcrawler_mgt">';
	if ( current_user_can( 'manage_options' ) ) {
		// echo $text;
		$allowed_html          = wp_kses_allowed_html( 'post' );
		$allowed_html['style'] = true;
		$allowed_html['input'] = array(
			'type' => array(),
			'name' => array(),
		// 'value'   => array(),
		// 'checked' => array(),
		);
		echo wp_kses( $text, $allowed_html );
		wp_nonce_field( 'resprobots_mgt', 'resprobots_mgt_nonce' );
		submit_button( __( 'Delete selected entries', 'respect-robotstxt-or-block' ), 'primary', 'delete-entry' );
	}
	echo '</form>';
}

function resprobots_add_entry_form() {
	echo '<form method="post" action="options-general.php?page=respect-robotstxt-or-block&tab=wp_badcrawler_mgt">';
	if ( current_user_can( 'manage_options' ) ) {
		echo '<select name="type">
		<option value="bot">Bot</option>
		<option value="name">Name</option>
		</select>
		<input type="text" name="browser" required minlength="4" maxlength="60" size="60" />';
		wp_nonce_field( 'resprobots_mgt', 'resprobots_mgt_nonce' );
		submit_button( __( 'Add entry', 'respect-robotstxt-or-block' ), 'primary', 'add-entry' );
	}
	echo '</form>';
}

function resprobots_handle_form( $table_name ) {
	global $wpdb;
	$text = '';
	if ( ! empty( $_POST ) && check_admin_referer( 'resprobots_mgt', 'resprobots_mgt_nonce' ) ) {
		// var_dump($_POST);
		if ( isset( $_POST['delete-entry'] ) ) {
			$bots = $_POST;
			unset( $bots['resprobots_mgt_nonce'] );
			unset( $bots['_wp_http_referer'] );
			unset( $bots['delete-entry'] );
			$text .= '<h3>' . __( 'Deleting:', 'respect-robotstxt-or-block' ) . '</h3>';
			$text .= '<p>';
			foreach ( $bots as $bot => $value ) {
				$clean_bot = str_replace( '_', '%', $bot );
				// var_dump( $clean_bot );

				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$mgt_code = $wpdb->query(
					$wpdb->prepare(
						'DELETE FROM %i WHERE browser LIKE %s',
						$table_name,
						$clean_bot,
					),
				);

				// var_dump( $mgt_code );
				if ( 1 === $mgt_code ) {
					$text .= __( 'Deleted:', 'respect-robotstxt-or-block' ) . ' ' . $bot . '</br>';
				} else {
					$text .= __( 'There was an error deleting the entry', 'respect-robotstxt-or-block' ) . ' ' . $bot . '.</br>';
				}
			}
			$text .= '</p>';
		}
		if ( isset( $_POST['add-entry'] ) ) {
			$type    = ! empty( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : '';
			$browser = ! empty( $_POST['browser'] ) ? sanitize_text_field( wp_unslash( $_POST['browser'] ) ) : '';
			$text   .= '<h3>' . __( 'Inserting', 'respect-robotstxt-or-block' ) . ':</h3><p>';
			if ( $type !== '' && $browser !== '' ) {
				$result_msg = __( 'There was an error inserting the entry for', 'respect-robotstxt-or-block' ) . ' browser ' . $browser . '.</br>';
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$entries = $wpdb->get_results(
					$wpdb->prepare(
						'SELECT * FROM %i WHERE browser = %s',
						$table_name,
						$browser,
					),
					ARRAY_A
				);
				// var_dump( 'Testing: ', $entries, count( $entries ) );
				if ( count( $entries ) === 0 ) {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$mgt_code = $wpdb->query(
						$wpdb->prepare(
							'INSERT INTO %i ( browser , type ) VALUES ( %s, %s )',
							$table_name,
							$browser,
							$type
						),
					);
					// var_dump( 'Result: ', $mgt_code );
					if ( 1 === $mgt_code ) {
						$result_msg = sprintf(
							/* translators: entry for browser name and type name/bot. */
							__( 'Entry for %1$s %2$s and %3$s %4$s inserted.', 'respect-robotstxt-or-block' ),
							'browser',
							$browser,
							'type',
							$type
						)
							. '.</br>';
					}
				}
				$text .= $result_msg;
			} else {
				$text .= __( 'There was an error in the form.', 'respect-robotstxt-or-block' );
			}
			$text .= '</p>';
		}
	}
	return $text;
}

function resprobots_mgt_table() {
	// var_dump( $_GET );
	[ $table_name, $title, $options ] = resprobots_get_table_options();
	// $table_name                       = $table_name . '_test';
	// var_dump( $table_name );

	$allowed_html = wp_kses_allowed_html( 'post' );
	echo wp_kses( $title, $allowed_html );
	echo wp_kses( resprobots_handle_form( $table_name ), $allowed_html );

	echo '<h3>' . esc_html__( 'Please select view', 'respect-robotstxt-or-block' ) . ':</h3>';
	$all_rows = filter_input(
		INPUT_GET,
		'all_rows',
		FILTER_VALIDATE_BOOL
	);
	$all_rows = isset( $all_rows ) && $all_rows === true ? true : false;

	echo '<form>';
	echo '<input type="hidden" name="page" value="respect-robotstxt-or-block" />';
	echo '<input type="hidden" name="tab" value="wp_badcrawler_mgt" />';
	echo '<input type="radio" name="all_rows" value="0" ';
	checked( ! ( $all_rows === true ) );
	echo '> ' . esc_html__( 'only without access', 'respect-robotstxt-or-block' ) . ' &nbsp;&nbsp; ';
	echo '<input type="radio" name="all_rows" value="1" ';
	checked( $all_rows === true );
	echo '> ' . esc_html__( 'all', 'respect-robotstxt-or-block' );
	wp_nonce_field( 'resprobots_mgt', 'resprobots_mgt_nonce' );
	submit_button( __( 'Change view', 'respect-robotstxt-or-block' ), 'primary', 'changeview' );
	echo '</form>';

	resprobots_table_delete_form( $table_name, $all_rows );
	resprobots_add_entry_form();
}
