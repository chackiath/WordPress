<?php

/**
 * Class PageAbstract.
 *
 * @since 1.0.0
 */
final class KPDNS_Page_Name_Servers extends KPDNS_Page {

	const ACTION_ADD_NAME_SERVER        = 'kpdns-add-name-server';
	const ACTION_UPDATE_NAME_SERVER     = 'kpdns-update-name-server';
	const ACTION_DELETE_NAME_SERVER     = 'kpdns-delete-name-server';
	const ACTION_SET_DEFAULT_NS         = 'kpdns-set-default-ns';
	const ACTION_UNSET_DEFAULT_NS       = 'kpdns-unset-default-ns';
	const ACTION_LIST_NS_BULK_ACTIONS   = 'kpdns-list-ns-bulk-actions';
	const ACTION_BULK_DELETE_NS         = 'kpdns_bulk_delete_ns';

	const NONCE_ADD_NAME_SERVER         = 'kpdns-add-name-server-nonce';
	const NONCE_UPDATE_NAME_SERVER      = 'kpdns-update-name-server-nonce';
	const NONCE_DELETE_NAME_SERVER      = 'kpdns-delete-name-server-nonce';
	const NONCE_SET_DEFAULT_NS          = 'kpdns-set-default-ns-nonce';
	const NONCE_UNSET_DEFAULT_NS        = 'kpdns-unset-default-ns-nonce';
	const NONCE_LIST_NS_BULK_ACTIONS    = 'kpdns-list-ns-bulk-actions-nonce';

	const VIEW_LIST_NAME_SERVERS        = 'list-name-servers';
	const VIEW_ADD_NAME_SERVER          = 'add-name-server';
	const VIEW_EDIT_NAME_SERVER         = 'edit-name-server';
	const VIEW_DELETE_NAME_SERVER       = 'delete-name-server';

	const CUSTOM_NS_DOMAIN_PLACEHOLDER  = 'your-ns-domain.com';

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

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		if ( is_multisite() ) {
			//add_action( 'network_admin_menu', array( $this, 'menu' ) );
			add_action( 'network_admin_edit_' . self::ACTION_ADD_NAME_SERVER, array( $this, 'add_name_server' ) );
			add_action( 'network_admin_edit_' . self::ACTION_UPDATE_NAME_SERVER, array( $this, 'update_name_server' ) );
			add_action( 'network_admin_edit_' . self::ACTION_DELETE_NAME_SERVER, array( $this, 'delete_name_server' ) );
			add_action( 'network_admin_edit_' . self::ACTION_SET_DEFAULT_NS, array( $this, 'set_default_ns' ) );
			add_action( 'network_admin_edit_' . self::ACTION_UNSET_DEFAULT_NS, array( $this, 'unset_default_ns' ) );
            add_action( 'network_admin_edit_' . self::ACTION_LIST_NS_BULK_ACTIONS, array( $this, 'list_ns_bulk_actions' ) );
		} else {
			//add_action( 'admin_menu', array( $this, 'menu' ) );
			add_action( 'admin_post_' . self::ACTION_ADD_NAME_SERVER, array( $this, 'add_name_server' ) );
			add_action( 'admin_post_' . self::ACTION_UPDATE_NAME_SERVER, array( $this, 'update_name_server' ) );
			add_action( 'admin_post_' . self::ACTION_DELETE_NAME_SERVER, array( $this, 'delete_name_server' ) );
			add_action( 'admin_post_' . self::ACTION_SET_DEFAULT_NS, array( $this, 'set_default_ns' ) );
			add_action( 'admin_post_' . self::ACTION_UNSET_DEFAULT_NS, array( $this, 'unset_default_ns' ) );
            add_action( 'admin_post_' . self::ACTION_LIST_NS_BULK_ACTIONS, array( $this, 'list_ns_bulk_actions' ) );
		}
	}

	public function enqueue_styles() {

	}

	public function enqueue_scripts() {
		wp_enqueue_script( 'jquery-ui-dialog' );
		wp_enqueue_script( 'kpdns-name-servers', KPDNS_PLUGIN_URL . 'assets/js/name-servers.js', array( 'jquery' ), KPDNS_PLUGIN_VERSION, false );

		$script_settings = array(
            'dialogs' => array(
                'confirmDelete' => array(
                    'autoDisplay'       => false,
                    'id'                => 'kpdns-delete-ns-dialog',
                    'title'             => __( 'Delete name server', 'keypress-dns' ),
                    'innerHTML'         => $this->build_confirm_delete_ns_dialog_innerHTML(),
                    'confirmButtonText' => __( 'Yes, delete %s custom NS', 'keypress-dns' ),
                    'cancelButtonText'  => __( 'Cancel', 'keypress-dns' ),
                ),
                'confirmDeleteOrphaned' => array(
                    'autoDisplay'       => false,
                    'id'                => 'kpdns-delete-ns-dialog',
                    'title'             => __( 'Delete name server', 'keypress-dns' ),
                    'innerHTML'         => $this->build_confirm_delete_orphaned_ns_dialog_innerHTML(),
                    'confirmButtonText' => __( 'Yes, delete orphaned name server ', 'keypress-dns' ),
                    'cancelButtonText'  => __( 'Cancel', 'keypress-dns' ),
                ),
                'nsCreated' => array(
                    'autoDisplay'       => false,
                    'id'                => 'kpdns-glue-records-dialog',
                    'title'             => __( 'Custom Name Server Setup', 'keypress-dns' ),
                    'innerHTML'         => '',
                    'confirmButtonText' => __( 'OK', 'keypress-dns' ),
                ),
            ),
            'customNSDomainPlaceholder' => self::CUSTOM_NS_DOMAIN_PLACEHOLDER,
        );

        $display_glue_records_dialog = isset( $_GET['display-gr-msg'] ) && 'true' == $_GET['display-gr-msg'];

		if ( $display_glue_records_dialog && isset( $_GET['ns-id'] ) && isset( $_GET['zone-id'] ) && isset( $_GET['domain'] ) ) {
		    //$ns_id = $_GET['ns-id'];
            //$api = kpdns_get_api();

            //if ( ! is_wp_error( $api ) ) {
                //$name_server = $api->get_name_server( $ns_id );

                //if ( ! is_wp_error( $name_server ) ) {
                    //$zone = $name_server->get_zone();

                    //if ( isset( $zone ) && $display_glue_records_dialog ) {
                        $script_settings['dialogs']['nsCreated']['autoDisplay'] = true;
                        $script_settings['dialogs']['nsCreated']['innerHTML']   = $this->build_glue_records_dialog_innerHTML( $_GET['zone-id'], $_GET['domain'] );
                    //}
                //}
            //}
        }

		wp_localize_script(
			'kpdns-name-servers',
			'kpdnsNameServers',
            $script_settings
		);
	}

	/*
	public function menu() {
		if ( KPDNS_Access_control::current_user_can_access_dns_manager() ) {
			$cap   = KPDNS_Access_control::get_capability();
			$label = __( 'Custom NS', 'keypress-dns' );
			$func  = array( $this, 'render' );

			add_submenu_page( KPDNS_PAGE_SETTINGS, $this->title, $label, $cap, $this->slug, $func );
		}
	}
	*/

	protected function render_main_content() {
		$current_view = $this->views[ $this->get_current_view_id() ];
		parent::render_view( $current_view['template'] );
	}


	private function get_views() {
		$views = array(
			self::VIEW_LIST_NAME_SERVERS => array(
				'id'       => self::VIEW_LIST_NAME_SERVERS,
				'name'     => __( 'Name Servers', 'keypress-dns' ),
				'template' => 'list-name-servers',
			),
			self::VIEW_ADD_NAME_SERVER => array(
				'id'       => self::VIEW_ADD_NAME_SERVER,
				'name'     => __( 'Add Name Server', 'keypress-dns' ),
				'template' => 'add-name-server',
			),
			self::VIEW_EDIT_NAME_SERVER => array(
				'id'       => self::VIEW_EDIT_NAME_SERVER,
				'name'     => __( 'Edit Name Server', 'keypress-dns' ),
				'template' => 'edit-name-server',
			),
			self::VIEW_DELETE_NAME_SERVER => array(
				'id'       => self::VIEW_DELETE_NAME_SERVER,
				'name'     => __( 'Delete Name Server', 'keypress-dns' ),
				'template' => 'delete-name-server',
			),
		);

		return $views;
	}

	public function add_name_server() {
		check_admin_referer( self::ACTION_ADD_NAME_SERVER, self::NONCE_ADD_NAME_SERVER );

		//If needed fields aren't present, we can't continue
		if ( ! isset( $_POST['name-server'] ) || ! isset( $_POST['name-server']['domain'] ) || ! isset( $_POST['name-server']['ns'] ) ) {
			wp_die( __( 'Unexpected error: Missing required field(s).', 'keypress-dns' ) );
		}

        $name_server = KPDNS_Utils::sanitize_name_server( $_POST['name-server'] );

		// Validate name server
		$is_valid_name_server = KPDNS_Utils::validate_name_server( $name_server );

        if ( ! is_wp_error( $is_valid_name_server ) ) {
            /**
             * Filters zone values to validate them.
             *
             * @since 1.1
             *
             * @param bool $is_valid_name_server Whether or not the name server values are valid.
             * @param array $name_server The name server values array.
             */
            $is_valid_name_server = apply_filters( 'kpdns_add_name_server_validate', $is_valid_name_server, $name_server );
        }

		self::check_errors( $is_valid_name_server, array( 'page' => KPDNS_PAGE_NAME_SERVERS, 'view' => self::VIEW_ADD_NAME_SERVER, 'name-server' => $name_server ) );

        $api = kpdns_get_api();
		self::check_errors( $api, array( 'page' => KPDNS_PAGE_NAME_SERVERS, 'view' => self::VIEW_ADD_NAME_SERVER, 'name-server' => $name_server ) );

		$domain = $name_server['domain'];
		$ns_array = array();

		foreach ( $name_server['ns'] as $index => $ns ) {
		    if ( empty( $ns ) ) {
                $ns_array[] = sprintf( 'ns%d.%s', $index + 1, $domain );
            } else {
                $ns_array[] = sprintf( '%s.%s', $ns, $domain );
            }
        }

		$args = array();

        /**
         * Filters the arguments to pass to the add_name_server method.
         *
         * @since 1.1
         *
         * @param array $args The args array.
         * @param array $name_server The name server values array.
         */
        $args = apply_filters( 'kpdns_add_name_server_args', $args, $name_server );

		$the_name_server = $api->add_name_server( $domain, $ns_array, $args );

		if ( is_wp_error( $the_name_server ) ) {
			$query_arg = KPDNS_Page::build_query_args( KPDNS_PAGE_NAME_SERVERS, false, $the_name_server->get_error_messages(), array( 'view' => self::VIEW_ADD_NAME_SERVER, 'name-server' => $name_server ) );
		} else {
			if ( isset( $name_server['default'] ) && 'true' === $name_server['default'] ) {
				KPDNS_Model::save_default_ns( $the_name_server->get_id(), $ns_array );
			}

			$query_arg = KPDNS_Page::build_query_args(
			    KPDNS_PAGE_NAME_SERVERS,
                true,
                array( __( 'Name Server created successfully.', 'keypress-dns' ) ),
                array(
                    'view'              => self::VIEW_LIST_NAME_SERVERS,
                    'display-gr-msg'    => 'true',
                    'ns-id'             => $the_name_server->get_id(),
                    'domain'            => $the_name_server->get_domain(),
                    'zone-id'           => $the_name_server->get_zone_id(),
                )
            );
		}

		wp_redirect( add_query_arg( $query_arg, self::get_admin_url() ) );
		exit();
	}

	public function update_name_server() {

		check_admin_referer( self::ACTION_UPDATE_NAME_SERVER, self::NONCE_UPDATE_NAME_SERVER );

		// If required fields aren't present, we can't continue.
		if ( ! isset( $_POST['name-server'] ) ||
		     ! isset( $_POST['name-server']['id'] ) ||
		     ! isset( $_POST['name-server']['name'] ) ) {
			wp_die( __( 'Unexpected error: Missing required field(s).', 'keypress-dns' ) );
		}

		$name_server = KPDNS_Utils::sanitize_name_server( $_POST['name-server'] );

		// Validate name server
		$is_valid = KPDNS_Utils::validate_name_server( $name_server );

		if ( is_wp_error( $is_valid ) ) {
			$query_arg = KPDNS_Page::build_query_args( KPDNS_PAGE_NAME_SERVERS, false, $is_valid->get_error_messages(), array( 'view' => self::VIEW_EDIT_NAME_SERVER, 'name-server' => urlencode_deep( $name_server ) ) );
			wp_redirect( add_query_arg( $query_arg, self::get_admin_url() ) );
			exit();
		}

        $api = kpdns_get_api();
		self::check_errors( $api, KPDNS_PAGE_NAME_SERVERS );

        $args = array();

        /**
         * Filters the arguments to pass to the edit_name_server method.
         *
         * @since 1.1
         *
         * @param array $args The args array.
         * @param array $name_server The name server values array.
         */
        $args = apply_filters( 'kpdns_edit_name_server_args', $args, $name_server );

		$response = $api->edit_name_server( $name_server, $args );

		if ( is_wp_error( $response ) ) {
			$query_arg = KPDNS_Page::build_query_args( KPDNS_PAGE_NAME_SERVERS, false, $response->get_error_messages(), array( 'view' => self::VIEW_EDIT_NAME_SERVER, 'name-server-id'   => $name_server['id'] ) );
		} else {

			if ( ! empty( $name_server['default'] ) ) {
				$this->set_default_name_server_id( $name_server['id'] );
			} elseif ( $this->get_default_name_server_id() === $name_server['id'] ) {
				$this->unset_default_name_server_id();
			}

			$query_arg = KPDNS_Page::build_query_args( KPDNS_PAGE_NAME_SERVERS, true, array( __( 'Name Server updated successfully.', 'keypress-dns' ) ), array( 'view' => self::VIEW_EDIT_NAME_SERVER, 'name-server-id'   => $name_server['id'] ) );
		}

		wp_redirect( add_query_arg( $query_arg, self::get_admin_url() ) );
		exit();
	}

	public function delete_name_server() {

		check_admin_referer( self::ACTION_DELETE_NAME_SERVER, self::NONCE_DELETE_NAME_SERVER );

		//If name server Id isn't present, we can't continue
		if ( ! isset( $_GET['id'] ) ) {
			wp_die( __( 'Unexpected error: Missing required field(s).', 'keypress-dns' ) );
		}

		$name_server_id = sanitize_text_field( trim( $_GET['id'] ) );

        $api = kpdns_get_api();
		self::check_errors( $api, KPDNS_PAGE_NAME_SERVERS );

        $args = array();

        /**
         * Filters the arguments to pass to the delete_name_server method.
         *
         * @since 1.1
         *
         * @param array $args The args array.
         * @param string $name_server_id The name server id.
         */
        $args = apply_filters( 'kpdns_delete_name_server_args', $args, $name_server_id );

		$response = $api->delete_name_server( $name_server_id, $args );

		if ( is_wp_error( $response  ) ) {
			$query_arg = KPDNS_Page::build_query_args( KPDNS_PAGE_NAME_SERVERS, false, $response->get_error_messages() );
		} else {
			$query_arg = KPDNS_Page::build_query_args( KPDNS_PAGE_NAME_SERVERS, true, array( __( 'Name Server deleted successfully.', 'keypress-dns' ) ) );
		}

		wp_redirect( add_query_arg( $query_arg, self::get_admin_url() ) );
		exit();
	}

	public function set_default_ns() {

		check_admin_referer( self::ACTION_SET_DEFAULT_NS, self::NONCE_SET_DEFAULT_NS );

		//If name server Id isn't present, we can't continue
		if ( ! isset( $_GET['id'] ) ||
             ! isset( $_GET['ns'] ) ) {
			wp_die( __( 'Unexpected error: Missing required field(s).', 'keypress-dns' ) );
		}

		$name_server_id = sanitize_text_field( trim( $_REQUEST['id'] ) );
		$name_servers   = array();;

		foreach ( $_GET['ns'] as $name_server ) {
            $name_servers[] = sanitize_text_field( trim( $name_server ) );
        }

		KPDNS_Model::save_default_ns( $name_server_id, $name_servers );

		$query_args = array(
		    'page'     => urlencode( KPDNS_PAGE_NAME_SERVERS ),
			'messages' => urlencode_deep( array( __( 'Default custom NS set successfully.', 'keypress-dns' ) ) ),
			'updated'  => urlencode( 'true' ),
		);

		$api = kpdns_get_api();
		$name_server = $api->build_name_server( $_GET );

        /**
         * Filters the query args after a custom name server has been set as default.
         *
         * @since 1.3
         *
         * @param array $query_args Associative array of query args.
         * @param KPDNS_Name_Server $name_servers Custom NS.
         */
        $query_args = apply_filters( 'kpdns_set_default_custom_ns_query_args', $query_args, $name_server );

		wp_redirect( add_query_arg( $query_args, self::get_admin_url() ) );
		exit();
	}

	public function unset_default_ns() {
		check_admin_referer( self::ACTION_UNSET_DEFAULT_NS, self::NONCE_UNSET_DEFAULT_NS );

		//If name server Id isn't present, we can't continue
		if ( ! isset( $_GET['id'] ) ) {
			wp_die( __( 'Unexpected error: Missing required field(s).', 'keypress-dns' ) );
		}

		$name_server_id = sanitize_text_field( trim( $_GET['id'] ) );
		$default_ns   = KPDNS_Model::get_default_ns();

		if ( $default_ns && is_array( $default_ns ) && isset( $default_ns['id'] ) && $name_server_id === $default_ns['id'] ) {
			KPDNS_Model::delete_default_ns();
			$updated = 'true';
			$messages = array( __( 'Default custom NS unset successfully.', 'keypress-dns' ) );
		} else {
			$updated = 'false';
			$messages = array( __( 'Default custom NS could not be unset.', 'keypress-dns' ) );
		}

		$query_args = array(
			'page'     => urlencode( KPDNS_PAGE_NAME_SERVERS ),
			'messages' => urlencode_deep( $messages ),
			'updated'  => urlencode( $updated ),
		);

        $api = kpdns_get_api();
        $name_server = $api->build_name_server( $_GET );

        /**
         * Filters the query args after a custom name server has been unset as default.
         *
         * @since 1.3
         *
         * @param array $query_args Associative array of query args.
         * @param array $name_server Custom NS id.
         */
        $query_args = apply_filters( 'kpdns_unset_default_custom_ns_query_args', $query_args, $name_server );

		wp_redirect( add_query_arg( $query_args, self::get_admin_url() ) );
		exit();
	}

	public function set_default_name_server_id( $name_server_id ) {
		$domain_mapping_settings = KPDNS_Option::get( KPDNS_OPTION_DOMAIN_MAPPING );

		if ( empty( $domain_mapping_settings ) ) {
			$domain_mapping_settings = array();
		}

		$domain_mapping_settings['default-name-server-id'] = $name_server_id;

		KPDNS_Model::save_default_ns( $domain_mapping_settings );
	}

	public function get_default_name_server_id() {
		$domain_mapping_settings = KPDNS_Option::get( KPDNS_OPTION_DOMAIN_MAPPING );

		$default_server_id = null;

		if ( isset( $domain_mapping_settings['default-name-server-id'] ) ) {
			$default_name_server_id = $domain_mapping_settings['default-name-server-id'];
		}

		return $default_name_server_id;
	}

	public function unset_default_name_server_id() {
		$domain_mapping_settings = KPDNS_Option::get( KPDNS_OPTION_DOMAIN_MAPPING );

		if ( isset( $domain_mapping_settings['default-name-server-id'] ) ) {
			unset( $domain_mapping_settings['default-name-server-id'] );
		}

		KPDNS_Option::update( KPDNS_OPTION_DOMAIN_MAPPING, $domain_mapping_settings );
	}

    public function list_ns_bulk_actions() {
        check_admin_referer( self::ACTION_LIST_NS_BULK_ACTIONS, self::NONCE_LIST_NS_BULK_ACTIONS );

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

        if ( ! isset( $_POST['name-servers'] ) || empty( $_POST['name-servers'] ) || -1 == $action ) {
            $query_arg = array(
                'page'     => urlencode( KPDNS_PAGE_NAME_SERVERS ),
            );
            wp_redirect( add_query_arg( $query_arg, self::get_admin_url() ) );
            exit();
        }

        $name_servers = $_POST['name-servers'];
        $api          = kpdns_get_api();

        self::check_errors( $api,KPDNS_PAGE_NAME_SERVERS );

        switch ( $action ) {
            case self::ACTION_BULK_DELETE_NS:
                $result          = $this->list_custom_ns_bulk_delete( $name_servers );
                $success_message = __( 'Custom NS deleted successfully.', 'keypress-dns' );

                /**
                 * Filters bulk delete success message.
                 *
                 * @since 1.3
                 * @param string $success_message
                 */
                $success_message = apply_filters( 'kpdns_list_custom_ns_bulk_delete_success_message', $success_message );
                break;

            default:
                $query_arg = array(
                    'page' => urlencode( KPDNS_PAGE_ZONES ),
                );
                wp_redirect( add_query_arg( $query_arg, self::get_admin_url() ) );
                exit();
        }

        if ( is_wp_error( $result ) ) {
            $query_arg = KPDNS_Page::build_query_args( KPDNS_PAGE_NAME_SERVERS, false, $result->get_error_messages() );
        } else {
            $messages = $result ? array( isset( $success_message ) ? $success_message : __( 'Action performed successfully.', 'keypress-dns' ) ) : array( isset( $fail_message ) ? $fail_message : __( 'Something went wrong.', 'keypress-dns' ) );
            $query_arg = KPDNS_Page::build_query_args( KPDNS_PAGE_NAME_SERVERS, $result, $messages );
        }

        // Redirect back to edit zone view
        wp_redirect( add_query_arg( $query_arg, self::get_admin_url() ) );
        exit();
    }

    private function list_custom_ns_bulk_delete( $name_servers ) {
        $api = kpdns_get_api();

        if ( is_wp_error( $api ) ) {
            return $api;
        }

        $messages = array();
        $ns_ids   = array();

        foreach ( $name_servers as $ns_id ) {
            $ns_ids[] = $ns_id;
        }

        $result = $api->delete_name_servers( $ns_ids );
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

        // All custom NS have been deleted successfully.
        return true;
    }

    private function build_confirm_delete_ns_dialog_innerHTML() {
        $innerHTML = '';
        $innerHTML .= '<p>' . __( 'You are about to delete custom name server <strong>%s</strong>.', 'keypress-dns' ) . '</p>';
        $innerHTML .= '<p>' . __( 'Before you continue, keep in mind that domains that point their NS records to this custom NS set will be left unreachable.', 'keypress-dns' ) . '</p>';
        $innerHTML .= '<p>' . __( 'Also note that deleting the custom NS WILL NOT DELETE the associated zone <strong>%s</strong>.', 'keypress-dns' ) . '</p>';
        $innerHTML .= '<p>' . __( 'This action cannot be undone. Do you want to continue?' , 'keypress-dns' ) . '</p>';

        return $innerHTML;
    }

    private function build_confirm_delete_orphaned_ns_dialog_innerHTML() {
        $innerHTML = '';
        $innerHTML .= '<p>' . __( 'You are about to delete an orphaned name server.', 'keypress-dns' ) . '</p>';
        $innerHTML .= '<p>' . __( 'This action cannot be undone. Do you want to continue?' , 'keypress-dns' ) . '</p>';
        return $innerHTML;
    }

	private function build_glue_records_dialog_innerHTML( $zone_id, $domain ) {
	    //$domain = rtrim( $zone->get_domain(), '.' ); // Remove last . when present.

        $api = kpdns_get_api();
        $records = $api->list_records( $zone_id );

        $A_records = array();

        foreach ( $records as $record ) {
            $record_name = rtrim( $record->get_name(), '.' );
            if ( $record_name !== $domain && $record->get_type() === KPDNS_Record::TYPE_A ) {
                $A_records[] = $record;
            }
        }

        $innerHTML = '';
        $innerHTML .= '<p>We have created a new DNS zone for domain <strong>' . $domain . '</strong>, with all the necessary records. Please note that if you edit that zone, your custom NS could stop working.</p>';
        $innerHTML .= '<p></p>';
        $innerHTML .= '<p>In order for your custom NS to work you must setup <strong>glue records</strong> for <strong>' . $domain . '</strong> in your domain name registrar. You can do it by adding these set of records:</p>';

        $innerHTML .= '<table id="kpdns-glue-records-dialog">';
        $innerHTML .=   '<tr>';
        $innerHTML .=       '<th>' . __( 'Name', 'Keypress-dns' ) . '</th>';
        $innerHTML .=       '<th>' . __( 'Type', 'Keypress-dns' ) . '</th>';
        $innerHTML .=       '<th>' . __( 'Value', 'Keypress-dns' ) . '</th>';
        $innerHTML .=   '</tr>';

        foreach ( $A_records as $record ) {
            $innerHTML .=   '<tr>';
            $innerHTML .=       '<td>' . $domain . ' </td>';
            $innerHTML .=       '<td>NS</td>';
            $innerHTML .=       '<td>' . $record->get_name() . '</td>';
            $innerHTML .=   '</tr>';
        }

        foreach ( $A_records as $record ) {
            $innerHTML .=   '<tr>';
            $innerHTML .=       '<td>' . $record->get_name() . ' </td>';
            $innerHTML .=       '<td>A</td>';
            $innerHTML .=       '<td>' . $record->get_rdata()[ KPDNS_Record::RDATA_KEY_VALUE ] . '</td>';
            $innerHTML .=   '</tr>';
        }

        $innerHTML .= '</table>';

        $innerHTML .= '<p>More info <a href="https://getkeypress.com/glue-records" target="_blank">here</a>.</p>';

        return $innerHTML;
    }
}