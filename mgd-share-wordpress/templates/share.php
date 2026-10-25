<?php
defined( 'ABSPATH' ) || exit;

$networks = array_values( array_intersect( array( 'copy', 'native', 'email', 'whatsapp', 'linkedin', 'x' ), $settings['networks'] ) );
$menu_id = wp_unique_id( 'mgd-share-menu-' );
$dialog_id = wp_unique_id( 'mgd-share-dialog-' );
?>
<section class="mgd-share mgd-share--<?php echo esc_attr( $settings['style'] ); ?>" data-mgd-share data-url="<?php echo esc_url( $url ); ?>" data-title="<?php echo esc_attr( $title ); ?>" style="--mgd-share-accent:<?php echo esc_attr( $settings['accent'] ); ?>">
    <button class="mgd-share__trigger" type="button" aria-expanded="false" aria-controls="<?php echo esc_attr( $menu_id ); ?>" data-mgd-share-trigger><?php echo esc_html( $settings['label'] ); ?></button>
    <div class="mgd-share__menu" id="<?php echo esc_attr( $menu_id ); ?>" data-mgd-share-menu hidden>
        <p class="mgd-share__title"><?php esc_html_e( 'Diesen Inhalt teilen', 'mgd-share' ); ?></p>
        <p class="mgd-share__hint"><?php esc_html_e( 'Link kopieren oder eine Methode auswählen.', 'mgd-share' ); ?></p>
        <div class="mgd-share__actions">
            <?php foreach ( $networks as $network ) : ?>
                <button class="mgd-share__button" type="button" data-mgd-share-action="<?php echo esc_attr( $network ); ?>"<?php echo in_array( $network, array( 'whatsapp', 'linkedin', 'x' ), true ) ? ' data-mgd-share-external' : ''; ?>><?php echo esc_html( $this->button_label( $network ) ); ?></button>
            <?php endforeach; ?>
        </div>
    </div>
    <span class="screen-reader-text" data-mgd-share-status aria-live="polite"></span>
    <dialog class="mgd-share__dialog" id="<?php echo esc_attr( $dialog_id ); ?>" data-mgd-share-dialog aria-labelledby="<?php echo esc_attr( $dialog_id ); ?>-title">
        <form method="dialog">
            <h2 id="<?php echo esc_attr( $dialog_id ); ?>-title"><?php esc_html_e( 'An externen Dienst übergeben?', 'mgd-share' ); ?></h2>
            <p data-mgd-share-dialog-copy></p>
            <p class="mgd-share__dialog-note"><?php esc_html_e( 'Titel und Link werden erst nach deiner Bestätigung an den gewählten Dienst übergeben.', 'mgd-share' ); ?></p>
            <?php if ( get_privacy_policy_url() ) : ?><a href="<?php echo esc_url( get_privacy_policy_url() ); ?>"><?php esc_html_e( 'Datenschutzerklärung', 'mgd-share' ); ?></a><?php endif; ?>
            <div class="mgd-share__dialog-actions"><button value="cancel"><?php esc_html_e( 'Abbrechen', 'mgd-share' ); ?></button><button class="mgd-share__confirm" value="confirm" data-mgd-share-confirm><?php esc_html_e( 'Weiter', 'mgd-share' ); ?></button></div>
        </form>
    </dialog>
</section>
