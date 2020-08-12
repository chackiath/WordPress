<?php
    if ( isset( $_GET['kpdns-settings'] ) ) {
        $kpdns_settings = $_GET['kpdns-settings'];
    }
?>
<form method="post" action="<?php esc_attr_e( KPDNS_Page::get_form_action_url( KPDNS_Page_Settings::ACTION_SAVE_DEFAULT_RECORDS ) ); ?>">
    <table class="form-table">
        <tbody>
            <tr valign="top">
                <th colspan="2">
                    <h3><?php _e( 'Default Records', 'keypress-dns' ) ?></h3>
                    <p><?php _e( 'When a new DNS zone is created, by default it contains only the NS records for Name Servers. Filling in the fields below will add other records in each new DNS zone. ', 'keypress-dns' ); ?></p>
                </th>
            </tr>
            <tr>
                <th>
                    <label for="kpdns-settings-default-records-cname"><?php _e( KPDNS_Record::TYPE_A, 'keypress-dns') ?></label>
                </th>
                <td >
                    <input type="hidden" name="kpdns-settings[default-records][0][type]" value="A" />
                    <input type="hidden" name="kpdns-settings[default-records][0][name]" value="" />
                    <input type="hidden" name="kpdns-settings[default-records][0][ttl]" value="3600" />
                    <input type="hidden" name="kpdns-settings[default-records][0][ttl-unit]" value="S" />
                    <?php
                    if ( isset( $kpdns_settings ) && isset( $kpdns_settings[ 'default-records' ] ) && isset( $kpdns_settings[ 'default-records' ][0] ) && isset( $kpdns_settings[ 'default-records' ][0]['value'] ) ) {
                        $record_0_val = $kpdns_settings[ 'default-records' ][0]['value'];
                    } else {
                        $default_records = KPDNS_Model::get_default_records();
                        if ( $default_records && isset( $default_records[1] ) && isset( $default_records[0]['value'] ) ) {
                            $record_0_val = $default_records[0]['value'];
                        }
                    }
                    ?>
                    <input name="kpdns-settings[default-records][0][value]" id="kpdns-settings-default-records-0-value" type="text" value="<?php esc_attr_e( isset( $record_0_val ) ? $record_0_val : '' ); ?>" class="small">
                    <p class="description"><?php _e( 'Enter the IPv4 address of this site.', 'keypress-dns'); ?></p>
                    <p class="description"><?php _e( 'Leave it blank if you don\'t want an A record to be created by default when a new zone is created.', 'keypress-dns' ); ?></p>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="kpdns-settings-create-zone"><?php _e( 'WWW CNAME', 'keypress-dns') ?></label>
                </th>
                <td >
                    <label for="kpdns-settings-default-records-1-create-record">
                        <input type="hidden" name="kpdns-settings[default-records][1][type]" value="CNAME" />
                        <input type="hidden" name="kpdns-settings[default-records][1][name]" value="www" />
                        <input type="hidden" name="kpdns-settings[default-records][1][value]" value="domain" />
                        <input type="hidden" name="kpdns-settings[default-records][1][ttl]" value="3600" />
                        <input type="hidden" name="kpdns-settings[default-records][1][ttl-unit]" value="S" />
                        <?php
                        $record_1_val = false;
                        if ( isset( $kpdns_settings ) && isset( $kpdns_settings['default-records'] ) && isset( $kpdns_settings['default-records'][1] ) && isset( $kpdns_settings[ 'default-records' ][1]['create-record'] ) ) {
                            $record_1_val = $kpdns_settings[ 'default-records' ][1]['create-record'] === 'true' ? true : false;
                        } else {
                            $default_records = KPDNS_Model::get_default_records();
                            if ( $default_records && isset( $default_records[1] ) && isset( $default_records[1]['create-record'] ) ) {
                                $record_1_val = $default_records[1]['create-record'] === 'true' ? true : false;
                            }
                        }
                        ?>
                        <input type="checkbox" name="kpdns-settings[default-records][1][create-record]" id="kpdns-settings-default-records-1-create-record" <?php checked( $record_1_val ); ?> value="true" />
                        <?php _e( 'Check to create a CNAME record for the subdomain www when a new zone is created.', 'keypress-dns' ); ?>
                    </label>
                </td>
            </tr>
            <?php if( is_multisite() && is_subdomain_install() ) : ?>
                <tr valign="top">
                    <th colspan="2">
                        <h3><?php _e( 'Wildcard Subdomains', 'keypress-dns' ) ?></h3>
                        <p><?php _e( 'Some hosts do not allow the use of wildcard subdomains, which can result in new subsites throwing a 404 error when created. Checking the box below will fix this by enabling the creation of CNAME records for each subdomain in the primary DNS zone.', 'keypress-dns' ); ?></p>
                    </th>
                </tr>
                <tr>
                    <th>
                        <label for="kpdns-settings-create-zone"><?php _e( 'Subdomain CNAME Record', 'keypress-dns') ?></label>
                    </th>
                    <td >
                        <label for="kpdns-settings-wildcard-subdomains-a-record">
                            <?php
                                if ( isset( $_GET['kpdns-settings'] ) && isset( $_GET['kpdns-settings']['wildcard-subdomains' ] ) ) {
                                    $wildcard_subdomains = $_GET['kpdns-settings']['wildcard-subdomains' ];
                                } else {
                                    $wildcard_subdomains = KPDNS_Model::get_wildcard_subdomains();
                                }

                                if ( $wildcard_subdomains && isset( $wildcard_subdomains['a-record'] ) && $wildcard_subdomains['a-record'] === 'true' ) {
                                    $wildcard_subdomain_a_record = true;
                                } else {
                                    $wildcard_subdomain_a_record = false;
                                }
                            ?>
                            <input type="checkbox" name="kpdns-settings[wildcard-subdomains][a-record]" id="kpdns-settings-wildcard-subdomains-a-record" <?php checked( $wildcard_subdomain_a_record ); ?> value="true" />
                            <?php _e( 'NOTE: Check this box only if you are having issues with site creation.', 'keypress-dns' ); ?>
                        </label>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <?php
        wp_nonce_field( KPDNS_Page_Settings::ACTION_SAVE_DEFAULT_RECORDS, KPDNS_Page_Settings::NONCE_SAVE_DEFAULT_RECORDS );
        submit_button( __( 'Save Settings', 'keypress-dns' ) );
    ?>
</form>