<?php
defined( 'ABSPATH' ) || exit;
$networks = array_values( array_intersect( array( 'copy', 'native', 'email', 'whatsapp', 'linkedin', 'x' ), $settings['networks'] ) );
?>
<section class="mgd-share mgd-share--<?php echo esc_attr( $settings['style'] ); ?>" data-mgd-share data-url="<?php echo esc_url( $url ); ?>" data-title="<?php echo esc_attr( $title ); ?>" style="--mgd-share-accent:<?php echo esc_attr( $settings['accent'] ); ?>">
    <span class="mgd-share__label"><?php echo esc_html( $settings['label'] ); ?></span>
    <div class="mgd-share__actions">
        <?php foreach ( $networks as $network ) : ?>
            <button class="mgd-share__button" type="button" data-mgd-share-action="<?php echo esc_attr( $network ); ?>"><?php echo esc_html( $this->button_label( $network ) ); ?></button>
        <?php endforeach; ?>
    </div>
    <span class="screen-reader-text" data-mgd-share-status aria-live="polite"></span>
</section>
