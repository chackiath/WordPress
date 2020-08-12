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

$domain_mapping_settings = get_site_option( KPDNS_OPTION_DOMAIN_MAPPING );

?>

<h2>
    <?php _e( 'Edit New Name Server', 'keypress-dns' ); ?>
    <a href="<?php esc_attr_e( add_query_arg( array( 'page' => KPDNS_PAGE_NAME_SERVERS ), KPDNS_Page::get_admin_url() ) ); ?>" class="page-title-action">
        <?php _e( 'Cancel', 'keypress-dns') ?>
    </a>
</h2>
<?php if( $name_server instanceof WP_Error ) : ?>
    <p><?php echo $name_server->get_error_message(); ?></p>
<?php else: ?>
    <form method="post" action="edit.php?action=<?php esc_attr_e( KPDNS_Page_Name_Servers::ACTION_UPDATE_NAME_SERVER ); ?>">
        <table class="form-table">
            <tbody>
                <tr>
                    <th>
                        <label for="kpdns-name-server-name"><?php _e( 'Name', 'keypress-dns' ); ?></label>
                    </th>
                    <td>
                        <input name="name-server[name]" id="kpdns-name-server-name" type="text" value="<?php esc_attr_e( $name_server['name'] ) ?>" placeholder="" class="regular-text">
                    </td>
                </tr>
                <?php if( isset( $domain_mapping_settings ) ) : ?>
                    <tr>
                        <th>
                            <label for="kpdns-name-server-default"><?php _e( 'Set as default', 'keypress-dns' ); ?></label>
                        </th>
                        <td colspan="2">
                            <input name="name-server[default]" id="kpdns-name-server-default" type="checkbox" value="yes" <?php checked( ! empty( $domain_mapping_settings['default-name-server-id'] && $name_server['id'] === $domain_mapping_settings['default-name-server-id'] ) ); ?> />
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        <input type="hidden" name="name-server[id]" value="<?php esc_attr_e( $name_server['id'] ); ?>"/>
        <?php
         wp_nonce_field( KPDNS_Page_Name_Servers::ACTION_UPDATE_NAME_SERVER, KPDNS_Page_Name_Servers::NONCE_UPDATE_NAME_SERVER );
         submit_button( __( 'Update Name Server', 'keypress-dns' ) );
        ?>
    </form>
<?php endif; ?>