<?php
defined( 'ABSPATH' ) || exit;
// Share-Leiste - $settings, $networks, $classes, $on, $url, $title, $image kommen aus dem Renderer
$is_floating = str_contains( $classes, 'mgd-share--floating' );
?>
<<?php echo $is_floating ? 'aside' : 'section'; ?> class="<?php echo esc_attr( $classes ); ?>" data-mgd-share data-url="<?php echo esc_url( $url ); ?>" data-title="<?php echo esc_attr( $title ); ?>" data-image="<?php echo esc_url( $image ); ?>" aria-label="<?php echo esc_attr( $settings['label'] ?: 'Teilen' ); ?>" style="--mgd-share-accent:<?php echo esc_attr( $settings['accent'] ); ?>;--mgd-share-on:<?php echo esc_attr( $on ); ?>">
    <span class="mgd-share__label"<?php echo ( $settings['show_label'] && '' !== $settings['label'] ) ? '' : ' hidden'; ?>><?php echo esc_html( $settings['label'] ); ?></span>
    <div class="mgd-share__actions">
        <?php foreach ( $networks as $network ) : $name = MGD_Share_Icons::label( $network ); $brand = MGD_Share_Icons::color( $network ); ?>
            <button class="mgd-share__button mgd-share__button--<?php echo esc_attr( $network ); ?>" type="button" data-mgd-share-action="<?php echo esc_attr( $network ); ?>" aria-label="<?php echo esc_attr( $name ); ?>" title="<?php echo esc_attr( $name ); ?>" style="--mgd-share-brand:<?php echo esc_attr( $brand ); ?>;--mgd-share-brand-on:<?php echo esc_attr( MGD_Share_Options::on_color( $brand ) ); ?>">
                <?php echo MGD_Share_Icons::svg( $network ); // phpcs:ignore -- feste SVGs aus der Klasse ?>
                <span class="mgd-share__text"><?php echo esc_html( $name ); ?></span>
            </button>
        <?php endforeach; ?>
    </div>
    <span class="mgd-share__status" data-mgd-share-status aria-live="polite"></span>
</<?php echo $is_floating ? 'aside' : 'section'; ?>>
