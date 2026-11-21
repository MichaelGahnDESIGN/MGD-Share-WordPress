<?php
defined( 'ABSPATH' ) || exit;

// Alle Optionen an einer Stelle - Admin, Shortcode und Vorschau nutzen das gleiche
final class MGD_Share_Options {
    // Auswahlfelder: Schlüssel => erlaubte Werte (erster = Standard)
    public const CHOICES = array(
        'placement' => array( 'none', 'before-content', 'after-content', 'both' ),
        'shape' => array( 'rounded', 'square', 'pill', 'circle' ),
        'variant' => array( 'solid', 'outline', 'soft', 'ghost' ),
        'color_mode' => array( 'accent', 'brand', 'mono' ),
        'size' => array( 'm', 's', 'l' ),
        'display' => array( 'icon-text', 'icon', 'text' ),
        'align' => array( 'left', 'center', 'right', 'spread' ),
        'hover' => array( 'lift', 'grow', 'glow', 'none' ),
        'container' => array( 'card', 'plain' ),
        'floating' => array( 'off', 'left', 'right' ),
    );

    // Texte fürs Admin
    public const LABELS = array(
        'placement' => array( 'none' => 'Nur Shortcode', 'before-content' => 'Vor dem Inhalt', 'after-content' => 'Nach dem Inhalt', 'both' => 'Vor und nach dem Inhalt' ),
        'shape' => array( 'rounded' => 'Abgerundet', 'square' => 'Eckig', 'pill' => 'Pille', 'circle' => 'Kreis (nur Icon)' ),
        'variant' => array( 'solid' => 'Gefüllt', 'outline' => 'Rahmen', 'soft' => 'Sanft getönt', 'ghost' => 'Nur Icon/Text' ),
        'color_mode' => array( 'accent' => 'Eine Akzentfarbe', 'brand' => 'Markenfarben der Netzwerke', 'mono' => 'Schlicht (Grau)' ),
        'size' => array( 's' => 'Klein', 'm' => 'Mittel', 'l' => 'Groß' ),
        'display' => array( 'icon-text' => 'Icon und Text', 'icon' => 'Nur Icon', 'text' => 'Nur Text' ),
        'align' => array( 'left' => 'Links', 'center' => 'Mitte', 'right' => 'Rechts', 'spread' => 'Volle Breite' ),
        'hover' => array( 'lift' => 'Anheben', 'grow' => 'Vergrößern', 'glow' => 'Leuchten', 'none' => 'Keiner' ),
        'container' => array( 'card' => 'Box', 'plain' => 'Ohne Box' ),
        'floating' => array( 'off' => 'Aus', 'left' => 'Am linken Rand', 'right' => 'Am rechten Rand' ),
    );

    public const DEFAULT_NETWORKS = array( 'copy', 'native', 'email', 'whatsapp', 'linkedin' );

    public static function defaults(): array {
        $out = array(
            'enabled' => true,
            'post_types' => array( 'post', 'page' ),
            'networks' => self::DEFAULT_NETWORKS,
            'label' => 'Teilen',
            'show_label' => true,
            'accent' => '#1d4ed8',
            'mobile_bar' => false,
            'placement' => 'none',
        );
        foreach ( self::CHOICES as $key => $values ) {
            $out[ $key ] = $out[ $key ] ?? $values[0];
        }
        return $out;
    }

    /** Alte Werte bis 0.2.0 umbiegen: style rounded/square/minimal > shape + container. */
    public static function migrate( array $raw ): array {
        if ( isset( $raw['style'] ) && ! isset( $raw['shape'] ) ) {
            $raw['shape'] = 'square' === $raw['style'] ? 'square' : 'rounded';
            $raw['container'] = 'minimal' === $raw['style'] ? 'plain' : 'card';
            // bis 0.2.0 waren die Buttons immer Rahmen + Text
            $raw['variant'] = $raw['variant'] ?? 'outline';
            $raw['display'] = $raw['display'] ?? 'text';
        }
        unset( $raw['style'] );
        return $raw;
    }

    /** Prüft alles, was reinkommt (Formular, Shortcode, gespeicherte Option). Unbekanntes fliegt raus. */
    public static function sanitize( array $raw, ?array $base = null ): array {
        $raw = self::migrate( $raw );
        $out = $base ?? self::defaults();
        foreach ( self::CHOICES as $key => $values ) {
            if ( isset( $raw[ $key ] ) && in_array( (string) $raw[ $key ], $values, true ) ) {
                $out[ $key ] = (string) $raw[ $key ];
            }
        }
        foreach ( array( 'enabled', 'show_label', 'mobile_bar' ) as $key ) {
            if ( array_key_exists( $key, $raw ) ) {
                $out[ $key ] = filter_var( $raw[ $key ], FILTER_VALIDATE_BOOLEAN );
            }
        }
        if ( isset( $raw['networks'] ) ) {
            $list = is_array( $raw['networks'] ) ? $raw['networks'] : explode( ',', (string) $raw['networks'] );
            $list = array_map( static fn( $n ) => sanitize_key( trim( (string) $n ) ), $list );
            // Reihenfolge vom Nutzer bleibt, x & Co fliegen raus
            $out['networks'] = array_values( array_unique( array_intersect( $list, MGD_Share_Plugin::NETWORKS ) ) );
        }
        if ( isset( $raw['post_types'] ) && is_array( $raw['post_types'] ) ) {
            $out['post_types'] = array_values( array_map( 'sanitize_key', $raw['post_types'] ) );
        }
        if ( isset( $raw['label'] ) ) {
            $out['label'] = sanitize_text_field( (string) $raw['label'] );
        }
        if ( isset( $raw['accent'] ) ) {
            $out['accent'] = sanitize_hex_color( (string) $raw['accent'] ) ?: $out['accent'];
        }
        return $out;
    }

    /** Schrift auf der Akzentfarbe: weiß oder dunkel, je nach Helligkeit. */
    public static function on_color( string $hex ): string {
        $hex = ltrim( $hex, '#' );
        if ( 3 === strlen( $hex ) ) { $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2]; }
        if ( 6 !== strlen( $hex ) ) { return '#ffffff'; }
        $lin = array();
        foreach ( str_split( $hex, 2 ) as $part ) {
            $c = hexdec( $part ) / 255;
            $lin[] = $c <= 0.03928 ? $c / 12.92 : ( ( $c + 0.055 ) / 1.055 ) ** 2.4;
        }
        $lum = 0.2126 * $lin[0] + 0.7152 * $lin[1] + 0.0722 * $lin[2];
        return $lum > 0.4 ? '#111827' : '#ffffff';
    }

    /** CSS-Klassen für die Leiste, gleiche Logik wie admin.js. */
    public static function classes( array $s ): string {
        $list = array( 'mgd-share' );
        foreach ( array( 'shape', 'variant', 'color_mode', 'size', 'display', 'align', 'hover', 'container' ) as $key ) {
            $list[] = 'mgd-share--' . str_replace( '_', '-', $key ) . '-' . $s[ $key ];
        }
        return implode( ' ', $list );
    }
}
