<?php

if ( ! isset( $_GET['zone-id'] ) || ! isset( $_GET['record'] ) ) {
	wp_die( __( 'Unexpected error: Missing required field(s).', 'keypress-dns' ) );
}

$zone_id  = $_GET['zone-id'];
$record   = $_GET['record'];
$api      = kpdns_get_api();
$zone     = $api->get_zone( sanitize_text_field( $zone_id ) );

?>

<h2><?php _e( 'Delete Record', 'keypress-dns' ); ?></h2>
<?php if( $zone instanceof WP_Error ): ?>
    <p><?php echo $zone->get_error_message(); ?></p>
<?php else: ?>
    <p><?php _e( 'You cannot undo this action. Do you want to delete this record?', 'keypress-dns' ); ?></p>
    <table class="form-table">
        <tbody>
            <tr>
                <th>
                    <label><?php _e('Zone', 'keypress-dns' ); ?></label>
                </th>
                <td >
                    <p><?php echo $zone['name']; ?></p>
                </td>
            </tr>
            <tr>
                <th>
                    <label><?php _e('Record Name', 'keypress-dns' ); ?></label>
                </th>
                <td >
                    <p><?php echo $record['name']; ?></p>
                </td>
            </tr>
            <tr>
                <th>
                    <label><?php _e('Type', 'keypress-dns' ); ?></label>
                </th>
                <td >
                    <p><?php echo $record['type']; ?></p>
                </td>
            </tr>
            <tr>
                <th>
                    <label><?php _e('Value(s)', 'keypress-dns' ); ?></label>
                </th>
                <td >
                    <p><?php echo implode('<br/>', $record['values'] ); ?></p>
                </td>
            </tr>
            <tr>
                <th>
                    <label><?php _e('TTL', 'keypress-dns' ); ?></label>
                </th>
                <td>
                    <p><?php echo $record['ttl']; ?></p>
                </td>
            </tr>
        </tbody>
    </table>
    <form method="post" action="edit.php?action=<?php esc_attr_e( KPDNS_Page_Zones::ACTION_DELETE_RECORD ); ?>" name="kpdns-delete-record" id="kpdns-delete-record">
        <input type="hidden" name="zone-id" value="<?php esc_attr_e( $zone_id ); ?>" />
        <input type="hidden" name="record[name]" value="<?php esc_attr_e( $record['name'] ); ?>" />
        <input type="hidden" name="record[type]" value="<?php esc_attr_e( $record['type'] ); ?>" />
        <input type="hidden" name="record[ttl]" value="<?php esc_attr_e( $record['ttl'] ); ?>" />
        <?php foreach ($record['values'] as $key => $val) : ?>
            <input type="hidden" name="record[values][<?php esc_attr_e( $key ) ?>]" value="<?php esc_attr_e( $val ); ?>"/>
        <?php endforeach;?>
        <?php wp_nonce_field( KPDNS_Page_Zones::ACTION_DELETE_RECORD, KPDNS_Page_Zones::NONCE_DELETE_RECORD ); ?>
        <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_attr_e( 'Delete Record', 'keypress-dns' ); ?>" />
        <a class="button-secondary" href="<?php esc_attr_e( add_query_arg( array( 'page' => KPDNS_PAGE_ZONES, 'view' => KPDNS_Page_Zones::VIEW_EDIT_ZONE, 'zone-id' => urlencode( $zone['id'] ) ), KPDNS_Page::get_admin_url() ) ); ?>" title="<?php esc_attr_e( 'Cancel', 'keypress-dns' ); ?>"><?php _e( 'Cancel', 'keypress-dns' ); ?></a>
    </form>
<?php endif; ?>