<?php

/**
 * Class KPDNS_Page_Zones.
 *
 * @since 1.0.0
 */
final class KPDNS_Page_Zones extends KPDNS_Page {

	const ACTION_ADD_ZONE                 = 'kpdns-add-zone';
	const ACTION_DELETE_ZONE              = 'kpdns-delete-zone';
	const ACTION_UPDATE_ZONE              = 'kpdns-update-zone';
	const ACTION_ADD_RECORD               = 'kpdns-add-record';
	const ACTION_DELETE_RECORD            = 'kpdns-delete-record';
	const ACTION_UPDATE_RECORD            = 'kpdns-update-record';
	const ACTION_ADD_NAME_SERVER          = 'kpdns-add-name-server';
	const ACTION_EDIT_NAME_SERVER         = 'kpdns-edit-name-server';
	const ACTION_LIST_ZONES_BULK_ACTIONS  = 'kpdns-list-zones-bulk-actions';
    const ACTION_BULK_DELETE_ZONES        = 'kpdns-bulk-delete-zones';
    const ACTION_BULK_UPDATE_A_RECORDS    = 'kpdns-update-a-records';
    const ACTION_BULK_UPDATE_AAAA_RECORDS = 'kpdns-update-aaaa-records';

    const NONCE_LIST_ZONES_BULK_ACTIONS   = 'kpdns-list-zones-bulk-actions-nonce';
	const NONCE_ADD_ZONE                  = 'kpdns-add-zone-nonce';
	const NONCE_DELETE_ZONE               = 'kpdns-delete-zone-nonce';
	const NONCE_UPDATE_ZONE               = 'kpdns-update-zone-nonce';
	const NONCE_ADD_RECORD                = 'kpdns-add-record-nonce';
	const NONCE_DELETE_RECORD             = 'kpdns-delete-record-nonce';
	const NONCE_UPDATE_RECORD             = 'kpdns-update-record-nonce';

	const VIEW_LIST_ZONES                 = 'list-zones';
	const VIEW_ADD_ZONE                   = 'add-zone';
	const VIEW_DELETE_ZONE                = 'delete-zone';
	const VIEW_EDIT_ZONE                  = 'edit-zone';
	const VIEW_ADD_RECORD                 = 'add-record';
	const VIEW_DELETE_RECORD              = 'delete-record';
	const VIEW_EDIT_RECORD                = 'edit-record';

	public $views;

	/**
	 * Initializes the admin settings.
	 *
	 * @since 1.2
	 * @return void
	 */
	public function __construct() {
		if ( ! is_admin() ) {
			return;
		}

		$this->views   = $this->get_views();

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		if ( is_multisite() ) {
			//add_action( 'network_admin_menu', array( $this, 'menu' ) );
			add_action( 'network_admin_edit_' . self::ACTION_ADD_ZONE, array( $this, 'add_zone' ) );
			add_action( 'network_admin_edit_' . self::ACTION_DELETE_ZONE, array( $this, 'delete_zone' ) );
			add_action( 'network_admin_edit_' . self::ACTION_UPDATE_ZONE, array( $this, 'update_zone' ) );
			add_action( 'network_admin_edit_' . self::ACTION_ADD_RECORD, array( $this, 'add_record' ) );
			add_action( 'network_admin_edit_' . self::ACTION_DELETE_RECORD, array( $this, 'delete_record' ) );
			add_action( 'network_admin_edit_' . self::ACTION_UPDATE_RECORD, array( $this, 'update_record' ) );
            add_action( 'network_admin_edit_' . self::ACTION_LIST_ZONES_BULK_ACTIONS, array( $this, 'list_zones_bulk_actions' ) );
		} else {
			//add_action( 'admin_menu', array( $this, 'menu' ) );
			add_action( 'admin_post_' . self::ACTION_ADD_ZONE, array( $this, 'add_zone' ) );
			add_action( 'admin_post_' . self::ACTION_DELETE_ZONE, array( $this, 'delete_zone' ) );
			add_action( 'admin_post_' . self::ACTION_UPDATE_ZONE, array( $this, 'update_zone' ) );
			add_action( 'admin_post_' . self::ACTION_ADD_RECORD, array( $this, 'add_record' ) );
			add_action( 'admin_post_' . self::ACTION_DELETE_RECORD, array( $this, 'delete_record' ) );
			add_action( 'admin_post_' . self::ACTION_UPDATE_RECORD, array( $this, 'update_record' ) );
            add_action( 'admin_post_' . self::ACTION_LIST_ZONES_BULK_ACTIONS, array( $this, 'list_zones_bulk_actions' ) );
		}
	}

	public function enqueue_assets( $hook ) {
		$this->enqueue_styles( $hook );
		$this->enqueue_scripts( $hook );
	}

	private function enqueue_styles( $hook ) {
		// TODO
	}

	private function enqueue_scripts( $hook ) {
		wp_enqueue_script( 'jquery-ui-dialog' );
		wp_enqueue_script( 'kpdns-zones', KPDNS_PLUGIN_URL . 'assets/js/zones.js', array( 'jquery' ), KPDNS_PLUGIN_VERSION, false );

        $script_settings = array(
            'bulkActions' => array(
                'delete'            => self::ACTION_BULK_DELETE_ZONES,
                'updateARecords'    => self::ACTION_BULK_UPDATE_A_RECORDS,
                'updateAAAARecords' => self::ACTION_BULK_UPDATE_AAAA_RECORDS,
            ),
            'ipv4Placeholder'       => __( 'IPv4 e.g. 192.168.1.0', 'keypress-dns' ),
            'ipv6Placeholder'       => __( 'IPv6     e.g. 2001:db8:a0b:12f0::1', 'keypress-dns' ),
            'recordTypes'           => KPDNS_Utils::get_record_types_config(),
            'recordTypeSelectDesc'  => __( 'Select the record type.', 'keypress-dns' ),
            'pullRecords'           => array(
                'url'       => admin_url( 'admin-ajax.php' ),
                'action'    => KPDNS_ACTION_AJAX_PULL_RECORDS,
                'nonce'     => wp_create_nonce( KPDNS_ACTION_AJAX_PULL_RECORDS ),
            ),
        );

        wp_localize_script(
            'kpdns-zones',
            'kpdnsZones',
            $script_settings
        );
	}

	/*
	public function menu() {
		if ( KPDNS_Access_control::current_user_can_access_dns_manager() ) {
			$cap   = KPDNS_Access_control::get_capability();
			$label = __( 'DNS Zones', 'keypress-dns' );
			$func  = array( $this, 'render' );

			add_submenu_page( KPDNS_PAGE_SETTINGS, $this->title, $label, $cap, $this->slug, $func );
		}
	}
	*/

	protected function render_main_content() {
		$current_view = $this->views[ $this->get_current_view_id() ];
		parent::render_view( $current_view['template'] );
	}

	/**
	 *
	 * Adds a new DNS Zone
	 *
	 * @since 0.1.0
	 *
	 */
	public function add_zone() {

		check_admin_referer( self::ACTION_ADD_ZONE, self::NONCE_ADD_ZONE );

		//If needed fields aren't present, we can't continue
		if ( ! isset( $_POST['zone'] ) || ! isset( $_POST['zone']['domain'] ) ) {
			wp_die( __( 'Unexpected error: Missing required field(s).', 'keypress-dns' ) );
		}

		$zone = KPDNS_Utils::sanitize_zone( $_POST['zone'] );

		//Validate zone
		$is_valid = KPDNS_Utils::validate_zone( $zone, $ignore = [ KPDNS_Utils::ZONE_FIELD_ID, KPDNS_Utils::ZONE_FIELD_NAME, KPDNS_Utils::ZONE_FIELD_DESCRIPTION ] );

        if ( ! is_wp_error( $is_valid ) ) {
            /**
             * Filters zone values to validate them.
             *
             * @since 1.1
             *
             * @param bool $is_valid Whether or not the zone values are valid.
             * @param array $zone The zone values array.
             */
            $is_valid = apply_filters( 'kpdns_add_zone_validate', $is_valid, $zone );
        }

        self::check_errors( $is_valid, array( 'page' => KPDNS_PAGE_ZONES, 'view' => self::VIEW_ADD_ZONE, 'zone' => $zone ) );

        if ( isset( $zone['a-record'] ) && ! empty( $zone['a-record'] ) ) {
            $is_valid_a_record = KPDNS_Utils::validate_ipv4( $zone['a-record'] );
            self::check_errors( $is_valid_a_record, array( 'page' => KPDNS_PAGE_ZONES, 'view' => self::VIEW_ADD_ZONE, 'zone' => $zone ) );
        }

		$api = kpdns_get_api();
		self::check_errors( $api, array( 'page' => KPDNS_PAGE_ZONES, 'view' => self::VIEW_ADD_ZONE, 'zone' => $zone ) );

		$the_zone = $api->build_zone( $zone );

        $args = array();

		// The provider supports Custom NS and a NS has been selected.
		if ( $api instanceof KPDNS_Custom_NS_API_Imp && isset( $_POST['zone']['custom-ns'] ) && '-1' !== $_POST['zone']['custom-ns'] ) {
            $custom_ns = sanitize_text_field( $_POST['zone']['custom-ns'] );
            $args['custom-ns'] = $custom_ns;
        }

		if ( isset( $zone['copy-records'] ) && 'true' === $zone['copy-records'] ) {
            $args['copy-records'] = $zone['copy-records'];
        }

        /**
         * Filters arguments to pass to the add_zone method.
         *
         * @since 1.1
         *
         * @param array $args The args array.
         * @param array $zone The zone values array.
         */
        $args = apply_filters( 'kpdns_add_zone_args', $args, $zone );

		$response_zone = $api->add_zone( $the_zone, $args );

		if ( is_wp_error( $response_zone ) ) {
			$query_arg = KPDNS_Page::build_query_args( KPDNS_PAGE_ZONES, false, $response_zone->get_error_messages(), array( 'view' => self::VIEW_ADD_ZONE, 'zone'=> $zone ) );
		} else {

			$errors = array();

			$zone['id'] = $response_zone->get_id();

			$default_records = array(
                'a-record'    => $zone['a-record'],
                'www-record' => isset( $zone['www-record'] ) && 'true' === $zone['www-record'],
            );

			KPDNS_Utils::maybe_add_default_records( $zone, $default_records );

			if ( isset( $zone['primary'] ) && $zone['primary'] === 'true' ) {
			    $the_zone = array(
                    'id'     => $zone['id'],
                    'domain' => rtrim( $zone['domain'], '.'),
                );
                KPDNS_Model::save_primary_zone( $the_zone );
            }

			$success_messages = apply_filters( 'kpdns_add_zone_success_messages', array( __( 'Zone created successfully.', 'keypress-dns' ) ) );

			$messages = empty( $errors ) ? $success_messages : $errors;

			$query_arg = KPDNS_Page::build_query_args( KPDNS_PAGE_ZONES, true, $messages, array( 'view' => self::VIEW_EDIT_ZONE, 'zone-id'=> $zone['id'] ) );
		}

		wp_redirect( add_query_arg( $query_arg, self::get_admin_url() ) );
		exit();
	}

	/**
	 *
	 * Deletes a DNS Zone
	 *
	 * @since 0.1.0
	 *
	 */
	public function delete_zone() {

		check_admin_referer( self::ACTION_DELETE_ZONE, self::NONCE_DELETE_ZONE );

		//If dnsm_zone isn't present, we can't continue
		if ( ! isset( $_REQUEST['zone-id'] ) ) {
			wp_die( __( 'Unexpected error: Missing required field(s).', 'keypress-dns' ) );
		}

		$zone_id = sanitize_text_field( $_REQUEST['zone-id'] );

		//Validate id
		$is_valid_id = KPDNS_Utils::validate_zone_id( $zone_id );
		self::check_errors( $is_valid_id, array( 'page' => KPDNS_PAGE_ZONES, 'view' => self::VIEW_DELETE_ZONE, 'zone-id' => $zone_id ) );

		$api = kpdns_get_api();
		self::check_errors( $api, array( 'page' => KPDNS_PAGE_ZONES, 'view' => self::VIEW_DELETE_ZONE, 'zone-id' => $zone_id ) );

        $args = array();

        /**
         * Filters arguments to pass to the delete_zone method.
         *
         * @since 1.1
         *
         * @param array $args The args array.
         * @param array $zone_id The zone id.
         */
        $args = apply_filters( 'kpdns_delete_zone_args', $args, $zone_id );

		$response = $api->delete_zone( $zone_id, $args );

		if ( is_wp_error( $response ) ) {
			$query_arg = KPDNS_Page::build_query_args( KPDNS_PAGE_ZONES, false, $response->get_error_messages() );
		} else {

            $primary_zone = KPDNS_Model::get_primary_zone();
            if ( $primary_zone ) {
                if ( isset( $primary_zone['id'] ) && $primary_zone['id'] === $zone_id ) {
                    KPDNS_Model::delete_primary_zone();
                }
            }
            /**
             * Fires after the zone has been successfully deleted.
             *
             * @since 1.2.1
             *
             * @param array $zone_id The zone id.
             */
            do_action( 'kpdns_after_delete_zone', $zone_id );

			$query_arg = KPDNS_Page::build_query_args( KPDNS_PAGE_ZONES, true, array( __( 'Zone deleted successfully.', 'keypress-dns' ) ) );
		}

		wp_redirect( add_query_arg( $query_arg, self::get_admin_url() ) );
		exit();
	}


	/**
	 *
	 *
	 *
	 * @since 0.1.0
	 *
	 */
	public function update_zone() {

		check_admin_referer( self::ACTION_UPDATE_ZONE, self::NONCE_UPDATE_ZONE );

		// If needed fields aren't present, we can't continue
		if ( ! isset( $_POST['zone'] ) ||
		     ! isset( $_POST['zone']['id'] )
		) {
			wp_die( __( 'Unexpected error: Missing required field(s).', 'keypress-dns' ) );
		}

		$zone = KPDNS_Utils::sanitize_zone( $_POST['zone'] );

		// Validate zone
		$is_valid = KPDNS_Utils::validate_zone( $zone, $ignore = [
			KPDNS_Utils::ZONE_FIELD_NAME,
            KPDNS_Utils::ZONE_FIELD_DESCRIPTION
		] );

		if ( ! is_wp_error( $is_valid ) ) {
            /**
             * Filters zone values to validate them.
             *
             * @since 1.1
             *
             * @param bool $is_valid Whether or not the zone values are valid.
             * @param array $zone The zone values array.
             */
            $is_valid = apply_filters( 'kpdns_update_zone_validate', $is_valid, $zone );
        }

		self::check_errors( $is_valid, array( 'page' => KPDNS_PAGE_ZONES, 'view' => self::VIEW_EDIT_ZONE, 'zone-id' => $zone['id'], 'zone' => $zone ) );

		$api = kpdns_get_api();
		self::check_errors( $api, array( 'page' => KPDNS_PAGE_ZONES, 'view' => self::VIEW_EDIT_ZONE, 'zone-id' => $zone['id'], 'zone' => $zone ) );

        $args = array();

        /**
         * Filters arguments to pass to the edit_zone method.
         *
         * @since 1.1
         *
         * @param array $args The args array.
         * @param array $zone The zone array.
         */
        $args = apply_filters( 'kpdns_edit_zone_args', $args, $zone );

		$kpdns_zone = $api->build_zone( $zone );

		$response = $api->edit_zone( $kpdns_zone, $args );

		if ( is_wp_error( $response ) ) {
			$query_arg = KPDNS_Page::build_query_args( KPDNS_PAGE_ZONES, false, $response->get_error_messages(), array( 'view' => self::VIEW_EDIT_ZONE, 'zone-id'   => $zone['id'], 'zone' => $zone ) );
		} else {

            if ( isset( $zone['primary'] ) && $zone['primary'] === 'true' ) {
                $the_zone = array(
                    'id'     => $zone['id'],
                    'domain' => rtrim( $zone['domain'], '.'),
                );
                KPDNS_Model::save_primary_zone( $the_zone );
            } elseif ( kpdns_is_primary_zone( $kpdns_zone ) ) {
                    KPDNS_Model::delete_primary_zone();
            }

			$query_arg = KPDNS_Page::build_query_args( KPDNS_PAGE_ZONES, true, array( __( 'Zone updated successfully.', 'keypress-dns' ) ), array( 'view' => self::VIEW_EDIT_ZONE, 'zone-id'   => $zone['id'] ) );
		}

		wp_redirect( add_query_arg( $query_arg, self::get_admin_url() ) );
		exit();
	}

	/**
	 *
	 *
	 *
	 * @since 0.1.0
	 *
	 */
	public function add_record() {

		check_admin_referer( self::ACTION_ADD_RECORD, self::NONCE_ADD_RECORD );

		// If needed fields aren't present, we can't continue
		if ( ! isset( $_POST['zone'] ) ||
             ! isset( $_POST['zone']['id'] ) ||
             ! isset( $_POST['zone']['domain'] ) ||
		     ! isset( $_POST['record'] )
		) {
			wp_die( __( 'Unexpected error: Missing required field(s).', 'keypress-dns' ) );
		}

		$zone_id     = sanitize_text_field( $_POST['zone']['id'] );
        $zone_domain = sanitize_text_field( $_POST['zone']['domain'] );
		$record      = KPDNS_Utils::sanitize_record( $_POST['record'] );

		// Validate zone id.
		$is_valid_zone = KPDNS_Utils::validate_zone_id( $zone_id );
		self::check_errors( $is_valid_zone, array( 'page' => KPDNS_PAGE_ZONES, 'view' => self::VIEW_EDIT_ZONE, 'zone-id' => $zone_id, 'record' => $record ) );

        // Validate zone domain.
        $is_valid_zone = KPDNS_Utils::validate_zone_domain_name( $zone_domain );
        self::check_errors( $is_valid_zone, array( 'page' => KPDNS_PAGE_ZONES, 'view' => self::VIEW_EDIT_ZONE, 'zone-id' => $zone_id, 'record' => $record ) );

		// Validate record.
		$is_valid_record = KPDNS_Utils::validate_record( $record );

        if ( ! is_wp_error( $is_valid_record ) ) {

            /**
             * Filters record values to validate them.
             *
             * @since 1.1
             *
             * @param bool $is_valid_record Whether or not the record values are valid.
             * @param array $record The record values array.
             */
            $is_valid_record = apply_filters( 'kpdns_add_record_validate', $is_valid_record, $record );
        }

        self::check_errors( $is_valid_record, array( 'page' => KPDNS_PAGE_ZONES, 'view' => self::VIEW_EDIT_ZONE, 'zone-id' => $zone_id, 'record' => $record ) );

		$api = kpdns_get_api();
		self::check_errors( $api, array( 'page' => KPDNS_PAGE_ZONES, 'view' => self::VIEW_EDIT_ZONE, 'zone-id' => $zone_id, 'record' => $record ) );

        $args = array();

        /**
         * Filters arguments to pass to the add_record method.
         *
         * @since 1.1
         *
         * @param array $args The args array.
         * @param array $record The record array.
         */
        $args = apply_filters( 'kpdns_add_record_args', $args, $record );

        $kpdns_record = $api->build_record( $record );

        if ( empty( $record['name'] ) ) {
            $kpdns_record->name = $zone_domain;
        } else {
            $kpdns_record->name .= '.' . $zone_domain;
        }

		// Try to add the record
		$response = $api->add_record( $kpdns_record, $zone_id, $args );

		if ( is_wp_error( $response ) ) {
			$query_arg = KPDNS_Page::build_query_args( KPDNS_PAGE_ZONES, false, $response->get_error_messages(), array( 'view' => self::VIEW_EDIT_ZONE, 'zone-id' => $zone_id, 'record' => $record ) );
		} else {
			$query_arg = KPDNS_Page::build_query_args( KPDNS_PAGE_ZONES, true, array( __( 'Record created successfully.', 'keypress-dns' ) ), array( 'view' => self::VIEW_EDIT_ZONE, 'zone-id'   => $zone_id ) );
		}

		// Redirect back to edit zone view
		wp_redirect( add_query_arg( $query_arg, self::get_admin_url() ) );
		exit();
	}

	/**
	 *
	 *
	 *
	 * @since 0.1.0
	 *
	 */
	public function delete_record() {

		check_admin_referer( self::ACTION_DELETE_RECORD, self::NONCE_DELETE_RECORD );

		// If needed fields aren't present, we can't continue
		if ( ! isset( $_GET['zone'] ) ||
             ! isset( $_GET['zone']['id'] ) ||
             ! isset( $_GET['zone']['domain'] ) ||
		     ! isset( $_GET['record'] )
		) {
			wp_die( __( 'Unexpected error: Missing required field(s).', 'keypress-dns' ) );
		}

		$zone_id     = sanitize_text_field( $_GET['zone']['id'] );
        $zone_domain = sanitize_text_field( $_GET['zone']['domain'] );
		$record      = KPDNS_Utils::sanitize_record( $_GET['record'] );

		// Validate record.
		$is_valid_record = KPDNS_Utils::validate_record( $record );
		self::check_errors( $is_valid_record, array( 'page' => KPDNS_PAGE_ZONES, 'view' => self::VIEW_DELETE_RECORD, 'zone-id' => $zone_id, 'record' => $record ) );

        // Validate zone id.
        $is_valid_zone = KPDNS_Utils::validate_zone_id( $zone_id );
        self::check_errors( $is_valid_zone, array( 'page' => KPDNS_PAGE_ZONES, 'view' => self::VIEW_DELETE_RECORD, 'zone-id' => $zone_id, 'record' => $record ) );

        // Validate zone domain.
        $is_valid_zone = KPDNS_Utils::validate_zone_domain_name( $zone_domain );
        self::check_errors( $is_valid_zone, array( 'page' => KPDNS_PAGE_ZONES, 'view' => self::VIEW_DELETE_RECORD, 'zone-id' => $zone_id, 'record' => $record ) );

        $api = kpdns_get_api();
        self::check_errors( $api, array( 'page' => KPDNS_PAGE_ZONES, 'view' => self::VIEW_DELETE_RECORD, 'zone-id' => $zone_id, 'record' => $record ) );

        $args = array();

        /**
         * Filters arguments to pass to the delete_record method.
         *
         * @since 1.1
         *
         * @param array $args The args array.
         * @param array $record The record array.
         */
        $args = apply_filters( 'kpdns_delete_record_args', $args, $record );

        $record = $api->build_record( $record, $zone_domain );

		// Try to edit the record
		$response = $api->delete_record( $record, $zone_id, $args );

		if ( is_wp_error( $response ) ) {
			$query_arg = KPDNS_Page::build_query_args( KPDNS_PAGE_ZONES, false, $response->get_error_messages(), array( 'view' => self::VIEW_EDIT_ZONE, 'zone-id' => $zone_id ) );
		} else {
			$query_arg = KPDNS_Page::build_query_args( KPDNS_PAGE_ZONES, true, array( __( 'Record deleted successfully.', 'keypress-dns' ) ), array( 'view' => self::VIEW_EDIT_ZONE, 'zone-id' => $zone_id ) );
		}

		// Redirect back to edit zone view
		wp_redirect( add_query_arg( $query_arg, self::get_admin_url() ) );
		exit();
	}


	/**
	 *
	 *
	 *
	 * @since 0.1.0
	 *
	 */
	public function update_record() {

		check_admin_referer( self::ACTION_UPDATE_RECORD, self::NONCE_UPDATE_RECORD );

		// If needed fields aren't present, we can't continue
		if ( ! isset( $_POST['zone'] ) ||
		     ! isset( $_POST['record'] )  ||
		     ! isset( $_POST['old-record'] )
		) {
			wp_die( __( 'Unexpected error: Missing required field(s).', 'keypress-dns' ) );
		}

		$zone       = $_POST['zone'];
		$zone_id    = sanitize_text_field( $zone['id'] );
		$record     = KPDNS_Utils::sanitize_record( $_POST['record'] );
		$old_record = KPDNS_Utils::sanitize_record( $_POST['old-record'] );

        // Validate zone id.
        $is_valid_zone = KPDNS_Utils::validate_zone_id( $zone_id );
        self::check_errors( $is_valid_zone, array( 'page' => KPDNS_PAGE_ZONES, 'view' => self::VIEW_EDIT_ZONE, 'zone' => $zone, 'record' => $record, 'old-record' => $old_record ) );

		// Validate record.
		$is_valid_record = KPDNS_Utils::validate_record( $record );

        if ( ! is_wp_error( $is_valid_record ) ) {

            /**
             * Filters the new record values to validate them.
             *
             * @since 1.1
             *
             * @param bool $is_valid_record Whether or not the new record values are valid.
             * @param array $new_record The new record values array.
             */
            $is_valid_record = apply_filters( 'kpdns_update_record_validate', $is_valid_record, $record );
        }

        self::check_errors( $is_valid_record, array( 'page' => KPDNS_PAGE_ZONES, 'view' => self::VIEW_EDIT_RECORD, 'zone' => $zone, 'record' => $record, 'old-record' => $old_record ) );

        $api = kpdns_get_api();
        self::check_errors( $api, KPDNS_PAGE_ZONES );

		// Convert TTL to seconds
		$record['ttl'] = KPDNS_Utils::ttl_to_seconds( $record['ttl'], $record['ttl-unit'] );

        $args = array();

        /**
         * Filters arguments to pass to the edit_record method.
         *
         * @since 1.1
         *
         * @param array $args The args array.
         * @param array $old_record The old record array.
         * @param array $record The new record array.
         */
        $args = apply_filters( 'kpdns_edit_record_args', $args, $old_record, $record );

        $record['name']     = KPDNS_Utils::get_formatted_record_name( $record['name'], $zone['domain'] );
        $old_record['name'] = KPDNS_Utils::get_formatted_record_name( $old_record['name'], $zone['domain'] );

        $kpdns_record     = $api->build_record( $record );
        $kpdns_old_record = $api->build_record( $old_record );

        if ( $kpdns_record == $kpdns_old_record ) {
            $response = true;
        } else {
            // Try to edit the record
            $response = $api->edit_record( $kpdns_old_record, $kpdns_record, $zone_id, $args );
        }

		if ( is_wp_error( $response ) ) {
			$query_arg = KPDNS_Page::build_query_args( KPDNS_PAGE_ZONES, false, $response->get_error_messages(), array( 'view' => self::VIEW_EDIT_RECORD, 'zone' => $zone, 'record' => urlencode_deep( $record ), 'old-record' => urlencode_deep( $old_record ) ) );
		} else {
			$query_arg = KPDNS_Page::build_query_args( KPDNS_PAGE_ZONES, true, array( __( 'Record updated successfully.', 'keypress-dns' ) ), array( 'view' => self::VIEW_EDIT_ZONE, 'zone-id' => $zone_id ) );
		}

		// Redirect back to edit zone view
		wp_redirect( add_query_arg( $query_arg, self::get_admin_url() ) );
		exit();
	}

    public function list_zones_bulk_actions() {
        check_admin_referer( self::ACTION_LIST_ZONES_BULK_ACTIONS, self::NONCE_LIST_ZONES_BULK_ACTIONS );

        //If needed fields aren't present, we can't continue
        if ( ! isset( $_POST['action'] ) ) {
            wp_die( __( 'Unexpected error: Missing required field(s).', 'keypress-dns' ) );
        }

        $action = -1;

        if ( isset( $_POST['action'] ) && -1 !== intval( $_POST['action'] ) ) {
            $action = $_POST['action'];
        } elseif ( isset( $_POST['action2'] ) && -1 !== intval( $_POST['action2'] ) ) {
            $action = $_POST['action2'];
        }

        if ( ! isset( $_POST['zones'] ) || empty( $_POST['zones'] ) || -1 == $action ) {
            $query_arg = array(
                'page'     => urlencode( KPDNS_PAGE_ZONES ),
            );
            wp_redirect( add_query_arg( $query_arg, self::get_admin_url() ) );
            exit();
        }

        $zones   = $_POST['zones'];

        $api = kpdns_get_api();
        self::check_errors( $api,KPDNS_PAGE_ZONES );

        switch ( $action ) {
            case self::ACTION_BULK_DELETE_ZONES:
                $result          = $this->list_zones_bulk_delete( $zones );
                $success_message = __( 'Zones deleted successfully.', 'keypress-dns' );

                /**
                 * Filters bulk delete success message.
                 *
                 * @since 1.3
                 * @param string $success_message
                 */
                $success_message = apply_filters( 'kpdns_list_zones_bulk_delete_success_message', $success_message );
                break;

            case self::ACTION_BULK_UPDATE_A_RECORDS:
                if ( ! isset( $_POST['value'] ) ) {
                    $result = false;
                    $fail_message = __( 'You must enter a valid IPv4.', 'keypress-dns' );
                    break;
                }

                $is_valid_ipv4 = KPDNS_Utils::validate_ipv4( $_POST['value'] );
                if ( is_wp_error( $is_valid_ipv4 ) ) {
                    $result = false;
                    $fail_message = __( 'You must enter a valid IPv4.', 'keypress-dns' );
                    break;
                }

                $args = array(
                    'type'   => KPDNS_Record::TYPE_A,
                    'value'  => $_POST['value'],
                    'ttl'    => 900, // $_POST['ttl']
                    'create' => true,
                );
                $result          = $this->list_zones_bulk_update_records( $api, $zones, $args );
                $success_message = __( 'A records updated successfully.', 'keypress-dns' );
                break;

            case self::ACTION_BULK_UPDATE_AAAA_RECORDS:
                $args = array(
                    'type'  => KPDNS_Record::TYPE_A,
                    'value' => $_POST['value'],//2001:db8:a0b:12f0::1
                    'ttl'   => 900,// $_POST['ttl']
                    'create' => true,
                );
                $result          = $this->list_zones_bulk_update_records( $api, $zones, $args );
                $success_message = __( 'AAAA records updated successfully.', 'keypress-dns' );
                break;

            default:
                $query_arg = array(
                    'page'     => urlencode( KPDNS_PAGE_ZONES ),
                );
                wp_redirect( add_query_arg( $query_arg, self::get_admin_url() ) );
                exit();
        }

        if ( is_wp_error( $result ) ) {
            $query_arg = KPDNS_Page::build_query_args( KPDNS_PAGE_ZONES, false, $result->get_error_messages() );
        } else {
            $messages = $result ? array( isset( $success_message ) ? $success_message : __( 'Action performed successfully.', 'keypress-dns' ) ) : array( isset( $fail_message ) ? $fail_message : __( 'Something went wrong.', 'keypress-dns' ) );
            $query_arg = KPDNS_Page::build_query_args( KPDNS_PAGE_ZONES, $result, $messages );
        }

        // Redirect back to edit zone view
        wp_redirect( add_query_arg( $query_arg, self::get_admin_url() ) );
        exit();
    }


    private function list_zones_bulk_delete( $zones ) {
        $api = kpdns_get_api();

        if ( is_wp_error( $api ) ) {
            return $api;
        }

        $messages = array();
        $zone_ids = array();

	    foreach ( $zones as $zone_id ) {
            $zone_ids[] = $zone_id;
        }

        $result = $api->delete_zones( $zone_ids );
        if ( is_wp_error( $result ) ) {
            $messages[] = $result->get_error_message( KPDNS_ERROR_CODE_GENERIC );
        }

        if ( ! empty( $messages ) ) {
            $wp_error =  new WP_Error();
            foreach ( $messages as $message ) {
                $wp_error->add( KPDNS_ERROR_CODE_GENERIC, $message );
            }
            return $wp_error;
        }

        // All zones have been deleted successfully.
        return true;
    }

    private function list_zones_bulk_update_records( $api, $zones, $args ) {

	    $messages = array();

        foreach ( $zones as $zone_id ) {


            $zone = $api->get_zone( $zone_id );
            if ( is_wp_error( $zone ) ) {
                return $zone;
            }

            $result_records = $api->list_records( $zone_id );
            if ( is_wp_error( $result_records ) ) {
                return $result_records;
            }

            $found = false;

            foreach ( $result_records as $record ) {
                if ( $record->get_type() === $args['type'] && $record->get_name() === $zone->get_domain() ) {
                    $found = true;
                    $new_record = clone $record;
                    $new_record->set_rdata( array( KPDNS_Record::RDATA_KEY_VALUE => $args['value'] ) );
                    //$new_record->set_ttl( $args['ttl'] );

                    $result_edit_record = $api->edit_record( $record, $new_record, $zone_id );

                    if ( is_wp_error( $result_edit_record ) ) {
                        $messages[] = $zone_id . ': '  . $result_edit_record->get_error_message( KPDNS_ERROR_CODE_GENERIC );
                    }
                }
            }

            if ( ! $found && $args['type'] ) {
                $record = $api->build_record(
                    array(
                        'name'  => $zone->get_domain(),
                        'value' => $args['value'],
                        'type'  => $args['type'],
                        'ttl'   => $args['ttl'],
                    )
                );
                $result_add_record = $api->add_record( $record, $zone_id );

                if ( is_wp_error( $result_add_record ) ) {
                    $messages[] = $zone_id . ': '  . $result_add_record->get_error_message( KPDNS_ERROR_CODE_GENERIC );
                }
            }
        }

        if ( ! empty( $messages ) ) {
            $wp_error =  new WP_Error();
            foreach ( $messages as $message ) {
                $wp_error->add( KPDNS_ERROR_CODE_GENERIC, $message );
            }
            return $wp_error;
        }

        // All records have been updated successfully.
        return true;
    }

	private function get_views() {
		$views = array(
			self::VIEW_LIST_ZONES => array(
				'id'       => self::VIEW_LIST_ZONES,
				'name'     => __( 'Zones', 'keypress-dns' ),
				'template' => 'list-zones',
			),
			self::VIEW_ADD_ZONE => array(
				'id'       => self::VIEW_ADD_ZONE,
				'name'     => __( 'Add New Zone', 'keypress-dns' ),
				'template' => 'add-zone',
			),
			self::VIEW_EDIT_ZONE => array(
				'id'       => self::VIEW_EDIT_ZONE,
				'name'     => __( 'Edit Zone', 'keypress-dns' ),
				'template' => 'edit-zone',
			),
			self::VIEW_DELETE_ZONE => array(
				'id'       => self::VIEW_DELETE_ZONE,
				'name'     => __( 'Delete Zone', 'keypress-dns' ),
				'template' => 'delete-zone',
			),
			self::VIEW_EDIT_RECORD => array(
				'id'       => self::VIEW_EDIT_RECORD,
				'name'     => __( 'Edit Record', 'keypress-dns' ),
				'template' => 'edit-record',
			),
			self::VIEW_DELETE_RECORD => array(
				'id'       => self::VIEW_DELETE_RECORD,
				'name'     => __( 'Delete Record', 'keypress-dns' ),
				'template' => 'delete-record',
			),
		);

		return $views;
	}

    public static function render_record_form_type_field() {
	    $config = KPDNS_Utils::get_record_type_field_config();
        $value  = isset( $_GET['record'] ) && isset( $_GET['record'][ $config['id'] ] ) ? $_GET['record'][ $config['id'] ] : ( isset( $config['value'] ) ? $config['value'] : '' );
        self::render_record_form_field( $value, $config );
    }

	public static function render_record_form_name_field( $domain ) {
        $config = KPDNS_Utils::get_record_name_field_config();
        $value = '';

        if ( isset( $_GET['record'] ) && isset( $_GET['record'][ $config['id'] ] ) ) {
            $value = rtrim( str_replace( $domain, '', $_GET['record'][ $config['id'] ] ), '.' );
        } elseif( isset( $config['value'] ) ) {
            $value = $config['value'];
        }

        //$value  = isset( $_GET['record'] ) && isset( $_GET['record'][ $config['id'] ] ) ? $_GET['record'][ $config['id'] ] : ( isset( $config['value'] ) ? $config['value'] : '' );

        self::render_record_form_field( $value, $config, array( 'domain' => $domain ) );
    }

	public static function render_record_form_rdata_fields() {
	    ?>
            <div id="kpdns-record-rdata-fields">
                <?php
                    $record_type = isset( $_GET['record'] ) && isset( $_GET['record']['type'] ) ? $_GET['record']['type'] : null;
                    $record_types_config = KPDNS_Utils::get_record_types_config();

                    if ( isset( $record_type ) && isset( $record_types_config[ $record_type ] ) ) {
                        $record_type_config = $record_types_config[ $record_type ];
                        foreach ( $record_type_config['rdata-fields'] as $rdata_field ) {
                            $value = '';

                            if ( isset( $_GET['record'] ) ) {
                                if ( isset( $_GET['record'][ $rdata_field['id'] ] ) ) {
                                    $value = $_GET['record'][ $rdata_field['id'] ];
                                } elseif( isset( $_GET['record']['rdata'] ) && isset( $_GET['record']['rdata'][ $rdata_field['id'] ] ) ) {
                                    $value = $_GET['record']['rdata'][ $rdata_field['id'] ];
                                }
                            } elseif( isset( $rdata_field['value'] ) ) {
                                $value = $rdata_field['value'];
                            }
                            //$value  = isset( $_GET['record'] ) && isset( $_GET['record']['rdata'] ) && isset( $_GET['record']['rdata'][ $rdata_field['id'] ] ) ? $_GET['record']['rdata'][ $rdata_field['id'] ] : ( isset( $rdata_field['value'] ) ? $rdata_field['value'] : '' );
                            self::render_record_form_rdata_field( $value, $rdata_field );
                        }
                    }
                ?>
            </div>
        <?php
    }

    public static function render_record_form_rdata_field( $value, $config ) {
	    ?>
        <div class="kpdns-record-field-row-container">
            <?php if ( isset( $config['label'] ) ): ?>
                <div class="kpdns-record-label-container">
                    <label
                        <?php if ( isset( $config['id'] ) ) : ?>
                            for="<?php esc_attr_e( $config['id'] ); ?>"
                        <?php endif; ?>
                    ><?php esc_attr_e( $config['label'] ); ?></label>
                </div>
            <?php endif; ?>
            <div class="kpdns-record-field-container">
                <?php
                self::_render_record_form_field( $value, $config );
                if ( isset( $config['description'] ) ) { ?>
                    <p class="description"><?php esc_html_e( $config['description'] ); ?></p>
                <?php }
                ?>
            </div>
        </div>
        <?php
    }

    public static function render_record_form_ttl_fields() {
        $ttl_config       = KPDNS_Utils::get_record_ttl_field_config();
        $ttl_units_config = KPDNS_Utils::get_record_ttl_units_field_config();

        ?>
        <div class="kpdns-record-field-row-container">
            <div class="kpdns-record-label-container">
                <label><?php esc_html_e( $ttl_config['label'] );?></label>
            </div>
            <div class="kpdns-record-field-container">
                <?php
                    $ttl_value = isset( $_GET['record'] ) && isset( $_GET['record'][ $ttl_config['id'] ] ) ? $_GET['record'][ $ttl_config['id'] ] : ( isset( $ttl_config['value'] ) ? $ttl_config['value'] : '' );
                    self::_render_record_form_field( $ttl_value, $ttl_config );
                ?>
                <p class="description"><?php esc_html_e( $ttl_config['description'] );?></p>
            </div>
            <?php if( isset( $ttl_units_config ) ) : ?>
                <div class="kpdns-record-field-container">
                    <?php
                        $ttl_value_units = isset( $_GET['record'] ) && isset( $_GET['record'][ $ttl_units_config['id'] ] ) ? $_GET['record'][ $ttl_units_config['id'] ] : ( isset( $ttl_units_config['value'] ) ? $ttl_units_config['value'] : '' );
                        self::_render_record_form_field( $ttl_value_units, $ttl_units_config );
                    ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    private static function render_record_form_field( $value, $config, $args = array() ) {
        ?>
            <div class="kpdns-record-field-row-container">
                <?php if( isset( $config['label'] ) ): ?>
                    <div class="kpdns-record-label-container">
                        <label><?php esc_html_e( $config['label'] );?></label>
                    </div>
                <?php endif; ?>
                <div class="kpdns-record-field-container">
                    <?php self::_render_record_form_field( $value, $config ) ?>
                    <?php if( isset( $args['domain'] ) ): ?>
                        <span class="kpdns-record-name-domain">.<?php esc_html_e( $args['domain'] );?></span>
                    <?php endif; ?>
                    <?php if( isset( $config['description'] ) ): ?>
                        <p class="description"><?php esc_html_e( $config['description'] );?></p>
                    <?php endif; ?>
                </div>
            </div>
        <?php
    }

    private static function _render_record_form_field( $value, $config ) {
        if ( ! isset( $config['type'] ) || ! isset( $config['id'] ) ) {
            return;
        }

        switch ( $config['type'] ) {
            case 'text':
                ?>
                <input
                    type="text"
                    id="<?php esc_attr_e( "kpdns-record-{$config['id']}" );?>"
                    name ="<?php esc_attr_e( "record[{$config['id']}]" );?>"
                    value="<?php esc_attr_e( stripslashes( $value ) );?>"
                    <?php if ( isset(  $config['placeholder'] ) ) : ?>
                        placeholder="<?php esc_attr_e( $config['placeholder'] );?>"
                    <?php endif; ?>
                    <?php if ( isset( $config['class'] ) ) : ?>
                        class="<?php esc_attr_e( $config['class'] );?>"
                    <?php endif; ?>
                    <?php if ( isset( $config['maxlength'] ) ) : ?>
                        maxlength="<?php esc_attr_e( $config['maxlength'] );?>"
                    <?php endif; ?>
                />
                <?php
                break;

            case 'textarea':
                ?>
                <textarea
                    id="<?php esc_attr_e( "kpdns-record-{$config['id']}" );?>"
                    name ="<?php esc_attr_e( "record[{$config['id']}]" );?>"
                        <?php if ( isset( $config['placeholder'] ) ) : ?>
                            placeholder="<?php esc_attr_e( $config['placeholder'] );?>"
                        <?php endif; ?>
                    <?php if ( isset( $config['class'] ) ) : ?>
                        class="<?php esc_attr_e( $config['class'] );?>"
                    <?php endif; ?>
                    <?php if ( isset( $config['maxlength'] ) ) : ?>
                        maxlength="<?php esc_attr_e( $config['maxlength'] );?>"
                    <?php endif; ?>
                    <?php if ( isset( $config['rows'] ) ) : ?>
                        rows="<?php esc_attr_e( $config['rows'] );?>"
                    <?php endif; ?>
                    <?php if ( isset( $config['cols'] ) ) : ?>
                        cols="<?php esc_attr_e( $config['cols'] );?>"
                    <?php endif; ?>
                    ><?php esc_html_e( stripslashes( $value ) );?></textarea>
                <?php
                break;

            case 'select':
                ?>
                <select id="<?php esc_attr_e( "kpdns-record-{$config['id']}" );?>"
                        name ="<?php esc_attr_e( "record[{$config['id']}]" );?>"
                    <?php if ( isset( $config['class'] ) ) : ?>
                        class="<?php esc_attr_e( $config['class'] ); ?>"
                    <?php endif; ?>
                >
                    <?php if( isset( $config['options'] ) ) : ?>
                        <?php foreach ( $config['options'] as $option ) : ?>
                            <option value="<?php esc_attr_e( $option['value'] ); ?>" <?php echo ( $value === $option['value'] ) ? 'selected="selected"' : ''  ?>><?php esc_html_e( $option['text'] ) ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                <?php
                break;
        }
    }
}