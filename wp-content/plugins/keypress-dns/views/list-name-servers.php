<?php
    $api = kpdns_get_api();

    error_log( print_r( $api, true ) );

    if ( is_wp_error( $api ) ) {
        $error = $api;
    } else {
        if ( $api instanceof KPDNS_Custom_NS_API_Imp ) {
            $args = [];
            if ( isset( $_POST['search'] ) ) {
                $args['search'] = $_POST['search'];
            }

            if ( isset( $_GET['paged'] ) ) {
                $args['page'] = $_GET['paged'];
            }

            $name_servers = $api->list_name_servers( $args );
        } else {
            $name_servers = new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( 'Sorry, your managed DNS provider does not support custom NS.', 'keypress-dns' ) );
        }
    }

?>

<h2>
    <?php
        $page_title = __( 'Custom Name Servers', 'keypress-dns' );

        /**
         * Filters the page title.
         *
         * @param string $page_title
         * @return string
         * @since 1.3
         */
        $page_title = apply_filters('kpdns_list_ns_page_title', $page_title);

        echo $page_title;
    ?>
	<?php if ( ! is_wp_error( $name_servers ) ): ?>
        <a href="<?php esc_attr_e( add_query_arg( array( 'page' => KPDNS_PAGE_NAME_SERVERS, 'view' => KPDNS_Page_Name_Servers::VIEW_ADD_NAME_SERVER ), KPDNS_Page::get_admin_url() ) ); ?>" class="page-title-action">
            <?php _e( 'Add New', 'keypress-dns') ?>
        </a>
	<?php endif; ?>
</h2>
<?php
    if ( is_wp_error( $name_servers ) ) {
        KPDNS_Page::render_error( $name_servers );

        /**
         * Fires after the error message.
         *
         * @since 1.3
         */
        do_action('kpdns_list_ns_page_after_error');

    } else {
        $name_servers_count = $name_servers->get_total_items();
        $current_page       = $name_servers->get_current_page();
        $total_pages        = $name_servers->get_pages_count();
        $has_results        = 0 < $name_servers_count;

        $more_info_link = 'https://getkeypress.com/custom-name-servers';
        $page_description = sprintf(
            __('You can use your own set of custom name servers instead of those provided by your Managed DNS provider. For example, ns1.your-domain.com and ns2.your-domain.com, instead of ns-0226.awsdns-15.org and ns-876.awsdns-35.net. More info <a href="%s" target="_blank">here</a>.', 'keypress-dns'),
            $more_info_link
        );

        /**
         * Filters the page description text.
         *
         * @param string $page_description
         * @param string $more_info_link
         * @return string
         * @since 1.3
         */
        $page_description = apply_filters('kpdns_list_ns_page_description', $page_description, $more_info_link);

        if ( ! empty( $page_description ) ) {
            echo '<div style="margin-bottom: 50px"><p>' . $page_description . '</div></p>';
        }

        /**
         * Fires after the page description.
         *
         * @since 1.3
         */
        do_action('kpdns_list_ns_page_after_description');

            if ( $has_results ) {
            kpdns_list_ns_render_search_box();
        }

        $action = KPDNS_Page::get_form_action_url(KPDNS_Page_Name_Servers::ACTION_LIST_NS_BULK_ACTIONS );

        echo '<form method="post" action="' . $action . '">';

        wp_nonce_field( KPDNS_Page_Name_Servers::ACTION_LIST_NS_BULK_ACTIONS, KPDNS_Page_Name_Servers::NONCE_LIST_NS_BULK_ACTIONS );

        if ( $has_results ) {
            kpdns_list_ns_bulk_actions_and_pagination( $name_servers_count, $total_pages, $current_page, $location = 'top' );
        }

        kpdns_list_ns_table( $name_servers );

        if ( $has_results ) {
            kpdns_list_ns_bulk_actions_and_pagination( $name_servers_count, $total_pages, $current_page, $location = 'bottom' );
        }

        echo '</form>';
    }
?>

<?php

    function kpdns_list_ns_render_search_box() {
        $search_action = add_query_arg(array('page' => KPDNS_PAGE_NAME_SERVERS), KPDNS_Page::get_admin_url());
        $search_label = __('Search Name Servers:', 'keypress-dns');
        $search_btn_txt = __('Search Name Servers', 'keypress-dns');
        KPDNS_PAGE::render_search_box( $search_action, $search_label, $search_btn_txt );
    }

    function kpdns_list_ns_bulk_actions_and_pagination( $name_servers_count, $total_pages, $current_page, $location = 'top' ) {
        echo '<div class="tablenav ' . $location . '" id="tablenav">';
        $bulk_actions = array(
            'delete' => array(
                'value' => KPDNS_Page_Name_Servers::ACTION_BULK_DELETE_NS,
                'label' => __( 'Delete', 'keypress-dns' ),
            ),
        );

        switch ( $location ) {
            case 'top':
                $action_name = 'action';
                break;
            case 'bottom':
                $action_name = 'action2';
                break;
            default:
                $action_name = '';
        }

        KPDNS_Page::render_bulk_actions( $bulk_actions, $location, $action_name );
        KPDNS_Page::render_pagination( $name_servers_count, $total_pages, $current_page, $location );
        echo '<br class="clear">';
        echo '</div>';
    }

    function kpdns_list_ns_table( $name_servers ) {
        ?>
            <table class="widefat striped">
                <thead>
                <tr>
                    <td id="cb" class="manage-column column-cb check-column"><label class="screen-reader-text" for="cb-select-all-1"><?php _e( 'Select All', 'keypress-dns' ) ?></label><input id="cb-select-all-1" type="checkbox"></td>
                    <th scope="col" class="manage-column"><?php esc_html_e( 'Domain Name', 'keypress-dns' ) ?></th>
                    <th scope="col" class="manage-column"><?php esc_html_e( 'NS Records', 'keypress-dns' ) ?></th>
                </tr>
                </thead>
                <tbody id="the-list">
                <?php if( ! isset( $name_servers ) || 0 === $name_servers->count() ) : ?>
                    <tr>
                        <td colspan="6"><?php _e( 'No custom name servers found', 'keypress-dns' ) ?></td>
                    </tr>
                <?php else : ?>
                    <?php
                        foreach ( $name_servers as $name_server ) :

                            $is_default_ns = kpdns_is_default_custom_ns( $name_server );
                            $ns            = $name_server->get_ns();
                            /*
                            if ( isset( $zone ) ) {
                                $records = $api->list_records( $zone->get_id() );
                                if ( ! is_wp_error( $records ) ) {
                                    foreach ( $records as $record ) {
                                        if ( $record->get_type() === KPDNS_Record::TYPE_NS ) {
                                            $rdata = $record->get_rdata();
                                            if ( isset( $rdata ) && ! empty( $rdata ) && isset( $rdata[ KPDNS_Record::RDATA_KEY_VALUE ] )  ) {
                                                $ns[] = $rdata[ KPDNS_Record::RDATA_KEY_VALUE ];
                                            }
                                        }
                                    }
                                }
                            }
                            */
                        ?>
                        <tr>
                            <th scope="row" class="check-column">
                                <input type="checkbox" name="name-servers[]" value="<?php esc_attr_e( $name_server->get_id() ); ?>">
                            </th>
                            <td scope="col" class="has-row-actions">
                                <?php
                                esc_html_e( $name_server->get_domain() );
                                /*
                                if ( isset( $zone ) ) {
                                    esc_html_e( rtrim( $zone->get_domain(), '.' ) );
                                } else {
                                    esc_html_e( ORPHANED_CUSTOM_NS_NAME );
                                    KPDNS_Page::render_tooltip( __( 'This Custom NS has no associated zones. You should create a zone or delete this NS.', 'keypress-dns' ) );
                                }
                                */

                                ?>
                                <?php if ( $is_default_ns ) : ?>
                                    <strong> — <?php esc_html_e( 'Default', 'keypress-dns' ); ?></strong>
                                <?php endif; ?>
                                <div class="row-actions">
                                    <?php if ( /*isset( $zone )*/ true ) : ?>
                                        <span class="edit">
                                                <?php if ( $is_default_ns ) : ?>
                                                    <a href="<?php echo esc_url( KPDNS_Page::get_link_action_url( KPDNS_Page_Name_Servers::ACTION_UNSET_DEFAULT_NS, KPDNS_Page_Name_Servers::NONCE_UNSET_DEFAULT_NS, $name_server->to_array() ) ); ?>"><?php esc_html_e( 'Unset as default', 'keypress-dns' ) ?></a> |
                                                <?php else : ?>
                                                    <a href="<?php echo esc_url( KPDNS_Page::get_link_action_url( KPDNS_Page_Name_Servers::ACTION_SET_DEFAULT_NS, KPDNS_Page_Name_Servers::NONCE_SET_DEFAULT_NS, $name_server->to_array() ) ); ?>"><?php esc_html_e( 'Set as default', 'keypress-dns' ) ?></a> |
                                                <?php endif; ?>
                                        </span>
                                        <span class="edit">
                                            <a href="<?php echo esc_url( add_query_arg( array( 'page' => KPDNS_PAGE_ZONES, 'view' => KPDNS_Page_Zones::VIEW_EDIT_ZONE, 'zone-id' => urlencode( $name_server->get_zone_id()/*$zone->get_id()*/ ) ), KPDNS_Page::get_admin_url() ) ); ?>"><?php _e( 'Edit Zone', 'keypress-dns' ) ?></a> |
                                        </span>
                                    <?php endif; ?>
                                    <span class="trash">
                                        <a href="<?php echo esc_url( KPDNS_Page::get_link_action_url( KPDNS_Page_Name_Servers::ACTION_DELETE_NAME_SERVER, KPDNS_Page_Name_Servers::NONCE_DELETE_NAME_SERVER, $name_server->to_array() ) ); ?>" class="kpdns-delete-ns submitdelete" data-name-server="<?php esc_attr_e( $name_server->get_domain() ); ?>"><?php esc_html_e( 'Delete', 'keypress-dns' ) ?></a>
                                    </span>
                                </div>
                            </td>
                            <th scope="col" class="manage-column">
                                <?php echo empty( $ns ) ? '-' : implode( '<br/>', $ns ); ?>
                            </th>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
                <tfoot>
                <tr>
                    <td class="manage-column column-cb check-column"><label class="screen-reader-text" for="cb-select-all-2"><?php _e( 'Select All', 'keypress-dns' ) ?></label><input id="cb-select-all-2" type="checkbox"></td>
                    <th scope="col" class="manage-column"><?php esc_html_e( 'Domain Name', 'keypress-dns' ) ?></th>
                    <th scope="col" class="manage-column"><?php esc_html_e( 'NS Records', 'keypress-dns' ) ?></th>
                </tr>
                </tfoot>
            </table>
        <?php
    }
?>
