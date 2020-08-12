<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'KPDNS_ClouDNS' ) ) {

    /**
     * Class KPDNS_ClouDNS
     *
     * @since 1.2.1
     */
	class KPDNS_ClouDNS extends KPDNS_Provider implements KPDNS_Registrable {

	    public function __construct() {
	        $id          = 'cloudns';
	        $name        = __( 'ClouDNS', 'keypress-dns' );
	        $url         = 'https://www.cloudns.net/';
            $credentials = new KPDNS_ClouDNS_Credentials();
            $api         = new KPDNS_ClouDNS_API( $credentials );
            parent::__construct( $id, $name, $url, $credentials, $api );
        }

        public function register() {
            add_action( 'kpdns_edit_zone_form_after_fields', array( $this, 'edit_zone_form_after_fields' ), 10 );
            add_filter( 'kpdns_update_zone_validate', array( $this, 'update_zone_validate' ), 10, 2 );

            add_filter( 'kpdns_list_zones_columns', array( $this, 'list_zones_columns' ) );
            add_filter( 'kpdns_list_zones_table_row_data', array( $this, 'list_zones_table_row_data' ), 10, 2 );

            add_action( 'kpdns_add_zone_form_after_fields', array( $this, 'add_zone_form_after_fields' ) );
            add_filter( 'kpdns_add_zone_validate', array( $this, 'add_zone_validate' ), 10, 2 );

            add_action( 'kpdns_add_name_server_form_after_fields', array( $this, 'add_name_server_form_after_fields' ) );
            add_filter( 'kpdns_add_name_server_validate', array( $this, 'add_name_server_validate' ), 10, 2 );
            add_filter( 'kpdns_add_name_server_args', array( $this, 'add_name_server_args' ), 10, 2 );

            add_filter( 'kpdns_record_ttl_field_config', array( $this, 'filter_record_ttl_field_config' ) );

            add_filter( 'kpdns_record_types', array( $this, 'filter_record_types' ) );

            add_filter( 'kpdns_record_ttl_unit_field_config', array( $this, 'filter_record_ttl_unit_field_config' ) );
        }

        public function edit_zone_form_after_fields( $zone ) {
            if ( $zone instanceof KPDNS_ClouDNS_Zone ) {
                ?>
                <table class="form-table">
                    <tbody>
                    <tr>
                        <th>
                            <label for="kpdns-zone-type"><?php _e( 'Type', 'keypress-dns' ) ?></label>
                        </th>
                        <td ><?php esc_html_e( $zone->get_type() ) ?></td>
                    </tr>
                    <tr>
                        <th>
                            <label for="kpdns-zone-status"><?php _e( 'Status', 'keypress-dns' ) ?></label>
                        </th>
                        <td >
                            <?php $statuses = KPDNS_ClouDNS_Zone::get_zone_statuses() ?>
                            <div>
                                <input type="radio" name="zone[status]" value="<?php esc_attr_e( KPDNS_ClouDNS_Zone::ZONE_STATUS_ACTIVE ) ?>" <?php checked( KPDNS_ClouDNS_Zone::ZONE_STATUS_ACTIVE === $zone->get_status() ); ?> />
                                <label for="kpdns-zone-status-<?php esc_attr_e( KPDNS_ClouDNS_Zone::ZONE_STATUS_ACTIVE );?>"><?php esc_html_e( $statuses[ KPDNS_ClouDNS_Zone::ZONE_STATUS_ACTIVE ]['label'] ) ?></label>
                            </div>
                            <div>
                                <input type="radio" name="zone[status]" value="<?php esc_attr_e( KPDNS_ClouDNS_Zone::ZONE_STATUS_INACTIVE ) ?>" <?php checked( KPDNS_ClouDNS_Zone::ZONE_STATUS_INACTIVE === $zone->get_status() ); ?> />
                                <label for="kpdns-zone-status-<?php esc_attr_e( KPDNS_ClouDNS_Zone::ZONE_STATUS_ACTIVE );?>"><?php esc_html_e( $statuses[ KPDNS_ClouDNS_Zone::ZONE_STATUS_INACTIVE ]['label'] ) ?></label>
                            </div>
                        </td>
                    </tr>
                    </tbody>
                </table>
                <?php
            }
        }

        /**
         * @param $is_valid
         * @param $zone
         * @return bool|WP_Error
         */
        public function update_zone_validate( $is_valid, $zone ) {

            if ( ! isset( $zone['status'] ) ) {
                return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( 'Missing required field "Status".', 'keypress-dns' ) );
            }

            if ( ! array_key_exists( $zone['status'], KPDNS_ClouDNS_Zone::get_zone_statuses() ) ) {
                return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( 'Invalid value for "Status" field.', 'keypress-dns' ) );
            }

            return $is_valid;
        }

        /**
         * @param $record_columns
         * @return array
         */
        /*
        public function edit_zone_record_columns( $record_columns ) {
            $record_columns[] = __( 'Id', 'keypress-dns' );
            $record_columns[] = __( 'Failover', 'keypress-dns' );
            return $record_columns;
        }
        */

        /**
         * @param $record
         */
        /*
        public function edit_zone_records_table_row_after_data( $record ) {
            if ( ! $record instanceof KPDNS_ClouDNS_Record ) {
                return;
            }
            ?>
                <td><?php esc_html_e( $record->get_id() ) ?></td>
                <td><?php esc_html_e( $record->get_failover() ) ?></td>
            <?php
        }
        +/

        /**
         *
         */
        /*
        public function add_record_form_after_fields() {
            ?>
            <table class="form-table">
                <tbody>
                    <tr>
                        <th>
                            <label for="kpdns-zone-failover"><?php _e( 'Failover', 'keypress-dns' ) ?></label>
                        </th>

                        <td >
                            <input name="record[failover]" id="kpdns-record-failover" type="text" value="<?php esc_attr_e( isset( $_GET['record'] ) ? ( isset( $_GET['record']['failover'] ) ? $_GET['record']['failover'] : '' ) : '' ) ?>" class="regular-text">
                            <p class="description"><?php _e( "Failover description", 'keypress-dns') ?></p>
                        </td>
                    </tr>
                </tbody>
            </table>
            <?php
        }
        */

        /*
        public function add_record_validate( $is_valid, $record ) {
            if ( ! isset( $record['failover'] ) || empty( $record['failover'] ) ) {
                return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( 'Missing required field "Failover".', 'keypress-dns' ) );
            }
            return $is_valid;
        }
        */

        /*
        public function edit_record_form_after_fields( $record, $new_record ) {
            ?>
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th>
                                <label for="kpdns-zone-type"><?php _e( 'Failover', 'keypress-dns' ) ?></label>
                            </th>
                            <td >
                                <input type="text" name="new-record[failover]" value="<?php esc_attr_e( isset( $new_record['failover'] ) ? $new_record['failover'] : $record['failover'] ); ?>" style="display: block;" class="regular-text"/>
                            </td>
                        </tr>
                        <tr>
                            <th>
                                <label for="kpdns-zone-type"><?php _e( 'Id', 'keypress-dns' ) ?></label>
                            </th>
                            <td >
                                <input type="text" name="new-record[id]" value="<?php esc_attr_e( isset( $new_record['id'] ) ? $new_record['id'] : $record['id'] ); ?>" style="display: block;" class="regular-text"/>
                            </td>
                        </tr>
                    </tbody>
                </table>
            <?php
        }
        */

        /*
        public function update_record_validate( $is_valid, $record ) {
            if ( ! isset( $record['id'] ) || empty( $record['id'] ) ) {
                return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( 'Missing required field "Id".', 'keypress-dns' ) );
            }

            if ( ! isset( $record['failover'] ) || empty( $record['failover'] ) ) {
                return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( 'Invalid value for "Failover" field.', 'keypress-dns' ) );
            }

            return $is_valid;
        }
        */

        /*
        public function edit_zone_records_table_row_data( $row_data, $record, $zone ) {
            $row_data['id'] = $record->get_id();
            return $row_data;
        }
        */

        /*
            [name] => atestns.com
            [type] => master
            [zone] => domain
            [status] => 1
         */

        public function list_zones_columns( $zones_columns ) {
            $zones_columns['type']   = __( 'Type', 'keypress-dns' );
            $zones_columns['status'] = __( 'Status', 'keypress-dns' );
            return $zones_columns;
        }

        public function list_zones_table_row_data( $row_data, $zone ) {
            $row_data['type']   = $zone->get_type();
            $row_data['status'] = KPDNS_ClouDNS_Zone::get_zone_statuses()[ $zone->get_status() ]['label'];
            return $row_data;
        }

        public function add_zone_form_after_fields( $zone ) {
            ?>
            <table class="form-table">
                <tbody>
                <tr>
                    <th>
                        <label for="kpdns-zone-status"><?php _e( 'Status', 'keypress-dns' ) ?></label>
                    </th>
                    <td >
                        <?php $statuses = KPDNS_ClouDNS_Zone::get_zone_statuses() ?>
                        <div>
                            <input type="radio" name="zone[status]" value="<?php esc_attr_e( KPDNS_ClouDNS_Zone::ZONE_STATUS_ACTIVE ) ?>" <?php checked( ( isset( $zone ) && KPDNS_ClouDNS_Zone::ZONE_STATUS_ACTIVE === $zone->get_status() ) || ! isset( $zone ), true ); ?> />
                            <label for="kpdns-zone-status-<?php esc_attr_e( KPDNS_ClouDNS_Zone::ZONE_STATUS_ACTIVE );?>"><?php esc_html_e( $statuses[ KPDNS_ClouDNS_Zone::ZONE_STATUS_ACTIVE ]['label'] ) ?></label>
                        </div>
                        <div>
                            <input type="radio" name="zone[status]" value="<?php esc_attr_e( KPDNS_ClouDNS_Zone::ZONE_STATUS_INACTIVE ) ?>" <?php checked( isset( $zone ) &&  KPDNS_ClouDNS_Zone::ZONE_STATUS_INACTIVE === $zone->get_status(), true ); ?> />
                            <label for="kpdns-zone-status-<?php esc_attr_e( KPDNS_ClouDNS_Zone::ZONE_STATUS_ACTIVE );?>"><?php esc_html_e( $statuses[ KPDNS_ClouDNS_Zone::ZONE_STATUS_INACTIVE ]['label'] ) ?></label>
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>
            <?php
        }

        public function add_name_server_form_after_fields( $name_server ) {
            ?>
            <table class="form-table">
                <tbody>
                <tr>
                    <th>
                        <label for="kpdns-zone-status">
                            <?php
                            _e( 'Name Servers Type', 'keypress-dns' );
                            KPDNS_Page::render_tooltip( __( 'Choose whether you want to use the Premium DNS Servers or the Free DNS Servers provided by your ClouDNS account to create your Custom NS.', 'keypress-dns' ) );
                            ?>
                        </label>
                    </th>
                    <td >
                        <?php $ns_types = KPDNS_ClouDNS_Name_Server::get_name_server_types() ?>
                        <div>
                            <input type="radio" name="name-server[type]" value="<?php esc_attr_e( KPDNS_ClouDNS_Name_Server::NS_TYPE_FREE ) ?>" <?php checked( ( isset( $name_server ) && KPDNS_ClouDNS_Name_Server::NS_TYPE_FREE === $name_server->get_type() ) || ! isset( $name_server ), true ); ?> />
                            <label for="kpdns-name-server-type-<?php esc_attr_e( KPDNS_ClouDNS_Name_Server::NS_TYPE_FREE );?>"><?php esc_html_e( $ns_types[ KPDNS_ClouDNS_Name_Server::NS_TYPE_FREE ]['label'] ) ?></label>
                        </div>
                        <div>
                            <input type="radio" name="name-server[type]" value="<?php esc_attr_e( KPDNS_ClouDNS_Name_Server::NS_TYPE_PREMIUM ) ?>" <?php checked( isset( $name_server ) &&  KPDNS_ClouDNS_Name_Server::NS_TYPE_PREMIUM === $name_server->get_type(), true ); ?> />
                            <label for="kpdns-zone-name-server-types-<?php esc_attr_e( KPDNS_ClouDNS_Name_Server::NS_TYPE_PREMIUM );?>"><?php esc_html_e( $ns_types[ KPDNS_ClouDNS_Name_Server::NS_TYPE_PREMIUM ]['label'] ) ?></label>
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>
            <?php
        }

        public function add_zone_validate( $is_valid, $zone ) {
            if ( ! isset( $zone['status'] ) ) {
                return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( 'Missing required field "Status".', 'keypress-dns' ) );
            }

            if ( ! array_key_exists( $zone['status'], KPDNS_ClouDNS_Zone::get_zone_statuses() ) ) {
                return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( 'Invalid value for "Status" field.', 'keypress-dns' ) );
            }

            return $is_valid;
        }

        public function add_name_server_validate( $is_valid, $name_server ) {
            if ( ! isset( $name_server['type'] ) ) {
                return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( 'Missing required field "Name Servers Type".', 'keypress-dns' ) );
            }

            if ( ! array_key_exists( $name_server['type'], KPDNS_ClouDNS_Name_Server::get_name_server_types() ) ) {
                return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( 'Invalid value for "Name Servers Type" field.', 'keypress-dns' ) );
            }

            return $is_valid;
        }

        public function add_name_server_args( $args, $name_server ) {
            if ( isset( $name_server['type'] ) ) {
                $args['type'] = $name_server['type'];
            }
            return $args;
        }

        public function filter_record_ttl_field_config( $config ) {
            $config['description'] = __( 'Available TTL\'s: 60 = 1 minute, 300 = 5 minutes, 900 = 15 minutes, 1800 = 30 minutes, 3600 = 1 hour, 21600 = 6 hours, 43200 = 12 hours, 86400 = 1 day, 172800 = 2 days, 259200 = 3 days, 604800 = 1 week, 1209600 = 2 weeks, 2592000 = 1 month', 'keypress-dns' );
            return $config;
        }

        public function filter_record_types( $types ) {
            unset( $types[ KPDNS_Record::TYPE_PTR ] );
            unset( $types[ KPDNS_Record::TYPE_SOA ] );
            return $types;
        }

        public function filter_record_ttl_unit_field_config( $config ) {
            return false;
        }
    }
}