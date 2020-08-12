<?php
    if ( ! isset( $_GET['zone-id'] ) ) {
       wp_die( __( 'Unexpected error: Invalid zone id.', 'keypress-dns' ) );
    }

    $api = kpdns_get_api();
    if ( $api instanceof WP_Error ) {
        $api_error = $api;
    } else {
        $zone = $api->get_zone( $_GET['zone-id'] );
    }
?>

<h2>
    <?php _e( 'Edit Zone', 'keypress-dns' ) ?>
    <a href="<?php esc_attr_e( add_query_arg( array( 'page' => KPDNS_PAGE_ZONES ), KPDNS_Page::get_admin_url() ) ); ?>" class="page-title-action">
        <?php _e( 'Go to DNS Zones', 'keypress-dns') ?>
    </a>
</h2>
<?php if( isset( $api_error ) ): ?>
    <p><?php echo $api_error->get_error_message() ?></p>
<?php else: ?>
    <form method="post" action="<?php esc_attr_e( KPDNS_Page::get_form_action_url(KPDNS_Page_Zones::ACTION_UPDATE_ZONE ) ); ?>" name="kpdns-edit-zone" id="kpdns-edit-zone">
        <table class="form-table">
            <tbody>
                <tr>
                    <th><?php _e( 'Domain', 'keypress-dns' ) ?></th>
                    <td ><?php esc_html_e( rtrim( $zone->get_domain(), '.' ) ); ?></td>
                </tr>
                <tr>
                    <th><?php _e( 'Primary Zone', 'keypress-dns' ) ?></th>
                    <td >
                        <input type="checkbox" name="zone[primary]" id="kpdns-zone-primary" <?php checked( $zone->is_primary() ); ?> value="true" />
                        <?php _e( 'Check to set as the <strong>Primary Zone</strong> for this site.', 'keypress-dns' ); ?>
                    </td>
                </tr>
            </tbody>
        </table>
        <?php
            /**
             * Fires after the edit zone form fields.
             *
             * @since 1.1
             *
             * @param KPDNS_Zone $zone
             */
            do_action( 'kpdns_edit_zone_form_after_fields', $zone );

            if ( $zone->is_primary() ) {
                echo sprintf(
                        '<p>%1$s %2$s</p>',
                        '<span class="dashicons dashicons-lock"></span>',
                        __( 'This is your <strong>Primary Zone</strong>. In order to be able to remove it you must uncheck the <strong>Primary Zone</strong> checkbox.', 'keypress-dns' )
                );
            }

            if ( $zone->is_custom_ns() ) {
                echo sprintf(
                    '<p>%1$s %2$s</p>',
                    '<span class="dashicons dashicons-lock"></span>',
                    sprintf( __( 'This zoned is associated to a <strong>Custom NS</strong>. In order to be able to delete it you must delete %s Custom NS first.', 'keypress-dns' ), $zone->get_domain() )
                );
            }
        ?>
        <?php error_log( print_r( $zone, true ) ) ?>
        <?php if ( $zone instanceof KPDNS_Zone && ! $zone->is_readonly() ) : ?>
            <table class="form-table">
                <tbody>
                    <tr valign="top">
                        <th colspan="2">
                            <input type="hidden" name="zone[id]" value="<?php esc_attr_e( $zone->get_id() ); ?>"/>
                            <input type="hidden" name="zone[domain]" value="<?php esc_attr_e( $zone->get_domain() ); ?>"/>
                            <?php wp_nonce_field( KPDNS_Page_Zones::ACTION_UPDATE_ZONE, KPDNS_Page_Zones::NONCE_UPDATE_ZONE ) ?>
                            <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_attr_e( 'Update Zone', 'keypress-dns' ); ?>">
                            <?php if( ! $zone->is_primary() && ! $zone->is_custom_ns() ) : ?>
                                <a class="button-secondary kpdns-delete-zone"
                                   href="<?php echo esc_url( KPDNS_Page::get_link_action_url( KPDNS_Page_Zones::ACTION_DELETE_ZONE, KPDNS_Page_Zones::NONCE_DELETE_ZONE, array( 'zone-id' => $zone->get_id() ) )  ); ?>"
                                   title="<?php esc_attr_e( 'Delete Zone', 'keypress-dns' ); ?>"
                                   data-zone="<?php esc_attr_e( $zone->get_domain() ) ?>"><?php _e( 'Delete Zone', 'keypress-dns' ); ?></a>
                            <?php endif; ?>
                        </th>
                    </tr>
                </tbody>
            </table>
        <?php endif; ?>
    </form><!-- end form edit_zone -->
    <?php
        $record_columns = array(
            'name'  => __( 'Name', 'keypress-dns'),
            'type'  => __( 'Type', 'keypress-dns'),
            'value' => __( 'Value', 'keypress-dns'),
            'ttl'   => __( 'TTL', 'keypress-dns'),
        );

        /**
         * Filters the columns displayed in the Records list table.
         *
         * @since 1.1
         *
         * @param string[] $record_columns An associative array of column headings.
         */
        $record_columns = apply_filters( 'kpdns_edit_zone_record_columns', $record_columns );
    ?>
    <table class="widefat striped">
        <thead>
            <tr>
                <?php foreach ( $record_columns as $column_key => $column_label ): ?>
                    <th scope="col" class="row-title"><?php esc_html_e( $column_label ); ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
        <?php
            $records = $api->list_records( $zone->get_id() );
        ?>
        <?php if ( is_wp_error( $records ) ) : ?>
            <p><?php echo $records->get_error_message() ?></p>
        <?php elseif( empty( $records->all() ) ) : ?>
            <tr>
                <td colspan="<?php esc_attr_e( count( $record_columns ) ); ?>">
                    <?php _e( 'No records found. If you have created the zone recently, your managed DNS provider might need some time to propagate the changes. Please, refresh the current page or try again later.', 'keypress-dns' ); ?>
                </td>
            </tr>
        <?php else : ?>
            <?php foreach( $records as $record ) : ?>
                <tr>
                    <?php
                        $row_data = array(
                            'name'  => rtrim( $record->get_name(), '.' ),
                            'type'  => $record->get_type(),
                            'value' => $record->get_rdata(),
                            'ttl'   => $record->get_ttl(),
                        );

                        /**
                         * Filters the rows data in the Records list table.
                         *
                         * @since 1.1
                         *
                         * @param string[] $record_columns An associative array of row data.
                         * @param $record KPDNS_Record
                         * @param $zone KPDNS_Zone
                         *
                         */
                        $row_data = apply_filters( 'kpdns_edit_zone_records_table_row_data', $row_data, $record, $zone );

                        foreach ( $row_data as $data_key => $data_value ) {
                            if ( 'name' === $data_key ) {
                                ?>
                                    <td class="column-primary has-row-actions">
                                        <?php if ( $record->is_readonly() ) : ?>
                                            <span class="dashicons dashicons-lock" style=""></span>
                                            <?php esc_html_e( wp_specialchars_decode( $data_value ) ); ?>
                                        <?php else: ?>
                                            <a href="<?php echo esc_url( add_query_arg( array( 'page' => KPDNS_PAGE_ZONES, 'view' => KPDNS_Page_Zones::VIEW_EDIT_RECORD, 'record' => urlencode_deep( $record->to_array() ), 'zone' => urlencode_deep( $zone->to_array() ) ), KPDNS_Page::get_admin_url() ) ); ?>">
                                                <?php esc_html_e( wp_specialchars_decode( $data_value ) ); ?>
                                            </a>
                                            <div class="row-actions">
                                                <span class="edit">
                                                    <a href="<?php echo esc_url( add_query_arg( array( 'page' => KPDNS_PAGE_ZONES, 'view' => KPDNS_Page_Zones::VIEW_EDIT_RECORD, 'record' => urlencode_deep( $record->to_array() ), 'zone' => urlencode_deep( $zone->to_array() ) ), KPDNS_Page::get_admin_url() ) ); ?>"><?php _e( 'Edit Record', 'keypress-dns') ?></a>
                                                    |
                                                </span>
                                                <span class="trash">
                                                    <a href="<?php echo esc_url( KPDNS_Page::get_link_action_url( KPDNS_Page_Zones::ACTION_DELETE_RECORD, KPDNS_Page_Zones::NONCE_DELETE_RECORD, array( 'zone' => $zone->to_array(), 'record' => $record->to_array() ) ) ); ?>" class="kpdns-delete-record submitdelete" data-record="<?php esc_attr_e( $record->get_type() . ': ' . $record->get_name() ) ?>"><?php _e( 'Delete', 'keypress-dns') ?></a>
                                               </span>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                <?php
                            } elseif( 'value' === $data_key ) {
                                ?>
                                    <td>
                                        <?php //esc_html_e( KPDNS_Record::get_formatted_value( $record ) );
                                            esc_html_e( $record->get_rdata_string() );
                                        ?>
                                    </td>
                                <?php
                            } else {
                                ?>
                                <td>
                                    <?php esc_html_e( $data_value ); ?>
                                </td>
                                <?php
                            }
                        }
                    ?>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <?php foreach ( $record_columns as $column_key => $column_label ): ?>
                    <th scope="col" class="row-title"><?php esc_html_e( $column_label ); ?></th>
                <?php endforeach; ?>
            </tr>
        </tfoot>
    </table>
    <form method="post" action="<?php esc_attr_e( KPDNS_Page::get_form_action_url( KPDNS_Page_Zones::ACTION_ADD_RECORD ) ); ?>" name="kpdns-add-record" id="kpdns-add-record">
        <h3><?php _e( 'Add New DNS Record', 'keypress-dns') ?></h3>
        <?php
            KPDNS_Page_Zones::render_record_form_type_field();
            KPDNS_Page_Zones::render_record_form_name_field( $zone->get_domain() );
            KPDNS_Page_Zones::render_record_form_rdata_fields();
            KPDNS_Page_Zones::render_record_form_ttl_fields();

            /**
             * Fires after the add record form fields.
             *
             * @since 1.1
             *
             */
            do_action( 'kpdns_add_record_form_after_fields' );
        ?>
        <?php wp_nonce_field( KPDNS_Page_Zones::ACTION_ADD_RECORD, KPDNS_Page_Zones::NONCE_ADD_RECORD ); ?>
        <input type="hidden" name="zone[id]" id="kpdns-zone-id" value="<?php esc_attr_e( $zone->get_id() ); ?>"/>
        <input type="hidden" name="zone[domain]" id="kpdns-zone-domain" value="<?php esc_attr_e( rtrim( $zone->get_domain(), '.' ) ); ?>"/>
        <input type="submit" class="button button-primary" id="kpdns-add-record-submit-btn" value="<?php esc_attr_e( 'Add Record', 'keypress-dns' );?>" <?php echo ! isset( $_GET['record'] ) ? 'disabled="disabled"' : '' ?>/>
    </form>
<?php endif; ?>