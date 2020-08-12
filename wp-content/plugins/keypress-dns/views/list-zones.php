<?php

$api = kpdns_get_api();

if ( $api instanceof WP_Error ) {
	$zones = $api;
} else {
    $args = [];
    if ( isset( $_POST['search'] ) ) {
        $args['search'] = $_POST['search'];
    }

    if ( isset( $_GET['paged'] ) ) {
        $args['page'] = $_GET['paged'];
    }

	$zones = $api->list_zones( $args );
}

?>

<h2>
    <?php _e( 'Zones', 'keypress-dns' ); ?>
    <?php if ( ! is_wp_error( $zones ) ): ?>
        <a href="<?php esc_attr_e( add_query_arg( array( 'page' => KPDNS_PAGE_ZONES, 'view' => KPDNS_Page_Zones::VIEW_ADD_ZONE ), KPDNS_Page::get_admin_url() ) ); ?>" class="page-title-action"><?php _e( 'Add New', 'keypress-dns') ?></a>
    <?php endif; ?>
</h2>
<?php if ( is_wp_error( $zones ) ): ?>
    <?php KPDNS_Page::render_error( $zones ); ?>
<?php else: ?>
    <?php
        $zones_count  = $zones->get_total_items(); // TODO When searching, on ClouDNS, displays the total amount of items, not the search results.
        $current_page = $zones->get_current_page();
        $total_pages  = $zones->get_pages_count();
    ?>
    <form method="post" action="<?php esc_attr_e( add_query_arg( array( 'page' => KPDNS_PAGE_ZONES ), KPDNS_Page::get_admin_url() ) ); ?>">
        <p class="search-box">
            <label class="screen-reader-text" for="zone-search-input"><?php esc_html_e( __( 'Search Zones:', 'keypress-dns' ) );?></label>
            <input type="search" id="zone-search-input" name="search" value="<?php echo isset( $_POST['search'] ) ? esc_attr( $_POST['search'] ) : '' ?>">
            <input type="submit" id="search-submit" class="button" value="<?php esc_attr_e( __( 'Search Zones', 'keypress-dns' ) );?>">
        </p>
    </form>
    <form method="post" action="<?php esc_attr_e( KPDNS_Page::get_form_action_url(KPDNS_Page_Zones::ACTION_LIST_ZONES_BULK_ACTIONS ) ); ?>">
        <?php wp_nonce_field( KPDNS_Page_Zones::ACTION_LIST_ZONES_BULK_ACTIONS, KPDNS_Page_Zones::NONCE_LIST_ZONES_BULK_ACTIONS ); ?>

        <!-- Top pagination -->
        <?php if ( 0 < $zones_count ) : ?>
            <div class="tablenav top" id="tablenav">

                <!-- Bulkactions -->
                <div class="alignleft actions bulkactions" id="bulkactions">
                    <div class="bulkactions-fields" style="display: inline-block;">
                        <label for="bulk-action-selector-top" class="screen-reader-text"><?php _e( 'Select bulk action', 'keypress-dns' ) ?></label>
                        <select name="action" id="bulk-action-selector-top" class="bulk-action-selector">
                            <option value="-1"><?php _e( 'Bulk Actions', 'keypress-dns' ) ?></option>
                            <option value="<?php esc_attr_e( KPDNS_Page_Zones::ACTION_BULK_DELETE_ZONES );?>"><?php _e( 'Delete', 'keypress-dns' ) ?></option>
                            <option value="<?php esc_attr_e( KPDNS_Page_Zones::ACTION_BULK_UPDATE_A_RECORDS );?>"><?php _e( 'Update A Records', 'keypress-dns' ) ?></option>
                            <option value="<?php esc_attr_e( KPDNS_Page_Zones::ACTION_BULK_UPDATE_AAAA_RECORDS );?>"><?php _e( 'Update AAAA Records', 'keypress-dns' ) ?></option>
                        </select>
                    </div>
                    <input type="submit" id="doaction" name="submit" class="button action" value="<?php esc_attr_e( 'Apply', 'keypress-dns' ) ?>">
                </div>
                <!-- End bulkactions -->

                <?php
                    KPDNS_Page::render_pagination( $zones_count, $total_pages, $current_page, 'top' );
                ?>
                <br class="clear">
            </div>
        <?php endif; ?>
        <!-- End top pagination -->

        <?php
            $zones_columns = array(
                'domain' => __( 'Domain', 'keypress-dns'),
               // __( 'Description', 'keypress-dns'),
               // __( 'Site', 'keypress-dns'),
            );

            /**
             * Filters the columns displayed in the Zones list table.
             *
             * @since 1.1
             *
             * @param string[] $zones_columns An associative array of column headings.
             */
            $zones_columns = apply_filters( 'kpdns_list_zones_columns', $zones_columns );

            $allowed_html = array(
                'a'    => array(
                    'href' => array(),
                    'style' => array(),
                    'class' => array()
                ),
                'span' => array(
                    'style' => array(),
                    'class' => array(),
                    'alt'   => array(),
                    'title' => array(),
                ),
                'div' => array(
                    'style' => array(),
                    'class' => array()
                ),
                'p' => array(
                    'style' => array(),
                    'class' => array()
                ),
                'input' => array(
                    'type' => array(),
                    'name' => array(),
                    'value' => array(),
                    'style' => array(),
                    'class' => array(),
                ),
                'em' => array(),
                'strong' => array(),
            );
        ?>
        <table class="widefat striped">
            <thead>
                <tr>
                    <td id="cb" class="manage-column column-cb check-column">
                        <label class="screen-reader-text" for="cb-select-all-1"><?php _e( 'Select All', 'keypress-dns' ) ?></label>
                        <input id="cb-select-all-1" type="checkbox">
                    </td>
                    <?php foreach ( $zones_columns as $column ): ?>
                        <th scope="col" class="manage-column"><?php esc_html_e( $column ); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody id="the-list">
            <?php if( 0 === $zones_count ) : ?>
                <tr>
                    <td colspan="6"><?php _e( 'No DNS zones found', 'keypress-dns' ) ?></td>
                </tr>
            <?php else : ?>
                <?php foreach ( $zones as $zone ) : ?>
                    <tr>
                        <th scope="row" class="check-column">
                            <?php
                                if ( $zone->is_primary() || $zone->is_custom_ns() ) {
                                    $check_col_cell_html = '<span class="dashicons dashicons-lock" style="margin: 0 0 0 8px;"></span>';
                                } else  {
                                    $check_col_cell_html = sprintf(
                                        '<input type="checkbox" name="zones[]" value="%s">',
                                        $zone->get_id()
                                    );
                                }

                                /**
                                 * Filters the check column cell html.
                                 *
                                 * @since 1.3
                                 *
                                 * @param string $check_col_cell_html
                                 * @param KPDNS_Zone $zone
                                 */
                                $check_col_cell_html = apply_filters( 'kpdns_list_zones_check_col_cell_html', $check_col_cell_html, $zone );

                                echo $check_col_cell_html;
                            ?>

                        </th>

                        <?php
                            $row_data = array(
                                'domain' => rtrim( $zone->get_domain(), '.' ),
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
                            $row_data = apply_filters( 'kpdns_list_zones_table_row_data', $row_data, $zone );

                            foreach ( $row_data as $data_key => $data_value ) {
                                if ( 'domain' === $data_key ) {
                                    $row_actions = array(
                                        'edit' => array(
                                            'text' => __( 'Edit Zone', 'keypress-dns' ),
                                            'url'  => add_query_arg( array( 'page' => KPDNS_PAGE_ZONES, 'view' => KPDNS_Page_Zones::VIEW_EDIT_ZONE, 'zone-id' => urlencode( $zone->get_id() ) ), KPDNS_Page::get_admin_url() ),
                                        ),
                                    );

                                    if ( ! $zone->is_primary() && ! $zone->is_custom_ns() ) {
                                        $row_actions['trash'] = array(
                                            'text'  => __( 'Delete', 'keypress-dns' ),
                                            'url'   => KPDNS_Page::get_link_action_url( KPDNS_Page_Zones::ACTION_DELETE_ZONE, KPDNS_Page_Zones::NONCE_DELETE_ZONE, array( 'zone-id' => $zone->get_id() ) ),
                                            'class' => 'kpdns-delete-zone submitdelete',
                                        );
                                    }

                                    /**
                                     * Filters the row actions.
                                     *
                                     * @since 1.3
                                     *
                                     * @param array $row_actions
                                     * @param KPDNS_Zone $zone
                                     */
                                    $row_actions = apply_filters( 'kpdns_list_zones_row_actions', $row_actions, $zone );

                                    $has_row_actions = isset( $row_actions ) && ! empty( $row_actions);

                                    ?>
                                    <td scope="col" <?php echo $has_row_actions ? 'class="has-row-actions"' : '' ?>>
                                    <?php

                                    $cell_html = sprintf(
                                        '<a href="%1$s">%2$s</a>',
                                        esc_url( add_query_arg( array( 'page' => KPDNS_PAGE_ZONES, 'view' => KPDNS_Page_Zones::VIEW_EDIT_ZONE, 'zone-id' => urlencode( $zone->get_id() ) ), KPDNS_Page::get_admin_url() ) ),
                                        $data_value
                                    );

                                    /**
                                     * Filters the domain cell html.
                                     *
                                     * @since 1.3
                                     *
                                     * @param string $cell_html
                                     * @param KPDNS_Zone $zone
                                     */
                                    $cell_html = apply_filters( 'kpdns_list_zones_domain_cell_html', $cell_html, $zone );

                                    echo wp_kses( $cell_html, $allowed_html );
                                    ?>

                                    <?php if ( $zone->is_primary() ) : ?>
                                     <strong> —
                                         <?php
                                         esc_html_e( 'Primary Zone', 'keypress-dns' );
                                         KPDNS_Page::render_tooltip( __( 'This zone\'s domain matches the domain of your WordPress install. If you delete it, your site could stop working.', 'keypress-dns' ) );
                                         ?>
                                     </strong>
                                    <?php endif; ?>
                                    <?php if ( $zone->is_custom_ns() ) : ?>
                                     <strong> —
                                         <?php
                                         esc_html_e( 'Custom NS', 'keypress-dns' );
                                         KPDNS_Page::render_tooltip( __( 'This area belongs to one of your Custom NS. If you delete it, the sites whose domains point to those NS will stop working.', 'keypress-dns' ) );
                                         ?>
                                     </strong>
                                    <?php endif; ?>
                                    <?php
                                    if ( $has_row_actions ) :
                                        ?>
                                            <div class="row-actions">
                                                <?php
                                                    $count_row_actions = count( $row_actions );
                                                    $counter            = 0;

                                                    foreach ( $row_actions as $action_id => $action ) {
                                                        $attrs = '';
                                                        if ( isset( $action['class'] ) ) {
                                                            $attrs .= ' class="' . $action['class'] . '"';
                                                        }
                                                        if ( $action_id === 'trash' ) {
                                                            $attrs .= ' data-zone="' . $zone->get_domain() . '"';
                                                        }
                                                        ?>
                                                            <span class="<?php echo esc_attr( $action_id ); ?>">
                                                                <a href="<?php echo esc_url( $action['url'] ); ?>"<?php echo $attrs; ?>><?php echo $action['text'] ?></a>
                                                                <?php
                                                                    if ( ++$counter < $count_row_actions ) {
                                                                        echo ' | ';
                                                                    }
                                                                ?>
                                                            </span>
                                                        <?php
                                                    }
                                                ?>
                                            </div>
                                        <?php
                                    endif;
                                    ?>
                                    </td>
                                    <?php
                                } else {
                                 ?>
                                    <td scope="col">
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
                    <td class="manage-column column-cb check-column">
                        <label class="screen-reader-text" for="cb-select-all-2"><?php _e( 'Select All', 'keypress-dns' ) ?></label>
                        <input id="cb-select-all-2" type="checkbox">
                    </td>
                    <?php foreach ( $zones_columns as $column ): ?>
                        <th scope="col" class="manage-column"><?php esc_html_e( $column ); ?></th>
                    <?php endforeach; ?>
                </tr>
            </tfoot>
        </table>

        <!-- Bottom pagination -->
        <?php if ( 0 < $zones_count ) : ?>
            <div class="tablenav bottom">

                <!-- Bulkactions -->
                <div class="alignleft actions bulkactions">
                    <div class="bulkactions-fields" style="display: inline-block;">
                        <label for="bulk-action-selector-bottom" class="screen-reader-text"><?php _e( 'Select bulk action', 'keypress-dns' ) ?></label>
                        <select name="action2" id="bulk-action-selector-bottom" class="bulk-action-selector">
                            <option value="-1"><?php _e( 'Bulk Actions', 'keypress-dns' ) ?></option>
                            <option value="<?php esc_attr_e( KPDNS_Page_Zones::ACTION_BULK_DELETE_ZONES );?>"><?php _e( 'Delete', 'keypress-dns' ) ?></option>
                            <option value="<?php esc_attr_e( KPDNS_Page_Zones::ACTION_BULK_UPDATE_A_RECORDS );?>"><?php _e( 'Update A Records', 'keypress-dns' ) ?></option>
                            <option value="<?php esc_attr_e( KPDNS_Page_Zones::ACTION_BULK_UPDATE_AAAA_RECORDS );?>"><?php _e( 'Update AAAA Records', 'keypress-dns' ) ?></option>
                        </select>
                    </div>
                    <input type="submit" id="doaction2" class="button action" value="<?php esc_attr_e( 'Apply', 'keypress-dns' ); ?>">
                </div>
                <!-- End bulkactions -->

                <div class="alignleft actions"></div>
                <!-- <div class="tablenav-pages one-page"> -->
                <?php
                    KPDNS_Page::render_pagination( $zones_count, $total_pages, $current_page, 'bottom' );
                ?>
                <br class="clear">
            </div>
        <?php endif; ?>
        <!-- End bottom pagination -->

    </form>
<?php endif; ?>