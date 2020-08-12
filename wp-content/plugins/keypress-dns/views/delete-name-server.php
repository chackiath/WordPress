<?php

if ( ! isset( $_GET['name-server-id'] ) ) {
	wp_die( __( 'Unexpected error: Invalid name server id.', 'keypress-dns' ) );
}

$api = kpdns_get_api();

if ( $api instanceof WP_Error ) {
	$name_server = $api;
} else {
	$name_server = $api->get_name_server( $_GET['name-server-id'] );
}

?>

<?php if ( $name_server instanceof WP_Error ) {
	KPDNS_Page::render_error( $name_server );
} else { ?>
    <h2>
        <?php _e( 'Delete Name Server: ', 'keypress-dns' ) . '<em>' . esc_html( $name_server['name'] ) . '</em>'; ?>
    </h2>
    <p><?php _e( 'Please note that you will not be able to remove this name server if it has associated zones.', 'keypress-dns' ); ?></p>
    <p><?php _e( 'You cannot undo this action. Do you want to delete this name server?', 'keypress-dns' ) ?></p>
    <form method="post" action="edit.php?action=<?php esc_attr_e( KPDNS_Page_Name_Servers::ACTION_DELETE_NAME_SERVER ); ?>" name="kpdns-delete-name-server" id="kpdns-delete-name-server">
        <input type="hidden" name="name-server-id" value="<?php esc_attr_e( $name_server['id'] ); ?>"/>
        <?php wp_nonce_field( KPDNS_Page_Name_Servers::ACTION_DELETE_NAME_SERVER, KPDNS_Page_Name_Servers::NONCE_DELETE_NAME_SERVER ); ?>
        <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_attr_e( 'Delete Name Server', 'keypress-dns' ) ?>">
        <a class="button-secondary" href="<?php esc_attr_e( add_query_arg( array( 'page' => KPDNS_PAGE_NAME_SERVERS ), KPDNS_Page::get_admin_url() ) ); ?>" title="<?php esc_attr_e( 'Cancel', 'keypress-dns' ); ?>"><?php _e( 'Cancel', 'keypress-dns' ); ?></a>
    </form>
<?php } ?>
