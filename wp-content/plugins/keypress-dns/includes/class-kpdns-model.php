<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'KPDNS_Model' ) ) {

	final class KPDNS_Model {

		const OPTION_PROVIDER               = 'kpdns_provider';
		const OPTION_LICENSE                = 'kpdns_license';
		const OPTION_DEFAULT_CUSTOM_NS      = 'kpdns_default_custom_ns';
		const OPTION_DEFAULT_RECORDS        = 'kpdns_default_records';
        const OPTION_WILDCARD_SUBDOMAINS    = 'kpdns_wildcard_subdomains';
		const OPTION_WP_ULTIMO_SETTINGS     = 'kpdns_wp_ultimo_settings';
        const OPTION_WP_ULTIMO_MAPPED_ZONES = 'kpdns_wp_ultimo_mapped_zones';
        const OPTION_PRIMARY_ZONE           = 'kpdns_primary_zone';

		/**
		 * @return KPDNS_License | null
		 */
		public static function get_license() {
			$stored_license = KPDNS_Option::get( self::OPTION_LICENSE );

			if ( ! $stored_license || ! isset( $stored_license['key'] ) ) {
				return null;
			}

			$license = new KPDNS_License( $stored_license['key'] );

			if ( isset( $stored_license['status'] ) ) {
				$license->set_status( $stored_license['status'] );
			}

			if ( isset( $stored_license['expiration'] ) ) {
				$license->set_expiration( $stored_license['expiration'] );
			}

			return $license;

		}

		/**
		 * @param $license | KPDNS_License
		 *
		 * @return bool
		 */
		public static function save_license( $license ) {
			if ( ! isset( $license ) || ! $license instanceof KPDNS_License ) {
				wp_die( 'Invalid license object.', 'keypress-dns' );
			}

			$license_to_store = array(
				'key' => $license->key,
			);

			$status = $license->get_status();
			if ( isset( $status ) ) {
				$license_to_store['status'] = $status;
			}

			$expiration = $license->get_expiration();
			if ( isset( $expiration ) ) {
				$license_to_store['expiration'] = $expiration;
			}

			return KPDNS_Option::update( self::OPTION_LICENSE, $license_to_store );
		}

		/**
		 * @param $license | KPDNS_License
		 *
		 * @return bool
		 */
		public static function delete_license( $license ) {
			if ( ! isset( $license ) || ! $license instanceof KPDNS_License ) {
				wp_die( 'Invalid license object.', 'keypress-dns' );
			}

			return KPDNS_Option::delete( self::OPTION_LICENSE );
		}

		/**
		 * @return string | null
		 */
		public static function get_provider_id() {
			$provider_id = KPDNS_Option::get( self::OPTION_PROVIDER );
			if ( $provider_id ) {
				return $provider_id;
			}
			return null;
		}

		/**
		 * @return KPDNS_Provider | null
		 */
		/*
		public static function get_provider() {
		    error_log('get_provider');
			$provider_id = KPDNS_Option::get( self::OPTION_PROVIDER );
			if ( $provider_id ) {
				 return KPDNS_Provider_Factory::create( $provider_id );
			}
			return null;
		}
		*/

		/**
		 * @param $provider_id | String
		 *
		 * @return bool | String error message.
		 */
		public static function save_provider( $provider ) {
			if ( ! isset( $provider ) || ! $provider instanceof KPDNS_Provider ) {
				return __( 'Invalid provider objects.', 'keypress-dns' );
			}

			KPDNS_Option::update( self::OPTION_PROVIDER, $provider->id );

			return true;
		}

		/**
		 * @param $provider | KPDNS_Provider
		 *
		 * @return bool
		 */
		public static function delete_provider( $provider ) {
			if ( ! isset( $provider ) || ! $provider instanceof KPDNS_Provider ) {
				wp_die( 'Invalid provider object.', 'keypress-dns' );
			}
			return KPDNS_Option::delete( self::OPTION_PROVIDER );;
		}

		public static function has_credentials( $provider_id ) {
			$option = "kpdns_{$provider_id}_credentials";
			return KPDNS_Option::get( $option );
		}

		public static function get_credentials( $provider_id ) {

			$option = "kpdns_{$provider_id}_credentials";

			$credentials = KPDNS_Option::get( $option );
			if ( ! $credentials ) {
				return null;
			}

			return $credentials;
		}

		/**
		 * @param $credentials | KPDNS_Credentials
		 *
		 * @return bool | string error message.
		 */
		public static function save_credentials( $credentials, $encryption_key ) {

			if ( ! isset( $credentials ) || ! $credentials instanceof KPDNS_Credentials ) {
				return __( 'Invalid credentials object.', 'keypress-dns' );
			}

			$option_name        = "kpdns_{$credentials->provider_id}_credentials";
			$stored_credentials = KPDNS_Option::get( $option_name );
			$credentials_arr    = (array) $credentials->to_array();

			foreach ( $credentials_arr as $key => $value ) {
				if ( $value === $credentials::ENCRYPTED_FIELD_PLACEHOLDER || $value === trim( $credentials::ENCRYPTED_FIELD_PLACEHOLDER ) ) {
					if ( isset( $stored_credentials ) && isset( $stored_credentials[ $key ] ) ) {
						$credentials_arr[ $key ] = $stored_credentials[ $key ];
					} else {
						$credentials_arr[ $key ] = '';
					}
				} else {
					$credentials_arr[ $key ] = KPDNS_Crypto::encrypt( $value, $encryption_key );
				}
			}

			KPDNS_Option::update( $option_name, $credentials_arr );

			return true;
		}

		/**
		 * @param $credentials | KPDNS_Credentials
		 *
		 * @return bool
		 */
		public static function delete_credentials( $credentials ) {
			if ( ! isset( $credentials ) || ! $credentials instanceof KPDNS_Credentials ) {
				wp_die( 'Invalid credentials object.', 'keypress-dns' );
			}
			$option = "kpdns_{$credentials->provider_id}_credentials";
			return KPDNS_Option::delete( $option );
		}

		/**
		 * @return bool
		 */
		public static function has_provider() {
			$provider_id = self::get_provider_id();

			if ( isset( $provider_id ) ) {
				return true;
			}

			return false;
		}

		public static function save_default_ns( $ns_id, $name_servers = array() ) {
			if ( ! isset( $ns_id ) ) {
				return __( 'Invalid name server id.', 'keypress-dns' );
			}

            if ( ! isset( $name_servers ) ) {
                return __( 'Invalid name servers.', 'keypress-dns' );
            }

            $provider_id = self::get_provider_id();

            $value = array(
                'id' => $ns_id,
                'ns' => $name_servers,
            );

			KPDNS_Option::update( self::OPTION_DEFAULT_CUSTOM_NS . '_' . $provider_id, $value );

			return true;
		}

		public static function get_default_ns() {

            $provider_id = self::get_provider_id();

            if ( isset( $provider_id ) ) {
                $default_ns_id = KPDNS_Option::get( self::OPTION_DEFAULT_CUSTOM_NS . '_' . $provider_id );
                if ( $default_ns_id ) {
                    return $default_ns_id;
                }
            }

            // Backwards Compatibility
			$default_ns_id = KPDNS_Option::get( self::OPTION_DEFAULT_CUSTOM_NS );
			if ( $default_ns_id ) {
				return $default_ns_id;
			}
			return null;
		}

		public static function delete_default_ns() {
            $provider_id = self::get_provider_id();

            if ( isset( $provider_id ) ) {
                $default_ns_id = KPDNS_Option::get( self::OPTION_DEFAULT_CUSTOM_NS . '_' . $provider_id );
                if ( $default_ns_id ) {
                    return KPDNS_Option::delete( self::OPTION_DEFAULT_CUSTOM_NS  . '_' . $provider_id );
                }
            }

            // Backwards Compatibility
			return KPDNS_Option::delete( self::OPTION_DEFAULT_CUSTOM_NS );
		}

		public static function save_default_records( $default_records ) {
			if ( ! isset( $default_records ) ) {
				return __( 'Invalid default records.', 'keypress-dns' );
			}

			KPDNS_Option::update( self::OPTION_DEFAULT_RECORDS, $default_records );

			return true;
		}

		public static function save_wildcard_subdomains( $wildcard_subdomains ) {
            if ( ! isset( $wildcard_subdomains ) ) {
                return __( 'Invalid values.', 'keypress-dns' );
            }

            KPDNS_Option::update( self::OPTION_WILDCARD_SUBDOMAINS, $wildcard_subdomains );

            return true;
        }

        public static function get_wildcard_subdomains() {
            return KPDNS_Option::get( self::OPTION_WILDCARD_SUBDOMAINS );
        }

        public static function save_wp_ultimo_settings( $wp_ultimo_settings ) {
            return KPDNS_Option::update( self::OPTION_WP_ULTIMO_SETTINGS, $wp_ultimo_settings );
        }

        public static function get_wp_ultimo_settings() {
            return KPDNS_Option::get( self::OPTION_WP_ULTIMO_SETTINGS );
        }

		public static function get_default_records() {
			$default_records = KPDNS_Option::get( self::OPTION_DEFAULT_RECORDS );
			if ( $default_records ) {
				return $default_records;
			}
			return null;
		}

		public static function delete_default_records() {
			return KPDNS_Option::delete( self::OPTION_DEFAULT_RECORDS );
		}

		public static function save_wp_ultimo_mapped_zone( array $zone ) {
		    if ( ! isset( $zone['domain'] ) || ! isset( $zone['id'] ) ) {
		        return false;
            }

            $mapped_zones = self::get_wp_ultimo_mapped_zones();
            if ( ! $mapped_zones ) {
                $mapped_zones = array();
            }

            $mapped_zones[ $zone['domain'] ] = $zone;

            return KPDNS_Option::update( self::OPTION_WP_ULTIMO_MAPPED_ZONES, $mapped_zones );
        }

        public static function get_wp_ultimo_mapped_zone( $domain ) {
            $mapped_zones = self::get_wp_ultimo_mapped_zones();
            if ( ! $mapped_zones ) {
                return false;
            }

            if ( isset( $mapped_zones[ $domain ] ) ) {
                return $mapped_zones[ $domain ];
            }

            return false;
        }

        public static function get_wp_ultimo_mapped_zones() {
            return KPDNS_Option::get( self::OPTION_WP_ULTIMO_MAPPED_ZONES );
        }

        public static function delete_wp_ultimo_mapped_zone( $domain ) {
            $mapped_zones = self::get_wp_ultimo_mapped_zones();
            if ( ! $mapped_zones ) {
                return false;
            }

            if ( isset( $mapped_zones[ $domain ] ) ) {
                unset( $mapped_zones[ $domain ] );
                return KPDNS_Option::update( self::OPTION_WP_ULTIMO_MAPPED_ZONES, $mapped_zones );
            }

            return false;
        }

        public static function save_primary_zone( array $zone ) {
            if ( ! isset( $zone['domain'] ) || ! isset( $zone['id'] ) ) {
                return false;
            }
            return KPDNS_Option::update( self::OPTION_PRIMARY_ZONE, $zone );
        }

        public static function get_primary_zone() {
            return KPDNS_Option::get( self::OPTION_PRIMARY_ZONE );
        }

        public static function delete_primary_zone() {
            return KPDNS_Option::delete( self::OPTION_PRIMARY_ZONE );
        }
	}
}
