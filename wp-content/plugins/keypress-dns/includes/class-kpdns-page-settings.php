<?php

/**
 * Handles logic for the admin settings page.
 *
 * @since 1.2
 */
final class KPDNS_Page_Settings extends KPDNS_Page {

	const ACTION_SAVE_LICENSE_KEY          = 'kpdns_save_license_key';
	const ACTION_DEACTIVATE_LICENSE        = 'kpdns_deactivate_license';
	const ACTION_SAVE_GENERAL_SETTINGS     = 'kpdns_save_general_settings';
    const ACTION_SAVE_DEFAULT_RECORDS      = 'kpdns_save_default_records';
	const ACTION_SAVE_PROVIDER_SETTINGS    = 'kpdns_save_provider_settings';
    const ACTION_SAVE_WP_ULTIMO_SETTINGS   = 'kpdns_save_wp_ultimo_settings';
	const ACTION_CREATE_NEW_ENCRYPTION_KEY = 'kpdns_create_new_encryption_key';

	const ACTION_CHECK_DEFINED_KEY         = 'kpdns_check_defined_key';
	const ACTION_GET_PROVIDER_CREDENTIALS  = 'kpdns_get_provider_credentials';

	const NONCE_SAVE_LICENSE_KEY           = 'kpdns_save_license_key_nonce';
	const NONCE_DEACTIVATE_LICENSE         = 'kpdns_deactivate_license_nonce';
	const NONCE_SAVE_GENERAL_SETTINGS      = 'kpdns_save_general_settings_nonce';
    const NONCE_SAVE_DEFAULT_RECORDS       = 'kpdns_save_default_records_nonce';
	const NONCE_SAVE_PROVIDER_SETTINGS     = 'kpdns_save_provider_settings_nonce';
    const NONCE_SAVE_WP_ULTIMO_SETTINGS    = 'kpdns_save_wp_ultimo_settings_nonce';

	const PARAM_KEY                        = 'kpdns_key';
	const PARAM_CREATE_KEY                 = 'kpdns_create_key';
	const PARAM_KEY_CREATED                = 'kpdns_key_created';

	const TAB_PROVIDER                     = 'provider';
	const TAB_GENERAL                      = 'general';
    const TAB_DEFAULT_RECORDS              = 'default-records';
	const TAB_LICENSE                      = 'license';
	const TAB_WP_ULTIMO                    = 'wp-ultimo';
	const TAB_GETTING_STARTED              = 'getting-started';

	const DOWNLOAD_URL                     = 'https://getkeypress.com/downloads/dns-manager/';

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

		$this->tabs  = $this->get_tabs();

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		if ( is_multisite() ) {
			add_action( 'network_admin_edit_' . self::ACTION_SAVE_GENERAL_SETTINGS, array( $this, 'save_general_settings' ) );
            add_action( 'network_admin_edit_' . self::ACTION_SAVE_DEFAULT_RECORDS, array( $this, 'save_default_records' ) );
			add_action( 'network_admin_edit_' . self::ACTION_SAVE_PROVIDER_SETTINGS, array( $this, 'save_provider_settings' ) );
            add_action( 'network_admin_edit_' . self::ACTION_SAVE_WP_ULTIMO_SETTINGS, array( $this, 'save_wp_ultimo_settings' ) );
			add_action( 'network_admin_edit_' . self::ACTION_SAVE_LICENSE_KEY, array( $this, 'save_and_activate_license' ) );
			add_action( 'network_admin_edit_' . self::ACTION_DEACTIVATE_LICENSE, array( $this, 'deactivate_license' ) );
			//add_action( 'network_admin_menu', array( $this, 'menu' ) );
		} else {
			add_action( 'admin_post_' . self::ACTION_SAVE_GENERAL_SETTINGS, array( $this, 'save_general_settings' ) );
            add_action( 'admin_post_' . self::ACTION_SAVE_DEFAULT_RECORDS, array( $this, 'save_default_records' ) );
			add_action( 'admin_post_' . self::ACTION_SAVE_PROVIDER_SETTINGS, array( $this, 'save_provider_settings' ) );
			add_action( 'admin_post_' . self::ACTION_SAVE_LICENSE_KEY, array( $this, 'save_and_activate_license' ) );
			add_action( 'admin_post_' . self::ACTION_DEACTIVATE_LICENSE, array( $this, 'deactivate_license' ) );
			//add_action( 'admin_menu', array( $this, 'menu' ) );
		}

		add_action( 'wp_ajax_' . self::ACTION_CHECK_DEFINED_KEY, array( $this, 'ajax_check_defined_key' ) );
		add_action( 'wp_ajax_' . self::ACTION_GET_PROVIDER_CREDENTIALS, array( $this, 'ajax_get_provider_credentials' ) );
		add_action( 'wp_ajax_' . self::ACTION_CREATE_NEW_ENCRYPTION_KEY, array( $this, 'ajax_create_new_encryption_key' ) );
	}

	public function enqueue_styles() {
		wp_enqueue_style( 'jquery-ui-dialog', WPINC . 'css/jquery-ui-dialog.css', array(), KPDNS_PLUGIN_VERSION, 'all' );
	}

	public function enqueue_scripts() {

		wp_enqueue_script( 'jquery-ui-dialog' );

		wp_enqueue_script( 'kpdns-settings', KPDNS_PLUGIN_URL . 'assets/js/settings.js', array( 'jquery' ), KPDNS_PLUGIN_VERSION, false );

		wp_localize_script(
			'kpdns-settings',
			'kpdnsSettings',
			array(
				'actions' => array(
					'checkDefinedKey'        => self::ACTION_CHECK_DEFINED_KEY,
					'getProviderCredentials' => self::ACTION_GET_PROVIDER_CREDENTIALS,
					'createNewEncryptionKey' => self::ACTION_CREATE_NEW_ENCRYPTION_KEY,
				),
				'nonces'  => array(
					'getProviderCredentials' => wp_create_nonce( self::ACTION_GET_PROVIDER_CREDENTIALS ),
					'checkDefinedKey'        => wp_create_nonce( self::ACTION_CHECK_DEFINED_KEY ),
					'createNewEncryptionKey' => wp_create_nonce( self::ACTION_CREATE_NEW_ENCRYPTION_KEY ),
				),
				'params' => array(
					'key'                    => self::PARAM_KEY,
				),
				'text'   => array(),
				'options' => array(
					'displaySaveSettingsKeyDialog' => isset( $_GET['updated'] ) && 'true' == $_GET['updated'] && isset( $_GET[ KPDNS_Page_Settings::PARAM_KEY ] ),
					'key' => isset( $_GET[ self::PARAM_KEY ] ) ? $_GET[ self::PARAM_KEY ] : false,
				),
				'page' => KPDNS_PAGE_SETTINGS,
			)
		);
	}

	/*
	public function menu() {
		if ( KPDNS_Access_control::current_user_can_access_dns_manager() ) {
			$cap   = KPDNS_Access_control::get_capability();
			$label = __( 'Settings', 'keypress-dns' );
			$func  = array( $this, 'render' );

			add_submenu_page( KPDNS_PAGE_SETTINGS, $this->title, $label, $cap, $this->slug, $func );
		}
	}
	*/

	protected function render_main_content() {
		$current_tab = $this->tabs[ $this->get_current_tab_id() ];
		parent::render_view( $current_tab['template'] );
	}

	private function get_tabs() {
		$tabs = array();

		$tabs[ self::TAB_GETTING_STARTED ] = array(
			'id'       => self::TAB_GETTING_STARTED,
			'name'     => __( 'Getting Started', 'keypress-dns' ),
			'template' => 'settings-getting-started',
		);

		/*
		$tabs[ self::TAB_GENERAL ] = array(
			'id'       => self::TAB_GENERAL,
			'name'     => __( 'General Settings', 'keypress-dns' ),
			'template' => 'settings-general',
		);
		*/

        $tabs[ self::TAB_DEFAULT_RECORDS ] = array(
            'id'       => self::TAB_DEFAULT_RECORDS,
            'name'     => __( 'Default Records', 'keypress-dns' ),
            'template' => 'settings-default-records',
        );

		$tabs[ self::TAB_PROVIDER ] = array(
			'id'       => self::TAB_PROVIDER,
			'name'     => __( 'DNS Provider', 'keypress-dns' ),
			'template' => 'settings-dns-provider',
		);

		//if ( class_exists( 'WU_Settings' ) && WU_Settings::get_setting( 'enable_domain_mapping' ) ) {
        if ( is_plugin_active( 'wp-ultimo/wp-ultimo.php' ) ) {
			$tabs[ self::TAB_WP_ULTIMO ] = array(
				'id'       => self::TAB_WP_ULTIMO,
				'name'     => __( 'WP Ultimo', 'keypress-dns' ),
				'template' => 'settings-wp-ultimo',
			);
		}

		$tabs[ self::TAB_LICENSE ] = array(
				'id'       => self::TAB_LICENSE,
				'name'     => __( 'License', 'keypress-dns' ),
				'template' => 'settings-license',
		);

		return $tabs;
	}

    public function save_general_settings() {
        check_admin_referer( self::ACTION_SAVE_GENERAL_SETTINGS, self::NONCE_SAVE_GENERAL_SETTINGS );

        //TODO
    }

	public function save_default_records() {
		check_admin_referer( self::ACTION_SAVE_DEFAULT_RECORDS, self::NONCE_SAVE_DEFAULT_RECORDS );

		// If needed fields aren't present, we can't continue
		if ( ! isset( $_POST['kpdns-settings'] ) || ! isset( $_POST['kpdns-settings']['default-records'] ) ) {
			wp_die( __( 'Unexpected error: Missing required field(s).', 'keypress-dns' ) );
		}

		$settings            = $_POST['kpdns-settings'];
		$default_records     = $settings['default-records'];

		$updated  = false;
		$messages = array();

		// Sanitize.
		foreach ( $default_records as $index => $record ) {
			if ( is_array( $record ) ) {
				foreach ( $record as $id => $value ) {
					$default_records[ $index ][ $id ] = sanitize_text_field( trim( $value ) );
				}
			}
		}

		if ( isset( $settings['wildcard-subdomains'] ) ) {
            $wildcard_subdomains = $settings['wildcard-subdomains'];

            // Sanitize
            foreach ( $wildcard_subdomains as $index => $value ) {
                $wildcard_subdomains[ $index ] = sanitize_text_field( trim( $value ) );
            }
        } else {
		    $wildcard_subdomains = array();
        }


		// Validate IPv4 address
		if ( $default_records[0]['value'] === '' ) {
			// Do not set default A record.
		} elseif ( ! filter_var( $default_records[0]['value'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
				$messages[] = __( 'Invalid IPv4 Address format.', 'keypress-dns' );
		}

		if ( empty( $messages ) ) {
			// Save default records.
			KPDNS_Model::save_default_records( $default_records );
            KPDNS_Model::save_wildcard_subdomains( $wildcard_subdomains );
			$updated = true;
			$messages[] = __( 'Settings updated successfully.', 'keypress-dns' );
		}

		$query_arg = array(
			'page'            => urlencode( KPDNS_PAGE_SETTINGS ),
			'tab'             => urlencode( self::TAB_DEFAULT_RECORDS ),
			'updated'         => urlencode( $updated ? 'true' : 'false' ),
			'messages'        => urlencode_deep( $messages ),
		);

		if ( ! $updated ) {
			$query_arg['kpdns-settings']  = urlencode_deep( $settings );
		}

		// Redirect to settings page.
		wp_redirect( add_query_arg( $query_arg, self::get_admin_url() ) );
		exit();
	}

	/**
	 * Saves the enabled modules.
	 *
	 * @since 1.0
	 * @access private
	 * @return void
	 */
	public function save_provider_settings() {

		check_admin_referer( self::ACTION_SAVE_PROVIDER_SETTINGS, self::NONCE_SAVE_PROVIDER_SETTINGS );

		// If needed fields aren't present, we can't continue
		if ( ! isset( $_POST['kpdns-settings'] ) || ! isset( $_POST['kpdns-settings']['provider'] ) ) {
			wp_die( __( 'Unexpected error: Missing required field(s).', 'keypress-dns' ) );
		}

		$settings = $_POST['kpdns-settings'];

		$provider_id    = sanitize_text_field( wp_unslash( $settings['provider'] ) );

		$messages = array();

		if ( -1 != $provider_id ) {
			$provider = KPDNS_Provider_Factory::create( $provider_id );

			if ( ! isset( $provider ) ) {
				wp_die( __( 'Unexpected error: Provider with id ' . $provider_id . ' does not exist.', 'keypress-dns' ) );
			}

			if ( ! isset( $settings['credentials'] ) || ! isset( $settings['credentials'][ $provider_id ] ) ) {
				wp_die( __( 'Unexpected error: No credentials found.', 'keypress-dns' ) );
			}

			$save_provider_result = $provider->save();
			if ( is_string( $save_provider_result ) ) {
				$messages[] = $save_provider_result;
			}

			$credentials = $provider->get_credentials();

			if ( ! defined( 'KPDNS_ENCRYPTION_KEY' ) || isset( $_GET[ self::PARAM_CREATE_KEY ] ) ) {
				$key = KPDNS_Crypto::keygen();
			} else {
				$key = hex2bin( KPDNS_ENCRYPTION_KEY );
			}

			$save_credentials_result = $credentials->save( $settings['credentials'][ $provider_id ], $key );
			if ( is_string( $save_credentials_result ) ) {
				$messages[] = $save_credentials_result;
			}

			// If the user has requested the creation of a new key, all stored credentials need to be re-encrypted.
			if ( isset( $_GET[ self::PARAM_CREATE_KEY ] ) && defined( 'KPDNS_ENCRYPTION_KEY' ) ) {
				$old_key = hex2bin( KPDNS_ENCRYPTION_KEY );
				foreach ( KPDNS_Provider_Factory::get_providers() as $provider ) {
					//Skip the current provider. We have already encrypted the credentials with the new key.
					if ( $provider->id === $provider_id ) {
						continue;
					}
					$new_credentials = array();
					foreach ( $provider->get_stored_credentials() as $field_id => $field_value ) {
						$dec = KPDNS_Crypto::decrypt( $field_value, $old_key );
						$new_credentials[ $field_id ] = KPDNS_Crypto::encrypt( $dec, $key );
					}
					$provider->save_credentials( $new_credentials );
				}
			}

		} else {
			$provider = KPDNS_Model::get_provider();
			if ( isset( $provider ) ) {
				$provider->delete();
			}

			foreach ( KPDNS_Provider_Factory::get_providers() as $provider ) {
				$credentials = $provider->get_credentials();
				$credentials->delete();
			}
		}

		if ( empty( $messages ) ) {
			$updated = 'true';
			$messages[] = __( 'Settings updated.', 'keypress-dns' );
		} else {
			$updated = 'false';
		}


		$query_arg = array(
			'page'            => urlencode( KPDNS_PAGE_SETTINGS ),
			'tab'             => urlencode( self::TAB_PROVIDER ),
			'updated'         => $updated,
			'messages'        => urlencode_deep( $messages ),
			'hide-key-notice' => 'true'
		);

		if ( -1 != $provider_id && $updated === 'true' && ( ! defined( 'KPDNS_ENCRYPTION_KEY' ) || isset( $_GET[ self::PARAM_CREATE_KEY ] ) ) ) {
			$query_arg[ self::PARAM_KEY ] = urlencode( bin2hex( $key ) );
		}

		// Redirect to settings page in network
		wp_redirect( add_query_arg( $query_arg, self::get_admin_url() ) );
		exit();
	}

	public function save_wp_ultimo_settings() {
        check_admin_referer( self::ACTION_SAVE_WP_ULTIMO_SETTINGS, self::NONCE_SAVE_WP_ULTIMO_SETTINGS );

        // If needed fields aren't present, we can't continue
        if ( ! isset( $_POST['kpdns-settings'] ) || ! isset( $_POST['kpdns-settings']['wu-metabox'] ) ) {
            wp_die( __( 'Unexpected error: Missing required field(s).', 'keypress-dns' ) );
        }

        $settings = $_POST['kpdns-settings'];

        $messages = array();

        // TODO Validations

        // Store data
        $updated = KPDNS_Model::save_wp_ultimo_settings( $settings );

        if ( $updated ) {
            $messages[] = __( 'Settings updated.', 'keypress-dns' );
        } else {
            $messages[] = __( 'Something went wrong, please try again.', 'keypress-dns' );
        }


        $query_arg = array(
            'page'            => urlencode( KPDNS_PAGE_SETTINGS ),
            'tab'             => urlencode( self::TAB_WP_ULTIMO ),
            'updated'         => urlencode( $updated ? 'true' : 'false' ),
            'messages'        => urlencode_deep( $messages ),
        );

        // Redirect to settings page in network
        wp_redirect( add_query_arg( $query_arg, self::get_admin_url() ) );
        exit();
    }


	/**
	 *
	 * Stores and activates a license key
	 *
	 * @since 0.1.0
	 */
	public function save_and_activate_license() {

		check_admin_referer( self::ACTION_SAVE_LICENSE_KEY, self::NONCE_SAVE_LICENSE_KEY );

		if ( ! isset( $_POST['kpdns_license_key'] ) ) {
			wp_die( __( 'Sorry, you are not allowed to access this page.', 'keypress-dns' ), 403 );
		}

		// Validation

		$updated = false;
		$messages = array();

		if ( 32 !== strlen( $_POST['kpdns_license_key'] ) ) {
			$messages[] = __( 'Please enter a valid license key', 'keypress-dns' );
		}

		$license_key = trim( sanitize_text_field( $_POST['kpdns_license_key'] ) );

		// Validations passed
		if ( empty( $messages ) ) {

			$license = new KPDNS_License( $license_key );
			$response = $license->remote_activate( KPDNS_PLUGIN_REMOTE_URL, KPDNS_PLUGIN_ID );

			if ( $response->success ) {
				$license->set_status( $response->status );
				$license->set_expiration( $response->expiration );
				$license->save();
				$updated = true;
				$messages[] = __( 'Your license key has been activated successfully.', 'keypress-dns' );
			} else {
				$messages[] = $license->get_error_message( $response->error );
			}
		}

		$query_args = array(
			'page'      => urlencode( $_POST['kpdns_page'] ),
			'tab'       => urlencode( $_POST['kpdns_tab'] ),
			'updated'   => urlencode( $updated ? 'true' : 'false' ),
			'messages'  => urlencode_deep( $messages ),
		);

		if ( ! $updated ) {
			$query_args[ self::PARAM_KEY ] = urlencode( $license_key );
		}

		wp_redirect( add_query_arg( $query_args, self::get_admin_url() ) );
		exit;
	}

	public function deactivate_license() {

		check_admin_referer( self::ACTION_DEACTIVATE_LICENSE, self::NONCE_DEACTIVATE_LICENSE );

		$license = KPDNS_Model::get_license();
		$response = $license->remote_deactivate( KPDNS_PLUGIN_REMOTE_URL, KPDNS_PLUGIN_ID );

		$messages = array();
		$updated = false;

		if ( $response->success ) {
			$license->delete();
			$updated = true;
			$messages[] = __( 'Your license key has been deactivated and deleted successfully.', 'keypress-dns' );
		} else {
			$messages[] = $license->get_error_message( $response->error );
		}

		$query_args = array(
			'page'     => urlencode( $_POST['kpdns_page'] ),
			'tab'      => urlencode( $_POST['kpdns_tab'] ),
			'updated'  => urlencode( $updated ? 'true' : 'false' ),
			'messages' => urlencode_deep( $messages ),
		);

		wp_redirect( add_query_arg( $query_args, self::get_admin_url() ) );
		exit;
	}

	public function ajax_check_defined_key() {
		check_ajax_referer( self::ACTION_CHECK_DEFINED_KEY );

		$key = isset( $_POST[ self::PARAM_KEY ] ) ? $_POST[ self::PARAM_KEY ] : false;

		$response = array();
		if ( defined( 'KPDNS_ENCRYPTION_KEY' ) && KPDNS_ENCRYPTION_KEY === $key ) {
			$response['success'] = true;
			$response['message'] = __( 'You have copied your secret key correctly. Click "OK" to close this window.', 'keypress-dns' );
		} else {
			$response['success'] = false;
			$response['message'] = __( 'You have not copied your secret key correctly, please try again.', 'keypress-dns' );
		}

		wp_send_json( $response );
	}

	/**
	 * @author Martín Di Felice
	 */
	public function ajax_get_provider_credentials() {
		check_ajax_referer( self::ACTION_GET_PROVIDER_CREDENTIALS );

        $providers_config = KPDNS_Provider_Factory::get_providers_config();

		if ( ! isset( $_POST['provider'] ) || ! isset( $providers_config[ $_POST['provider'] ] ) ) {
			_e( 'Error: Provider not found.', 'keypress-dns' );
			wp_die();
		}

		$provider_id = sanitize_text_field( wp_unslash( $_POST['provider'] ) );
		$provider = KPDNS_Provider_Factory::create( $provider_id );
		$credentials = $provider->get_credentials();
		$credentials->render_fields();

		wp_die();
	}

	public function ajax_create_new_encryption_key() {
		check_ajax_referer( self::ACTION_CREATE_NEW_ENCRYPTION_KEY );

		$key = KPDNS_Crypto::keygen();
		$errors = array();

		$old_key = hex2bin( KPDNS_ENCRYPTION_KEY );
		foreach ( KPDNS_Provider_Factory::get_providers() as $provider ) {

			$credentials = $provider->get_credentials();

			if ( isset( $credentials ) ) {
				$new_credentials = array();

				foreach ( $credentials->to_array() as $id => $value ) {
					$decrypted_value = KPDNS_Crypto::decrypt( $value, $old_key );
					$new_credentials[ $id ] = $decrypted_value;
				}

				$save_credentials_return = $credentials->save( $new_credentials, $key );

				if ( is_string( $save_credentials_return ) ) {
					$errors[] = $save_credentials_return;
				}
			}
		}

		$response = array(
			self::PARAM_KEY_CREATED => empty( $errors ) ? true : false,
			self::PARAM_KEY         => bin2hex( $key ),
			'messages'              => $errors,
		);

		wp_send_json( $response );

	}
}