<?php
defined( 'ABSPATH' ) || exit;
// Einstellungsseite mit Live-Vorschau - $s, $post_types, $order kommen aus MGD_Share_Admin::settings_page
$presets = array(
    'klassisch' => array( 'name' => 'Klassisch', 'v' => array( 'shape' => 'rounded', 'variant' => 'outline', 'color_mode' => 'accent', 'display' => 'icon-text', 'container' => 'card', 'hover' => 'lift', 'size' => 'm' ) ),
    'bunt' => array( 'name' => 'Bunte Pillen', 'v' => array( 'shape' => 'pill', 'variant' => 'solid', 'color_mode' => 'brand', 'display' => 'icon-text', 'container' => 'plain', 'hover' => 'lift', 'size' => 'm' ) ),
    'kreise' => array( 'name' => 'Icon-Kreise', 'v' => array( 'shape' => 'circle', 'variant' => 'solid', 'color_mode' => 'brand', 'display' => 'icon', 'container' => 'plain', 'hover' => 'grow', 'size' => 'm' ) ),
    'sanft' => array( 'name' => 'Sanft', 'v' => array( 'shape' => 'rounded', 'variant' => 'soft', 'color_mode' => 'brand', 'display' => 'icon-text', 'container' => 'plain', 'hover' => 'glow', 'size' => 'm' ) ),
    'minimal' => array( 'name' => 'Minimal', 'v' => array( 'shape' => 'rounded', 'variant' => 'ghost', 'color_mode' => 'mono', 'display' => 'icon', 'container' => 'plain', 'hover' => 'none', 'size' => 's' ) ),
    'kraeftig' => array( 'name' => 'Kräftig eckig', 'v' => array( 'shape' => 'square', 'variant' => 'solid', 'color_mode' => 'accent', 'display' => 'icon-text', 'container' => 'card', 'hover' => 'lift', 'size' => 'l' ) ),
);
$swatches = array( '#1d4ed8', '#c8161a', '#0f766e', '#7c3aed', '#ea580c', '#db2777', '#111827' );
?>
<div class="wrap mgd-sa">
    <h1>MGD Share Einstellungen</h1>
    <?php if ( isset( $_GET['updated'] ) ) : // phpcs:ignore -- nur Anzeige ?><div class="notice notice-success is-dismissible"><p>Gespeichert.</p></div><?php endif; ?>

    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="mgd-sa-form">
        <?php wp_nonce_field( 'mgd_share_save' ); ?>
        <input type="hidden" name="action" value="mgd_share_save">

        <div class="mgd-sa-presets">
            <span class="mgd-sa-presets__title">Schnellstart</span>
            <?php foreach ( $presets as $key => $preset ) : ?>
                <button type="button" class="button mgd-sa-preset" data-preset="<?php echo esc_attr( wp_json_encode( $preset['v'] ) ); ?>"><?php echo esc_html( $preset['name'] ); ?></button>
            <?php endforeach; ?>
        </div>

        <div class="mgd-sa-grid">
            <div class="mgd-sa-main">
                <section class="mgd-sa-card">
                    <h2>Wo erscheint die Leiste?</h2>
                    <label class="mgd-sa-switch"><input type="checkbox" name="enabled" value="1" <?php checked( $s['enabled'] ); ?>><span>Share-Leiste anzeigen</span></label>
                    <h3>Im Inhalt</h3>
                    <?php MGD_Share_Admin::choice( 'placement', $s ); ?>
                    <h3>Schwebend am Rand</h3>
                    <?php MGD_Share_Admin::choice( 'floating', $s ); ?>
                    <label class="mgd-sa-switch"><input type="checkbox" name="mobile_bar" value="1" <?php checked( $s['mobile_bar'] ); ?>><span>Am Handy als Leiste unten fixieren</span></label>
                    <h3>Auf diesen Seiten</h3>
                    <div class="mgd-sa-checks">
                        <?php foreach ( $post_types as $type ) : ?>
                            <label><input type="checkbox" name="post_types[]" value="<?php echo esc_attr( $type->name ); ?>" <?php checked( in_array( $type->name, $s['post_types'], true ) ); ?>> <?php echo esc_html( $type->labels->singular_name ); ?></label>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section class="mgd-sa-card">
                    <h2>Buttons und Reihenfolge</h2>
                    <p class="description">Haken setzen = Button an. Mit dem Griff ziehen = Reihenfolge ändern. Externe Plattformen öffnen sich erst beim Klick.</p>
                    <ul class="mgd-sa-networks" id="mgd-sa-networks">
                        <?php foreach ( $order as $network ) : ?>
                            <li class="mgd-sa-network" style="--brand:<?php echo esc_attr( MGD_Share_Icons::color( $network ) ); ?>">
                                <span class="mgd-sa-network__grip dashicons dashicons-menu" aria-hidden="true"></span>
                                <label>
                                    <input type="checkbox" name="networks[]" value="<?php echo esc_attr( $network ); ?>" <?php checked( in_array( $network, $s['networks'], true ) ); ?>>
                                    <span class="mgd-sa-network__icon"><?php echo MGD_Share_Icons::svg( $network ); // phpcs:ignore -- feste SVGs ?></span>
                                    <?php echo esc_html( MGD_Share_Icons::label( $network ) ); ?>
                                </label>
                                <span class="mgd-sa-network__move">
                                    <button type="button" class="button-link" data-move="-1" aria-label="<?php echo esc_attr( MGD_Share_Icons::label( $network ) ); ?> nach oben">▲</button>
                                    <button type="button" class="button-link" data-move="1" aria-label="<?php echo esc_attr( MGD_Share_Icons::label( $network ) ); ?> nach unten">▼</button>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </section>

                <section class="mgd-sa-card">
                    <h2>Aussehen</h2>
                    <h3>Form</h3><?php MGD_Share_Admin::choice( 'shape', $s ); ?>
                    <h3>Stil</h3><?php MGD_Share_Admin::choice( 'variant', $s ); ?>
                    <h3>Inhalt der Buttons</h3><?php MGD_Share_Admin::choice( 'display', $s ); ?>
                    <h3>Größe</h3><?php MGD_Share_Admin::choice( 'size', $s ); ?>
                    <h3>Effekt beim Drüberfahren</h3><?php MGD_Share_Admin::choice( 'hover', $s ); ?>
                    <h3>Rahmen um die Leiste</h3><?php MGD_Share_Admin::choice( 'container', $s ); ?>
                    <h3>Ausrichtung</h3><?php MGD_Share_Admin::choice( 'align', $s ); ?>
                </section>

                <section class="mgd-sa-card">
                    <h2>Farben</h2>
                    <?php MGD_Share_Admin::choice( 'color_mode', $s ); ?>
                    <h3><label for="mgd-sa-accent">Akzentfarbe</label></h3>
                    <div class="mgd-sa-color">
                        <input id="mgd-sa-accent" name="accent" type="color" value="<?php echo esc_attr( $s['accent'] ); ?>">
                        <?php foreach ( $swatches as $hex ) : ?>
                            <button type="button" class="mgd-sa-swatch" data-color="<?php echo esc_attr( $hex ); ?>" style="background:<?php echo esc_attr( $hex ); ?>" aria-label="Farbe <?php echo esc_attr( $hex ); ?>"></button>
                        <?php endforeach; ?>
                    </div>
                    <p class="description">Die Akzentfarbe gilt für „Eine Akzentfarbe“ und für die Box. Die Schriftfarbe auf gefüllten Buttons wird automatisch hell oder dunkel gewählt, damit sie lesbar bleibt.</p>
                </section>

                <section class="mgd-sa-card">
                    <h2>Beschriftung</h2>
                    <label class="mgd-sa-switch"><input type="checkbox" name="show_label" value="1" <?php checked( $s['show_label'] ); ?>><span>Text vor den Buttons zeigen</span></label>
                    <p><input id="mgd-sa-label" class="regular-text" name="label" value="<?php echo esc_attr( $s['label'] ); ?>" aria-label="Text vor den Buttons"></p>
                </section>

                <?php submit_button( 'Einstellungen speichern' ); ?>
            </div>

            <aside class="mgd-sa-side">
                <div class="mgd-sa-card mgd-sa-preview">
                    <div class="mgd-sa-preview__head">
                        <h2>Live-Vorschau</h2>
                        <div class="mgd-sa-preview__bg" role="group" aria-label="Hintergrund der Vorschau">
                            <button type="button" class="is-active" data-bg="light">Hell</button>
                            <button type="button" data-bg="dark">Dunkel</button>
                        </div>
                    </div>
                    <div class="mgd-sa-preview__stage" id="mgd-sa-stage">
                        <p class="mgd-sa-preview__dummy">So sieht die Leiste unter einem Beitrag aus. Klicks sind hier nur eine Vorschau.</p>
                        <?php echo $this->renderer->preview( $s ); // phpcs:ignore -- Template escaped selbst ?>
                    </div>
                </div>
                <div class="mgd-sa-card">
                    <h2>Shortcode</h2>
                    <p>Überall einsetzbar, auch im Block-Editor und in Page Buildern:</p>
                    <p><code>[mgd_share]</code></p>
                    <p>Einzelne Einstellungen kannst du direkt im Shortcode ändern:</p>
                    <p><code>[mgd_share shape="pill" color_mode="brand" networks="whatsapp,email,copy"]</code></p>
                    <p class="description">Möglich: shape, variant, color_mode, size, display, align, hover, container, accent, label, show_label, networks.</p>
                </div>
                <p><a href="<?php echo esc_url( admin_url( 'admin.php?page=mgd-share-plugins' ) ); ?>">Info, Updates und Installation melden</a></p>
            </aside>
        </div>
    </form>
</div>
