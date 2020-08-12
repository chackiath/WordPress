<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'KPDNS_Loader' ) ) {

	/**
	 * Responsible for setting up plugin constants, classes and includes.
	 *
	 * @since 1.1
	 */
	final class KPDNS_Loader {

		/**
		 * Inits the plugin.
		 *
		 * @since 1.1
		 * @return void
		 */
		static public function init() {
			if ( ! is_admin() ) {
				return;
			}

			self::define_constants();
			self::load_files();

			// Autoload classes and interfaces.
            // TODO Use namespaces.
            try {
                spl_autoload_register( __CLASS__ . '::autoload', false );
            } catch ( Exception $e ) {
                error_log( print_r( $e, true ) );
            }
		}

		/**
		 * Define plugin constants.
		 *
		 * @access private
		 * @since 1.1
         * @return void
		 */
		private static function define_constants() {
			self::define_constant( 'KPDNS_PLUGIN_FILE', trailingslashit( dirname( dirname( __FILE__ ) ) ) . 'keypress-dns.php' );
			self::define_constant( 'KPDNS_PLUGIN_DIR', plugin_dir_path( KPDNS_PLUGIN_FILE ) );

			self::define_constant( 'KPDNS_PLUGIN_VERSION', '1.2.2' );
			self::define_constant( 'KPDNS_PLUGIN_AUTHOR', 'KeyPress Media' );
			self::define_constant( 'KPDNS_PLUGIN_IS_BETA', false );
			self::define_constant( 'KPDNS_PLUGIN_URL', plugin_dir_url( KPDNS_PLUGIN_FILE ) );
			self::define_constant( 'KPDNS_PLUGIN_PAGE_URL', 'https://getkeypress.com/downloads/dns-manager/' );
			self::define_constant( 'KPDNS_PLUGIN_REMOTE_URL', 'https://getkeypress.com/' );
			self::define_constant( 'KPDNS_PLUGIN_ID', 62 );

			self::define_constant( 'KPDNS_PAGE', 'kpdns' );
			self::define_constant( 'KPDNS_PAGE_SETTINGS', 'kpdns-settings' );
			self::define_constant( 'KPDNS_PAGE_ZONES', 'kpdns-zones' );
			self::define_constant( 'KPDNS_PAGE_NAME_SERVERS', 'kpdns-name-servers' );

			self::define_constant( 'KPDNS_ERROR_CODE_GENERIC', '10' );
            self::define_constant( 'KPDNS_DEFAULT_ERROR_MESSAGE', __( 'Something went wrong please try again.', 'keypress-dns' ) );

            self::define_constant( 'KPDNS_ITEMS_PER_PAGE', '10' );
            self::define_constant( 'KPDNS_ACTION_AJAX_PULL_RECORDS', 'kpdns-ajax-pull-records' );

		}

        /**
         * Loads required files.
         *
         * @since 1.1
         * @access private
         * @return void
         */
		private static function load_files() {
            // Make sure /wp-admin/includes/plugin.php file is loaded
            if ( ! function_exists( 'is_plugin_active' ) ) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }

		    require_once KPDNS_PLUGIN_DIR . 'includes/functions.php';
        }

        /**
         * Autoloads classes and interfaces.
         *
         * @since 1.1
         * @param $name Class or Interface name.
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
		 * Define a constant.
		 *
		 * @since 1.1
		 * @access private
		 * @param $name The constant name.
		 * @param $value The constant value.
		 * @return void
		 */
		private static function define_constant( $name, $value ) {
			if ( ! defined( $name ) ) {
				define( $name, $value );
			}
		}
	}
}

KPDNS_Loader::init();