<?php
defined( 'ABSPATH' ) || exit;

/** Rendert ausschließlich lokale Buttons; externe Ziele werden erst nach einem bewussten Klick geöffnet. */
final class MGD_Share_Renderer {
    public function register(): void {
        add_shortcode( 'mgd_share', array( $this, 'shortcode' ) );
    }

    public function assets(): void {
        if ( is_admin() ) {
            return;
        }
        wp_register_style( 'mgd-share', MGD_SHARE_URL . 'assets/share.css', array(), MGD_SHARE_VERSION );
        wp_register_script( 'mgd-share', MGD_SHARE_URL . 'assets/share.js', array(), MGD_SHARE_VERSION, true );
    }

    public function append_to_content( string $content ): string {
        $settings = MGD_Share_Plugin::settings();
        if ( ! $settings['enabled'] || ! is_singular( $settings['post_types'] ) || ! in_the_loop() || ! is_main_query() ) {
            return $content;
        }
        if ( 'before-content' === $settings['placement'] ) {
            return $this->markup() . $content;
        }
        if ( 'after-content' === $settings['placement'] ) {
            return $content . $this->markup();
        }
        return $content;
    }

    public function shortcode(): string {
        return $this->markup();
    }

    private function markup(): string {
        $settings = MGD_Share_Plugin::settings();
        if ( ! $settings['enabled'] ) {
            return '';
        }
        wp_enqueue_style( 'mgd-share' );
        wp_enqueue_script( 'mgd-share' );
        $url = $this->share_url();
        $title = get_the_title() ?: __( 'Diese Seite', 'mgd-share' );
        if ( ! $url ) {
            return '';
        }
        ob_start();
        include MGD_SHARE_DIR . 'templates/share.php';
        return (string) ob_get_clean();
    }

    /** Liefert eine stabile Seitenadresse ohne Fragmente oder Besucherparameter. */
    private function share_url(): string {
        $url = wp_get_canonical_url() ?: get_permalink();
        if ( ! $url ) {
            return '';
        }
        return strtok( esc_url_raw( $url ), '?#' ) ?: '';
    }

    private function button_label( string $network ): string {
        return array( 'copy' => 'Link kopieren', 'native' => 'Teilen', 'email' => 'E-Mail', 'whatsapp' => 'WhatsApp', 'linkedin' => 'LinkedIn', 'x' => 'X' )[ $network ] ?? 'Teilen';
    }
}
