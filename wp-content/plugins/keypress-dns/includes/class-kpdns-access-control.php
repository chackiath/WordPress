<?php

if ( ! class_exists( 'KPDNS_Access_Control' ) ) {

	final class KPDNS_Access_Control {


		/**
		 * Restrict DNS Manager accessibility based on the defined capability.
		 *
		 * @since 1.2.0
		 * @access private
		 * @return bool
		 */
		// TODO Future versions will allow Role Based Access Control.
		static public function current_user_can_access_dns_manager() {
			return current_user_can( self::get_capability() );
		}

		/**
		 * Define capability.
		 *
		 * @since 1.2.0
		 * @return string
		 */
		// TODO Future versions will allow Role Based Access Control.
		static public function get_capability() {
			$capability = is_multisite() ? 'manage_network_options' : 'manage_options' ;

			return $capability;
		}

		/**
		 * Returns the required capability.
		 *
		 * @since 0.2.0
		 * @return string  Capability.
		 */
		static function get_required_capability() {
			if ( is_multisite() ) {
				return 'manage_network_options';
			} else {
				return 'manage_options';
			}
		}

		static function check_capability( $capability = false ) {
			if ( ! $capability ) {
				$capability = self::get_required_capability();
			}

			if ( ! current_user_can( $capability ) ) {
				wp_die( __( 'Sorry, you are not allowed to access this page.', 'keypress-ui' ), 403 );
			}
		}

		static function is_plugin_page( $hook = '' ) {
			if ( isset( $hook ) && ! empty( $hook ) && strpos( $hook, 'page_kpdns' ) !== false ) {
				return true;
			} elseif ( isset( $_GET['page'] ) && strpos( $_GET['page'], 'kpdns' ) !== false ) {
				return true;
			} elseif ( isset( $_GET['kpdns-page'] ) && strpos( $_GET['kpdns-page'], 'kpdns' ) !== false ) {
				return true;
			}
			return false;
		}
	}
}