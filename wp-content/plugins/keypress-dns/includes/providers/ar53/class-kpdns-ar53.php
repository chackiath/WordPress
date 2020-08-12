<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'KPDNS_AR53' ) ) {

    /**
     * Class KPDNS_AR53
     *
     * @since 1.2.1
     */
	class KPDNS_AR53 extends KPDNS_Provider implements KPDNS_Registrable {

	    public function __construct() {
	        $id          = 'ar53';
	        $name        = __( 'Amazon Route 53', 'keypress-dns' );
	        $url         = 'https://aws.amazon.com/route53/';
            $credentials = new KPDNS_AR53_Credentials();
            $api         = new KPDNS_AR53_API( $credentials );
            parent::__construct( $id, $name, $url, $credentials, $api );
        }

        public function register() {
            add_action( 'kpdns_add_zone_form_after_fields', array( $this, 'add_zone_form_after_fields' ), 10 );
            add_filter( 'kpdns_add_zone_validate', array( $this, 'add_zone_validate' ), 10, 2 );

            add_action( 'kpdns_edit_zone_form_after_fields', array( $this, 'edit_zone_form_after_fields' ), 10 );
            add_filter( 'kpdns_update_zone_validate', array( $this, 'update_zone_validate' ), 10, 2 );

            add_filter( 'kpdns_list_zones_columns', array( $this, 'list_zones_columns' ) );
            add_filter( 'kpdns_list_zones_table_row_data', array( $this, 'list_zones_table_row_data' ), 10, 2 );

            add_filter( 'kpdns_record_types', array( $this, 'filter_record_types' ) );
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
            if ( $zone instanceof KPDNS_AR53_Zone ) {

                if ( isset( $_GET['zone'] ) && isset( $_GET['zone']['description'] ) ) {
                    $description = $_GET['zone']['description'];
                } else {
                    $description = $zone->get_description();
                }

                /*
                if ( isset( $_GET['zone'] ) && isset( $_GET['zone']['private'] ) ) {
                    $private = isset( $_GET['zone'] ) && isset( $_GET['zone']['private'] );
                } else {
                    $private = $zone->is_private();
                }
                */

                ?>
                <table class="form-table">
                    <tbody>
                    <tr>
                        <th>
                            <label for="kpdns-zone-description"><?php _e( 'Description', 'keypress-dns' ) ?></label>
                        </th>
                        <td >
                            <input name="zone[description]" id="kpdns-zone-description" type="text" maxlength="256" placeholder="" value="<?php esc_attr_e( $description ) ?>" class="regular-text">
                            <p class="description"><?php _e( 'Max. 256 characters.', 'keypress-dns' ) ?></p>
                        </td>
                    </tr>
                    <!--<tr>
                                <th>
                                    <label for="kpdns-zone-private"><?php _e( 'Private', 'keypress-dns' ) ?></label>
                                </th>
                                <td >
                                    <input type="checkbox" id ="kpdns-zone-private" name="zone[private]" value="true" <?php //checked( $private ) ?>>
                                    <?php _e( 'Check to make this zone private.', 'keypress-dns' ); ?>
                                </td>
                            </tr>-->
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
            $zones_columns['description']   = __( 'Description', 'keypress-dns' );
            return $zones_columns;
        }

        public function list_zones_table_row_data( $row_data, $zone ) {
            $row_data['description']  = ! empty( $zone->get_description() ) ? $zone->get_description() : '-';
            return $row_data;
        }

        public function filter_record_types( $types ) {
            if ( isset( $types[ KPDNS_Record::TYPE_SRV ]['rdata-fields'][ KPDNS_Record::RDATA_KEY_SERVICE ] ) ) {
                unset( $types[ KPDNS_Record::TYPE_SRV ]['rdata-fields'][ KPDNS_Record::RDATA_KEY_SERVICE ] );
            }

            if ( isset( $types[ KPDNS_Record::TYPE_SRV ]['rdata-fields'][ KPDNS_Record::RDATA_KEY_PROTOCOL ] ) ) {
                unset( $types[ KPDNS_Record::TYPE_SRV ]['rdata-fields'][ KPDNS_Record::RDATA_KEY_PROTOCOL ] );
            }

            if ( isset( $types[ KPDNS_Record::TYPE_SOA ] ) ) {
                $types[ KPDNS_Record::TYPE_SOA ]['rdata-fields'][ KPDNS_Record::RDATA_KEY_EMAIL ]['label'] = __( 'Authorithy Domain', 'keypress-dns' );
                $types[ KPDNS_Record::TYPE_SOA ]['rdata-fields'][ KPDNS_Record::RDATA_KEY_EMAIL ]['description'] = __( 'Enter the Authorithy Domain.', 'keypress-dns' );
                $types[ KPDNS_Record::TYPE_SOA ]['rdata-fields'][ KPDNS_Record::RDATA_KEY_EMAIL ]['validation_callback'] = 'KPDNS_Utils::validate_domain_name';
            }

            return $types;
        }
    }
}