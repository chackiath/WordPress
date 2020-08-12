<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'KPDNS_GCDNS' ) ) {

    /**
     * Class KPDNS_GCDNS
     *
     * @since 1.2.1
     */
	class KPDNS_GCDNS extends KPDNS_Provider implements KPDNS_Registrable {

	    public function __construct() {
	        $id          = 'gcdns';
	        $name        = __( 'Google Cloud DNS', 'keypress-dns' );
	        $url         = 'https://cloud.google.com/dns/';
            $credentials = new KPDNS_GCDNS_Credentials();
            $api         = new KPDNS_GCDNS_API( $credentials );
            parent::__construct( $id, $name, $url, $credentials, $api );
        }

        public function register() {
            add_action( 'kpdns_add_zone_form_after_fields', array( $this, 'add_zone_form_after_fields' ) );
            add_filter( 'kpdns_add_zone_validate', array( $this, 'add_zone_validate' ), 10, 2 );

            add_action( 'kpdns_edit_zone_form_after_fields', array( $this, 'edit_zone_form_after_fields' ), 10 );
            add_filter( 'kpdns_update_zone_validate', array( $this, 'update_zone_validate' ), 10, 2 );

            add_filter( 'kpdns_list_zones_columns', array( $this, 'list_zones_columns' ) );
            add_filter( 'kpdns_list_zones_table_row_data', array( $this, 'list_zones_table_row_data' ), 10, 2 );
        }

        public static function add_zone_form_after_fields( $zone ) {
            ?>
            <table class="form-table">
                <tbody>
                <tr>
                    <th>
                        <label for="kpdns-zone-name"><?php _e( 'Name', 'keypress-dns' ) ?></label>
                    </th>
                    <td >
                        <input type="text" name="zone[name]" value="<?php esc_attr_e( isset( $_GET['zone'] ) && isset( $_GET['zone']['name'] ) ? $_GET['zone']['name'] : '' ) ?>" class="regular-text"/>
                        <p class="description"><?php _e( 'A name to identify your zone. Must be different from the Domain Name field.', 'keypress-dns' ) ?></p>
                    </td>
                </tr>
                <tr>
                    <th>
                        <label for="kpdns-zone-status"><?php _e( 'Description', 'keypress-dns' ) ?></label>
                    </th>
                    <td>
                        <input type="text" name="zone[description]" value="<?php esc_attr_e( isset( $_GET['zone'] ) && isset( $_GET['zone']['description'] ) ? $_GET['zone']['description'] : '' ) ?>" class="regular-text"/>
                        <p class="description"><?php _e( 'Max. 256 characters.', 'keypress-dns' ) ?></p>
                    </td>
                </tr>
                </tbody>
            </table>
            <?php
        }

        public static function edit_zone_form_after_fields( $zone ) {
            if ( $zone instanceof KPDNS_GCDNS_Zone ) {

                if ( isset( $_GET['zone'] ) && isset( $_GET['zone']['description'] ) ) {
                    $description = $_GET['zone']['description'];
                } else {
                    $description = $zone->get_description();
                }

                if ( isset( $_GET['zone'] ) && isset( $_GET['zone']['name'] ) ) {
                    $name = $_GET['zone']['name'];
                } else {
                    $name = $zone->get_name();
                }

                ?>
                <table class="form-table">
                    <tbody>
                    <tr>
                        <th>
                            <label for="kpdns-zone-name"><?php _e( 'Name', 'keypress-dns' ) ?></label>
                        </th>
                        <td >
                            <?php esc_html_e( $name ) ?>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <label for="kpdns-zone-description"><?php _e( 'Description', 'keypress-dns' ) ?></label>
                        </th>
                        <td >
                            <input name="zone[description]" id="kpdns-zone-description" type="text" maxlength="256" placeholder="" value="<?php esc_attr_e( $description ) ?>" class="regular-text">
                            <p class="description"><?php _e( 'Max. 256 characters.', 'keypress-dns' ) ?></p>
                        </td>
                    </tr>
                    </tbody>
                </table>
                <?php
            }
        }

        public static function add_zone_validate( $is_valid, $zone ) {
            if ( ! isset( $zone['name'] ) || empty( $zone['name'] ) ) {
                return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( 'Missing required field "Name".', 'keypress-dns' ) );
            }

            if ( isset( $zone['domain'] ) && $zone['domain'] === $zone['name'] ) {
                return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( 'Invalid value for "Name" field. Must be different from the Domain Name field.', 'keypress-dns' ) );
            }

            return $is_valid;
        }

        /**
         * @param $is_valid
         * @param $zone
         * @return bool|WP_Error
         */
        public static function update_zone_validate( $is_valid, $zone ) {

            if ( isset( $zone['description'] ) && 256 < strlen( $zone['description'] ) ) {
                return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( 'The maximum length of the "Description" field is 256 characters.', 'keypress-dns' ) );
            }

            return $is_valid;
        }

        public static function list_zones_columns( $zones_columns ) {
            $zones_columns['description']   = __( 'Description', 'keypress-dns' );
            return $zones_columns;
        }

        public static function list_zones_table_row_data( $row_data, $zone ) {
            $row_data['description']  = ! empty( $zone->get_description() ) ? $zone->get_description() : '-';
            return $row_data;
        }
    }
}