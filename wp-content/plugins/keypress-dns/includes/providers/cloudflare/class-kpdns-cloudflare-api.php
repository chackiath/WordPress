<?php

class KPDNS_Cloudflare_API extends KPDNS_API implements KPDNS_API_Imp {

	const RECORD_TYPE_LOC    = 'LOC';
    const RECORD_TYPE_CERT   = 'CERT';
    const RECORD_TYPE_DNSKEY = 'DNSKEY';
    const RECORD_TYPE_DS     = 'DS';
    const RECORD_TYPE_NAPTR  = 'NAPTR';
    const RECORD_TYPE_SMIMEA = 'SMIMEA';
    const RECORD_TYPE_SSHFP  = 'SSHFP';
    const RECORD_TYPE_TLSA   = 'TLSA';
    const RECORD_TYPE_URI    = 'URI';
	
	/* 
	* API endpoint, can be created a constant. 
	*/
	private $endpoint = 'https://api.cloudflare.com/client/v4';
	
	private $cf_api_key;
	private $cf_email;

	public function __construct( KPDNS_Credentials $credentials ) {
		$this->cf_api_key = KPDNS_Crypto::decrypt( $credentials->get_api_key(), hex2bin( KPDNS_ENCRYPTION_KEY ) );
		$this->cf_email = KPDNS_Crypto::decrypt( $credentials->get_email(), hex2bin( KPDNS_ENCRYPTION_KEY ) );
	}

    /**
     * Adds a new zone.
     *
     * @param KPDNS_Zone $zone
     * @param array $args
     * @return string zone id|WP_Error
     */
	public function add_zone( KPDNS_Zone $zone, array $args ) {
		if ( ! empty( $zone->get_domain() ) ) {
			$endpoint_url = $this->endpoint . '/zones';
			$details = array(
						'name'       => $zone->get_domain(),
						'jump_start' => true,
						'type'       => "full"
					  );
			$response = wp_remote_post( $endpoint_url, array(
												'body'   	  => json_encode($details),
												'data_format' => 'body',
												'headers'	  => array(
																'content-type' => 'application/json',
																'x-auth-email' => $this->cf_email,
																'x-auth-key'   => $this->cf_api_key,
															),
												)
						);

			/* If not WP error. */
			if ( ! is_wp_error( $response ) ) {
				$api_data = json_decode( $response['body'] );
				if ( isset( $api_data->result ) && ! empty( $api_data->result ) ) {
				    return $this->_parse_zone( $api_data->result );
					//return $api_data->result->id;
				} else {
					return new WP_Error( $api_data->errors[0]->code, $api_data->errors[0]->message );
				}
			}
			return new WP_Error( KPDNS_ERROR_CODE_GENERIC, KPDNS_DEFAULT_ERROR_MESSAGE );
		}
		return new WP_Error( KPDNS_ERROR_CODE_GENERIC, "Please enter domain name." );
	}

    /**
     * Deletes a DNS zone.
     *
     * @param string $zone_id
     * @return bool|WP_Error
     */
	public function delete_zone( string $zone_id, array $args ) {
		if ( ! empty( $zone_id ) ) {
			$endpoint_url = '/zones/' . $zone_id;

			/* Blank array just to use common post method. */
			$data = array();
			$api_data = $this->post_request($endpoint_url, $data, "DELETE");
			if ( isset( $api_data->success ) && $api_data->success && isset( $api_data->result ) && ! empty( $api_data->result ) ) {
				return true;
			}
			return false;
		}
		return new WP_Error( KPDNS_ERROR_CODE_GENERIC, "Please select zone to delete." );
	}

	public function delete_zones( array $zone_ids, array $args = array() ) {
        // TODO Improve
	    foreach( $zone_ids as $id ) {
            $result = $this->delete_zone( $id, $args );
            if ( is_wp_error( $result ) ) {
                return $result;
            }
        }
	    return true;
    }

    public function add_records( KPDNS_Records_List $records, string $zone_id, array $args = array() ) {
        // TODO Improve
        foreach( $records as $record ) {
            $result = $this->add_record( $record, $args );
            if ( is_wp_error( $result ) ) {
                return $result;
            }
        }
        return true;
    }

    /**
     * Edits a DNS zone.
     *
     * @param KPDNS_Zone $zone
     * @return bool|WP_Error
     */
	public function edit_zone( KPDNS_Zone $zone, array $args ) {
		try {
            // Return true if there are no errors.
			return true;
        } catch ( \Cloudflare\API\Adapter\ResponseException $e ) {
            return new WP_Error( KPDNS_ERROR_CODE_GENERIC, $e->getMessage() );
        }
	}

    /**
     * Gets a zone by id.
     *
     * @param string $id
     * @return WP_Error
     */
	public function get_zone( string $id, array $args = array() ) {
		$endpoint = '/zones/' . $id;
		$zones_data = $this->get_request($endpoint);
		/* If zone data found. */
		if ( ! empty( $zones_data ) && isset( $zones_data->result ) && ! empty( $zones_data->result ) ) {
			/* Creating zone object. */
            $zone = $this->_parse_zone( $zones_data->result );
            if ( kpdns_is_primary_zone( $zone ) ) {
                $zone->set_primary( true );
            }
			return $zone;
		}
		return new WP_Error( KPDNS_ERROR_CODE_GENERIC, 'Zone record not found.');
	}

    /**
     * Gets a zone by domain name.
     *
     * @param string $domain
     * @return KPDNS_Zone|WP_Error
     */
    public function get_zone_by_domain( string $domain, array $args = array() ) {
		$endpoint = '/zones?name=' . $domain;
		$zones_data = $this->get_request( $endpoint );

		/* If zones found. */
		if ( isset( $zones_data->result ) && ! empty( $zones_data->result ) ) {
			$zones_list = new KPDNS_Zones_List();
			foreach ( $zones_data->result as $zone ) {
				$zones_list->add( new KPDNS_Zone( $zone->id, $zone->name, '' ) );
			}
			return $zones_list;
		}
		return new WP_Error( KPDNS_ERROR_CODE_GENERIC, 'Zone record not found.' );
    }

    /**
     * Gets a list of zones.
     *
     * @return KPDNS_Zones_List|WP_Error
     */
	public function list_zones( array $args = array() ) {

	    // TODO pagination

        $request_args = array();

        if ( isset( $args['search'] ) ) {
            $request_args['name'] = $args['search'];
        }

		$zones_data = $this->get_request('/zones', array(), $request_args );

		/* If zones found. */
		if ( isset( $zones_data->result ) ) {
			$zones_list = new KPDNS_Zones_List();

			foreach ( $zones_data->result as $result_zone ) {
			    $zone = $this->_parse_zone( $result_zone );

                if ( kpdns_is_primary_zone( $zone ) ) {
                    $zone->set_primary( true );
                }

				$zones_list->add( $zone );
			}

            if ( ! isset( $args['page'] ) || $args['page'] !== 'all' ) {
                $current_page = KPDNS_Utils::get_current_page( $args );
                $zones_list->maybe_paginate( $current_page );
            }

			return $zones_list;
		}
		return new WP_Error( KPDNS_ERROR_CODE_GENERIC, 'Unexpected error when trying to list zones.' );
	}

    /**
     * Adds a record to a zone.
     *
     * @param KPDNS_Record $record
     * @param string $zone_id
     * @return KPDNS_Zone|WP_Error
     */
	public function add_record( KPDNS_Record $record, string $zone_id, array $args = array() ) {

        $endpoint_url = '/zones/'.$zone_id.'/dns_records';
        $ttl = ! empty( $record->get_ttl() ) ? intval( $record->get_ttl() ) : 1;

        $type  = $record->get_type();
        $name  = $record->get_name();
        $rdata = $record->get_rdata();

        $data = array(
            'type'    => $type,
            'name'    => $name,
            'ttl'     => $ttl
        );

        switch ( $type ) {
            case KPDNS_Record::TYPE_CAA:
                $data['data']['flags'] = intval( $rdata[ KPDNS_Record::RDATA_KEY_FLAG ] );
                $data['data']['tag']   = $rdata[ KPDNS_Record::RDATA_KEY_TAG ];
                $data['data']['value'] = $rdata[ KPDNS_Record::RDATA_KEY_VALUE ];
                break;
            case KPDNS_Record::TYPE_MX:
                $data['priority'] = intval( $rdata[ KPDNS_Record::RDATA_KEY_PRIORITY ] );
                $data['content']  = $rdata[KPDNS_Record::RDATA_KEY_MAIL_SERVER ];
                break;
            case KPDNS_Record::TYPE_SRV:
                $data['name']             = $rdata[ KPDNS_Record::RDATA_KEY_SERVICE ] . '._' . $rdata[ KPDNS_Record::RDATA_KEY_PROTOCOL ]; // _servicename._tcp
                $data['data']['service']  = $rdata[ KPDNS_Record::RDATA_KEY_SERVICE ];
                $data['data']['proto']    = '_' . $rdata[ KPDNS_Record::RDATA_KEY_PROTOCOL ]; // _tcp
                $data['data']['priority'] = intval( $rdata[ KPDNS_Record::RDATA_KEY_PRIORITY ] );
                $data['data']['weight']   = intval( $rdata[ KPDNS_Record::RDATA_KEY_WEIGHT ] );
                $data['data']['port']     = intval( $rdata[ KPDNS_Record::RDATA_KEY_PORT ] );
                $data['data']['target']   = $rdata[ KPDNS_Record::RDATA_KEY_HOST ];
                break;
            default:
                $data['content'] = $rdata[ KPDNS_Record::RDATA_KEY_VALUE ];
        }

        $response = $this->post_request( $endpoint_url, $data );

        return $this->_check_response( $response );
	}

    /**
     * Deletes a record from a zone.
     *
     * @param KPDNS_Record $record
     * @param string $zone_id
     * @return KPDNS_Zone|WP_Error
     */
	public function delete_record( KPDNS_Record $record, string $zone_id, array $args = array() ) {
		$meta  = $record->get_meta();

		if ( ! isset( $meta['id'] ) ) {
            return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( 'Invalid record id.', 'keypress-dns' ) );
        }

        $endpoint_url = '/zones/' . $zone_id . '/dns_records/' . $meta['id'];
        $data = array();
        $response = $this->post_request( $endpoint_url, $data, "DELETE" );
        return $this->_check_response( $response );
	}


    /**
     * Edits a record by overriding the old record values with the new record values.
     *
     * @param KPDNS_Record $record
     * @param KPDNS_Record $new_record
     * @param string $zone_id
     * @return KPDNS_Zone|WP_Error
     */
	public function edit_record( KPDNS_Record $record, KPDNS_Record $new_record, string $zone_id, array $args = array() ) {

		$meta = $record->get_meta();

        if ( ! isset( $meta['id'] ) ) {
            return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( 'Invalid record id.', 'keypress-dns' ) );
        }

        $new_ttl = ! empty( $new_record->get_ttl() ) ? intval( $new_record->get_ttl() ) : 1;
        $endpoint = '/zones/' . $zone_id . '/dns_records/' . $meta['id'];

        $type  = $new_record->get_type();
        $rdata = $new_record->get_rdata();

        $data = array(
            'type'    => $type,
            'name'    => $new_record->get_name(),
            'ttl'     => $new_ttl
        );

        switch ( $type ) {
            case KPDNS_Record::TYPE_CAA:
                $data['data']['flags'] = intval( $rdata[ KPDNS_Record::RDATA_KEY_FLAG ] );
                $data['data']['tag']   = $rdata[ KPDNS_Record::RDATA_KEY_TAG ];
                $data['data']['value'] = $rdata[ KPDNS_Record::RDATA_KEY_VALUE ];
                break;
            case KPDNS_Record::TYPE_MX:
                $data['priority'] = intval( $rdata[ KPDNS_Record::RDATA_KEY_PRIORITY ] );
                $data['content']  = $rdata[KPDNS_Record::RDATA_KEY_MAIL_SERVER ];
                break;
            case KPDNS_Record::TYPE_SRV:
                $data['name']             = $rdata[ KPDNS_Record::RDATA_KEY_SERVICE ] . '._' . $rdata[ KPDNS_Record::RDATA_KEY_PROTOCOL ]; // _servicename._tcp
                $data['data']['service']  = $rdata[ KPDNS_Record::RDATA_KEY_SERVICE ];
                $data['data']['proto']    = '_' . $rdata[ KPDNS_Record::RDATA_KEY_PROTOCOL ]; // _tcp
                $data['data']['priority'] = intval( $rdata[ KPDNS_Record::RDATA_KEY_PRIORITY ] );
                $data['data']['weight']   = intval( $rdata[ KPDNS_Record::RDATA_KEY_WEIGHT ] );
                $data['data']['port']     = intval( $rdata[ KPDNS_Record::RDATA_KEY_PORT ] );
                $data['data']['target']   = $rdata[ KPDNS_Record::RDATA_KEY_HOST ];
                break;
            default:
                $data['content'] = $rdata[ KPDNS_Record::RDATA_KEY_VALUE ];
        }

        $response = $this->post_request( $endpoint, $data, 'PUT' );
        return $this->_check_response( $response );
	}

    public function list_records( string $zone_id, array $args = array()  ) {
        $records = new KPDNS_Records_List();
        /* Fetch DNS Records, fetching max 100 records (limit by API). */
        $endpoint = '/zones/' . $zone_id . '/dns_records?page=1&per_page=100&order=type';
        $response = $this->get_request( $endpoint );

        /* If DNS record found. */
        if ( ! empty( $response ) && isset( $response->result ) && ! empty( $response->result ) ) {
            foreach ( $response->result as $response_record ) {
                $record = $this->_parse_response_record( $response_record );
                $records->add( $record );
            }
        }

        $records->sort();

        return $records;
    }

	
	/**
	Parse Zone to create Zone object.
	*/
	private function _parse_zone( $zone )
	{
	    $parsed_zone = new KPDNS_Cloudflare_Zone( $zone->id, $zone->name, '' );

        $parsed_zone->development_mode      = $zone->development_mode;
        $parsed_zone->original_name_servers = $zone->original_name_servers;
        $parsed_zone->original_registrar    = $zone->original_registrar;
        $parsed_zone->original_dnshost      = $zone->original_dnshost;
        $parsed_zone->created_on            = $zone->created_on;
        $parsed_zone->modified_on           = $zone->modified_on;
        $parsed_zone->activated_on          = $zone->activated_on;
        $parsed_zone->owner                 = $zone->owner;
        $parsed_zone->account               = $zone->account;
        $parsed_zone->permissions           = $zone->permissions;
        $parsed_zone->plan                  = new KPDNS_Cloudflare_Plan( $zone->plan->id, $zone->plan->name, $zone->plan->price, $zone->plan->currency, $zone->plan->frequency, $zone->plan->legacy_id, $zone->plan->is_subscribed, $zone->plan->can_subscribe );
        $parsed_zone->status                = $zone->status;
        $parsed_zone->paused                = $zone->paused;
        $parsed_zone->type                  = $zone->type;
        $parsed_zone->name_servers          = $zone->name_servers;

        if ( isset( $zone->plan_pending ) ) {
            $parsed_zone->plan_pending      = new KPDNS_Cloudflare_Plan( $zone->plan_pending->id, $zone->plan_pending->name, $zone->plan_pending->price, $zone->plan_pending->currency, $zone->plan_pending->frequency, $zone->plan_pending->legacy_id, $zone->plan_pending->is_subscribed, $zone->plan_pending->can_subscribe );
        }

		return $parsed_zone;
	}

    public function build_zone( array $zone ): ?KPDNS_Zone {

        $id          = isset( $zone['id'] ) ? $zone['id'] : '';
        $domain      = isset( $zone['domain'] ) ? $zone['domain'] : '';

        return new KPDNS_Cloudflare_Zone( $id, $domain );
    }


    private function _parse_response_record( $response_record ) {

        $name  = $response_record->name;
        $type  = $response_record->type;
        $ttl   = $response_record->ttl;
        $rdata = array();
        $meta  = array();

        switch( $type ) {

            case KPDNS_Record::TYPE_A:
            case KPDNS_Record::TYPE_AAAA:
            case KPDNS_Record::TYPE_CNAME:
            case KPDNS_Record::TYPE_NS:
            case KPDNS_Record::TYPE_PTR:
            case KPDNS_Record::TYPE_SPF:
            case KPDNS_Record::TYPE_TXT:
                $rdata[ KPDNS_Record::RDATA_KEY_VALUE ] = $response_record->content;
                break;

            case KPDNS_Record::TYPE_CAA:
                $rdata[ KPDNS_Record::RDATA_KEY_FLAG ]  = $response_record->data->flags;
                $rdata[ KPDNS_Record::RDATA_KEY_TAG ]   = $response_record->data->tag;
                $rdata[ KPDNS_Record::RDATA_KEY_VALUE ] = $response_record->data->value;
                break;

            case KPDNS_Record::TYPE_MX:
                $rdata[ KPDNS_Record::RDATA_KEY_PRIORITY ] = $response_record->priority;
                $rdata[ KPDNS_Record::RDATA_KEY_MAIL_SERVER ] = $response_record->content;
                break;
            /*
            case KPDNS_Record::TYPE_SOA:
                $rdata[ KPDNS_Record::RDATA_KEY_NAME_SERVER ]   = $record[ KPDNS_Record::RDATA_KEY_NAME_SERVER ];
                $rdata[ KPDNS_Record::RDATA_KEY_EMAIL ]         = $record[ KPDNS_Record::RDATA_KEY_EMAIL ];
                $rdata[ KPDNS_Record::RDATA_KEY_SERIAL_NUMBER ] = $record[ KPDNS_Record::RDATA_KEY_SERIAL_NUMBER ];
                $rdata[ KPDNS_Record::RDATA_KEY_REFRESH ]       = $record[ KPDNS_Record::RDATA_KEY_REFRESH ];
                $rdata[ KPDNS_Record::RDATA_KEY_RETRY ]         = $record[ KPDNS_Record::RDATA_KEY_RETRY ];
                $rdata[ KPDNS_Record::RDATA_KEY_TIME_TRANSFER ] = $record[ KPDNS_Record::RDATA_KEY_TIME_TRANSFER ];
                break;
            */

            case KPDNS_Record::TYPE_SRV:
                $rdata[ KPDNS_Record::RDATA_KEY_SERVICE ]  = $response_record->data->service;
                $rdata[ KPDNS_Record::RDATA_KEY_PROTOCOL ] = $response_record->data->proto;
                $rdata[ KPDNS_Record::RDATA_KEY_PRIORITY ] = $response_record->data->priority;
                $rdata[ KPDNS_Record::RDATA_KEY_WEIGHT ]   = $response_record->data->weight;
                $rdata[ KPDNS_Record::RDATA_KEY_PORT ]     = $response_record->data->port;
                $rdata[ KPDNS_Record::RDATA_KEY_HOST ]     = $response_record->data->target;
                break;
        }

        $meta['id'] = $response_record->id;
        $meta['content'] = $response_record->content;
        $meta['locked'] = $response_record->locked;
        $meta['proxiable'] =  $response_record->proxiable;
        $meta['proxied'] = $response_record->proxied;
        $meta['created_on'] = $response_record->created_on;
        $meta['modified_on'] = $response_record->modified_on;
        $meta['meta'] = json_decode( json_encode( $response_record->meta ), true );

        if ( isset( $response_record->data ) ) {
            $meta['data'] = json_decode( json_encode( $response_record->data ), true );
        }

        if ( isset( $response_record->priority ) ) {
            $meta['priority'] = $response_record->priority;
        }

        return new KPDNS_Record( $type, $name, $rdata, $ttl, $meta );;
    }

    public function build_record( array $record ): ?KPDNS_Record {

        $the_record = parent::build_record( $record );

        if ( isset( $record['meta'] ) ) {
            $meta = $record['meta'];
        } else {
            $meta = array();

            if ( isset( $record['id'] ) ) {
                $meta['id'] = $record['id'];
            }

            if ( isset( $record['content'] ) ) {
                $meta['content'] = $record['content'];
            }

            if ( isset( $record['locked'] ) ) {
                $meta['locked'] = $record['locked'];
            }

            if ( isset( $record['proxiable'] ) ) {
                $meta['proxiable'] = $record['proxiable'];
            }

            if ( isset( $record['proxied'] ) ) {
                $meta['proxied'] = $record['proxied'];
            }

            if ( isset( $record['created_on'] ) ) {
                $meta['created_on'] = $record['created_on'];
            }

            if ( isset( $record['modified_on'] ) ) {
                $meta['modified_on'] = $record['modified_on'];
            }

            if ( isset( $record['meta'] ) ) {
                $meta['meta'] = $record['meta'];
            }

            if ( isset( $record['data'] ) ) {
                $meta['data'] = $record['data'];
            }

            if ( isset( $record['priority'] ) ) {
                $meta['priority'] = $record['priority'];
            }
        }

        $the_record->set_meta( $meta );

        return $the_record;
    }
	
	/* 
	* common get request.
	*/
	private function get_request( $endpoint, $headers = array(), $args = array() ) {
		if ( empty( $headers ) )  {
			$headers = array(
							'content-type' => 'application/json',
							'x-auth-email' => $this->cf_email,
							'x-auth-key'   => $this->cf_api_key,
						);
		}

		/* Headers (include other parameters if any). */
		$params = array( "headers" => $headers );

		/* Endpoint URL. */
		$endpoint_url = $this->endpoint . $endpoint;

        $endpoint_url = add_query_arg( $args, $endpoint_url );

		/* GET request to Cloudflare API. */
		$api_response = wp_remote_get( $endpoint_url, $params );

		/* If not WP error. */
		if ( ! is_wp_error( $api_response ) ) {
			/* Return API response. */
			return json_decode( $api_response['body'] );
		}
		return new WP_Error( KPDNS_ERROR_CODE_GENERIC, KPDNS_DEFAULT_ERROR_MESSAGE );
	}
	
	/* 
	* common post/delete request.
	*/
	private function post_request( $endpoint, $data = array(), $method = "POST", $headers = array()) {
		if ( empty( $headers ) ) {
			$headers = array(
							'content-type' => 'application/json',
							'x-auth-email' => $this->cf_email,
							'x-auth-key'   => $this->cf_api_key,
						);
		}

		/* Endpoint URL. */
		$endpoint_url = $this->endpoint.$endpoint;

		/* Request array. */
		$params['method'] = $method;
		$params['headers'] = $headers;

		if ( ! empty( $data ) ) {
			$params['body'] = json_encode( $data );
		}

		/* POST request to Cloudflare API. */
		$response = wp_remote_post( $endpoint_url, $params);

		/* If not WP error. */
		if( ! is_wp_error( $response ) ) {
			/* Return API response. */
			return json_decode( $response['body'] );
		}
		return new WP_Error( KPDNS_ERROR_CODE_GENERIC, KPDNS_DEFAULT_ERROR_MESSAGE );
	}

	private function _check_response($response ) {
        if ( isset( $response->success ) && $response->success && isset( $response->result ) && ! empty( $response->result ) ) {
            return true;
        } else {
            if ( isset( $response->errors ) ) {
                $wp_error = new WP_Error();
                foreach( $response->errors as $error ) {
                    if ( isset( $error->error_chain ) ) {
                        foreach( $error->error_chain as $error_chain ) {
                            $wp_error->add( KPDNS_ERROR_CODE_GENERIC, 'Error code ' . $error_chain->code . ': ' . $error_chain->message );
                        }
                    } else {
                        $wp_error->add( KPDNS_ERROR_CODE_GENERIC, 'Error code ' . $error->code . ': ' . $error->message );
                    }
                }
                return $wp_error;
            }

            return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( 'Something went wrong, please try again.', 'keypress-dns' ) );
        }
    }

    /*
    private function get_formatted_mx_record_value( $response_record_content ) {
        return $response_record_content->priority . ' ' . $response_record_content->content;
    }
    */
}
