<?php
/**
 * Plugin Name: MGD Share
 * Plugin URI: https://theme.michael-gahn.de/plugins/plugin-share-wordpress
 * Description: Datenschutzfreundliche Teilen-Leiste für Beiträge und Seiten, ohne Tracker.
 * Version: 2.2.0
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * Author: Michael Gahn DESIGN
 * Author URI: https://michael-gahn.de
 * License: GPL-2.0-or-later
 * Update URI: https://github.com/MichaelGahnDESIGN/MGD-Share-WordPress
 * Text Domain: mgd-share
 */

defined( 'ABSPATH' ) || exit;

define( 'MGD_SHARE_VERSION', '2.2.0' );
define( 'MGD_SHARE_FILE', __FILE__ );
define( 'MGD_SHARE_DIR', plugin_dir_path( __FILE__ ) );
define( 'MGD_SHARE_URL', plugin_dir_url( __FILE__ ) );

require_once MGD_SHARE_DIR . 'includes/class-mgd-share-options.php';
require_once MGD_SHARE_DIR . 'includes/class-mgd-share-icons.php';
require_once MGD_SHARE_DIR . 'includes/class-mgd-share-plugin.php';
require_once MGD_SHARE_DIR . 'includes/class-mgd-share-renderer.php';
require_once MGD_SHARE_DIR . 'includes/class-mgd-share-privacy-scanner.php';
require_once MGD_SHARE_DIR . 'includes/class-mgd-share-admin.php';

register_activation_hook( __FILE__, array( 'MGD_Share_Plugin', 'activate' ) );
MGD_Share_Plugin::boot();

// Baukasten aus MichaThemeV5-Pro > includes/Kit (bin/build-plugin-kit.php)
spl_autoload_register( static function ( string $class ): void {
    $prefix = 'Mgd\\Share\\Kit\\';
    if ( str_starts_with( $class, $prefix ) ) {
        $file = MGD_SHARE_DIR . 'includes/Kit/' . str_replace( '\\', '/', substr( $class, strlen( $prefix ) ) ) . '.php';
        if ( is_file( $file ) ) {
            require $file;
        }
    }
} );

// Updater + MGD Tools Menü vom Baukasten
use Mgd\Share\Kit\Platform\WordPress\Setup;
use Mgd\Share\Kit\Platform\WordPress\Tools;

MGD_Share_Plugin::$updater = Setup::updater( __FILE__, 'mgd-share-wordpress', 'wordpress', MGD_SHARE_VERSION, 'MGD Share', Tools::url() );
// Reste vom alten Updater wegräumen, 1x pro Version
Setup::cleanupLegacy( 'mgd-share-wordpress', MGD_SHARE_VERSION, array( 'mgd_admin_release_*' ) );

// Einstellungsseite unter MGD Tools - Seiten-Kennung bleibt mgd-share-settings
Setup::tools( __FILE__, 'mgd-share-wordpress', 'wordpress', MGD_SHARE_VERSION, 'MGD Share', MGD_Share_Plugin::$updater, array(
    'slug'     => 'plugin-share-wordpress',
    'repo'     => 'MGD-Share-WordPress',
    'settings' => array( 'page' => 'mgd-share-settings', 'legacy' => array( 'mgd-share', 'mgd-share-plugins' ), 'title' => 'Share', 'cap' => 'manage_options', 'render' => static function (): void { MGD_Share_Plugin::$admin?->page(); } ),
    'license'  => false,
) );
