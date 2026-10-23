<?php
/**
 * Plugin Name: MGD Share
 * Plugin URI: https://github.com/MichaelGahnDESIGN/MGD-Share-WordPress
 * Description: Datenschutzfreundliche Teilen-Funktion mit Link-Kopie und Datenschutz-Dashboard.
 * Version: 0.1.4
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * Author: Michael Gahn DESIGN
 * License: GPL-2.0-or-later
 * Update URI: https://github.com/MichaelGahnDESIGN/MGD-Share-WordPress
 * Text Domain: mgd-share
 */

defined( 'ABSPATH' ) || exit;

define( 'MGD_SHARE_VERSION', '0.1.4' );
define( 'MGD_SHARE_FILE', __FILE__ );
define( 'MGD_SHARE_DIR', plugin_dir_path( __FILE__ ) );
define( 'MGD_SHARE_URL', plugin_dir_url( __FILE__ ) );

require_once MGD_SHARE_DIR . 'includes/class-mgd-share-plugin.php';
require_once MGD_SHARE_DIR . 'includes/class-mgd-share-renderer.php';
require_once MGD_SHARE_DIR . 'includes/class-mgd-share-privacy-scanner.php';
require_once MGD_SHARE_DIR . 'includes/class-mgd-wordpress-plugin-catalog.php';
require_once MGD_SHARE_DIR . 'includes/class-mgd-share-admin.php';

register_activation_hook( __FILE__, array( 'MGD_Share_Plugin', 'activate' ) );
MGD_Share_Plugin::boot();

require_once __DIR__ . '/includes/class-mgd-plugin-admin-tools.php';
\MGD_Plugin_Admin_Tools_20261023::register([
    'file' => __FILE__,
    'repo' => 'MGD-Share-WordPress',
    'slug' => 'mgd-share-wordpress',
    'name' => 'MGD Share',
    'description' => 'Datenschutzfreundliche Teilen-Funktion mit Link-Kopie und Datenschutz-Dashboard.',
    'version' => '0.1.4',
    'settings' => 'admin.php?page=mgd-share-settings',
    'requires' => '6.5',
    'php' => '8.1',
    'caches' => ['mgd_admin_release_mgd-share-wordpress'],
    'package' => 'mgd-share-wordpress-{version}.zip',
]);
