<?php

if ( ! isset( $_GET['zone-id'] ) ) {
	wp_die( __( 'Unexpected error: Invalid zone id.', 'keypress-dns' ) );
}

$api = kpdns_get_api();
if ( $api instanceof WP_Error ) {
	$zone = $api;
} else {
	$zone = $api->get_zone( $_GET['zone-id'] );
}

?>

<?php if ( $zone instanceof WP_Error ) {
	KPDNS_Page::render_error( $zone );
} else { ?>
    <h2><?php echo __( 'Delete Zone: ', 'keypress-dns' ) . '<em>' . esc_html( $zone['name'] ) . '</em>' ?></h2>
    <p><?php _e( 'You cannot undo this action. Do you want to delete this zone?', 'keypress-dns' ) ?></p>
    <form method="post" action="edit.php?action=<?php esc_attr_e( KPDNS_Page_Zones::ACTION_DELETE_ZONE ); ?>" name="kpdns-delete-zone" id="kpdns-delete-zone">
        <input type="hidden" name="zone-id" id="kpdns-zone-id"  value="<?php esc_attr_e( $zone['id'] ); ?>"/>
        <?php wp_nonce_field( KPDNS_Page_Zones::ACTION_DELETE_ZONE, KPDNS_Page_Zones::NONCE_DELETE_ZONE ); ?>
        <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_attr_e( 'Delete Zone', 'keypress-dns' ) ?>">
        <a class="button-secondary" href="<?php esc_attr_e( add_query_arg( array( 'page' => KPDNS_PAGE_ZONES ), KPDNS_Page::get_admin_url() ) ); ?>" title="<?php esc_attr_e( 'Cancel', 'keypress-dns' ); ?>"><?php _e( 'Cancel', 'keypress-dns' ); ?></a>
    </form>
<?php } ?>
