<?php
defined( 'ABSPATH' ) || exit;

/** Administrationsseiten für Darstellung, Funktionen und datenschutzrelevante Hinweise. */
final class MGD_Share_Admin {
    public function __construct( private MGD_Share_Privacy_Scanner $scanner ) {}

    public function register(): void {
        add_action( 'admin_menu', array( $this, 'menu' ) );
        add_action( 'admin_post_mgd_share_save', array( $this, 'save' ) );
    }

    public function menu(): void {
        add_menu_page( 'MGD Share', 'MGD Share', 'manage_options', 'mgd-share', array( $this, 'dashboard' ), 'dashicons-share', 58 );
        add_submenu_page( 'mgd-share', 'Einstellungen', 'Einstellungen', 'manage_options', 'mgd-share-settings', array( $this, 'settings_page' ) );
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
        $settings = MGD_Share_Plugin::settings(); $post_types = get_post_types( array( 'public' => true ), 'objects' );
        ?><div class="wrap"><h1>MGD Share Einstellungen</h1><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><?php wp_nonce_field( 'mgd_share_save' ); ?><input type="hidden" name="action" value="mgd_share_save">
        <table class="form-table"><tr><th>Aktiv</th><td><label><input type="checkbox" name="enabled" value="1" <?php checked( $settings['enabled'] ); ?>> Share-Leiste anzeigen</label></td></tr>
        <tr><th><label for="placement">Position</label></th><td><select id="placement" name="placement"><?php foreach ( array( 'none' => 'Nur Shortcode', 'before-content' => 'Vor dem Inhalt', 'after-content' => 'Nach dem Inhalt' ) as $value => $label ) : ?><option value="<?php echo esc_attr( $value ); ?>" <?php selected( $settings['placement'], $value ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></td></tr>
        <tr><th>Inhaltstypen</th><td><?php foreach ( $post_types as $type ) : ?><label style="display:block"><input type="checkbox" name="post_types[]" value="<?php echo esc_attr( $type->name ); ?>" <?php checked( in_array( $type->name, $settings['post_types'], true ) ); ?>> <?php echo esc_html( $type->labels->singular_name ); ?></label><?php endforeach; ?></td></tr>
        <tr><th>Funktionen</th><td><?php foreach ( array( 'copy' => 'Link kopieren', 'native' => 'System-Teilen (Web Share API)', 'email' => 'E-Mail', 'whatsapp' => 'WhatsApp', 'linkedin' => 'LinkedIn', 'x' => 'X' ) as $value => $label ) : ?><label style="display:block"><input type="checkbox" name="networks[]" value="<?php echo esc_attr( $value ); ?>" <?php checked( in_array( $value, $settings['networks'], true ) ); ?>> <?php echo esc_html( $label ); ?></label><?php endforeach; ?><p class="description">Externe Plattformen werden nur beim Klick geöffnet.</p></td></tr>
        <tr><th><label for="label">Beschriftung</label></th><td><input id="label" class="regular-text" name="label" value="<?php echo esc_attr( $settings['label'] ); ?>"></td></tr>
        <tr><th><label for="style">Stil</label></th><td><select id="style" name="style"><option value="rounded" <?php selected( $settings['style'], 'rounded' ); ?>>Abgerundet</option><option value="square" <?php selected( $settings['style'], 'square' ); ?>>Eckig</option><option value="minimal" <?php selected( $settings['style'], 'minimal' ); ?>>Minimal</option></select></td></tr>
        <tr><th><label for="accent">Akzentfarbe</label></th><td><input id="accent" name="accent" type="color" value="<?php echo esc_attr( $settings['accent'] ); ?>"></td></tr></table><?php submit_button( 'Einstellungen speichern' ); ?></form>
        <h2>Shortcode</h2><p><code>[mgd_share]</code></p></div><?php
    }

    public function save(): void {
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Nicht berechtigt.' ); }
        check_admin_referer( 'mgd_share_save' );
        $allowed_networks = array( 'copy', 'native', 'email', 'whatsapp', 'linkedin', 'x' );
        $allowed_placements = array( 'none', 'before-content', 'after-content' );
        $allowed_styles = array( 'rounded', 'square', 'minimal' );
        $post_types = array_map( 'sanitize_key', (array) ( $_POST['post_types'] ?? array() ) );
        $networks = array_values( array_intersect( $allowed_networks, array_map( 'sanitize_key', (array) ( $_POST['networks'] ?? array() ) ) ) );
        update_option( 'mgd_share_settings', array(
            'enabled' => ! empty( $_POST['enabled'] ),
            'placement' => in_array( sanitize_key( $_POST['placement'] ?? '' ), $allowed_placements, true ) ? sanitize_key( $_POST['placement'] ) : 'none',
            'post_types' => $post_types,
            'networks' => $networks,
            'label' => sanitize_text_field( wp_unslash( $_POST['label'] ?? 'Teilen' ) ),
            'style' => in_array( sanitize_key( $_POST['style'] ?? '' ), $allowed_styles, true ) ? sanitize_key( $_POST['style'] ) : 'rounded',
            'accent' => sanitize_hex_color( wp_unslash( $_POST['accent'] ?? '' ) ) ?: '#1d4ed8',
            'show_label' => true,
        ) );
        wp_safe_redirect( admin_url( 'admin.php?page=mgd-share-settings&updated=1' ) ); exit;
    }
}
