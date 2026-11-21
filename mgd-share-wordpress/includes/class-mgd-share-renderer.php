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
        $this->register_assets();
    }

    public function register_assets(): void {
        wp_register_style( 'mgd-share', MGD_SHARE_URL . 'assets/share.css', array(), MGD_SHARE_VERSION );
        wp_register_script( 'mgd-share', MGD_SHARE_URL . 'assets/share.js', array(), MGD_SHARE_VERSION, true );
    }

    private function applies( array $settings ): bool {
        return $settings['enabled'] && ! empty( $settings['post_types'] ) && is_singular( $settings['post_types'] );
    }

    public function append_to_content( string $content ): string {
        $settings = MGD_Share_Plugin::settings();
        if ( ! $this->applies( $settings ) || ! in_the_loop() || ! is_main_query() ) {
            return $content;
        }
        $place = $settings['placement'];
        if ( in_array( $place, array( 'before-content', 'both' ), true ) ) {
            $content = $this->markup( $settings ) . $content;
        }
        if ( in_array( $place, array( 'after-content', 'both' ), true ) ) {
            $content .= $this->markup( $settings );
        }
        return $content;
    }

    /** [mgd_share style="pill" networks="copy,whatsapp" ...] - alles aus den Einstellungen überschreibbar. */
    public function shortcode( $atts = array() ): string {
        $atts = is_array( $atts ) ? $atts : array();
        // style= als Kurzform für shape=
        if ( isset( $atts['style'] ) && ! isset( $atts['shape'] ) ) { $atts['shape'] = $atts['style']; }
        unset( $atts['style'] );
        $settings = MGD_Share_Options::sanitize( $atts, MGD_Share_Plugin::settings() );
        return $this->markup( $settings );
    }

    // Schwebende Leiste am Rand bzw. unten am Handy
    public function floating(): void {
        $settings = MGD_Share_Plugin::settings();
        if ( ! $this->applies( $settings ) || ( 'off' === $settings['floating'] && ! $settings['mobile_bar'] ) ) {
            return;
        }
        $extra = 'mgd-share--floating mgd-share--float-' . $settings['floating'] . ( $settings['mobile_bar'] ? ' mgd-share--mobile-bar' : '' );
        $float = array_merge( $settings, array( 'container' => 'plain', 'align' => 'left', 'show_label' => false ) );
        // Kreis braucht Platz, am Rand immer nur Icons
        if ( 'icon-text' === $float['display'] ) { $float['display'] = 'icon'; }
        echo $this->markup( $float, $extra, get_queried_object_id() ); // phpcs:ignore WordPress.Security.EscapeOutput -- Template escaped selbst
    }

    /** Vorschau im Admin: alle Netzwerke, Beispiel-Link, JS blendet ab. */
    public function preview( array $settings ): string {
        $settings['networks'] = MGD_Share_Plugin::NETWORKS;
        $settings['enabled'] = true;
        return $this->render( $settings, 'mgd-share--preview', home_url( '/beispiel-beitrag/' ), 'Beispiel-Beitrag', '' );
    }

    private function markup( array $settings, string $extra = '', int $post_id = 0 ): string {
        if ( ! $settings['enabled'] || ! $settings['networks'] ) {
            return '';
        }
        $post_id = $post_id ?: (int) get_the_ID();
        $url = get_permalink( $post_id );
        $title = get_the_title( $post_id );
        if ( ! $url || ! $title ) {
            return '';
        }
        wp_enqueue_style( 'mgd-share' );
        wp_enqueue_script( 'mgd-share' );
        $image = (string) get_the_post_thumbnail_url( $post_id, 'large' );
        return $this->render( $settings, $extra, $url, wp_strip_all_tags( $title ), $image );
    }

    private function render( array $settings, string $extra, string $url, string $title, string $image ): string {
        $networks = array_values( array_intersect( $settings['networks'], MGD_Share_Plugin::NETWORKS ) );
        $classes = trim( MGD_Share_Options::classes( $settings ) . ' ' . $extra );
        $on = MGD_Share_Options::on_color( $settings['accent'] );
        ob_start();
        include MGD_SHARE_DIR . 'templates/share.php';
        return (string) ob_get_clean();
    }
}
