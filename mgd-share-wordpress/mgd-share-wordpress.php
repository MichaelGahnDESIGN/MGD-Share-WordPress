<?php
/**
 * Plugin Name: MGD Share
 * Plugin URI: https://github.com/MichaelGahnDESIGN/MGD-Share-WordPress
 * Description: Datenschutzfreundliche Teilen-Funktion mit Link-Kopie und Datenschutz-Dashboard.
 * Version: 0.2.0
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * Author: Michael Gahn DESIGN
 * License: GPL-2.0-or-later
 * Update URI: https://github.com/MichaelGahnDESIGN/MGD-Share-WordPress
 * Text Domain: mgd-share
 */

defined( 'ABSPATH' ) || exit;

define( 'MGD_SHARE_VERSION', '0.2.0' );
define( 'MGD_SHARE_FILE', __FILE__ );
define( 'MGD_SHARE_DIR', plugin_dir_path( __FILE__ ) );
define( 'MGD_SHARE_URL', plugin_dir_url( __FILE__ ) );

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

// Updater + Install-Melder vom Baukasten, alter GitHub-Updater (Admin-Tools) ist raus
\Mgd\Share\Kit\Platform\WordPress\Setup::updater( __FILE__, 'mgd-share-wordpress', 'wordpress', MGD_SHARE_VERSION, 'MGD Share' );
\Mgd\Share\Kit\Install\Platform\WordPress::boot( __FILE__, 'mgd-share-wordpress', 'wordpress', MGD_SHARE_VERSION, admin_url( 'admin.php?page=mgd-share-settings' ) );

// Link Einstellungen in der Plugin-Liste
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), static function ( array $links ): array {
    array_unshift( $links, '<a href="' . esc_url( admin_url( 'admin.php?page=mgd-share-settings' ) ) . '">Einstellungen</a>' );
    return $links;
} );
