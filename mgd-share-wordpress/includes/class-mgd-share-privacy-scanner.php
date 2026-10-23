<?php
defined( 'ABSPATH' ) || exit;

/** Erkennt bekannte Datenschutz-Plugins, ohne deren Optionen zu raten oder ungefragt zu verändern. */
final class MGD_Share_Privacy_Scanner {
    private const KNOWN_PLUGINS = array(
        'complianz-gdpr/complianz-gpdr.php' => 'Complianz',
        'complianz-gdpr/complianz-gpdr-premium.php' => 'Complianz Premium',
        'real-cookie-banner-pro/real-cookie-banner-pro.php' => 'Real Cookie Banner Pro',
        'real-cookie-banner/real-cookie-banner.php' => 'Real Cookie Banner',
        'borlabs-cookie/borlabs-cookie.php' => 'Borlabs Cookie',
    );

    public function scan(): array {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        $installed = get_plugins();
        $found = array();
        foreach ( self::KNOWN_PLUGINS as $file => $name ) {
            if ( isset( $installed[ $file ] ) ) {
                $found[] = array( 'name' => $name, 'file' => $file, 'active' => is_plugin_active( $file ) );
            }
        }
        return $found;
    }

    public function guidance(): array {
        return array(
            'Das Plugin bindet vor dem Klick keine Inhalte von sozialen Netzwerken ein.',
            'Die Buttons öffnen externe Dienste erst nach einer bewussten Aktion der Besucherinnen und Besucher.',
            'Prüfe in deiner Datenschutzerklärung, ob du die Nutzung externer Plattformen und das Teilen eigener Inhalte erklären möchtest.',
            'Für Links mit personenbezogenen Parametern oder eigene Tracking-Parameter ist eine separate Datenschutzprüfung nötig.',
        );
    }
}
