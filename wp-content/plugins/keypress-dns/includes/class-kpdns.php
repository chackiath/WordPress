<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Main plugin admin class.
 *
 * @since 0.1
 */
if ( ! class_exists( 'KPDNS' ) ) {

	final class KPDNS {

		private static $instance;

		private $page;

		public $provider;

        /**
         * Main KPDNS Instance.
         *
         * Insures that only one instance of KPDNS exists in memory at any one
         * time and prevents needing to define globals all over the place.
         *
         * @since  1.3
         * @return object|KPDNS The one true KPDNS
         */
		public static function instance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof KPDNS ) ) {
				self::$instance = new KPDNS;
				self::$instance->define_constants();
				self::$instance->load_files();

                // Autoload classes and interfaces.
                spl_autoload_register( __CLASS__ . '::autoload', false );

                self::$instance->page = self::$instance->get_current_page();

                $provider_id = KPDNS_Model::get_provider_id();

                self::$instance->provider = $provider_id ? KPDNS_Provider_Factory::create( $provider_id ) : null;

                self::$instance->register();

			}
			return self::$instance;
		}

        /**
         * Throw error on object clone.
         *
         * The whole idea of the singleton design pattern is that there is a single
         * object therefore, we don't want the object to be cloned.
         *
         * @since  1.0.2
         * @access protected
         * @return void
         */
        public function __clone() {
            // Cloning instances of the class is forbidden.
            _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'keypress-dns' ), KPDNS_PLUGIN_VERSION );
        }

        /**
         * Disable unserializing of the class.
         *
         * @since  1.0
         * @access protected
         * @return void
         */
        public function __wakeup() {
            // Unserializing instances of the class is forbidden.
            _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'keypress-dns' ), KPDNS_PLUGIN_VERSION );
        }

        /**
         * Define plugin constants.
         *
         * @access private
         * @since  1.1
         * @return void
         */
        private function define_constants() {
            if ( ! defined( 'KPDNS_PLUGIN_FILE' ) ) {
                define( 'KPDNS_PLUGIN_FILE', trailingslashit( dirname( dirname( __FILE__ ) ) ) . 'keypress-dns.php' );
            }

            if ( ! defined( 'KPDNS_PLUGIN_DIR' ) ) {
                define( 'KPDNS_PLUGIN_DIR', plugin_dir_path( KPDNS_PLUGIN_FILE ) );
            }

            if ( ! defined( 'KPDNS_PLUGIN_VERSION' ) ) {
                define( 'KPDNS_PLUGIN_VERSION', '1.3' );
            }

            if ( ! defined( 'KPDNS_PLUGIN_AUTHOR' ) ) {
                define( 'KPDNS_PLUGIN_AUTHOR', 'KeyPress Media' );
            }

            if ( ! defined( 'KPDNS_PLUGIN_IS_BETA' ) ) {
                define( 'KPDNS_PLUGIN_IS_BETA', false );
            }

            if ( ! defined( 'KPDNS_PLUGIN_URL' ) ) {
                define( 'KPDNS_PLUGIN_URL', plugin_dir_url( KPDNS_PLUGIN_FILE ) );
            }

            if ( ! defined( 'KPDNS_PLUGIN_PAGE_URL' ) ) {
                define( 'KPDNS_PLUGIN_PAGE_URL', 'https://getkeypress.com/downloads/dns-manager/' );
            }

            if ( ! defined( 'KPDNS_PLUGIN_REMOTE_URL' ) ) {
                define( 'KPDNS_PLUGIN_REMOTE_URL', 'https://getkeypress.com/' );
            }

            if ( ! defined( 'KPDNS_PLUGIN_ID' ) ) {
                define( 'KPDNS_PLUGIN_ID', 62 );
            }

            if ( ! defined( 'KPDNS_PAGE_SETTINGS' ) ) {
                define( 'KPDNS_PAGE_SETTINGS', 'kpdns-settings' );
            }

            if ( ! defined( 'KPDNS_PAGE_ZONES' ) ) {
                define( 'KPDNS_PAGE_ZONES', 'kpdns-zones' );
            }

            if ( ! defined( 'KPDNS_PAGE_NAME_SERVERS' ) ) {
                define( 'KPDNS_PAGE_NAME_SERVERS', 'kpdns-name-servers' );
            }

            if ( ! defined( 'KPDNS_ERROR_CODE_GENERIC' ) ) {
                define( 'KPDNS_ERROR_CODE_GENERIC', '10' );
            }

            if ( ! defined( 'KPDNS_DEFAULT_ERROR_MESSAGE' ) ) {
                define( 'KPDNS_DEFAULT_ERROR_MESSAGE', __( 'Something went wrong please try again.', 'keypress-dns' ) );
            }

            if ( ! defined( 'KPDNS_ITEMS_PER_PAGE' ) ) {
                define( 'KPDNS_ITEMS_PER_PAGE', '10' );
            }

            if ( ! defined( 'KPDNS_ACTION_AJAX_PULL_RECORDS' ) ) {
                define( 'KPDNS_ACTION_AJAX_PULL_RECORDS', 'kpdns-ajax-pull-records' );
            }
        }

        /**
         * Loads required files.
         *
         * @since  1.1
         * @access private
         * @return void
         */
        private function load_files() {
            // Make sure /wp-admin/includes/plugin.php file is loaded
            if ( ! function_exists( 'is_plugin_active' ) ) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }

            require_once KPDNS_PLUGIN_DIR . 'includes/functions.php';
        }

        /**
         * Autoloads classes and interfaces.
         *
         * @since  1.1
         * @param  $name Class or Interface name.
         * @return void
         */
        public static function autoload( $name ) {

            if ( strpos( $name, 'KPDNS' ) === false) {
                return;
            }

            $name = str_replace( '_', '-', strtolower( $name ) );

            if ( file_exists(KPDNS_PLUGIN_DIR. 'includes/class-' . $name . '.php' ) ) {
                require_once( KPDNS_PLUGIN_DIR . 'includes/class-' . $name . '.php' );
                return;
            } elseif ( file_exists(KPDNS_PLUGIN_DIR. 'includes/interface-' . $name . '.php' ) ) {
                require_once( KPDNS_PLUGIN_DIR . 'includes/interface-' . $name . '.php' );
                return;
            }

            // Maybe it's a provider class.
            $providers_dirs = array_filter( glob(KPDNS_PLUGIN_DIR . 'includes/providers/*' ), 'is_dir' );

            if ( is_array( $providers_dirs ) && ! empty( $providers_dirs ) ) {
                foreach ( $providers_dirs as $dir ) {
                    if ( file_exists($dir . '/class-' . $name . '.php' ) ) {
                        require_once( $dir . '/class-' . $name . '.php' );
                        return;
                    }
                }
            }
        }

		/**
		 * Initialize hooks.
		 *
		 * @since  1.2.0
		 * @return void
		 */
		public function register() {
			register_activation_hook( KPDNS_PLUGIN_FILE, array( $this, 'activate' ) );
			register_deactivation_hook( KPDNS_PLUGIN_FILE, array( $this, 'deactivate' ) );
			register_uninstall_hook( KPDNS_PLUGIN_FILE, __CLASS__ . '::uninstall' );

			add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );
			add_action( 'admin_init', array( $this, 'check_plugin_updates' ) );


			if ( KPDNS_Access_Control::is_plugin_page() ) {
				add_action( 'admin_init', array( $this, 'check_requirements' ) );
				add_filter( 'admin_body_class', array( $this, 'filter_admin_body_class' ) );
				add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
			}

			if ( is_multisite() ) {
                add_action( 'network_admin_menu', array( $this, 'menu' ) );

                add_action( 'wp_insert_site', array( $this, 'maybe_add_subdomains_records' ) );
                add_action( 'wp_delete_site', array( $this, 'maybe_delete_subdomains_records' ) );

                // Support for WP Ultimo.
                if ( is_plugin_active( 'wp-ultimo/wp-ultimo.php' ) ) {
                    //add_action( 'load-toplevel_page_wu-my-account', array( $this , 'wp_ultimo_support' ), 10 );
                    add_action( 'admin_init', 'KPDNS_WP_Ultimo::register_hooks' );
                }

            } else {
                add_action( 'admin_menu', array( $this, 'menu' ) );
            }

            add_action( 'wp_ajax_' . KPDNS_ACTION_AJAX_PULL_RECORDS, 'KPDNS_Utils::ajax_pull_records' );

			if ( self::$instance->provider instanceof KPDNS_Registrable) {
                self::$instance->provider->register();
            }

        }

        private function get_current_page() {
            if ( isset( $_REQUEST['page'] ) ) {
                $current_page = $_REQUEST['page'];
            } elseif( isset( $_REQUEST['kpdns-page'] ) ) {
                $current_page = $_REQUEST['kpdns-page'];
            } else {
                return null;
            }

            switch( $current_page ) {
                case KPDNS_PAGE_SETTINGS:
                    return new KPDNS_Page_Settings();

                case KPDNS_PAGE_ZONES:
                    return new KPDNS_Page_Zones();

                case KPDNS_PAGE_NAME_SERVERS:
                    return new KPDNS_Page_Name_Servers();

                default:
                    return null;
            }
        }

		/**
		 * The code that runs during plugin activation.
		 */
		public function activate() {
			// TODO
		}

		/**
		 * The code that runs during plugin deactivation.
		 */
		public function deactivate() {
			// TODO
		}

		/**
		 * The code that runs during plugin uninstall.
		 */
		public static function uninstall() {
			// TODO
		}

		/**
		 * Load the plugin text domain for translation.
		 *
		 * @since    0.1.0
		 */
		public function load_plugin_textdomain() {
			load_plugin_textdomain(
				'keypress-dns',
				false,
				KPDNS_PLUGIN_DIR . '/languages/'
			);

		}

		/**
		 * Enqueue admin assets.
		 *
		 * @param $hook
		 */
		public function enqueue_assets() {
		    $this->enqueue_styles();
			$this->enqueue_scripts();
        }

		/**
		 * Register the stylesheets for the admin area.
		 *
		 * @since    0.1.0
		 */
		private function enqueue_styles() {
			wp_enqueue_style( 'kpdns-admin', KPDNS_PLUGIN_URL . 'assets/css/admin.css', array(), KPDNS_PLUGIN_VERSION, 'all' );
		}

		/**
		 * Register the JavaScript scripts for the admin area.
		 *
		 * @since    0.1.0
		 */
		private function enqueue_scripts() {
			wp_enqueue_script( 'jquery-ui-tooltip' );
			wp_enqueue_script( 'kpdns-admin', KPDNS_PLUGIN_URL . 'assets/js/admin.js', array( 'jquery', 'jquery-ui-tooltip' ), KPDNS_PLUGIN_VERSION, false );
		}

		/**
		 * Add the admin menu.
		 */
		public function menu() {
			add_menu_page(
				__( 'DNS Manager', 'keypress-dns' ),
				__( 'DNS Manager', 'keypress-dns' ),
				KPDNS_Access_control::get_capability(),
				KPDNS_PAGE_SETTINGS,
				function(){},
				'dashicons-networking'
			);

			add_submenu_page(
				KPDNS_PAGE_SETTINGS,
				__( 'DNS Manager Settings', 'keypress-dns' ),
				__( 'Settings', 'keypress-dns' ),
				KPDNS_Access_control::get_capability(),
				KPDNS_PAGE_SETTINGS,
				array( $this, 'render_page' )
			);

			add_submenu_page(
				KPDNS_PAGE_SETTINGS,
				__( 'DNS Manager Zones', 'keypress-dns' ),
				__( 'DNS Zones', 'keypress-dns' ),
				KPDNS_Access_control::get_capability(),
				KPDNS_PAGE_ZONES,
				array( $this, 'render_page' )
			);

			add_submenu_page(
				KPDNS_PAGE_SETTINGS,
				__( 'DNS Manager Name Servers', 'keypress-dns' ),
				__( 'Custom NS', 'keypress-dns' ),
				KPDNS_Access_control::get_capability(),
				KPDNS_PAGE_NAME_SERVERS,
				array( $this, 'render_page' )
			);
		}

		public function render_page() {
			if ( isset( $this->page ) && $this->page instanceof KPDNS_Page ) {
				$this->page->render();
			}
		}

		/**
		 * Checks the plugin requirements and renders an admin notice when needed.
		 */
		public function check_requirements() {
			$this->check_ssl();
			$this->check_provider();
			$this->check_key();
        }

		/**
		 * Checks to see if the site has SSL enabled and shows
		 * an admin notice if not.
		 *
		 * @since  1.2.0
		 * @access private
		 * @return void
		 */
		private function check_ssl() {

			if ( is_ssl() ) {
				return;
			} elseif ( 0 === stripos( get_option( 'siteurl' ), 'https://' ) ) {
				return;
			} elseif ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && 'https' == $_SERVER['HTTP_X_FORWARDED_PROTO'] ) {
				return;
			}

			add_action( 'admin_notices', array( $this, 'ssl_admin_notice' ) );
			add_action( 'network_admin_notices', array( $this, 'ssl_admin_notice' ) );
		}

		/**
		 * Shows an admin notice if there the user has not set up a provider.
		 *
		 * @since  1.2.0
		 * @return void
		 */
		private function check_provider() {
			$provider_id = KPDNS_Model::get_provider_id();

			if ( isset( $provider_id ) ) {
				return;
			}

			add_action( 'admin_notices', array( $this, 'provider_admin_notice' ) );
			add_action( 'network_admin_notices', array( $this, 'provider_admin_notice' ) );
		}

		/**
		 * Shows an admin notice if there the user has set up a provider but the encryption key constant is not defined.
		 *
		 * @since  1.2.0
		 * @return void
		 */
		private function check_key() {

			$provider_id = KPDNS_Model::get_provider_id();

			if ( isset( $provider_id ) && ! defined( 'KPDNS_ENCRYPTION_KEY' ) && ! isset( $_GET['hide-key-notice'] ) ) {
				add_action( 'admin_notices', array( $this, 'key_admin_notice' ) );
				add_action( 'network_admin_notices', array( $this, 'key_admin_notice' ) );
			}
		}

		/**
		 * Shows an admin notice if SSL is not enabled.
		 *
		 * @since  1.2.0
		 * @return void
		 */
		public function ssl_admin_notice() {
			$message = __( 'Your site does not have SSL enabled. We strongly recommend that you enable SSL to ensure the security of your DNS Manager data.', 'keypress-dns' );

			$this->render_admin_notice( $message, 'warning' );
		}

		/**
		 * Shows an admin notice if SSL is not enabled.
		 *
		 * @since  1.2.0
		 * @return void
		 */
		public function provider_admin_notice() {
			$message = __( sprintf( 'Please select a Managed DNS provider and enter your credentials. <a href="%s">Go to DNS Provider settings</a>.', add_query_arg( array( 'page' => KPDNS_PAGE_SETTINGS, 'tab' => KPDNS_Page_Settings::TAB_PROVIDER ), KPDNS_Page::get_admin_url() ) ), 'keypress-dns' );

			$this->render_admin_notice( $message, 'warning' );
		}


		/**
		 * Shows an admin notice if there the user has set up a provider but the encryption key constant is not defined.
		 *
		 * @since  1.2.0
		 * @return void
		 */
		public function key_admin_notice() {
			$message = __( 'You don\'t seem to have copied the encryption key into your wp-config.php file, so we can\'t decrypt your DNS provider\'s credentials. Please go to DNS Manager/Settings/DNS Provider, click on the "Create New Encryption Key" button and follow the instructions. When done, this admin notice should disappear after refreshing the page.', 'keypress-dns' );

			$this->render_admin_notice( $message, 'warning' );
		}

		/**
		 * Renders an admin notice.
		 *
		 * @since  1.2.0
		 * @access private
		 * @param string $message
		 * @param string $type
		 * @return void
		 */
		private function render_admin_notice( $message, $type = 'update' ) {
			if ( ! is_admin() ) {
				return;
			} elseif ( ! is_user_logged_in() ) {
				return;
			} elseif ( ! current_user_can( 'update_plugins' ) ) {
				return;
			}

			echo '<div class="notice notice-' . $type . ' is-dismissible">';
			echo '<p>' . $message . '</p>';
			echo '</div>';
		}

		/**
		 * Plugin updater
		 *
		 * @since 0.2.0
		 */
		public function check_plugin_updates() {
			$capability = is_multisite() ? 'manage_network_plugins' : 'update_plugins';

			if ( ! current_user_can( $capability ) ) {
				return;
			}

			if ( ! class_exists( 'KPDNS_Model' ) ) {

			}

			// Check for updates
			$license = KPDNS_Model::get_license();

			if ( $license ) {
				// setup the updater
				new KPDNS_EDD_SL( KPDNS_PLUGIN_REMOTE_URL, KPDNS_PLUGIN_FILE, array(
					'version' 	=> KPDNS_PLUGIN_VERSION,		// current version number
					'license' 	=> $license->key,	// license key (used get_option above to retrieve from DB)
					'item_id'   => KPDNS_PLUGIN_ID,	// id of this plugin
					'author' 	=> KPDNS_PLUGIN_AUTHOR,	// author of this plugin
					'url'       => home_url(),
					'beta'      => KPDNS_PLUGIN_IS_BETA // set to true if you wish customers to receive update notifications of beta releases
				) );

				//add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'add_plugin_update_icon' ) );
            }
		}

		/**
		 * Adds the plugin icon on the updates screen.
		 *
		 * @param  $_transient_data
		 * @return mixed
		 */
		public function add_plugin_update_icon( $_transient_data ) {
			$_transient_data->response[ plugin_basename( KPDNS_PLUGIN_FILE ) ]->icons = array(
				'1x' => KPDNS_PLUGIN_URL . 'admin/assets/images/kpdns-128.gif',
				'2x' => KPDNS_PLUGIN_URL . 'admin/assets/images/kpdns-256.gif',
				'default' => KPDNS_PLUGIN_URL . 'admin/assets/images/kpdns-256.gif',
			);

			return $_transient_data;
		}

		/**
		 * Adds 'kpdns' class to the body tag.
		 *
		 * @param  $classes| string CSS classes.
		 * @return string
		 */
        public function filter_admin_body_class( $classes ) {
		    $classes .= empty( $classes ) ? 'kpdns' : ' kpdns';
		    return $classes;
        }

        public function maybe_add_subdomains_records( $new_site ) {
            $wildcard_subdomains = KPDNS_Model::get_wildcard_subdomains();
            if ( $wildcard_subdomains && isset( $wildcard_subdomains['a-record'] ) && $wildcard_subdomains['a-record'] === 'true' ) {
                $primary_zone = KPDNS_Model::get_primary_zone();
                if ( $primary_zone && isset( $primary_zone['id'] ) ) {
                    $api = $this->provider->api;
                    if ( is_wp_error( $api ) ) {
                        return;
                    }

                    /*
                    $records = $api->list_records( $primary_zone['id'] );

                    if ( is_wp_error( $records ) ) {
                        return;
                    }

                    $the_record = false;
                    foreach ( $records as $record ) {
                        if ( $record->get_type() === KPDNS_Record::TYPE_A ) {
                            if ( rtrim( $record->get_name(), '.' ) === $primary_zone['domain'] ) {
                                $the_record = $record;
                                break;
                            }
                        }
                    }

                    if ( $the_record ) {
                        $the_record->set_name( $new_site->domain );
                        $api->add_record( $the_record, $primary_zone['id'] );
                    }
                    */

                    $record = new KPDNS_Record( KPDNS_Record::TYPE_CNAME, $new_site->domain, array( KPDNS_Record::RDATA_KEY_VALUE => $primary_zone['domain'] ), 3600 );
                    $api->add_record( $record, $primary_zone['id'] );
                }
            }
        }

        public function maybe_delete_subdomains_records( $old_site ) {
            $wildcard_subdomains = KPDNS_Model::get_wildcard_subdomains();
            if ( $wildcard_subdomains && isset( $wildcard_subdomains['a-record'] ) && $wildcard_subdomains['a-record'] === 'true' ) {
                $primary_zone = KPDNS_Model::get_primary_zone();
                if ( $primary_zone && isset( $primary_zone['id'] ) ) {
                    $api = $this->provider->api;
                    if ( is_wp_error( $api ) ) {
                        return;
                    }

                    $records = $api->list_records( $primary_zone['id'] );

                    if ( is_wp_error( $records ) ) {
                        return;
                    }

                    $the_record = false;
                    foreach ( $records as $record ) {
                        if ( $record->get_type() === KPDNS_Record::TYPE_A ) {
                            if ( rtrim( $record->get_name(), '.' ) === $old_site->domain ) {
                                $the_record = $record;
                                break;
                            }
                        }
                    }

                    if ( $the_record ) {
                        $api->delete_record( $the_record, $primary_zone['id'] );
                    }
                }
            }
        }
	}
}