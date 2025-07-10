<?php
/**
 *  Respect the robots.txt or be blocked Settings
 *
 * @package respect-robotstxt-or-block
 **/

// Direktzugriff auf diese Datei verhindern.
defined( 'ABSPATH' ) || die();

// linkes Menu
function resprobots_add_sub_page() {
	add_submenu_page(
		'options-general.php',
		__( 'Robots', 'respect-robotstxt-or-block' ),
		__( 'Robots', 'respect-robotstxt-or-block' ),
		'manage_options',
		'respect-robotstxt-or-block',
		'resprobots_admin',
	);
}
add_action( 'admin_menu', 'resprobots_add_sub_page' );

// Admin page for the plugin
function resprobots_admin() {
	echo '<h2>' . esc_html__( 'Respect the robots.txt or be blocked', 'respect-robotstxt-or-block' ) . '</h2>';
	echo '<h3>' . esc_html__( 'Help and Options', 'respect-robotstxt-or-block' ) . '</h3>';

	$tab        = filter_input(
		INPUT_GET,
		'tab',
		FILTER_CALLBACK,
		array( 'options' => 'esc_html' )
	);
	$active_tab = $tab ? $tab : 'help';

	echo '<div style="max-width: 1000px;">';
	resprobots_admin_robots_tabs();

	if ( $active_tab === 'settings' ) {
		if ( is_multisite() ) {
			require_once __DIR__ . '/admin/multisite.php';
			$domains = resprobots_get_domains();
			if ( count( $domains ) > 1 ) {
				if ( get_current_blog_id() === get_main_site_id() ) {
					echo wp_kses_post( resprobots_mgt_domains_help( $domains ) );
					echo '<form method="post" action="options.php">';
					wp_nonce_field( 'resprobots', 'resprobots_nonce' );
					settings_fields( 'resprobots_multi_settings' );
					do_settings_sections( 'resprobots_multi_settings' );
					if ( current_user_can( 'manage_options' ) ) {
						submit_button();
						submit_button( __( 'Reset', 'respect-robotstxt-or-block' ), 'delete', 'delete', false );
					}
					echo '</form>';
				} else {
					echo '<h3>' . esc_html__( 'Domains', 'respect-robotstxt-or-block' ) . '</h3>';
					echo '<p>';
					printf(
						/* translators: %s is a link. */
						esc_html__( 'Multisite domains settings are available on %1$smain site%2$s.', 'respect-robotstxt-or-block' ),
						'<a href="' . esc_html( get_site_url( get_main_site_id() ) ) . '/wp-admin/options-general.php?page=respect-robotstxt-or-block&tab=settings">',
						'</a>'
					);
					echo '</p>';
				}
			}
		}

		$mgt_settings = resprobots_get_domains_mgt();
		echo '<h3>' . esc_html( __( 'Settings for the table', 'respect-robotstxt-or-block' ) . ' ' . $mgt_settings['table'] ) . '</h3>';
		if ( get_current_blog_id() === $mgt_settings['site'] ) {
			echo '<form method="post" action="options.php">';
			settings_fields( 'resprobots_settings' );
			wp_nonce_field( 'resprobots', 'resprobots_nonce' );
			do_settings_sections( 'resprobots_settings' );
			if ( current_user_can( 'manage_options' ) ) {
				submit_button();
				submit_button( __( 'Reset', 'respect-robotstxt-or-block' ), 'delete', 'delete', false );
			}
			echo '</form>';
		} else {
			echo '<p>';
			printf(
				/* translators: %s is a link. */
				esc_html__( 'You can manage the settings of the table %1$s %2$shere%3$s.', 'respect-robotstxt-or-block' ),
				esc_html( $mgt_settings['table'] ),
				'<a href="' . esc_html( $mgt_settings['mgt-site'] ) . '">',
				'</a>'
			);
			echo '</p>';
		}
	} elseif ( $active_tab === 'readme' ) {
		require_once __DIR__ . '/admin/display-readme.php';
	} elseif ( $active_tab === 'wp_badcrawler' ) {
		require_once __DIR__ . '/admin/display-crawler-table.php';
		resprobots_help();
	} else {
		if ( function_exists( 'leafext_updates_from_github' ) ) {
			leafext_updates_from_github();
		}
		require_once __DIR__ . '/admin/display-readme.php';
	}
	echo '</div>';
}

function resprobots_admin_robots_tabs() {
	$tab        = filter_input(
		INPUT_GET,
		'tab',
		FILTER_CALLBACK,
		array( 'options' => 'esc_html' )
	);
	$active_tab = $tab ? $tab : 'help';

	echo '<h3 class="nav-tab-wrapper">';
	echo '<a href="' . esc_url( '?page=respect-robotstxt-or-block&tab=help' ) . '" class="nav-tab';
	echo $active_tab === 'help' ? ' nav-tab-active' : '';
	echo '">' . esc_html__( 'Help', 'respect-robotstxt-or-block' ) . '</a>' . "\n";

	$tabs   = array();
	$tabs[] = array(
		'tab'   => 'settings',
		'title' => __( 'Settings', 'respect-robotstxt-or-block' ),
	);
	$tabs[] = array(
		'tab'   => 'wp_badcrawler',
		'title' => __( 'wp_badcrawler', 'respect-robotstxt-or-block' ),
	);

	foreach ( $tabs as $tab ) {
		echo '<a href="' . esc_url( '?page=respect-robotstxt-or-block&tab=' . $tab['tab'] ) . '" class="nav-tab';
		$active = ( $active_tab === $tab['tab'] ) ? ' nav-tab-active' : '';
		if ( isset( $tab['strpos'] ) ) {
			if ( strpos( $active_tab, $tab['strpos'] ) !== false ) {
				$active = ' nav-tab-active';
			}
		}
		echo esc_attr( $active );
		echo '">' . esc_html( $tab['title'] ) . '</a>' . "\n";
	}
	echo '</h3>';
}
