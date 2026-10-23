<?php
defined( 'ABSPATH' ) || exit;

/** Öffentliche MGD-WordPress-Releases im Backend anzeigen und nur nach Admin-Klick installieren. */
if ( ! class_exists( 'MGD_WordPress_Plugin_Catalog' ) ) {
    final class MGD_WordPress_Plugin_Catalog {
        private const OWNER = 'MichaelGahnDESIGN';
        private const CACHE_KEY = 'mgd_wordpress_plugin_catalog_v1';

        public function register( string $parent_slug ): void {
            add_submenu_page( $parent_slug, 'Weitere Plugins', 'Weitere Plugins', 'install_plugins', 'mgd-wordpress-plugin-catalog', array( $this, 'page' ) );
            add_action( 'admin_post_mgd_wordpress_plugin_install', array( $this, 'install' ) );
        }

        /** GitHub wird nur im Backend angefragt; die Ergebnisliste bleibt sechs Stunden im WordPress-Cache. */
        public function items( bool $refresh = false ): array {
            $cached = $refresh ? false : get_transient( self::CACHE_KEY );
            if ( is_array( $cached ) ) { return $cached; }
            $response = wp_remote_get( 'https://api.github.com/users/' . self::OWNER . '/repos?type=owner&per_page=100&sort=updated', array( 'timeout' => 10, 'headers' => array( 'Accept' => 'application/vnd.github+json', 'User-Agent' => 'MGD-WordPress-Plugin-Catalog/' . ( defined( 'MGD_SHARE_VERSION' ) ? MGD_SHARE_VERSION : MGD_NOTIFICATION_VERSION ) ) ) );
            if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) { return array(); }
            $items = array();
            foreach ( (array) json_decode( wp_remote_retrieve_body( $response ), true ) as $repository ) {
                $name = (string) ( $repository['name'] ?? '' );
                if ( empty( $repository['private'] ) && 1 === preg_match( '/^MGD-.+-WordPress$/', $name ) ) { $items[] = $this->release_item( $name, (string) ( $repository['description'] ?? '' ) ); }
            }
            $items = array_values( array_filter( $items ) ); set_transient( self::CACHE_KEY, $items, 6 * HOUR_IN_SECONDS ); return $items;
        }

        private function release_item( string $repository, string $description ): ?array {
            $cache_key = 'mgd_release_' . md5( $repository ); $release = get_transient( $cache_key );
            if ( ! is_array( $release ) ) {
                $response = wp_remote_get( 'https://api.github.com/repos/' . self::OWNER . '/' . rawurlencode( $repository ) . '/releases/latest', array( 'timeout' => 10, 'headers' => array( 'Accept' => 'application/vnd.github+json', 'User-Agent' => 'MGD-WordPress-Plugin-Catalog/' . ( defined( 'MGD_SHARE_VERSION' ) ? MGD_SHARE_VERSION : MGD_NOTIFICATION_VERSION ) ) ) );
                if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) { return null; }
                $release = json_decode( wp_remote_retrieve_body( $response ), true ); set_transient( $cache_key, $release, 6 * HOUR_IN_SECONDS );
            }
            $asset = (array) ( $release['assets'][0] ?? array() ); $download = esc_url_raw( $asset['browser_download_url'] ?? '' );
            if ( ! str_starts_with( $download, 'https://github.com/' . self::OWNER . '/' . $repository . '/releases/download/' ) ) { return null; }
            return array( 'repository' => $repository, 'description' => $description, 'version' => sanitize_text_field( $release['tag_name'] ?? '' ), 'download' => $download, 'installed' => $this->is_installed( $repository ) );
        }

        private function is_installed( string $repository ): bool {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
            foreach ( get_plugins() as $plugin ) {
                $uri = (string) ( $plugin['UpdateURI'] ?? $plugin['PluginURI'] ?? '' );
                if ( 'https://github.com/' . self::OWNER . '/' . $repository === untrailingslashit( $uri ) ) { return true; }
            }
            return false;
        }

        public function page(): void {
            if ( ! current_user_can( 'install_plugins' ) ) { wp_die( 'Nicht berechtigt.' ); }
            $items = $this->items( ! empty( $_GET['refresh'] ) );
            ?><div class="wrap"><h1>Weitere MGD WordPress-Plugins</h1><p>Die Liste entsteht aus öffentlichen GitHub-Repositories nach dem Namensmuster <code>MGD-…-WordPress</code>. Sie aktualisiert sich automatisch spätestens nach sechs Stunden oder über „Liste aktualisieren“.</p><p><a class="button" href="<?php echo esc_url( add_query_arg( array( 'page' => 'mgd-wordpress-plugin-catalog', 'refresh' => 1 ), admin_url( 'admin.php' ) ) ); ?>">Liste aktualisieren</a></p><?php if ( ! $items ) : ?><p>Keine veröffentlichten MGD-WordPress-Releases geladen. Prüfe die Internetverbindung oder veröffentliche zuerst ein GitHub-Release mit ZIP-Anhang.</p><?php else : ?><table class="widefat striped"><thead><tr><th>Plugin</th><th>Version</th><th>Beschreibung</th><th>Status</th><th>Aktion</th></tr></thead><tbody><?php foreach ( $items as $item ) : ?><tr><td><strong><?php echo esc_html( $item['repository'] ); ?></strong></td><td><?php echo esc_html( $item['version'] ); ?></td><td><?php echo esc_html( $item['description'] ); ?></td><td><?php echo $item['installed'] ? 'Installiert' : 'Nicht installiert'; ?></td><td><?php if ( ! $item['installed'] ) : ?><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><?php wp_nonce_field( 'mgd_plugin_catalog_install_' . $item['repository'] ); ?><input type="hidden" name="action" value="mgd_wordpress_plugin_install"><input type="hidden" name="repository" value="<?php echo esc_attr( $item['repository'] ); ?>"><button class="button button-primary">Installieren</button></form><?php endif; ?></td></tr><?php endforeach; ?></tbody></table><?php endif; ?></div><?php
        }

        public function install(): void {
            if ( ! current_user_can( 'install_plugins' ) ) { wp_die( 'Nicht berechtigt.' ); }
            $repository = sanitize_text_field( wp_unslash( $_POST['repository'] ?? '' ) ); check_admin_referer( 'mgd_plugin_catalog_install_' . $repository );
            foreach ( $this->items() as $item ) {
                if ( hash_equals( $item['repository'], $repository ) && ! $item['installed'] ) {
                    require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
                    ( new Plugin_Upgrader( new Automatic_Upgrader_Skin() ) )->install( $item['download'] ); break;
                }
            }
            wp_safe_redirect( admin_url( 'admin.php?page=mgd-wordpress-plugin-catalog' ) ); exit;
        }
    }
}
