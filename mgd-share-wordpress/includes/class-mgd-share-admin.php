<?php
defined( 'ABSPATH' ) || exit;

/** Administrationsseiten für Darstellung, Funktionen und datenschutzrelevante Hinweise. */
final class MGD_Share_Admin {

    public function __construct( private MGD_Share_Privacy_Scanner $scanner, private MGD_Share_Renderer $renderer ) {}

    public function register(): void {
        add_action( 'admin_post_mgd_share_save', array( $this, 'save' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
    }

    // CSS/JS nur auf der Einstellungsseite
    public function assets( string $hook ): void {
        if ( ! str_ends_with( $hook, '_page_mgd-share-settings' ) ) { return; }
        $this->renderer->register_assets();
        wp_enqueue_style( 'mgd-share' );
        wp_enqueue_script( 'mgd-share' );
        wp_enqueue_style( 'mgd-share-admin', MGD_SHARE_URL . 'assets/admin.css', array( 'mgd-share' ), MGD_SHARE_VERSION );
        wp_enqueue_script( 'mgd-share-admin', MGD_SHARE_URL . 'assets/admin.js', array( 'jquery-ui-sortable', 'mgd-share' ), MGD_SHARE_VERSION, true );
    }

    public function page(): void {
        $this->settings_page();
        $this->dashboard();
    }

    private function dashboard(): void {
        $plugins = $this->scanner->scan();
        ?><div class="wrap"><h1>MGD Share Dashboard</h1>
        <p>MGD Share lädt keine Social-Media-Skripte und setzt selbst keine Cookies. Externe Plattformen werden ausschließlich nach einem Klick geöffnet.</p>
        <h2>Datenschutz-Check</h2><ul class="ul-disc"><?php foreach ( $this->scanner->guidance() as $item ) : ?><li><?php echo esc_html( $item ); ?></li><?php endforeach; ?></ul>
        <h2>Erkannte Datenschutz-Plugins</h2>
        <?php if ( ! $plugins ) : ?><p>Kein bekanntes Datenschutz-Plugin erkannt. Das ist kein Fehler, aber prüfe die Datenschutzerklärung vor Veröffentlichung.</p><?php else : ?><table class="widefat striped"><thead><tr><th>Plugin</th><th>Status</th><th>Integration</th></tr></thead><tbody><?php foreach ( $plugins as $plugin ) : ?><tr><td><?php echo esc_html( $plugin['name'] ); ?></td><td><?php echo $plugin['active'] ? 'Aktiv' : 'Installiert, nicht aktiv'; ?></td><td>Keine Änderung erforderlich: MGD Share lädt vor dem Klick keine Drittanbieter-Ressourcen.</td></tr><?php endforeach; ?></tbody></table><?php endif; ?>
        <p><strong>Hinweis:</strong> Das Dashboard bietet technische Orientierung und ersetzt keine Rechtsberatung. Fremde Plugin-Einstellungen werden nicht automatisch verändert, weil deren rechtliche Bedeutung und Datenstruktur installationsabhängig sind.</p>
        <p><a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=mgd-share-settings' ) ); ?>">Darstellung und Funktionen konfigurieren</a></p></div><?php
    }

    public function settings_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Nicht berechtigt.', '', array( 'response' => 403 ) ); }
        $s = MGD_Share_Plugin::settings();
        $post_types = get_post_types( array( 'public' => true ), 'objects' );
        // aktive zuerst in gespeicherter Reihenfolge, dann der Rest
        $order = array_values( array_unique( array_merge( $s['networks'], MGD_Share_Plugin::NETWORKS ) ) );
        include MGD_SHARE_DIR . 'templates/admin-settings.php';
    }

    /** Auswahl als Kachel-Knöpfe (Radio). */
    public static function choice( string $key, array $s ): void {
        echo '<div class="mgd-sa-choices" role="radiogroup">';
        foreach ( MGD_Share_Options::LABELS[ $key ] as $value => $label ) {
            printf(
                '<label class="mgd-sa-choice"><input type="radio" name="%1$s" value="%2$s" %3$s><span>%4$s</span></label>',
                esc_attr( $key ),
                esc_attr( $value ),
                checked( $s[ $key ], $value, false ),
                esc_html( $label )
            );
        }
        echo '</div>';
    }

    public function save(): void {
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Nicht berechtigt.' ); }
        check_admin_referer( 'mgd_share_save' );
        $raw = wp_unslash( $_POST ); // phpcs:ignore -- wird in sanitize() Feld für Feld geprüft
        $raw = is_array( $raw ) ? $raw : array();
        // Checkboxen fehlen im POST, wenn aus
        foreach ( array( 'enabled', 'show_label', 'mobile_bar' ) as $flag ) { $raw[ $flag ] = ! empty( $raw[ $flag ] ); }
        $raw['networks'] = (array) ( $raw['networks'] ?? array() );
        $raw['post_types'] = (array) ( $raw['post_types'] ?? array() );
        update_option( 'mgd_share_settings', MGD_Share_Options::sanitize( $raw ) );
        wp_safe_redirect( admin_url( 'admin.php?page=mgd-share-settings&updated=1' ) ); exit;
    }
}
