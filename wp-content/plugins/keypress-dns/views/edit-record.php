<?php

if ( ! isset( $_GET['zone'] ) || ! isset( $_GET['record'] ) ) {
	wp_die( __( 'Unexpected error: Missing required field(s).', 'keypress-dns' ) );
}

$zone       = $_GET['zone'];
$record     = $_GET['record'];
$old_record = isset ( $_GET['old-record'] ) ? $_GET['old-record'] : $record;

$zone_id = $zone['id'];
$domain  = rtrim( $zone['domain'], '.' );

?>
<h2>
    <?php _e( 'Edit Record', 'keypress-dns' )?>
</h2>
<?php if( /*$zone instanceof WP_Error*/ false ): ?>
    <p><?php echo $zone->get_error_message() ?></p>
<?php else: ?>
    <form method="post" action="<?php esc_attr_e( KPDNS_Page::get_form_action_url(KPDNS_Page_Zones::ACTION_UPDATE_RECORD ) ); ?>" name="kpdns-edit-record" id="kpdns-edit-record">
        <table class="form-table">
            <tbody>
            <tr>
                <th>
                    <label for="kpdns-record-type"><?php _e( 'Type', 'keypress-dns') ?></label>
                </th>
                <td >
                    <?php esc_html_e( $record['type'] ); ?>
                </td>
            </tr>
            </tbody>
        </table>
        <?php
            KPDNS_Page_Zones::render_record_form_name_field( $domain );
            KPDNS_Page_Zones::render_record_form_rdata_fields();
            KPDNS_Page_Zones::render_record_form_ttl_fields();
        ?>

        <?php
        /**
         * Fires after the edit record form fields.
         *
         * @since 1.1
         *
         * @param array $old_record The old record values.
         * @param array|null $record The new record values if the form was submitted and there where errors, null otherwise.
         *
         */
        do_action( 'kpdns_edit_record_form_after_fields', $record, $record );
        ?>

        <input type="hidden" name="record[type]" value="<?php esc_attr_e( $record['type'] ); ?>"/>

        <?php
            foreach ( $zone as $key => $value ) {
                if ( is_array( $value ) ) {
                    foreach ( $value as $k => $val ) {
                        if ( ! is_array( $val ) ) {
                            ?>
                                <input type="hidden" name="zone[<?php esc_attr_e( $key ); ?>][<?php esc_attr_e( $k ); ?>]" value="<?php esc_attr_e( $val ); ?>"/>
                            <?php
                        } else {
                            foreach ( $val as $i => $v ) {
                                ?>
                                    <input type="hidden" name="zone[<?php esc_attr_e( $key ); ?>][<?php esc_attr_e( $k ); ?>][<?php esc_attr_e( $i ); ?>]" value="<?php esc_attr_e( $v ); ?>"/>
                                <?php
                            }
                        }
                    }
                } else {
                    ?>
                        <input type="hidden" name="zone[<?php esc_attr_e( $key ); ?>]" value="<?php esc_attr_e( $value ); ?>"/>
                    <?php
                }
            }
        ?>

        <?php
            foreach ( $old_record as $key => $value ) {
                if( 'rdata' === $key ) {
                    foreach ( $old_record['rdata'] as $rdata_key => $rdata_value ) {
                        ?>
                            <input type="hidden" name="old-record[rdata][<?php esc_attr_e( $rdata_key ); ?>]" value="<?php esc_attr_e( $rdata_value ); ?>"/>
                        <?php
                    }
                } elseif( 'meta' === $key ) {
                    foreach ( $old_record['meta'] as $rdata_key => $rdata_value ) {
                        if ( is_array( $rdata_value ) ) {
                            foreach ( $rdata_value as $rv_key => $rv_value ) {
                                ?>
                                    <input type="hidden" name="old-record[meta][<?php esc_attr_e( $rdata_key ); ?>][<?php esc_attr_e( $rv_key ); ?>]" value="<?php esc_attr_e( $rv_value ); ?>"/>
                                <?php
                            }
                        } else {
                            ?>
                                <input type="hidden" name="old-record[meta][<?php esc_attr_e( $rdata_key ); ?>]" value="<?php esc_attr_e( $rdata_value ); ?>"/>
                            <?php
                        }
                    }
                } else {
                    if ( 'name' === $key ) {
                        $value = rtrim( str_replace( $domain, '', $value ), '.');
                    }
                    ?>
                        <input type="hidden" name="old-record[<?php esc_attr_e( $key ); ?>]" value="<?php esc_attr_e( $value ); ?>"/>
                    <?php
                }
            }
        ?>

        <?php wp_nonce_field( KPDNS_Page_Zones::ACTION_UPDATE_RECORD, KPDNS_Page_Zones::NONCE_UPDATE_RECORD ); ?>
        <input type="submit" name="submit" id="submit" class="button button-primary" value="Update Record">
        <a class="button-secondary" href="<?php esc_attr_e( add_query_arg( array( 'page' => KPDNS_PAGE_ZONES, 'view' => KPDNS_Page_Zones::VIEW_EDIT_ZONE, 'zone-id' => urlencode( $zone_id ) ), KPDNS_Page::get_admin_url() ) ); ?>" title="<?php esc_attr_e( 'Cancel', 'keypress-dns' ); ?>"><?php _e( 'Cancel', 'keypress-dns' ); ?></a>
    </form>
<?php endif; ?>