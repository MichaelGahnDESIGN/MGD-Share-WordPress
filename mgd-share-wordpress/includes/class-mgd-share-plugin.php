<?php
defined( 'ABSPATH' ) || exit;

/** Initialisiert Einstellungen und hält die öffentliche Oberfläche frei von Drittanbieter-Code. */
final class MGD_Share_Plugin {
    public static function defaults(): array {
        return array(
            'enabled' => true,
            'placement' => 'none',
            'post_types' => array( 'post', 'page' ),
            'networks' => array( 'copy', 'native', 'email', 'whatsapp', 'linkedin', 'x' ),
            'label' => 'Teilen',
            'style' => 'rounded',
            'accent' => '#1d4ed8',
            'show_label' => true,
        );
    }

    public static function settings(): array {
        $saved = get_option( 'mgd_share_settings', array() );
        return array_merge( self::defaults(), is_array( $saved ) ? $saved : array() );
    }

    public static function activate(): void {
        add_option( 'mgd_share_settings', self::defaults() );
    }

    public static function boot(): void {
        $renderer = new MGD_Share_Renderer();
        add_action( 'init', array( $renderer, 'register' ) );
        add_action( 'wp_enqueue_scripts', array( $renderer, 'assets' ) );
        add_filter( 'the_content', array( $renderer, 'append_to_content' ), 30 );
        if ( is_admin() ) {
            ( new MGD_Share_Admin( new MGD_Share_Privacy_Scanner() ) )->register();
        }
    }
}
