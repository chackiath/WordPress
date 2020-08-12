<?php

if ( ! class_exists( 'KPDNS_Option' ) ) {

	final class KPDNS_Option {

		/**
		 * Returns an option value based on an option name.
		 *
		 * @since 0.2.0
		 * @return mixed Value set for the option.
		 * @param string $option The name of the option to retrieve.
		 * @param bool   $default value to return if the option doesn't exist.
		 * @param bool   $use_cache Whether to use the cache.
		 */
		static function get( $option, $default = false, $use_cache = true ) {
			if ( is_multisite() ) {
				return get_site_option( $option, $default, $use_cache );
			} else {
				return get_option( $option, $default );
			}
		}

		/**
		 * Update the value of an option that was already added.
		 *
		 * @since 0.2.0
		 * @return bool       False if value was not updated and true if value was updated.
		 * @param string      $option The name of the option to update.
		 * @param mixed       $value The new value for the option.
		 * @param string|bool $autoload Whether to load the option when WordPress starts up.
		 */
		static function update( $option, $value, $autoload = null ) {
			if ( is_multisite() ) {
				return update_site_option( $option, $value );
			} else {
				return update_option( $option, $value, $autoload );
			}
		}

		/**
		 * Adds a named option/value pair to the database.
		 *
		 * @since 0.2.0
		 * @return bool       False if option was not added and true if option was added.
		 * @param string      $option The name of the option to add.
		 * @param mixed       $value The value for the option.
		 * @param string|bool $autoload The value for the option.
		 */
		static function add( $option, $value, $autoload = null ) {
			if ( is_multisite() ) {
				return add_site_option( $option, $value );
			} else {
				return add_option( $option, $value, '', $autoload );
			}
		}

		/**
		 * Removes a named option/value pair from the database.
		 *
		 * @since 0.2.0
		 * @return bool  True, if option is successfully deleted. False on failure, or option does not exist.
		 * @param string $option The name of the option to be deleted.
		 */
		static function delete( $option ) {
			if ( is_multisite() ) {
				return delete_site_option( $option );
			} else {
				return delete_option( $option );
			}
		}
	}
}