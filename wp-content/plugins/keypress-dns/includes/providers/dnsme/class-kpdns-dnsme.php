<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'KPDNS_DNSME' ) ) {

    /**
     * Class KPDNS_DNSME_Hooks
     *
     * @since 1.2.1
     */
	class KPDNS_DNSME extends KPDNS_Provider implements KPDNS_Registrable {

	    public function __construct() {
	        $id          = 'dnsme';
	        $name        = __( 'DNS Made Easy', 'keypress-dns' );
	        $url         = 'https://dnsmadeeasy.com/';
            $credentials = new KPDNS_DNSME_Credentials();
            $api         = new KPDNS_DNSME_API( $credentials );
            parent::__construct( $id, $name, $url, $credentials, $api );
        }

        public function register() {

            //add_action( 'kpdns_add_zone_form_after_fields', __CLASS__ . '::add_zone_form_after_fields' );
            //add_filter( 'kpdns_add_zone_validate', __CLASS__ . '::add_zone_validate', 10, 2 );

            add_action( 'kpdns_edit_zone_form_after_fields', array( $this, 'edit_zone_form_after_fields' ), 10 );

            add_filter( 'kpdns_edit_zone_args', array( $this, 'edit_zone_args' ), 10, 2 );

            add_filter( 'kpdns_update_zone_validate', array( $this, 'update_zone_validate' ), 10, 2 );

            add_filter( 'kpdns_list_zones_columns', array( $this, 'list_zones_columns' ) );
            add_filter( 'kpdns_list_zones_table_row_data', array( $this, 'list_zones_table_row_data' ), 10, 2 );

            add_filter( 'kpdns_record_types', array( $this, 'filter_record_types' ) );

            add_filter( 'kpdns_add_zone_success_messages', array( $this, 'filter_add_zone_success_messages' ) );

            add_filter( 'kpdns_list_zones_check_col_cell_html', array( $this, 'filter_list_zones_check_col_cell_html' ), 10, 2 );

            add_filter( 'kpdns_list_zones_domain_cell_html', array( $this, 'filter_list_zones_domain_cell_html' ), 10, 2 );

            add_filter( 'kpdns_list_zones_row_actions', array( $this, 'filter_list_zones_row_actions' ), 10, 2 );

            add_action( 'network_admin_notices', array( $this, 'maybe_render_not_default_ns_admin_notice' ) );

            add_filter( 'kpdns_add_name_server_args', array( $this, 'filter_add_name_server_args' ), 10, 2 );

            add_filter( 'kpdns_set_default_custom_ns_query_args', array( $this, 'filter_set_default_custom_ns_query_args' ), 10, 2 );

            add_filter( 'kpdns_unset_default_custom_ns_query_args', array( $this, 'filter_unset_default_custom_ns_query_args' ), 10, 2 );

            add_filter( 'kpdns_list_ns_page_description', array( $this, 'filter_list_ns_page_description' ), 10, 2 );

            add_filter( 'kpdns_is_default_custom_ns', array( $this, 'filter_is_default_custom_ns' ), 10, 2 );

            add_filter( 'kpdns_list_zones_bulk_delete_success_message', array( $this, 'filter_list_zones_bulk_delete_success_message' ) );
        }

        public function add_zone_form_after_fields( $zone ) {
            ?>
            <table class="form-table">
                <tbody>
                <tr>
                    <th>
                        <label for="kpdns-zone-status"><?php _e( 'Description', 'keypress-dns' ) ?></label>
                    </th>
                    <td>
                        <input type="text" name="zone[description]" value="<?php esc_attr_e( isset( $_GET['zone'] ) && isset( $_GET['zone']['description'] ) ? $_GET['zone']['description'] : '' ) ?>" class="regular-text"/>
                    </td>
                </tr>
                </tbody>
            </table>
            <?php
        }

        public function edit_zone_form_after_fields( $zone ) {
            if ( $zone instanceof KPDNS_DNSME_Zone ) {
                ?>
                    <table class="form-table">
                        <tbody>
                            <tr>
                                <th>
                                    <label for="kpdns-zone-status"><?php _e( 'Status', 'keypress-dns' ) ?></label>
                                </th>
                                <td >
                                    <?php
                                        echo $zone->get_status_string();
                                        if ( $zone->get_status() === 1 ) {// Creating
                                            echo sprintf(
                                                '<p class="description">%s</p>',
                                                __( 'This zone is currently being created by DNS Made Easy. Creation may take a few minutes.', 'keypress-dns' )
                                            );
                                        }
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <th>
                                    <label for="kpdns-zone-update-date"><?php _e( 'Last Update', 'keypress-dns' ) ?></label>
                                </th>
                                <td ><?php esc_html_e( date("F j, Y", substr($zone->get_updated(), 0, 10))) ?></td>
                            </tr>
                            <?php
                                $api = kpdns_get_api();
                                if ( $api instanceof KPDNS_Custom_NS_API_Imp && ! $zone->is_custom_ns() ) {
                                    $custom_name_servers = $api->list_name_servers();
                                    if ( ! is_wp_error( $custom_name_servers ) ) {
                                        ?>
                                            <tr>
                                                <th><?php _e( 'Custom NS', 'keypress-dns' ) ?></th>
                                                <td >
                                                    <select name="zone[custom-ns]">
                                                        <option value="-1"><?php _e( 'None', 'keypress-dns' ); ?></option>
                                                        <?php
                                                            foreach ( $custom_name_servers as $custom_name_server ) {
                                                                ?>
                                                                <option value="<?php echo $custom_name_server->get_id(); ?>" <?php selected( $zone->get_vanity_id(), $custom_name_server->get_id() ); ?>>
                                                                    <?php
                                                                        $is_default_ns = kpdns_is_default_custom_ns( $custom_name_server );
                                                                        $output        = $custom_name_server->get_domain();
                                                                        if ( $is_default_ns ) {
                                                                            $output .= ' (default)';
                                                                        }
                                                                        echo $output;
                                                                    ?>
                                                                </option>
                                                                <?php
                                                            }
                                                        ?>
                                                    </select>
                                                </td>
                                            </tr>
                                        <?php
                                    }
                                }
                            ?>
                        </tbody>
                    </table>
                <?php
            }
        }

        public function add_zone_validate( $is_valid, $zone ) {
            // TODO
            /*
            if ( ! isset( $zone['status'] ) ) {
                return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( 'Missing required field "Status".', 'keypress-dns' ) );
            }

            if ( ! array_key_exists( $zone['status'], KPDNS_ClouDNS_Zone::get_zone_statuses() ) ) {
                return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( 'Invalid value for "Status" field.', 'keypress-dns' ) );
            }
            */

            return $is_valid;
        }

        /**
         * @param $is_valid
         * @param $zone
         * @return bool|WP_Error
         */
        public function update_zone_validate( $is_valid, $zone ) {

            if ( isset( $zone['description'] ) && 256 < strlen( $zone['description'] ) ) {
                return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( 'The maximum length of the "Description" field is 256 characters.', 'keypress-dns' ) );
            }

            return $is_valid;
        }

        public function list_zones_columns( $zones_columns ) {
            $zones_columns['status']   = __( 'Status', 'keypress-dns' );
            $zones_columns['updated-on']   = __( 'Last Update', 'keypress-dns' );
            return $zones_columns;
        }

        public function list_zones_table_row_data( $row_data, $zone ) {
            if ( ! $zone instanceof KPDNS_DNSME_Zone ) {
                return $row_data;
            }

            $row_data['status']  = $zone->get_status_string();
            $row_data['updated-on']  = ! empty( $zone->get_updated() ) ? date("F j, Y", substr($zone->get_updated(), 0, 10)) : '-';
            
            return $row_data;
        }

        public function filter_record_types( $types ) {
            unset( $types[ KPDNS_Record::TYPE_SOA ] );
            return $types;
        }

        public function filter_add_zone_success_messages( $messages ) {
            $messages[] = __( 'DNS Made Easy automatically creates a set of default records. This may take a few seconds, so they may not appear now.', 'keypress-dns' );
            return $messages;
        }

        public function filter_list_zones_check_col_cell_html( $html, $zone ) {
            if ( $zone->get_status() === 3 || $zone->get_status() === 1 ) {
                return '<span class="dashicons dashicons-lock" style="margin: 0 0 0 8px;"></span>';
            }

            return $html;
        }

        public function filter_list_zones_domain_cell_html( $html, $zone ) {
            if ( $zone->get_status() === 3 ) {
                $html = '<span style="text-decoration: line-through;">' . $zone->get_domain() . '</span>';
                $html .= KPDNS_Page::get_tooltip_hml( __( 'This zone is currently pending deletion from DNS Made Easy. Deletion may take a few minutes.', 'keypress-dns' ) );
            }

            return $html;
        }

        public function filter_list_zones_row_actions( $row_actions, $zone ) {
            if ( $zone->get_status() === 3 ) {
                return array();
            }

            if ( $zone->get_status() === 1 ) {
                unset( $row_actions['trash'] );
            }

            return $row_actions;
        }

        public function edit_zone_args( $args, $zone ) {
            if ( isset( $zone['custom-ns'] ) ) {
                $args['custom-ns'] = $zone['custom-ns'];
            }
            return $args;
        }

        public function maybe_render_not_default_ns_admin_notice() {
            if ( ! is_admin() ) {
                return;
            } elseif ( ! is_user_logged_in() ) {
                return;
            } elseif ( ! current_user_can( 'update_plugins' ) ) {
                return;
            }

            $default_ns = $this->api->check_default_ns();

            if ( $default_ns instanceof WP_Error ) {
                $type    = 'error';
                $message = $default_ns->get_error_message( KPDNS_ERROR_CODE_GENERIC );
                echo '<div class="notice notice-' . $type . ' is-dismissible">';
                echo '<p>' . $message . '</p>';
                echo '</div>';
            }
        }

        public function filter_add_name_server_args( $args, $name_server ) {
            if ( isset( $name_server['default'] ) && 'true' === $name_server['default'] ) {
                $args['default'] = true;
            }
            return $args;
        }

        public function filter_set_default_custom_ns_query_args( $query_args, $name_server ) {
            return self::_filter_default_custom_ns_query_args( $query_args, $name_server, true );
        }

        public function filter_unset_default_custom_ns_query_args( $query_args, $name_server ) {
            return self::_filter_default_custom_ns_query_args( $query_args, $name_server, false );
        }

        private function _filter_default_custom_ns_query_args( $query_args, $name_server, $default ) {

            if ( is_wp_error( $this->api ) ) {
                $query_args['messages'] = urlencode_deep( array( $this->api->get_error_message( KPDNS_ERROR_CODE_GENERIC ) ) );
                $query_args['updated']  = urlencode( 'false' );
                return $query_args;
            }

            $args        = array(
                'default' => $default,
            );

            $response = $this->api->edit_name_server( $name_server, $args );
            if ( is_wp_error( $response ) ) {
                $query_args['messages'] = urlencode_deep( array( $response->get_error_message( KPDNS_ERROR_CODE_GENERIC ) ) );
                $query_args['updated']  = urlencode( 'false' );
                return $query_args;
            }

            return $query_args;
        }

        public function filter_list_ns_page_description( $page_description, $more_info_link ) {
            $page_description = sprintf(
                __('You can use your own set of custom name servers instead of those provided by your Managed DNS provider. For example, ns1.your-domain.com and ns2.your-domain.com, instead of ns1.dnsmadeeasy.com and ns2.dnsmadeeasy.com. More info <a href="%s" target="_blank">here</a>.', 'keypress-dns'),
                $more_info_link
            );
            return $page_description;
        }

        public function filter_is_default_custom_ns( $is_default_ns, $name_server ) {
            return $is_default_ns && $name_server->is_default();
        }

        public function filter_list_zones_bulk_delete_success_message( $success_message ) {
            return __( 'Zones deleted successfully. DNSME might take some minutes to complete the deletion. Deleted zones will be listed with status "pending deletion" until they are actually deleted by DNSME.', 'keypress-dns' );
        }
    }
}