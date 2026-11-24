<?php
defined( 'ABSPATH' ) || exit;

/** Initialisiert Einstellungen und hält die öffentliche Oberfläche frei von Drittanbieter-Code. */
final class MGD_Share_Plugin {
    // erlaubte Ziele - X/Twitter ist raus und kommt auch nicht wieder rein (Michael)
    public const NETWORKS = array( 'copy', 'native', 'email', 'whatsapp', 'linkedin', 'facebook', 'telegram', 'pinterest', 'reddit', 'xing', 'bluesky', 'threads', 'print' );

    // Updater vom Baukasten, setzt die Haupt-Datei - fürs Dashboard
    public static ?MGD_Share_Admin $admin = null;
    public static ?\Mgd\Share\Kit\Platform\WordPress\Updater $updater = null;

    public static function defaults(): array {
        return MGD_Share_Options::defaults();
    }

    public static function settings(): array {
        $saved = get_option( 'mgd_share_settings', array() );
        return MGD_Share_Options::sanitize( is_array( $saved ) ? $saved : array() );
    }

    /** Einmal nach dem Update: x aus gespeicherten Einstellungen werfen, Rest bleibt wie er ist. */
    public static function drop_removed_networks(): void {
        $saved = get_option( 'mgd_share_settings' );
        if ( ! is_array( $saved ) || ! isset( $saved['networks'] ) || ! is_array( $saved['networks'] ) ) { return; }
        $clean = array_values( array_intersect( $saved['networks'], self::NETWORKS ) );
        if ( $clean !== array_values( $saved['networks'] ) ) {
            $saved['networks'] = $clean;
            update_option( 'mgd_share_settings', $saved );
        }
    }

    public static function activate(): void {
        add_option( 'mgd_share_settings', self::defaults() );
    }

    public static function boot(): void {
        $renderer = new MGD_Share_Renderer();
        add_action( 'init', array( $renderer, 'register' ) );
        add_action( 'wp_enqueue_scripts', array( $renderer, 'assets' ) );
        add_filter( 'the_content', array( $renderer, 'append_to_content' ), 30 );
        // schwebende Leiste + Handy-Leiste
        add_action( 'wp_footer', array( $renderer, 'floating' ) );
        add_action( 'admin_init', array( self::class, 'drop_removed_networks' ) );
        if ( is_admin() ) {
            self::$admin = new MGD_Share_Admin( new MGD_Share_Privacy_Scanner(), $renderer );
            self::$admin->register();
        }
    }
}
