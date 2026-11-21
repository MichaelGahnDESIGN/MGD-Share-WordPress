<?php
defined( 'ABSPATH' ) || exit;

/** Administrationsseiten für Darstellung, Funktionen und datenschutzrelevante Hinweise. */
final class MGD_Share_Admin {
    private string $settings_hook = '';

    public function __construct( private MGD_Share_Privacy_Scanner $scanner, private MGD_Share_Renderer $renderer ) {}

    public function register(): void {
        add_action( 'admin_menu', array( $this, 'menu' ) );
        add_action( 'admin_post_mgd_share_save', array( $this, 'save' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );
    }

    public function menu(): void {
        add_menu_page( 'MGD Share', 'MGD Share', 'manage_options', 'mgd-share', array( $this, 'dashboard' ), 'dashicons-share', 58 );
        $this->settings_hook = (string) add_submenu_page( 'mgd-share', 'Einstellungen', 'Einstellungen', 'manage_options', 'mgd-share-settings', array( $this, 'settings_page' ) );
        // Dashboard vom Baukasten: Info, Updates, Weitere Plugins, Installation melden
        add_submenu_page( 'mgd-share', 'Info und Updates', 'Info und Updates', 'manage_options', 'mgd-share-plugins', array( $this, 'plugins_page' ) );
    }

    // CSS/JS nur auf der Einstellungsseite
    public function assets( string $hook ): void {
        if ( $hook !== $this->settings_hook ) { return; }
        $this->renderer->register_assets();
        wp_enqueue_style( 'mgd-share' );
        wp_enqueue_script( 'mgd-share' );
        wp_enqueue_style( 'mgd-share-admin', MGD_SHARE_URL . 'assets/admin.css', array( 'mgd-share' ), MGD_SHARE_VERSION );
        wp_enqueue_script( 'mgd-share-admin', MGD_SHARE_URL . 'assets/admin.js', array( 'jquery-ui-sortable', 'mgd-share' ), MGD_SHARE_VERSION, true );
    }

    public function dashboard(): void {
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

    public function plugins_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Nicht berechtigt.', '', array( 'response' => 403 ) ); }
        $list = \Mgd\Share\Kit\Platform\WordPress\Setup::recommendations( 'mgd-share-wordpress', 'wordpress', 'plugin-share-wordpress', $this->fallback_plugins(), 'MGD-Share-WordPress' );
        // parent leer = kein eigener Menüpunkt, Lizenz bleibt aus
        $dash = \Mgd\Share\Kit\Platform\WordPress\Setup::dashboard( 'mgd-share-wordpress', 'wordpress', MGD_SHARE_VERSION, 'MGD Share', MGD_Share_Plugin::$updater, $list, false, '' );
        if ( null === $dash ) { echo '<div class="wrap"><p>Die Seite konnte gerade nicht geladen werden.</p></div>'; return; }
        $dash->page();
    }

    /** Ersatzliste aus plugin-catalog/plugins.json, falls theme.michael-gahn.de nicht antwortet. */
    private function fallback_plugins(): array {
        $raw = file_get_contents( MGD_SHARE_DIR . 'includes/plugin-catalog/plugins.json' );
        $data = is_string( $raw ) ? json_decode( $raw, true ) : null;
        $out = array();
        foreach ( is_array( $data ) ? $data : array() as $row ) {
            if ( is_array( $row ) && ! empty( $row['slug'] ) ) {
                $out[] = array( 'slug' => (string) $row['slug'], 'name' => (string) ( $row['name'] ?? '' ), 'description' => (string) ( $row['description'] ?? '' ), 'version' => '' );
            }
        }
        return $out;
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
