<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'KPDNS_Cloudflare' ) ) {

    /**
     * Class KPDNS_DNSME_Hooks
     *
     * @since 1.2.1
     */
	class KPDNS_Cloudflare extends KPDNS_Provider implements KPDNS_Registrable {

	    public function __construct() {
	        $id          = 'cloudflare';
	        $name        = __( 'Cloudflare', 'keypress-dns' );
	        $url         = 'https://www.cloudflare.com/dns/';
            $credentials = new KPDNS_Cloudflare_Credentials();
            $api         = new KPDNS_Cloudflare_API( $credentials );
            parent::__construct( $id, $name, $url, $credentials, $api );
        }

        public function register() {
            //add_action( 'kpdns_add_zone_form_after_fields', __CLASS__ . '::add_zone_form_after_fields' );
            //add_filter( 'kpdns_add_zone_validate', __CLASS__ . '::add_zone_validate', 10, 2 );

            add_action( 'kpdns_edit_zone_form_after_fields', array( $this, 'edit_zone_form_after_fields' ), 10 );
            add_filter( 'kpdns_update_zone_validate', array( $this, 'update_zone_validate' ), 10, 2 );

            add_filter( 'kpdns_list_zones_columns', array( $this, 'list_zones_columns' ) );
            add_filter( 'kpdns_list_zones_table_row_data', array( $this, 'list_zones_table_row_data' ), 10, 2 );

            add_filter( 'kpdns_record_ttl_field_config', array( $this, 'filter_record_ttl_field_config' ) );

            add_filter( 'kpdns_default_records_ttl', function( $ttl ) { return 120; } );

            add_filter( 'kpdns_record_types', array( $this, 'filter_record_types' ) );

            add_filter( 'kpdns_add_zone_success_messages', array( $this, 'filter_add_zone_success_messages' ) );
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
            if ( $zone instanceof KPDNS_Cloudflare_Zone ) {
                ?>
                <table class="form-table">
                    <tbody>
                    <tr>
                        <th>
                            <label for="kpdns-zone-status"><?php _e( 'Status', 'keypress-dns' ) ?></label>
                        </th>
                        <td ><?php esc_html_e( $zone->get_status() ) ?></td>
                    </tr>
                    <tr>
                        <th>
                            <label for="kpdns-zone-creation-date"><?php _e( 'Creation Date', 'keypress-dns' ) ?></label>
                        </th>
                        <td ><?php esc_html_e( date( 'F j, Y, g:i a', strtotime( $zone->get_created_on() ) ) ) ?></td>
                    </tr>
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
            $zones_columns['created-on']   = __( 'Creation Date', 'keypress-dns' );
            return $zones_columns;
        }

        public function list_zones_table_row_data( $row_data, $zone ) {
            if ( ! $zone instanceof KPDNS_Cloudflare_Zone ) {
                return $row_data;
            }

            $row_data['status']  = ! empty( $zone->get_status() ) ? $zone->get_status() : '-';
            $row_data['created-on']  = ! empty( $zone->get_created_on() ) ? date( 'F j, Y, g:i a', strtotime( $zone->get_created_on() ) ) : '-';

            return $row_data;
        }

        public function filter_record_ttl_field_config( $config ) {
            $config['description'] = __( 'Must be bigger than 120 seconds, or 1 for automatic.', 'keypress-dns' );
            return $config;
        }

        public function filter_record_types( $types ) {
            unset( $types[ KPDNS_Record::TYPE_SOA ] );
            return $types;
        }

        public function filter_add_zone_success_messages( $messages ) {
            $messages[] = __( 'CloudFlare automatically creates a set of default records. This may take a few seconds, so they may not appear now.', 'keypress-dns' );
            return $messages;
        }
    }
}