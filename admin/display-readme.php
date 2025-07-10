<?php
/**
 * Documentation HELP
 *
 * @package respect-robotstxt-or-block
 **/

// Direktzugriff auf diese Datei verhindern.
defined( 'ABSPATH' ) || die();

$style                 = '<style>
li {list-style-type:disc;margin-left: 1.5em;}
pre code {display:flex;max-width:800px}
</style>';
$allowed_html          = wp_kses_allowed_html( 'post' );
$allowed_html['style'] = true;

require_once __DIR__ . '/../pkg/parsedown/Parsedown.php';

require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
$filesystem = new WP_Filesystem_Direct( true );
$text       = $filesystem->get_contents( __DIR__ . '/../readme.md' );

$parsedown = new Parsedown();
echo wp_kses( $style . $parsedown->text( $text ), $allowed_html );
