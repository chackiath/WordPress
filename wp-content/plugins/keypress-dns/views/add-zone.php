<?php

$api = kpdns_get_api();
$name_servers = null;

if ( ! $api instanceof WP_Error ) {
    if ( $api instanceof KPDNS_Custom_NS_API_Imp ) {
	    $name_servers = $api->list_name_servers();
    }
}

$default_records   = KPDNS_Model::get_default_records();

$default_A_record_value    = '';
$default_www_CNAME_record  = false;

foreach ( $default_records as $record ) {
    if ( $record['type'] === KPDNS_Record::TYPE_A ) {
        $default_A_record_value = $record['value'];
    }

    if ( $record['type'] === KPDNS_Record::TYPE_CNAME && $record['name'] === 'www' && $record['create-record'] === 'true' ) {
        $default_www_CNAME_record = true;
    }
}

?>

<h2><?php _e( 'Add New Zone', 'keypress-dns' ); ?><a href="<?php esc_attr_e( add_query_arg( array( 'page' => KPDNS_PAGE_ZONES, 'view' => KPDNS_Page_Zones::VIEW_LIST_ZONES ), KPDNS_Page::get_admin_url() ) ); ?>" class="page-title-action"><?php _e( 'Cancel', 'keypress-dns') ?></a></h2>
<p><?php _e('A DNS zone is a container of DNS records for the same Domain Name. Before you can add DNS records to a DNS zone you must first create it.', 'keypress-dns') ?></p>
<form method="post" action="<?php esc_attr_e( KPDNS_Page::get_form_action_url( KPDNS_Page_Zones::ACTION_ADD_ZONE ) ); ?>">
    <table class="form-table">
        <tbody>
            <tr>
                <th>
                    <label for="kpdns-zone-domain"><?php _e( 'Domain Name', 'keypress-dns' ) ?></label>
                </th>
                <td >
                    <input name="zone[domain]" id="kpdns-zone-domain" type="text" placeholder="<?php _e( 'E.g. myzone.com', 'keypress-ui' ); ?>" value="<?php esc_attr_e( isset( $_GET['zone'] ) && isset( $_GET['zone']['domain'] ) ? $_GET['zone']['domain'] : '' ) ?>" placeholder="" class="regular-text" />
                    <span class="kpdns-input-validation-icon kpdns-input-valid-icon"></span>
                    <span id="kpdns-add-zone-domain-spinner" class="spinner" style="float: none;vertical-align: top;"></span>
                    <p class="description"><?php _e( 'Enter the domain name of the zone, something like example.com.', 'keypress-dns' ) ?></p>
                    <div id="kpdns-records-container" />
                </td>
            </tr>
        </tbody>
    </table>

    <?php
        $zone = isset( $_GET['zone'] ) ? $api->build_zone( $_GET['zone'] ) : null;
        /**
         * Fires after the add zone form fields.
         *
         * @since 1.1
         *
         * @var $zone KPDNS_Zone|null
         *
         */
        do_action( 'kpdns_add_zone_form_after_fields', $zone );
    ?>

    <table class="form-table">
        <tbody>
                <?php if ( isset( $name_servers ) && ! is_wp_error( $name_servers ) ) { ?>
                    <tr>
                        <th>
                            <label for="kpdns-zone-name-server">
                                <?php
                                _e( 'Custom NS', 'keypress-dns' );
                                KPDNS_Page::render_tooltip( __( 'If you select "None", your DNS provider may assign the zone a set of NS records pointing to their default Name Servers.', 'keypress-dns' ) );
                                ?>
                            </label>
                        </th>
                        <td>
                            <select id="kpdns-zone-name-server" name="zone[custom-ns]">
                                <option value="-1"><?php esc_html_e( '- None -', 'keypress-dns' ); ?></option>
                                <?php foreach ( $name_servers->all() as $name_server ) {
                                    $is_default_ns = kpdns_is_default_custom_ns( $name_server );

                                    ?>
                                    <option value="<?php esc_attr_e( $name_server->get_id() ); ?>" <?php selected( $is_default_ns ); ?>>
                                        <?php
                                        esc_html_e( $name_server->get_domain() );
                                        if ( $is_default_ns ) {
                                            _e( ' (default)', 'keypress-dns' );
                                        }
                                        ?>
                            </option>
                        <?php } ?>
                    </select>
                </td>
            </tr>
        <?php } ?>
        <tr>
            <th>
                <label for="kpdns-zone-a-record">
                    <?php _e( 'A Record', 'keypress-dns' ); ?>
                </label>
            </th>
            <td>
                <input name="zone[a-record]" id="kpdns-zone-a-record" type="text" placeholder="" value="<?php esc_attr_e( isset( $_GET['zone'] ) && isset( $_GET['zone']['a-record'] ) ? $_GET['zone']['a-record'] : $default_A_record_value ) ?>" placeholder="" class="regular-text">
                <p class="description"><?php _e( 'Enter an IPv4 address or leave blank if you don\'t want an A record to be created', 'keypress-dns'); ?></p>
            </td>
        </tr>
        <tr>
            <th>
                <label for="kpdns-zone-www-record">
                    <?php _e( 'WWW CNAME', 'keypress-dns' ); ?>
                </label>
            </th>
            <td>
                <input type="checkbox" name="zone[www-record]" id="kpdns-zone-www-record" <?php checked( ( isset( $_GET['zone'] ) && isset( $_GET['zone']['www-record'] ) && 'true' === $_GET['zone']['www-record'] ) || ( ! isset( $_GET['zone'] ) && $default_www_CNAME_record ) ); ?> value="true" />
                <?php _e( 'Check to create a CNAME record for the subdomain www. ', 'keypress-dns' ); ?>
            </td>
        </tr>
        <tr>
            <th>
                <label for="kpdns-zone-primary">
                    <?php _e( 'Primary Zone', 'keypress-dns' ); ?>
                </label>
            </th>
            <td>
                <input type="checkbox" name="zone[primary]" id="kpdns-zone-primary" <?php checked( ( isset( $_GET['zone'] ) && isset( $_GET['zone']['primary'] ) && 'true' === $_GET['zone']['primary'] ) ); ?> value="true" />
                <?php _e( 'Check to set as the <strong>Primary Zone</strong> for this site.', 'keypress-dns' ); ?>
            </td>
        </tr>
        <!--
        <tr>
            <th>
                <label for="kpdns-zone-copy-records">
                    <?php _e( 'Copy Existing Records', 'keypress-dns' ); ?>
                </label>
            </th>
            <td>
                <input type="checkbox" name="zone[copy-records]" id="kpdns-zone-copy-records" <?php checked( isset( $_GET['zone'] ) && isset( $_GET['zone']['copy-records'] ) && 'true' === $_GET['zone']['copy-records'] ); ?> value="true" />
                <?php _e( 'Check to copy DNS records from previous provider.', 'keypress-dns' ); ?>
            </td>
        </tr>
        -->
        </tbody>
    </table>

	<?php
        wp_nonce_field( KPDNS_Page_Zones::ACTION_ADD_ZONE, KPDNS_Page_Zones::NONCE_ADD_ZONE );
        submit_button( __('Add Zone', 'keypress-dns') );
	?>
</form>
