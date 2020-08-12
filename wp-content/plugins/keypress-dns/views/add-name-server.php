<?php

$api = kpdns_get_api();

if ( is_wp_error( $api ) ) {
    $suports_custom_ns = $api;
} else if ( ! $api instanceof KPDNS_Custom_NS_API_Imp ) {
    $suports_custom_ns = new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( 'Sorry, your managed DNS provider does not support custom NS.', 'keypress-dns' ) );
} else {
    $suports_custom_ns = true;
}

$domain = isset( $_GET['name-server'] ) && isset( $_GET['name-server']['domain'] ) ? $_GET['name-server']['domain'] : '';

?>
<h2>
    <?php _e( 'Add New Set of Custom NS', 'keypress-dns' ); ?>
    <a href="<?php esc_attr_e( add_query_arg( array( 'page' => KPDNS_PAGE_NAME_SERVERS ), KPDNS_Page::get_admin_url() ) ); ?>" class="page-title-action">
		<?php _e( 'Cancel', 'keypress-dns') ?>
    </a>
</h2>

<?php
    if ( is_wp_error( $suports_custom_ns ) ) :
        KPDNS_Page::render_error( $suports_custom_ns );
    else :
?>
    <form method="post" action="<?php esc_attr_e( KPDNS_Page::get_form_action_url( KPDNS_Page_Name_Servers::ACTION_ADD_NAME_SERVER ) ); ?>">
        <table class="form-table">
            <tbody>
                <tr>
                    <th>
                        <label for="kpdns-name-server-domain"><?php _e( 'Domain Name', 'keypress-dns' ) ?></label>
                    </th>
                    <td >
                        <input name="name-server[domain]" id="kpdns-name-server-domain" type="text" placeholder="<?php esc_attr_e( KPDNS_Page_Name_Servers::CUSTOM_NS_DOMAIN_PLACEHOLDER );?>" value="<?php esc_attr_e( $domain ) ?>" class="regular-text">
                        <p class="description"><?php _e( 'The domain name that we will use to identify this set of Custom NS. We will create a new DNS Zone for that domain and will add the four NS records below.', 'keypress-ui' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th>
                        <label for="kpdns-name-server-ns-1"><?php _e( 'NS Record 1', 'keypress-dns' ) ?></label>
                    </th>
                    <td >
                        <input name="name-server[ns][0]" id="kpdns-name-server-ns-1" type="text" placeholder="<?php esc_attr_e( __( 'ns1', 'keypress-dns' ) );?>" value="<?php esc_attr_e( isset( $_GET['name-server'] ) && isset( $_GET['name-server']['ns'] ) && isset( $_GET['name-server']['ns'][0] ) ? $_GET['name-server']['ns'][0] : '' ) ?>" style="width: 100px; text-align: right">
                        <span class="kpdns-ns-domain">.<?php echo empty( $domain ) ? KPDNS_Page_Name_Servers::CUSTOM_NS_DOMAIN_PLACEHOLDER : $domain ?></span>
                    </td>
                </tr>
                <tr>
                    <th>
                        <label for="kpdns-name-server-ns-2"><?php _e( 'NS Record 2', 'keypress-dns' ) ?></label>
                    </th>
                    <td >
                        <input name="name-server[ns][1]" id="kpdns-name-server-ns-2" type="text" placeholder="<?php esc_attr_e( __( 'ns2', 'keypress-dns' ) );?>" value="<?php esc_attr_e( isset( $_GET['name-server'] ) && isset( $_GET['name-server']['ns'] ) && isset( $_GET['name-server']['ns'][1] ) ? $_GET['name-server']['ns'][1] : '' ) ?>" style="width: 100px; text-align: right">
                        <span class="kpdns-ns-domain">.<?php echo empty( $domain ) ? KPDNS_Page_Name_Servers::CUSTOM_NS_DOMAIN_PLACEHOLDER : $domain ?></span>
                    </td>
                </tr>
                <tr>
                    <th>
                        <label for="kpdns-name-server-ns-3"><?php _e( 'NS Record 3', 'keypress-dns' ) ?></label>
                    </th>
                    <td >
                        <input name="name-server[ns][2]" id="kpdns-name-server-ns-3" type="text" placeholder="<?php esc_attr_e( __( 'ns3', 'keypress-dns' ) );?>" value="<?php esc_attr_e( isset( $_GET['name-server'] ) && isset( $_GET['name-server']['ns'] ) && isset( $_GET['name-server']['ns'][2] ) ? $_GET['name-server']['ns'][2] : '' ) ?>" style="width: 100px; text-align: right">
                        <span class="kpdns-ns-domain">.<?php echo empty( $domain ) ? KPDNS_Page_Name_Servers::CUSTOM_NS_DOMAIN_PLACEHOLDER : $domain ?></span>
                    </td>
                </tr>
                <tr>
                    <th>
                        <label for="kpdns-name-server-ns-4"><?php _e( 'NS Record 4', 'keypress-dns' ) ?></label>
                    </th>
                    <td >
                        <input name="name-server[ns][3]" id="kpdns-name-server-ns-4" type="text" placeholder="<?php esc_attr_e( __( 'ns4', 'keypress-dns' ) );?>" value="<?php esc_attr_e( isset( $_GET['name-server'] ) && isset( $_GET['name-server']['ns'] ) && isset( $_GET['name-server']['ns'][3] ) ? $_GET['name-server']['ns'][3] : '' ) ?>" style="width: 100px; text-align: right">
                        <span class="kpdns-ns-domain">.<?php echo empty( $domain ) ? KPDNS_Page_Name_Servers::CUSTOM_NS_DOMAIN_PLACEHOLDER : $domain ?></span>
                    </td>
                </tr>
                <tr>
                    <th>
                        <label for="kpdns-name-server-default"><?php _e( 'Set as default', 'keypress-dns' ) ?></label>
                    </th>
                    <td colspan="2">
                        <label for="kpdns-name-server-default">
                            <input name="name-server[default]" id="kpdns-name-server-default" type="checkbox" value="true" <?php checked( ! empty( $_GET['name-server']['default'] ) ); ?> />
                            <?php _e( 'Check if you want new zones to use this set of Custom NS. ', 'keypress-dns' ); ?>
                        </label>
                    </td>
                </tr>
            </tbody>
        </table>
        <?php
            $name_server = null;
            /**
             * Fires after the add name server form fields.
             *
             * @since 1.1
             *
             * @var $name_server KPDNS_Name_Server|null
             *
             */
            do_action( 'kpdns_add_name_server_form_after_fields', $name_server );
        ?>
        <?php
            wp_nonce_field( KPDNS_Page_Name_Servers::ACTION_ADD_NAME_SERVER, KPDNS_Page_Name_Servers::NONCE_ADD_NAME_SERVER );
            submit_button( __('Save Custom NS Set', 'keypress-dns') );
        ?>
    </form>
<?php endif; ?>
