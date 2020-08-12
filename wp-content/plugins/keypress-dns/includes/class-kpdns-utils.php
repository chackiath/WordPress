<?php

if ( ! class_exists( 'KPDNS_Utils' ) ) {

	final class KPDNS_Utils {

		const ZONE_FIELD_ID                 = 'ID';
		const ZONE_FIELD_NAME               = 'NAME';
		const ZONE_FIELD_DOMAIN             = 'DOMAIN';
		const ZONE_FIELD_DESCRIPTION        = 'DESCRIPTION';

		const NAME_SERVER_FIELD_NAME        = 'NAME';

		static function maybe_add_default_records( $zone, $args = array() ) {

		    $api = kpdns_get_api();

			if ( is_wp_error( $api ) ) {
				return $api;
			}

			if ( ! isset( $zone ) || ! is_array( $zone ) || empty( $zone ) ) {
				return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( 'Invalid zone.', 'keypress-dns' ) );
			}

			if ( ! isset( $zone['id'] ) || empty( $zone['id'] ) ) {
				return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( 'Invalid zone id.', 'keypress-dns' ) );
			}

			$default_records_ttl = apply_filters( 'kpdns_default_records_ttl', 60 );

            $error = new WP_Error();

			/** User has overridden default records. **/

			if ( ! empty( $args ) ) {

			    // A record
                if ( isset( $args['a-record'] ) && ! empty( $args['a-record'] )  ) {
                    $is_valid_a_record = KPDNS_Utils::validate_ipv4( $args['a-record'] );
                    if ( is_wp_error( $is_valid_a_record ) ) {
                        $error->add( KPDNS_ERROR_CODE_GENERIC, $is_valid_a_record->get_error_message() );
                    }

                    $new_record = new KPDNS_Record( KPDNS_Record::TYPE_A, $zone['domain'], array( KPDNS_Record::RDATA_KEY_VALUE => $args['a-record'] ), $default_records_ttl );
                    $result = $api->add_record( $new_record, $zone['id'] );

                    if ( is_wp_error( $result ) ) {
                        $error->add( KPDNS_ERROR_CODE_GENERIC, $result->get_error_message() );
                    }
                }

                // CNAME record
                if ( isset( $args['www-record'] ) && $args['www-record'] ) {

                    $new_record = new KPDNS_Record( KPDNS_Record::TYPE_CNAME, 'www.' . $zone['domain'], array( KPDNS_Record::RDATA_KEY_VALUE => $zone['domain'] ), $default_records_ttl );
                    $result = $api->add_record( $new_record, $zone['id'] );

                    if ( is_wp_error( $result ) ) {
                        $error->add( KPDNS_ERROR_CODE_GENERIC, $result->get_error_message() );
                    }
                }

                if ( $error->has_errors() ) {
                    return $error;
                }

                return true;

            }

			/** Use default records. **/
			$default_records = KPDNS_Model::get_default_records();

			if ( ! $default_records || ! is_array( $default_records ) || empty( $default_records ) ) {
				return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( 'Default records not defined.', 'keypress-dns' ) );
			}

			$error = new WP_Error();

			foreach ( $default_records as $record ) {
				$new_record = null;

				switch( $record['type'] ) {
					case KPDNS_Record::TYPE_A:
						$new_record = new KPDNS_Record( KPDNS_Record::TYPE_A, $zone['domain'], array( KPDNS_Record::RDATA_KEY_VALUE => $record['value'] ), self::ttl_to_seconds( $record['ttl'], $record['ttl-unit'] ) );
						break;

					case KPDNS_Record::TYPE_CNAME:
						if ( ! isset( $record['create-record'] ) || 'true' !== $record['create-record'] ) {
							break;
						}
                        $new_record = new KPDNS_Record( KPDNS_Record::TYPE_CNAME,'www.' . $zone['domain'], array( KPDNS_Record::RDATA_KEY_VALUE => $zone['domain'] ), self::ttl_to_seconds( $record['ttl'], $record['ttl-unit'] ) );
                        break;
				}

				if ( isset( $new_record ) ) {
					$result = $api->add_record( $new_record, $zone['id'] );

					if ( is_wp_error( $result ) ) {
						$error->add( KPDNS_ERROR_CODE_GENERIC, $result->get_error_message() );
					}
				}
			}

			if ( $error->has_errors() ) {
				return $error;
			}

			return true;
		}

		static function validate_zone( $zone, $ignore = null ) {

			if ( null == $ignore || ! in_array( self::ZONE_FIELD_ID, $ignore ) ) {
				$is_valid = self::validate_zone_id( $zone['id'] );
				if ( $is_valid instanceof  WP_Error ) return $is_valid;
			}
			if ( null == $ignore || ! in_array( self::ZONE_FIELD_NAME, $ignore ) ) {
				$is_valid = self::validate_zone_name( $zone['name'] );
				if ( $is_valid instanceof  WP_Error ) return $is_valid;
			}
			if ( null == $ignore || ! in_array( self::ZONE_FIELD_DOMAIN, $ignore ) ) {
				$is_valid = self::validate_zone_domain_name( $zone['domain'] );
				if ( $is_valid instanceof  WP_Error ) return $is_valid;
			}

			return true;
		}

		/**
		 * @param $id
		 *
		 * @return bool|WP_Error
		 */
		static function validate_zone_id( $id ) {

			if ( isset( $id ) && '' != $id ) {
			    return true;
			} else {
				return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( 'Id field is required.', 'keypress-dns' ) );
			}
		}

		static function validate_zone_name( $name ) {

			if ( isset( $name ) && '' != $name ) {
				//Zone name must be 63 characters or less
				if ( strlen( $name ) > 63 ) {
					return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( 'Zone name must be 63 characters or less.', 'keypress-dns' ) );
				}

				//Check format
				if ( ! preg_match( "/^[a-z](((\-)?[a-z0-9])*$)/", $name ) ) {
					return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( 'Zone name must begin with a letter, end with a letter or digit, and only contain lowercase letters, digits or dashes.', 'keypress-dns' ) );
				}

			} else {
				return new WP_Error( KPDNS_ERROR_CODE_GENERIC,  __( 'Name field is required.', 'keypress-dns' ) );
			}

			return true;
		}

		/**
		 *
		 *
		 *
		 * @since 0.1.0
		 *
		 */
		static function validate_zone_domain_name( $domain_name ) {
			if ( isset( $domain_name ) && '' != $domain_name ) {
				return self::validate_domain_name( $domain_name );
			} else {
				return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( 'Domain Name field is required.', 'keypress-dns' ) );
			}

			return true;
		}


		/**
		 * @author Martín Di Felice
		 */
		static function validate_domain_name( $domain ) {
			if ( ! self::is_valid_domain( $domain ) ) {
				return new WP_Error( KPDNS_ERROR_CODE_GENERIC,  __( 'Invalid Domain Name.', 'keypress-dns' ) );
			}

			return true;
		}

		static function is_valid_domain( $domain ) {
		    return preg_match( "/^(?=^.{4,253}$)(^((?!-)[a-zA-Z0-9-]{1,63}(?<!-)\.)+[a-zA-Z]{2,63}\.?$)/", $domain );
        }

        static function is_quoted( $text ) {
            $first_char = substr( $text, 0, 1);
            $last_char  = substr( $text, strlen( $text ) -1, 1);
            if ( $first_char !== '"' || $last_char !== '"' ) {
                return false;
            }
            return true;
        }

		static function validate_wlns_name( $hostname ) {

			if ( isset( $hostname ) && '' != $hostname ) {

				//Record name must be 253 characters or less
				if ( strlen( $hostname ) > 253 ) {
					return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( 'NS name must be 253 characters or less.', 'keypress-dns' ) );
				}

				//Check labels length
				$labels = explode( ".", $hostname );
				if ( count( $labels ) > 0 ) {
					foreach ( $labels as $label ) {
						if ( strlen( $label ) > 63 ) {
							return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( 'Labels must be 63 characters or less.', 'keypress-dns' ) );
							break;
						}
					}
				}

				//Check format
				//TODO Make a better regex
				$regex = '/^(([a-z]|[a-z][a-z0-9\-]*)\.)+(([a-z]|[a-z][a-z0-9\-]*)\.)+([a-z0-9]*)$/i';
				if ( ! preg_match( $regex, $hostname ) ) {
					return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( 'Invalid hostname. A valid hostname is something like ns1.yourdomain.com.', 'keypress-dns' ) );
				}

			} else {
				return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( 'NS name cannot be empty.', 'keypress-dns' ) );
			}

			return true;
		}

		/**
		 *
		 *
		 *
		 * @since 0.1.0
		 *
		 */
		static function validate_record( array $record ) {

		    if ( ! isset( $record['type'] ) ) {
                return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( 'Missing required field "Type".', 'keypress-dns' ) );
            }

		    $record_types_config = self::get_record_types_config();

		    if ( ! isset( $record_types_config[ $record['type'] ] ) ) {
                return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( 'Invalid value for field "Type".', 'keypress-dns' ) );
            }

		    // Validate record name
            $is_valid = self::validate_record_name( $record['name'] );
            if ( is_wp_error( $is_valid ) ) {
                return $is_valid;
            }

            // Validate TTL
            $is_valid = self::validate_record_ttl( $record['ttl'] );
            if ( is_wp_error( $is_valid ) ) {
                return $is_valid;
            }

            // Validate TTL Unit
            if ( isset( $record['ttl-unit'] ) ) {
                $is_valid = self::validate_record_ttl_unit( $record['ttl-unit'] );
                if ( is_wp_error( $is_valid ) ) {
                    return $is_valid;
                }
            }

		    $config = $record_types_config[ $record['type'] ];

		    foreach( $record as $key => $value ) {
                if ( isset( $config['rdata-fields'][ $key ] ) ) {
                    if ( isset( $config['rdata-fields'][ $key ]['validation_callback'] ) ) {
                        $is_valid = call_user_func( $config['rdata-fields'][ $key ]['validation_callback'], $value );
                        if ( is_wp_error( $is_valid ) ) {
                            return $is_valid;
                        }
                    } else {
                        wp_die( sprintf( __( 'Missing validation callback for field %s.', 'keypress-dns' ), $key ) );
                    }
                }
            }

			$is_valid = true;

            /**
             * Filters the record validation.
             *
             * @since 1.1.1
             *
             * @param bool $is_valid Whether the record passes validation or not.
             * @param array $record Associative array with the record fields to validate.
             */
			$is_valid = apply_filters( 'kpdns_validate_record', $is_valid, $record );

			return $is_valid;
		}

		/**
		 *
		 *
		 *
		 * @since 0.1.0
		 *
		 */
		static function validate_record_name( $record_name ) {

            //Record name must be 253 characters or less
            if ( strlen( $record_name ) > 253 ) {
                return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( 'DNS name must be 253 characters or less.', 'keypress-dns' ) );
            }

            //Check labels length
            $labels = explode( ".", $record_name );
            if ( count( $labels ) > 0 ) {
                foreach ( $labels as $label ) {
                    if ( strlen( $label ) > 63 ) {
                        return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( 'Labels must be 63 characters or less.', 'keypress-dns' ) );
                        break;
                    }
                }
            }

            //Check format
            //TODO Make a better regex
            if ( ! empty( $record_name ) && ! preg_match( "/[a-z0-9-*.]/", $record_name ) ) {
                return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( 'Record names only contain lowercase letters, digits or dashes.', 'keypress-dns' ) );
            }

            //GCDNS
            if ( '.' != substr( $record_name, - 1 ) ) {
                $record_name .= '.';
            }

			return true;
		}

		/**
		 *
		 *
		 *
		 * @since 0.1.0
		 *
		 */
		static function validate_record_type( $type ) {

			if ( isset( $type ) && '' != $type ) {

				if ( ! array_key_exists( $type, self::get_record_types_config() ) ) {
					return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( 'Invalid record type.', 'keypress-dns' ) );
				}

			} else {
				return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( 'Record type is required.', 'keypress-dns' ) );
			}

			return true;
		}

		static function validate_ipv4( $ip ) {
            if ( '' == $ip || ! filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
                return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( 'Enter a valid IPv4 address.', 'keypress-dns' ) );
            }

            return true;
        }

        static function validate_ipv6( $ip ) {
            if ( '' == $ip || ! filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
                return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( 'Enter a valid IPv6 address.', 'keypress-dns' ) );
            }

            return true;
        }

        static function validate_mx_record_priority( $priority ) {
            if ( ! is_numeric( $priority ) || intval( $priority ) < 0 || intval( $priority ) > 65535 ) {
                return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( 'Invalid priority: Must be an unsigned integer between 0-65535.', 'keypress-dns' ) );
            }
            return true;
        }

        static function validate_mx_record_mail_server_host( $mail_server_host ) {
            if ( ! self::is_valid_domain( $mail_server_host ) ) {
                return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( 'Invalid mail server host name.', 'keypress-dns' ) );
            }
            return true;
        }

        static function validate_caa_record_flag( $flag ) {
            if ( ! is_numeric( $flag ) || intval( $flag ) < 0 || intval( $flag ) > 255 ) {
                return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( 'Invalid flag. Must be an unsigned integer between 0-255.', 'keypress-dns' ) );
            }
            return true;
        }

        static function validate_caa_record_tag( $tag ) {
            if ( ! in_array( $tag, ['issue', 'issuewild', 'iodef'] ) ) {
                return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( 'Invalid tag. Allowed values: issue, issuewild or iodef.', 'keypress-dns' ) );
            }
            return true;
        }

        static function validate_caa_record_value( $value ) {
            if ( empty( $value ) ) {
                return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( '"Value" field cannot be empty. Example: caa.example.com.', 'keypress-dns' ) );
            }
            return true;
        }

        static function validate_record_spf_value( $value ) {
            if ( empty( $value ) ) {
                return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( '"Value" field cannot be empty. Example: v=spf1 a mx ip4:69.64.153.131 include:_spf.google.com ~all.', 'keypress-dns' ) );
            }
            return true;
        }

        static function validate_srv_record_service( $service ) {
            if ( empty( $service ) ) {
                return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( '"Service" field cannot be empty.', 'keypress-dns' ) );
            }

            if ( substr( $service, 0, 1) !== '_' ) {
                return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( '"Service" field must start with an underscore. Example: _servicename.', 'keypress-dns' ) );
            }
            return true;
        }

        static function validate_srv_record_protocol( $protocol ) {
            if ( ! in_array( $protocol, ['TCP', 'UDP', 'TLS'] ) ) {
                return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( 'Invalid protocol. Allowed values: TCP, UDP or TLS.', 'keypress-dns' ) );
            }
            return true;
        }

        static function validate_srv_record_priority( $priority ) {
            if ( ! is_numeric( $priority ) || intval( $priority ) < 0 || intval( $priority ) > 255 ) {
                return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( 'Invalid priority. Must be an unsigned integer between 0-65535.', 'keypress-dns' ) );
            }
            return true;
        }

        static function validate_srv_record_weight( $weight ) {
            if ( ! is_numeric( $weight ) || intval( $weight ) < 0 || intval( $weight ) > 65535 ) {
                return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( 'Invalid weight. Must be an unsigned integer between 0-65535.', 'keypress-dns' ) );
            }
            return true;
        }

        static function validate_srv_record_port( $port ) {
            if ( ! is_numeric( $port ) || intval( $port ) < 0 || intval( $port ) > 65535 ) {
                return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( 'Invalid port. Must be an unsigned integer between 0-65535.', 'keypress-dns' ) );
            }
            return true;
        }

        static function validate_srv_record_host_name( $host_name ) {
            if ( ! self::is_valid_domain( $host_name ) ) {
                return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( 'Invalid host name.', 'keypress-dns' ) );
            }
        }

        static function validate_txt_record_value( $value ) {
            if ( '' == $value ) {
                return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( 'Enter a valid TXT value in "Value" field.', 'keypress-dns' ) );
            }

            if ( '' == $value || 255 < strlen( $value ) ) {
                return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( 'Error in "Value" field: TXT records cannot contain more than 255 characters.', 'keypress-dns' ) );
            }
            return true;
        }



        static function validate_soa_record_email( $value ) {
            if ( ! filter_var( $value, FILTER_VALIDATE_EMAIL ) ) {
                return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( 'Enter a valid "Email".', 'keypress-dns' ) );
            }
            return true;
        }

        static function validate_soa_record_serial_number( $value ) {
            if ( ! is_numeric( $value ) || intval( $value ) < 0 ) {
                return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( 'Invalid "Serial Number" value. Must be an unsigned integer between 0-65535.', 'keypress-dns' ) );
            }
            return true;
        }

        static function validate_soa_record_refresh( $value ) {
            if ( ! is_numeric( $value ) || intval( $value ) < 0 ) {
                return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( 'Invalid "Refresh" value. Must be an unsigned integer between 0-65535.', 'keypress-dns' ) );
            }
            return true;
        }

        static function validate_soa_record_retry( $value ) {
            if ( ! is_numeric( $value ) || intval( $value ) < 0 ) {
                return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( 'Invalid "Retry" value. Must be an unsigned integer between 0-65535.', 'keypress-dns' ) );
            }
            return true;
        }

        static function validate_soa_record_time_transfer( $value ) {
            if ( ! is_numeric( $value ) || intval( $value ) < 0 ) {
                return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( 'Invalid "Time Transfer" value. Must be an unsigned integer between 0-65535.', 'keypress-dns' ) );
            }
            return true;
        }


		/**
		 *
		 *
		 *
		 * @since 0.1.0
		 *
		 */
		static function validate_record_ttl( $ttl ) {

			if ( ! isset( $ttl ) || '' == $ttl ) {
				return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( 'TTL is required.', 'keypress-dns' ) );
			}

			if ( ! is_numeric( $ttl ) || ! ( preg_match( '/^\d+$/', $ttl ) || 0 >= intval( $ttl ) ) ) {
				return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( 'TTL must be a positive integer.', 'keypress-dns' ) );
			}

			return true;
		}

		/**
		 *
		 *
		 *
		 * @since 0.1.0
		 *
		 */
		static function validate_record_ttl_unit( $ttl_unit ) {

			if ( ! isset( $ttl_unit ) ) {
				return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( 'TTL unit is required.', 'keypress-dns' ) );
			}

			if ( '' === $ttl_unit || ! isset( self::get_ttl_units()[ $ttl_unit ] ) ) {
				return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( 'Invalid TTL unit.', 'keypress-dns' ) );
			}

			return true;
		}

		static function validate_name_server( $name_server, $ignore = null ) {
			if ( null === $ignore || ! in_array( self::NAME_SERVER_FIELD_NAME, $ignore ) ) {
				$is_valid = self::validate_name_server_name( $name_server['domain'] );
				if ( $is_valid instanceof  WP_Error ) return $is_valid;
			}

			return true;
		}

		static function validate_name_server_name( $name ) {
			if ( empty( $name ) ) {
				return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( 'Domain Name field is required.', 'keypress-dns' ) );
			} else {
				return self::validate_domain_name( $name );
			}

			return $errors;
		}

		static function sanitize_zone( $zone ) {
			$sanitized_zone = array();
			foreach ( $zone as $key => $value ) {
				$sanitized_zone[ $key ] = trim( sanitize_text_field( $value ) );
			}
			return $sanitized_zone;
		}

		static function sanitize_record( $record ) {

		    $record_types = self::get_record_types_config();

            $rdata_fields_config = $record_types[ $record['type'] ]['rdata-fields'];

            foreach ( $record as $key => $value ) {
                if ( isset( $rdata_fields_config[ $key ] ) ) {
                    if ( isset( $rdata_fields_config[ $key ]['type'] ) ) {
                        $record[ $key ] = self::sanitize_deep( $value, $rdata_fields_config[ $key ]['type'] );
                    }
                } else {
                    $record[ $key ] = self::sanitize_deep( $value, 'text' );
                }
            }

            return $record;
		}

		static function sanitize_deep( $value, $field_type ) {
            if ( is_array( $value ) ) {
                $sanitized_value = array();
                foreach ( $value as $k => $v ) {
                    $sanitized_value[ $k ] = self::sanitize_field( $v, $field_type );
                }
                return $sanitized_value;
            } else {
                return self::sanitize_field(  $value, $field_type );
            }
        }

		static function sanitize_field( $value, $field_type ) {
		    switch ( $field_type ) {
                case 'text':
                case 'password':
                    $value = stripslashes( trim( sanitize_text_field( $value ) ) );
                    break;

                case 'textarea':
                    $value = stripslashes( trim( sanitize_textarea_field( $value ) ) );
                    break;
            }

            return $value;
        }

		static function sanitize_name_server( $name_server ) {
			$sanitized_name_server = array();
			foreach ( $name_server as $key => $value ) {
			    if ( is_array( $value) ) {
			        foreach ( $value as $i => $val ) {
                        $sanitized_name_server[ $key ][ $i ] = trim( sanitize_text_field( $val ) );
                    }
                } else {
                    $sanitized_name_server[ $key ] = trim( sanitize_text_field( $value ) );
                }

			}
			return $sanitized_name_server;
		}

		static function get_formatted_record_name( $name, $domain ) {
            if ( empty( $name ) ) {
                $name .= $domain;
            } else {
                $name .= '.' . $domain;
            }
            return $name;
        }

		/**
		 * @author Martín Di Felice
		 */
		static function ttl_to_seconds( $ttl, $ttl_unit ) {

			switch ( $ttl_unit ) {
				case 'S':
					return $ttl;

				case 'M':
					return $ttl * 60;

				case 'H':
					return $ttl = 3600;

				case 'D':
					return $ttl * 86400;

				case 'W':
					return $ttl * 604800;
			}

            return $ttl;

		}

		static function get_record_types_config() {
			$record_types = array(
				KPDNS_Record::TYPE_A => array(
				    'description' => __( 'An A record maps a domain name to the IP address (Version 4) of the computer hosting the domain.', 'keypress-dns' ),
				    'rdata-fields' => array(
                        KPDNS_Record::RDATA_KEY_VALUE => array(
                            'id'                   => KPDNS_Record::RDATA_KEY_VALUE,
                            'type'                 => 'text',
                            'placeholder'          => __( '', 'keypress-dns' ),
                            'class'                => 'regular-text',
                            'label'                => __( 'Value', 'keypress-dns' ),
                            'description'          => __( 'IPv4 address in dotted decimal notation. Example: 192.168.1.10', 'keypress-dns' ),
                            'validation_callback'  => __CLASS__ . '::validate_ipv4',
                        ),
                    ),
                ),
				KPDNS_Record::TYPE_AAAA => array(
				    'description' => __( 'An AAAA record maps a domain name to the IP address (Version 6) of the computer hosting the domain.', 'keypress-dns' ),
                    'rdata-fields' => array(
                        KPDNS_Record::RDATA_KEY_VALUE => array(
                            'id'          => KPDNS_Record::RDATA_KEY_VALUE,
                            'type'        => 'text',
                            'placeholder' => __( '', 'keypress-dns' ),
                            'class'       => 'regular-text',
                            'label'       => __( 'Value', 'keypress-dns' ),
                            'description' => __( 'IPv6 address in colon-separated hexadecimal format. Example: 2400:cb00:2049:1::a29f:1804', 'keypress-dns' ),
                            'validation_callback'  => __CLASS__ . '::validate_ipv6',
                        ),
                    ),
                ),
				KPDNS_Record::TYPE_CAA => array(
                    'description' => __( 'A CAA record specifies which certificate authorities (CAs) are allowed to issue certificates for a domain or subdomain. Creating a CAA record helps to prevent the wrong CAs from issuing certificates for your domains. A CAA record isn\'t a substitute for the security requirements that are specified by your certificate authority, such as the requirement to validate that you\'re the owner of a domain. Example of CAA record: 0 issue "ca.example.net; account=123456"', 'keypress-dns' ),
                    'rdata-fields' => array(
                        KPDNS_Record::RDATA_KEY_FLAG => array(
                            'id'          => KPDNS_Record::RDATA_KEY_FLAG,
                            'type'        => 'text',
                            'class'       => 'regular-text',
                            'placeholder' => __( '', 'keypress-dns' ),
                            'label'       => __( 'Flag', 'keypress-dns' ),
                            'description' => __( 'An unsigned integer between 0-255.', 'keypress-dns' ),
                            'validation_callback'  => __CLASS__ . '::validate_caa_record_flag',
                        ),
                        KPDNS_Record::RDATA_KEY_TAG => array(
                            'id'          => KPDNS_Record::RDATA_KEY_TAG,
                            'type'        => 'select',
                            'options'     => array(
                                'issue' => array(
                                    'value' => 'issue',
                                    'text'  => 'issue',
                                ),
                                'issuewild' => array(
                                    'value' => 'issuewild',
                                    'text'  => 'issuewild',
                                ),
                                'iodef' => array(
                                    'value' => 'iodef',
                                    'text'  => 'iodef',
                                ),
                            ),
                            'placeholder' => __( 'Ex. issue', 'keypress-dns' ),
                            'label'       => __( 'Tag', 'keypress-dns' ),
                            'description' => __( 'The value of tag can contain only the characters A-Z, a-z, and 0-9.', 'keypress-dns' ),
                            'validation_callback'  => __CLASS__ . '::validate_caa_record_tag',
                        ),
                        KPDNS_Record::RDATA_KEY_VALUE => array(
                            'id'          => KPDNS_Record::RDATA_KEY_VALUE,
                            'type'        => 'text',
                            'placeholder' => __( '', 'keypress-dns' ),
                            'class'       => 'regular-text',
                            'label'       => __( 'Value', 'keypress-dns' ),
                            'description' => __( 'Specify the certificate authority that can issue certificates for this domain. Example: caa.example.com.', 'keypress-dns' ),
                            'validation_callback'  => __CLASS__ . '::validate_caa_record_value',
                        ),
                    ),
                ),
				KPDNS_Record::TYPE_CNAME => array(
                    'description' => __( 'CNAME records can be used to alias one name to another. CNAME stands for Canonical Name.', 'keypress-dns' ),
                    'rdata-fields' => array(
                        KPDNS_Record::RDATA_KEY_VALUE => array(
                            'id'          => KPDNS_Record::RDATA_KEY_VALUE,
                            'type'        => 'text',
                            'placeholder' => __( '', 'keypress-dns' ),
                            'class'       => 'regular-text',
                            'label'       => __( 'Value', 'keypress-dns' ),
                            'description' => __( 'The domain name that you want to resolve to instead of the value in the Name field. Example: www.example.com.', 'keypress-dns' ),
                            'validation_callback'  => __CLASS__ . '::validate_domain_name',
                        ),
                    ),
                ),
				KPDNS_Record::TYPE_MX => array(
                    'description' => __( 'An MX record specifies the names of your mail servers and, if you have two or more mail servers, the priority order.', 'keypress-dns' ),
                    'rdata-fields' => array(
                        KPDNS_Record::RDATA_KEY_PRIORITY => array(
                            'id'          => KPDNS_Record::RDATA_KEY_PRIORITY,
                            'type'        => 'text',
                            'placeholder' => __( '', 'keypress-dns' ),
                            'class'       => 'regular-text',
                            'label'       => __( 'Priority', 'keypress-dns' ),
                            'description' => __( 'An unsigned integer that represents the priority for a mail server. It can be any integer between 0 and 65535.', 'keypress-dns' ),
                            'validation_callback' => __CLASS__ . '::validate_mx_record_priority',
                        ),
                        KPDNS_Record::RDATA_KEY_MAIL_SERVER => array(
                            'id'          => KPDNS_Record::RDATA_KEY_MAIL_SERVER,
                            'type'        => 'text',
                            'placeholder' => __( '', 'keypress-dns' ),
                            'class'       => 'regular-text',
                            'label'       => __( 'Mail Server', 'keypress-dns' ),
                            'description' => __( 'Mail server host name. Example: mailserver.example.com.', 'keypress-dns' ),
                            'validation_callback' => __CLASS__ . '::validate_mx_record_mail_server_host',
                        ),

                    ),
                ),
				KPDNS_Record::TYPE_NS => array(
                    'description' => __( 'NS stands for ‘name server’ and this record indicates which DNS server is authoritative for that domain (which server contains the actual DNS records). A domain will often have multiple NS records.', 'keypress-dns' ),
                    'rdata-fields' => array(
                        KPDNS_Record::RDATA_KEY_VALUE => array(
                            'id'          => KPDNS_Record::RDATA_KEY_VALUE,
                            'type'        => 'text',
                            'placeholder' => __( '', 'keypress-dns' ),
                            'class'       => 'regular-text',
                            'label'       => __( 'Value', 'keypress-dns' ),
                            'description' => __( 'The domain name of a name server. Example: ns1.example.com.', 'keypress-dns' ),
                            'validation_callback'  => __CLASS__ . '::validate_domain_name',
                        ),
                    ),
                ),
				KPDNS_Record::TYPE_PTR => array(
                    'description' => __( 'A PTR record maps an IP address to the corresponding domain name or subdomain.', 'keypress-dns' ),
                    'rdata-fields' => array(
                        KPDNS_Record::RDATA_KEY_VALUE => array(
                            'id'          => KPDNS_Record::RDATA_KEY_VALUE,
                            'type'        => 'text',
                            'placeholder' => __( '', 'keypress-dns' ),
                            'class'       => 'regular-text',
                            'label'       => __( 'Value', 'keypress-dns' ),
                            'description' => __( 'The domain name that you want to return. Example: www.example.com.', 'keypress-dns' ),
                            'validation_callback'  => __CLASS__ . '::validate_domain_name',
                        ),
                    ),
                ),
				KPDNS_Record::TYPE_SOA=> array(
                    'description' => __( 'The start of authority (SOA) record identifies the base DNS information about the domain.', 'keypress-dns' ),
                    'rdata-fields' => array(
                        KPDNS_Record::RDATA_KEY_EMAIL => array(
                            'id'          => KPDNS_Record::RDATA_KEY_EMAIL,
                            'type'        => 'text',
                            'placeholder' => __( '', 'keypress-dns' ),
                            'class'       => 'regular-text',
                            'label'       => __( 'Admin Email', 'keypress-dns' ),
                            'description' => __( 'The email address of the administrator. The @ symbol is replaced by a period, for example, hostmaster.example.com.', 'keypress-dns' ),
                            'validation_callback'  => __CLASS__ . '::validate_soa_record_email',
                        ),
                        KPDNS_Record::RDATA_KEY_SERIAL_NUMBER => array(
                            'id'          => KPDNS_Record::RDATA_KEY_SERIAL_NUMBER,
                            'type'        => 'text',
                            'placeholder' => __( '', 'keypress-dns' ),
                            'class'       => 'regular-text',
                            'label'       => __( 'Serial Number', 'keypress-dns' ),
                            'description' => __( 'A serial number that you can optionally increment whenever you update a record in the hosted zone, for example, 1.', 'keypress-dns' ),
                            'validation_callback'  => __CLASS__ . '::validate_soa_record_serial_number',
                        ),
                        KPDNS_Record::RDATA_KEY_REFRESH => array(
                            'id'          => KPDNS_Record::RDATA_KEY_REFRESH,
                            'type'        => 'text',
                            'placeholder' => __( '', 'keypress-dns' ),
                            'class'       => 'regular-text',
                            'label'       => __( 'Refresh Time', 'keypress-dns' ),
                            'description' => __( 'A refresh time in seconds that secondary DNS servers wait before querying the primary DNS server\'s SOA record to check for changes. For example, 7200 (seconds).', 'keypress-dns' ),
                            'validation_callback'  => __CLASS__ . '::validate_soa_record_refresh',
                        ),
                        KPDNS_Record::RDATA_KEY_RETRY => array(
                            'id'          => KPDNS_Record::RDATA_KEY_RETRY,
                            'type'        => 'text',
                            'placeholder' => __( '', 'keypress-dns' ),
                            'class'       => 'regular-text',
                            'label'       => __( 'Retry Interval', 'keypress-dns' ),
                            'description' => __( 'The retry interval in seconds that a secondary server waits before retrying a failed zone transfer. Normally, the retry time is less than the refresh time. For example, 900 (seconds).', 'keypress-dns' ),
                            'validation_callback'  => __CLASS__ . '::validate_soa_record_retry',
                        ),
                        KPDNS_Record::RDATA_KEY_TIME_TRANSFER => array(
                            'id'          => KPDNS_Record::RDATA_KEY_TIME_TRANSFER,
                            'type'        => 'text',
                            'placeholder' => __( '', 'keypress-dns' ),
                            'class'       => 'regular-text',
                            'label'       => __( 'Time To Transfer', 'keypress-dns' ),
                            'description' => __( 'The time in seconds that a secondary server will keep trying to complete a zone transfer. If this time elapses before a successful zone transfer, the secondary server will stop answering queries because it considers its data too old to be reliable. For example 1209600 (seconds) (two weeks). ', 'keypress-dns' ),
                            'validation_callback'  => __CLASS__ . '::validate_soa_record_time_transfer',
                        ),
                    ),
                ),
				KPDNS_Record::TYPE_SPF => array(
                    'description' => __( 'An SPF record is a Sender Policy Framework record. It’s used to indicate to mail exchanges which hosts are authorized to send mail for a domain.', 'keypress-dns' ),
                    'rdata-fields' => array(
                        KPDNS_Record::RDATA_KEY_VALUE => array(
                            'id'          => KPDNS_Record::RDATA_KEY_VALUE,
                            'type'        => 'text',
                            'placeholder' => __( '', 'keypress-dns' ),
                            'class'       => 'regular-text',
                            'label'       => __( 'Value', 'keypress-dns' ),
                            'description' => __( 'SPF records are defined as a single string of text. Enclose values in quotation marks. Example: "v=spf1 a mx ip4:69.64.153.131 include:_spf.google.com ~all".', 'keypress-dns' ),
                            'validation_callback'  => __CLASS__ . '::validate_record_spf_value',
                        ),
                    ),
                ),
				KPDNS_Record::TYPE_SRV => array(
                    'description' => __( 'A Service record (SRV record) is a specification of data in the Domain Name System defining the location, i.e., the hostname and port number, of servers for specified services.', 'keypress-dns' ),
                    'rdata-fields' => array(
                        KPDNS_Record::RDATA_KEY_SERVICE => array(
                            'id'          => KPDNS_Record::RDATA_KEY_SERVICE,
                            'type'        => 'text',
                            'placeholder' => __( '', 'keypress-dns' ),
                            'class'       => 'regular-text',
                            'label'       => __( 'Service', 'keypress-dns' ),
                            'description' => __( 'Service name. Must start with an underscore. Example: _servicename', 'keypress-dns' ),
                            'validation_callback'  => __CLASS__ . '::validate_srv_record_service',
                        ),
                        KPDNS_Record::RDATA_KEY_PROTOCOL => array(
                            'id'          => KPDNS_Record::RDATA_KEY_PROTOCOL,
                            'type'        => 'select',
                            'options'     => array(
                                'TCP' => array(
                                    'value' => 'TCP',
                                    'text'  => 'TCP',
                                ),
                                'UDP' => array(
                                    'value' => 'UDP',
                                    'text'  => 'UDP',
                                ),
                                'TLS' => array(
                                    'value' => 'TLS',
                                    'text'  => 'TLS',
                                ),
                            ),
                            'placeholder' => __( '', 'keypress-dns' ),
                            'label'       => __( 'Protocol', 'keypress-dns' ),
                            'validation_callback'  => __CLASS__ . '::validate_srv_record_protocol',
                        ),
                        KPDNS_Record::RDATA_KEY_PRIORITY => array(
                            'id'          => KPDNS_Record::RDATA_KEY_PRIORITY,
                            'type'        => 'text',
                            'placeholder' => __( '', 'keypress-dns' ),
                            'class'       => 'regular-text',
                            'label'       => __( 'Priority', 'keypress-dns' ),
                            'description' => __( 'An unsigned integer between 0-65535.', 'keypress-dns' ),
                            'validation_callback'  => __CLASS__ . '::validate_srv_record_priority',
                        ),
                        KPDNS_Record::RDATA_KEY_WEIGHT => array(
                            'id'          => KPDNS_Record::RDATA_KEY_WEIGHT,
                            'type'        => 'text',
                            'placeholder' => __( '', 'keypress-dns' ),
                            'class'       => 'regular-text',
                            'label'       => __( 'Weight', 'keypress-dns' ),
                            'description' => __( 'An unsigned integer between 0-65535.', 'keypress-dns' ),
                            'validation_callback'  => __CLASS__ . '::validate_srv_record_weight',
                        ),
                        KPDNS_Record::RDATA_KEY_PORT => array(
                            'id'          => KPDNS_Record::RDATA_KEY_PORT,
                            'type'        => 'text',
                            'placeholder' => __( '', 'keypress-dns' ),
                            'class'       => 'regular-text',
                            'label'       => __( 'Port', 'keypress-dns' ),
                            'description' => __( 'An unsigned integer between 0-65535.', 'keypress-dns' ),
                            'validation_callback'  => __CLASS__ . '::validate_srv_record_port',
                        ),
                        KPDNS_Record::RDATA_KEY_HOST => array(
                            'id'          => KPDNS_Record::RDATA_KEY_HOST,
                            'type'        => 'text',
                            'placeholder' => __( '', 'keypress-dns' ),
                            'class'       => 'regular-text',
                            'label'       => __( 'Host Name', 'keypress-dns' ),
                            'description' => __( 'Enter a host name. Example: xmpp-server.example.com.', 'keypress-dns' ),
                            'validation_callback'  => __CLASS__ . '::validate_srv_record_host_name',
                        ),
                    ),
                ),
				KPDNS_Record::TYPE_TXT => array(
                    'description' => __( 'TXT records are used to provide the ability to associate arbitrary text with a host or other name, such as human readable information about a server, network, data center, or other accounting information. Example: "v=spf1 ip4:192.168.0.1/16 -all"', 'keypress-dns' ),
                    'rdata-fields' => array(
                        KPDNS_Record::RDATA_KEY_VALUE => array(
                            'id'          => KPDNS_Record::RDATA_KEY_VALUE,
                            'type'        => 'text',
                            'maxlength'   => '255',
                            'placeholder' => __( '', 'keypress-dns' ),
                            'class'       => 'regular-text',
                            'label'       => __( 'Value', 'keypress-dns' ),
                            'description' => __( 'Enclose text in quotation marks. Example: "v=spf1 ip4:192.168.0.1/16 -all".', 'keypress-dns' ),
                            'validation_callback'  => __CLASS__ . '::validate_txt_record_value',
                        ),
                    ),
                ),
                /*
                KPDNS_Record::TYPE_LOC => array(
                    'description' => __( 'A LOC record (experimental <a href="https://tools.ietf.org/html/rfc1876" target="_blank">RFC 1876</a>) is a means for expressing geographic location information for a domain name.', 'keypress-dns' ),
                    'fields' => array(
                        'name' => 'name' => self::get_name_field_config(),
                        'size' => array(
                            'id'          => 'size',
                            'type'        => 'text',
                            'placeholder' => __( '', 'keypress-dns' ),
                            'class'       => 'small',
                            'maxlength'   => '8',
                            'label'       => __( 'Size', 'keypress-dns' ),
                            'description' => __( 'Size of location in meters. unsigned integer between 0 and 90000000.', 'keypress-dns' ),
                        ),
                        'altitude' => array(
                            'id'          => 'altitude',
                            'type'        => 'text',
                            'placeholder' => __( '', 'keypress-dns' ),
                            'class'       => 'small',
                            'maxlength'   => '11',
                            'label'       => __( 'Altitude', 'keypress-dns' ),
                            'description' => __( 'Altitude of location in meters. Float between -100000 and 42849672.95.', 'keypress-dns' ),
                        ),
                        'long_degrees' => array(
                            'id'          => 'long_degrees',
                            'type'        => 'text',
                            'placeholder' => __( '', 'keypress-dns' ),
                            'class'       => 'small',
                            'maxlength'   => '3',
                            'label'       => __( 'Longitude Degrees', 'keypress-dns' ),
                            'description' => __( 'Degrees of longitude. Unsigned integer between 0 and 180.', 'keypress-dns' ),
                        ),
                        'lat_degrees' => array(
                            'id'          => 'lat_degrees',
                            'type'        => 'text',
                            'placeholder' => __( '', 'keypress-dns' ),
                            'class'       => 'small',
                            'maxlength'   => '2',
                            'label'       => __( 'Latitude Degrees', 'keypress-dns' ),
                            'description' => __( 'Degrees of latitude. Unsigned integer between 0 and 90.', 'keypress-dns' ),
                        ),
                        'precision_horz' => array(
                            'id'          => 'precision_horz',
                            'type'        => 'text',
                            'placeholder' => __( '', 'keypress-dns' ),
                            'class'       => 'small',
                            'maxlength'   => '8',
                            'label'       => __( 'Horizontal Precision', 'keypress-dns' ),
                            'description' => __( 'Horizontal precision of location. Unsigned integer between 0 and 90000000.', 'keypress-dns' ),
                        ),
                        'precision_vert' => array(
                            'id'          => 'precision_vert',
                            'type'        => 'text',
                            'placeholder' => __( '', 'keypress-dns' ),
                            'class'       => 'small',
                            'maxlength'   => '8',
                            'label'       => __( 'Vertical Precision', 'keypress-dns' ),
                            'description' => __( 'Vertical precision of location. Unsigned integer between 0 and 90000000.', 'keypress-dns' ),
                        ),
                        'long_direction' => array(
                            'id'          => 'long_direction',
                            'type'        => 'select',
                            'placeholder' => __( '', 'keypress-dns' ),
                            'class'       => 'small',
                            'options'     => array(
                                'W' => array(
                                    'value' => 'W',
                                    'text'  => 'W',
                                ),
                                'E' => array(
                                    'value' => 'E',
                                    'text'  => 'E',
                                ),
                            ),
                            'label'       => __( 'Longitude Direction', 'keypress-dns' ),
                        ),
                        'long_minutes' => array(
                            'id'          => 'long_minutes',
                            'type'        => 'text',
                            'placeholder' => __( '', 'keypress-dns' ),
                            'class'       => 'small',
                            'maxlength'   => '2',
                            'label'       => __( 'Longitude Minutes', 'keypress-dns' ),
                            'description' => __( 'Minutes of longitude. Integer between 0 and 59.', 'keypress-dns' ),
                        ),
                        'long_seconds' => array(
                            'id'          => 'long_seconds',
                            'type'        => 'text',
                            'placeholder' => __( '', 'keypress-dns' ),
                            'class'       => 'small',
                            'maxlength'   => '6',
                            'label'       => __( 'Longitude Seconds', 'keypress-dns' ),
                            'description' => __( 'Seconds of longitude. Float between 0 and 59.999.', 'keypress-dns' ),
                        ),
                        'lat_direction' => array(
                            'id'          => 'lat_direction',
                            'type'        => 'select',
                            'placeholder' => __( '', 'keypress-dns' ),
                            'class'       => 'small',
                            'options'     => array(
                                'N' => array(
                                    'value' => 'N',
                                    'text'  => 'N',
                                ),
                                'S' => array(
                                    'value' => 'ES',
                                    'text'  => 'S',
                                ),
                            ),
                            'label'       => __( 'Latitude Direction', 'keypress-dns' ),
                        ),
                        'lat_minutes' => array(
                            'id'          => 'lat_minutes',
                            'type'        => 'text',
                            'placeholder' => __( '', 'keypress-dns' ),
                            'class'       => 'small',
                            'maxlength'   => '2',
                            'label'       => __( 'Latitude Minutes', 'keypress-dns' ),
                            'description' => __( 'Minutes of latitude. Integer between 0 and 59.', 'keypress-dns' ),
                        ),
                        'lat_seconds' => array(
                            'id'          => 'lat_seconds',
                            'type'        => 'text',
                            'placeholder' => __( '', 'keypress-dns' ),
                            'class'       => 'small',
                            'maxlength'   => '6',
                            'label'       => __( 'Latitude Seconds', 'keypress-dns' ),
                            'description' => __( 'Seconds of latitude. Float between 0 and 59.999.', 'keypress-dns' ),
                        ),
                        'ttl' => self::get_ttl_field_config(),
                        'ttl-unit' => self::get_ttl_units_field_config(),
                    ),
                ),
                */
			);

            /**
             * Filters the record types.
             *
             * @since 1.1
             *
             * @param array $record_types The record types array.
             */
            $record_types = apply_filters( 'kpdns_record_types', $record_types );

            ksort( $record_types );

			return $record_types;
		}

        static function get_record_type_field_config() {
            $config = array(
                'id'          => 'type',
                'type'        => 'select',
                'options'     => self::_get_record_type_options_config(),
                'label'       => __( 'Type', 'keypress-dns' ),
                'description' => __( 'Enter the host name or leave empty.', 'keypress-dns' ),
            );

            /**
             * Filters the record type field config.
             *
             * @since 1.1
             *
             * @param array $config The record type config array.
             */
            $config = apply_filters( 'kpdns_record_type_field_config', $config );

            return $config;
        }

        private static function _get_record_type_options_config() {
		    $records_types = array_keys( self::get_record_types_config() );
		    $options = array(
		        '-1' => array(
                    'value' => '-1',
                    'text'  => '---',
                ),
            );
		    foreach ($records_types as $type ) {
                $options[ $type ] = array(
                    'value' => $type,
                    'text'  => $type,
                );
            }
		    return $options;
        }

        static function get_record_name_field_config() {
            $config = array(
                'id'          => 'name',
                'type'        => 'text',
                'placeholder' => __( '', 'keypress-dns' ),
                'class'       => 'regular-text',
                'label'       => __( 'Name', 'keypress-dns' ),
                'description' => __( 'Enter the host name or leave empty.', 'keypress-dns' ),
            );

            /**
             * Filters the record name field config.
             *
             * @since 1.1
             *
             * @param array $config The record name config array.
             */
            $config = apply_filters( 'kpdns_record_name_field_config', $config );

            return $config;
        }

        static function get_record_ttl_field_config() {
            $config = array(
                'id'                   => 'ttl',
                'type'                 => 'text',
                'placeholder'          => __( '', 'keypress-dns' ),
                'class'                => 'regular-text',
                'validation_callback'  => __CLASS__ . '::validate_record_ttl',
                'label'       => __( 'TTL', 'keypress-dns' ),
                'description' => __( 'Time To Live.', 'keypress-dns' ),
            );

            /**
             * Filters the record TTL field config.
             *
             * @since 1.1
             *
             * @param array $config The record TTL config array.
             */
            $config = apply_filters( 'kpdns_record_ttl_field_config', $config );

            return $config;
        }

        static function get_record_ttl_units_field_config() {
            $config = array(
                'id'          => 'ttl-unit',
                'name'        => 'record[ttl-unit]',
                'label'       => 'TTL Unit',
                'description' => '',
                'type'        => 'select',
                'options'     => array(
                    'S' => array(
                        'value' => 'S',
                        'text'  => __( 'seconds', 'keypress-dns' ),
                    ),
                    'M' => array(
                        'value' => 'M',
                        'text'  => __( 'minutes', 'keypress-dns' ),
                    ),
                    'H' => array(
                        'value' => 'H',
                        'text'  => __( 'hours', 'keypress-dns' ),
                    ),
                    'D' => array(
                        'value' => 'D',
                        'text'  => __( 'days', 'keypress-dns' ),
                    ),
                    'W' => array(
                        'value' => 'W',
                        'text'  => __( 'weeks', 'keypress-dns' ),
                    ),
                ),
                'class' => 'kpdns-ttl-unit-select',
            );

            /**
             * Filters the record TTL Unit field config.
             *
             * @since 1.1
             *
             * @param array $config The record TTL unit config array.
             */
            $config = apply_filters( 'kpdns_record_ttl_unit_field_config', $config );

            return $config;
        }

		static function get_ttl_units() {
			return array(
				'S' => __( 'seconds', 'keypress-dns' ),
				'M' => __( 'minutes', 'keypress-dns' ),
				'H' => __( 'hours', 'keypress-dns' ),
				'D' => __( 'days', 'keypress-dns' ),
				'W' => __( 'weeks', 'keypress-dns' )
			);
		}

        public static function maybe_add_quotation_marks( $value ) {

            $value = stripslashes( $value );

            if ( substr( $value, 0, 1 ) !== '"') {
                $value = '"' . $value;
            }

            if ( $value[ strlen( $value ) -1 ] !== '"') {
                $value .= '"';
            }
            return $value;
        }

        public static function replace_shortcodes( $text, $shortcodes ) {
            foreach ( $shortcodes as $shortcode => $value ) {
                $text = str_replace( $shortcode, $value, $text );
            }
            return $text;
        }

        public static function get_site_domain() {
            $domain = str_replace('http://', '', is_multisite() ? network_home_url() : home_url() );
            $domain = str_replace('https://', '', $domain);
            $domain = trim($domain, '/');
            return $domain;
        }

        public static function get_text( $key, $texts ) {
		    if ( isset( $texts[ $key ] ) ) {
		        return $texts[ $key ];
            }
		    return '';
        }

        public static function ajax_pull_records() {

            $error_msg   = __( 'Error!', 'keypress-dns' );
            $success_msg = __( 'Done!', 'keypress-dns' );

            if ( ! current_user_can( 'manage_network_options' ) ||
                ! isset( $_POST['action'] ) ||
                ! isset( $_POST['nonce'] ) ||
                ! isset( $_POST['domain'] ) ||
                ! wp_verify_nonce( $_POST['nonce'], KPDNS_ACTION_AJAX_PULL_RECORDS ) )
            {
                wp_die( $error_msg );
            }

            $domain = $_POST['domain'];

            $result = dns_get_record( $domain, DNS_ALL );

            wp_die( $result );
        }

        /**
         * Gets the number of items per page.
         *
         * @since 1.3
         *
         * @return int Items per page.
         */
        public static function get_items_per_page() {
            /**
             * Filters the number of items per page.
             *
             * @since 1.3
             *
             * @param int $items_per_page The number of items per page. Default 10.
             */
		    return apply_filters( 'kpdns_items_per_page', 10 );
        }

        /**
         * Gets the search terms.
         *
         * @since 1.3
         *
         * @param array $args
         *
         * @return bool|string
         */
        public static function get_search_terms( array $args = array() ) {
            if ( ! isset( $args ) || empty( $args ) || ! isset( $args['search'] ) ) {
                return false;
            }

            /**
             * Filters the search terms.
             *
             * @since 1.3
             *
             * @param string $search_terms The search terms.
             */
            $search_terms = apply_filters( 'kpdns_search_terms', trim( $args['search'] ) );

            return sanitize_text_field( $search_terms );
        }

        /**
         * Gets the current page.
         *
         * @since 1.3
         *
         * @param array $args
         *
         * @return bool|int
         */
        public static function get_current_page( array $args = array() ) {
            $current_page = isset( $args['page'] ) ? $args['page'] : 1;

            /**
             * Filters the current page.
             *
             * @since 1.3
             *
             * @param int $items_per_page The number of items per page. Default 10.
             */
            $current_page = apply_filters( 'kpdns_current_page', $current_page );

            return intval( sanitize_text_field( $current_page ) );
        }

	}
}