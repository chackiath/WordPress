<?php

/**
 * Class KPDNS_DNSME_API
 *
 */

class KPDNS_DNSME_API extends KPDNS_API implements KPDNS_API_Imp, KPDNS_Custom_NS_API_Imp {
	
	//private $api_url = 'https://api.sandbox.dnsmadeeasy.com/V2.0/dns/';
	private $api_url = 'https://api.dnsmadeeasy.com/V2.0/dns/';
	//private $dme_api_key;
	//private $dme_secret_key;

    private $auth_params;

    private $request_cache;

	public function __construct( KPDNS_Credentials $credentials ) {

	    if ( is_wp_error( $credentials ) ) {
	        return;
        }

		$api_key    = KPDNS_Crypto::decrypt( $credentials->get_api_key(), hex2bin( KPDNS_ENCRYPTION_KEY ) );
		$secret_key = KPDNS_Crypto::decrypt( $credentials->get_secret_key(), hex2bin( KPDNS_ENCRYPTION_KEY ) );

        $this->auth_params = array(
            'api_key'    => $api_key,
            'secret_key' => $secret_key,
        );

        $this->request_cache = new KPDNS_Request_Cache();
	}


    /**
     * Adds a new zone.
     *
     * @param KPDNS_Zone $zone
     * @param array $args
     * @return KPDNS_Zone|WP_Error
     */
	public function add_zone( KPDNS_Zone $zone, array $args = array() ) {
        $endpoint_url =  'managed/';
        $data = array(
            'name' => $zone->get_domain(),
        );

        if ( isset( $args['custom-ns'] ) && ! empty( $args['custom-ns'] ) ) {
            $data['vanityId'] = $args['custom-ns'];
        }

        $response = $this->post_request( $endpoint_url, $data );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        return $this->_parse_zone( $response );
	}


    /**
     * Deletes a DNS zone.
     *
     * @param string $zone_id
     * @param array $args
     * @return bool|WP_Error
     */
	public function delete_zone( string $zone_id, array $args = array() ) {
        $endpoint_url = 'managed/' . $zone_id;
        $response     = $this->post_request( $endpoint_url, [ $zone_id ], 'DELETE' );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        return true;
	}

    /**
     * Deletes the specified DNS zones.
     *
     * @param array $zone_ids
     * @param array $args
     * @return bool|WP_Error
     */
	public function delete_zones( array $zone_ids, array $args = array() ) {
        $endpoint_url = 'managed/';
        $response     = $this->post_request( $endpoint_url, $zone_ids, 'DELETE' );
        if ( is_wp_error( $response ) ) {
            return $response;
        }

        return true;
    }


    /**
     * Edits a DNS zone.
     *
     * @param KPDNS_Zone $zone
     * @param array $args
     * @return bool|WP_Error
     */
	public function edit_zone( KPDNS_Zone $zone, array $args = array() ) {
	    if ( isset( $args['custom-ns'] ) ) {
            $custom_ns_id = $args['custom-ns'];

            if ( -1 == $custom_ns_id ) {
                //error_log( 'Delete custom NS' );
            } else {

                /*
                $endpoint_url =  'vanity/';// . $zone->get_id();

                $data = array(
                    'vanityId' => $custom_ns_id,
                    'ids' => [ $zone->get_id() ],
                );
                */

                $endpoint_url =  'managed/' . $zone->get_id();

                $data = array(
                    'vanityId' => $custom_ns_id,
                );

                $response = $this->post_request( $endpoint_url, $data, 'PUT' );

                if ( is_wp_error( $response ) ) {
                    return $response;
                }
            }
        }
        return true;
	}


    /**
     * Gets a zone by id.
     *
     * @param string $id
     * @param array $args
     * @return KPDNS_Zone|WP_Error
     */
	public function get_zone( string $id, array $args = array() ) {
	    $endpoint = 'managed/' . $id;
        $zone_response = $this->get_request( $endpoint );

        if ( is_wp_error( $zone_response ) ) {
            return $zone_response;
        }

        $custom_ns_array = $this->_get_custom_ns_array();

        if ( is_wp_error( $custom_ns_array ) ) {
            return $custom_ns_array;
        }

        $zone = $this->_parse_zone( $zone_response );

        if ( kpdns_is_primary_zone( $zone ) ) {
            $zone->set_primary( true );
        }

        if ( in_array( $zone->get_domain(), $custom_ns_array ) ) {
            $zone->set_custom_ns( true );
        }

        return $zone;
	}

    /**
     * Gets a zone by domain name.
     *
     * @param string $domain
     * @param array $args
     * @return KPDNS_Zone|WP_Error
     */
    public function get_zone_by_domain( string $domain, array $args = array() ) {
        $zones_map = $this->get_zones_map();

        if ( isset( $zones_map[ $domain ] ) ) {
            return $zones_map[ $domain ];
        }

		return new WP_Error( KPDNS_ERROR_CODE_GENERIC, 'Zone not found.' );
    }

    /**
     * Gets a list of zones.
     *
     * @param array $args
     * @return KPDNS_Zones_List|WP_Error
     */
	public function list_zones( array $args = array() ) {
        $response_zones = $this->get_request('managed/' );

        if ( is_wp_error( $response_zones ) ) {
            return $response_zones;
        }

        $custom_ns_array = $this->_get_custom_ns_array();

        if ( is_wp_error( $custom_ns_array ) ) {
            return $custom_ns_array;
        }

        $zones_list  = new KPDNS_Zones_List();
        $search_terms = KPDNS_Utils::get_search_terms( $args );

        foreach ( $response_zones->data as $result_zone ) {
            // If there are search terms but don't match the zone name, don't add it to the list.
            if ( $search_terms && strpos( $result_zone->name, $search_terms ) === false ) {
                continue;
            }
            $zone = $this->_parse_zone( $result_zone );

            if ( kpdns_is_primary_zone( $zone ) ) {
                $zone->set_primary( true );
            }

            if ( in_array( $zone->get_domain(), $custom_ns_array ) ) {
                $zone->set_custom_ns( true );
            }

            $zones_list->add( $zone );
        }

        if ( ! isset( $args['page'] ) || $args['page'] !== 'all' ) {
            $current_page = KPDNS_Utils::get_current_page( $args );
            $zones_list->maybe_paginate( $current_page );
        }

        return $zones_list;
	}

	protected function _get_custom_ns_array() {
        $response = $this->get_request( 'vanity/' );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $custom_ns_array = array();

        if ( isset( $response->data ) ) {
            foreach ( $response->data as $custom_ns ) {
                $custom_ns_array[] = $custom_ns->name;
            }
        }

        return $custom_ns_array;
    }

    /**
     * List a zone's records.
     *
     * @param string $zone_id
     * @param array $args
     * @return KPDNS_Records_List
     */
    public function list_records( string $zone_id, array $args = array()  ) {
		$records = new KPDNS_Records_List();
		
        /* Fetch DNS Records, fetching max 100 records (limit by API). */
        $endpoint = 'managed/' . $zone_id . '/records';
        $response = $this->get_request( $endpoint );
		
        /* If DNS record found. */
        if ( ! empty( $response ) && isset( $response->data ) && ! empty( $response->data ) ) {
            foreach ( $response->data as $response_record ) {
                $record = $this->_parse_response_record( $response_record );
                $records->add( $record );
            }
        }

        $zone = $this->get_zone( $zone_id );
        if ( ! is_wp_error( $zone ) && ! $zone->is_custom_ns() ) {
            $vanity_name_servers = $zone->get_vanity_name_servers();

            if ( isset( $vanity_name_servers ) ) {
                foreach ( $vanity_name_servers as $name_server ) {
                    $record = new KPDNS_Record( KPDNS_Record::TYPE_NS, $zone->get_domain(), array( KPDNS_Record::RDATA_KEY_VALUE => $name_server->fqdn ), 3600 );
                    $record->set_readonly( true );
                    $records->add( $record );
                }
            }
        }

        $records->sort();
            
        return $records;
    }


    /**
     * Adds a record to a zone.
     *
     * @param KPDNS_Record $record
     * @param string $zone_id
     * @param array $args
     * @return bool|WP_Error
     */
	public function add_record( KPDNS_Record $record, string $zone_id, array $args = array() ) {

	    $endpoint_url = 'managed/'.$zone_id.'/records/';
        $ttl = ! empty( $record->get_ttl() ) ? intval( $record->get_ttl() ) : 1;

        $type  = $record->get_type();
        $name  = $record->get_name();
        $rdata = $record->get_rdata();
       
		$gtdlocation = 'DEFAULT';
        $data = array(
            'type'    => $type,
            'name'    => $name,
            'ttl'     => $ttl,
			'gtdLocation'=> $gtdlocation
        );

        switch ( $type ) {
            case KPDNS_Record::TYPE_CAA:
                $data['issuerCritical'] = intval( $rdata[ KPDNS_Record::RDATA_KEY_FLAG ] );
                $data['caaType']   = $rdata[ KPDNS_Record::RDATA_KEY_TAG ];
                $data['value'] = $rdata[ KPDNS_Record::RDATA_KEY_VALUE ];
				
                break;
            case KPDNS_Record::TYPE_MX:
                $data['mxLevel'] = intval( $rdata[ KPDNS_Record::RDATA_KEY_PRIORITY ] );
                $data['value']  = $rdata[KPDNS_Record::RDATA_KEY_MAIL_SERVER ];
				
                break;
            case KPDNS_Record::TYPE_SRV:
              	
               $data['priority'] = intval( $rdata[ KPDNS_Record::RDATA_KEY_PRIORITY ] );
               $data['weight']   = intval( $rdata[ KPDNS_Record::RDATA_KEY_WEIGHT ] );
               $data['port']     = intval( $rdata[ KPDNS_Record::RDATA_KEY_PORT ] );
               $data['value']   = $rdata[ KPDNS_Record::RDATA_KEY_HOST ];
               break;
            default:
                $data['value'] = $rdata[ KPDNS_Record::RDATA_KEY_VALUE ];
				
        }

        $response = $this->post_request( $endpoint_url, $data );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        return true;
	}

	// TODO Duplicated code in add_record.
    public function add_records( KPDNS_Records_List $records, string $zone_id, array $args = array() ) {

        $endpoint_url = 'managed/' . $zone_id . '/records/createMulti/';

        $post_data = array();

        foreach( $records as $record ) {
            $ttl = ! empty( $record->get_ttl() ) ? intval( $record->get_ttl() ) : 1;

            $type  = $record->get_type();
            $name  = $record->get_name();
            $rdata = $record->get_rdata();

            $gtdlocation = 'DEFAULT';
            $data = array(
                'type'    => $type,
                'name'    => $name,
                'ttl'     => $ttl,
                'gtdLocation'=> $gtdlocation
            );
           

            switch ( $type ) {
                case KPDNS_Record::TYPE_CAA:
                    $data['issuerCritical'] = intval( $rdata[ KPDNS_Record::RDATA_KEY_FLAG ] );
                    $data['caaType']   = $rdata[ KPDNS_Record::RDATA_KEY_TAG ];
                    $data['value'] = $rdata[ KPDNS_Record::RDATA_KEY_VALUE ];
                    break;
                case KPDNS_Record::TYPE_MX:
                    $data['mxLevel'] = intval( $rdata[ KPDNS_Record::RDATA_KEY_PRIORITY ] );
                    $data['value']  = $rdata[KPDNS_Record::RDATA_KEY_MAIL_SERVER ];
                    break;
                case KPDNS_Record::TYPE_SRV:
                    $data['priority'] = intval( $rdata[ KPDNS_Record::RDATA_KEY_PRIORITY ] );
                    $data['weight']   = intval( $rdata[ KPDNS_Record::RDATA_KEY_WEIGHT ] );
                    $data['port']     = intval( $rdata[ KPDNS_Record::RDATA_KEY_PORT ] );
                    $data['value']   = $rdata[ KPDNS_Record::RDATA_KEY_HOST ];
                    break;
                case KPDNS_Record::TYPE_SOA:
                    $data['name']   = $rdata[ KPDNS_Record::RDATA_KEY_NAME_SERVER ];
                    $data['email']  = $rdata[ KPDNS_Record::RDATA_KEY_EMAIL ];
                    $data['serial'] = intval($rdata[ KPDNS_Record::RDATA_KEY_SERIAL_NUMBER ]);
                    $data['refresh'] = intval($rdata[ KPDNS_Record::RDATA_KEY_REFRESH ]);
                    $data['retry']   = intval($rdata[ KPDNS_Record::RDATA_KEY_RETRY ]);
                    $data['expire'] = intval($rdata[ KPDNS_Record::RDATA_KEY_TIME_TRANSFER ]);
                   break;
				   
				   default:
                    $data['value'] = $rdata[ KPDNS_Record::RDATA_KEY_VALUE ];

            }

            $post_data[] = $data;

        }

        $response = $this->post_request( $endpoint_url, $post_data );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        return true;
    }

    /**
     * Lists custom name servers.
     *
     * @return KPDNS_Name_Servers_List|WP_Error
     */
    public function list_name_servers( array $args = array() ) {
        $endpoint = 'vanity/';
        $response_vanity_ns = $this->get_request( $endpoint );

        if ( is_wp_error( $response_vanity_ns) ) {
            return $response_vanity_ns;
        }

        if ( ! empty( $response_vanity_ns->error ) ) {
            return new WP_Error( $response_vanity_ns->error[0], $response_vanity_ns->error[0] );
        }

        $search_terms      = KPDNS_Utils::get_search_terms( $args );
        $name_servers_list = new KPDNS_Name_Servers_List();

        if ( isset( $response_vanity_ns ) && ! empty( $response_vanity_ns ) && empty( $response_vanity_ns->error ) ) {
            $single_data = $response_vanity_ns->data;
            $zones_map   = $this->get_zones_map();
			
			if ( isset( $single_data ) && ! empty( $single_data ) ) {
                foreach ( $single_data as $single_custom_name ) {
                    // If there are search terms but don't match the zone name, don't add it to the list.
                    if ( $search_terms && strpos( $single_custom_name->name, $search_terms ) === false ) {
                        continue;
                    }

                    if ( $single_custom_name->public != 1 ) {
                        $zone_id = '';
                        if ( isset( $zones_map[ $single_custom_name->name ] ) ) {
                            $zone    = $zones_map[ $single_custom_name->name ];
                            $zone_id = $zone->get_id();
                        }
                        $name_server = new KPDNS_DNSME_Name_Server( $single_custom_name->id, $single_custom_name->name, $single_custom_name->servers, $zone_id, $single_custom_name->default );
                        $name_servers_list->add( $name_server );
                    }
                }
            }

        }

        $current_page = KPDNS_Utils::get_current_page( $args );

        $name_servers_list->maybe_paginate( $current_page );

		return $name_servers_list;
    }


    /**
     * Gets a custom name server by id.
     *
     * @param string $name_server_id
     * @return KPDNS_Name_Server|WP_Error
     */
	public function get_name_server( string $name_server_id, array $args = array() ) {
        $endpoint = 'vanity/' . $name_server_id;
        $response_ns = $this->get_request( $endpoint );

        if ( is_wp_error( $response_ns ) ) {
            return $response_ns;
        }

        if ( ! isset( $response_ns->name ) || ! isset( $response_ns->servers ) || ! isset( $response_ns->default ) ) {
            return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( 'DNSME response error: Missing name field(s).') );
        }

        $zone = $this->get_zone_by_domain( $response_ns->name );

        if ( is_wp_error( $zone ) ) {
            return $zone;
        }

        return new KPDNS_DNSME_Name_Server( $name_server_id, $zone->get_domain(), $response_ns->servers, $response_ns->default );
    }
	
    /**
     * Adds a new custom name server.
     * @param string $domain
     * @param array $name_servers
     * @param array $args
     * @return string|WP_Error
     */
	public function add_name_server( string $domain, array $name_servers, array $args = array() ) {

	    $zone = $this->get_zone_by_domain( $domain );

	    if ( is_wp_error( $zone ) ) {
            $zone = $this->add_zone( $this->build_zone( array( 'domain' => $domain ) ) );

            if ( is_wp_error( $zone ) ) {
                return $zone;
            }
            $existing_records = new KPDNS_Records_List();
        } else {
	        // TODO get_zone_by_domain doesn't retrieve the zone's name servers.
	        $zone = $this->get_zone( $zone->get_id() );
            $existing_records = $this->list_records( $zone->get_id() );
        }

        // Create A and NS records for the Name Servers.
        $zone_ns = $zone->get_name_servers();
        $zone_id = $zone->get_id();

        $ns_list = new KPDNS_Records_List();

        foreach ( $name_servers as $index => $ns ) {
            $ns_record_found = false;
            $a_record_found  = false;
            foreach ( $existing_records as $record ) {
                if (
                    ! $ns_record_found &&
                    $record->get_type() === KPDNS_Record::TYPE_NS &&
                    $record->get_name() === $domain &&
                    isset( $record->get_rdata()[ KPDNS_Record::RDATA_KEY_VALUE ] ) && $record->get_rdata()[ KPDNS_Record::RDATA_KEY_VALUE ] === $ns
                ) {
                    $ns_record_found = true;
                }

                if (
                    ! $a_record_found &&
                    $record->get_type() === KPDNS_Record::TYPE_A &&
                    $record->get_name() === $ns &&
                    isset( $record->get_rdata()[ KPDNS_Record::RDATA_KEY_VALUE ] ) && $record->get_rdata()[ KPDNS_Record::RDATA_KEY_VALUE ] === $zone_ns[ $index ]['ipv4']
                ) {
                    $a_record_found = true;
                }

                if ( $ns_record_found && $a_record_found ) {
                    break;
                }
            }

            if ( ! $ns_record_found ) {
                $ns_record = new KPDNS_Record( KPDNS_Record::TYPE_NS, $domain, array( KPDNS_Record::RDATA_KEY_VALUE => $ns ), 3600 );
                $ns_list->add( $ns_record );
            }

            if ( ! $a_record_found ) {
                $ip = $zone_ns[ $index ]['ipv4'];
                $a_record = new KPDNS_Record( KPDNS_Record::TYPE_A, $ns, array( KPDNS_Record::RDATA_KEY_VALUE => $ip ), 3600 );
                $ns_list->add( $a_record );
            }
        }

        if ( $ns_list->count() > 0 ) {
            $result = $this->add_records( $ns_list, $zone_id );

            if ( is_wp_error( $result ) ) {
                return $result;
            }
        }

        //Create SOA record.
        $soa_endpoint_url = 'soa/';

        $soa_details = array(
                            "name" => $domain,
                            "email" => "dns.dnsmadeeasy.com.",
                            "ttl" =>  21600,
                            "negativeCache" => 10800,
                            "retry" => 3600,
                            "serial" => 2009010103,
                            "comp" =>  $name_servers[0],
                            "expire" => 604800,
                            "refresh" => 14400,
                            "id" => $zone_id
                        );

        $response_soa =  $this->post_request( $soa_endpoint_url , $soa_details );

        if ( is_wp_error( $response_soa ) ) {
            return $response_soa;
        }

        // Create Vanity NS.
        $endpoint_url =  'vanity/';

        $default = isset( $args['default'] ) && $args['default'];

        $details = array(
            //'nameServerGroupId'	=>	1,
            'nameServerGroupId'	=>	2,
            'default'    => $default,
            //'nameServerGroup'     => "ns0,ns1,ns2,ns3,ns4.dnsmadeeasy.com",
            'nameServerGroup'     => "ns10,ns11,ns12,ns13,ns14,ns15.dnsmadeeasy.com",
            'public'=> false,
            'servers' => $name_servers,
            'name' => $domain
        );

        $response_ns =  $this->post_request( $endpoint_url , $details );

        if ( is_wp_error( $response_ns ) ) {
            return $response_ns;
        }

        //return $response_ns->id;
        return new KPDNS_DNSME_Name_Server( $response_ns->id, $domain, $name_servers, $zone_id, $default );
    }

    /**
     * Edits a custom name server.
     *
     * @param KPDNS_Name_Server $name_server
     * @param array $args
     * @return KPDNS_Name_Server|WP_Error
     */
    public function edit_name_server( KPDNS_Name_Server $name_server, array $args = array() ) {

        $endpoint = 'vanity/' . $name_server->get_id();

        // TODO name, nameServerGroupId and servers!

        // TODO dismiss default custom ns admin notice.
        $data = array(
            // Required params!
            'nameServerGroupId'	=>	2,
            'servers'           => $name_server->get_ns(),
            'name'              => $name_server->get_domain(),
        );

        if ( isset( $args['default'] ) ) {
            $data['default'] = boolval( $args['default'] );
        }

        $response = $this->post_request( $endpoint, $data, 'PUT' );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        return $name_server;
    }

    /**
     * Deletes a name server.
     *
     * @param string $name_server_id
     * @return bool|WP_Error
     */
    public function delete_name_server( string $name_server_id, array $args = array() ) {

        $endpoint = 'vanity/' . $name_server_id;

        $data = $name_server_id;

        $response = $this->post_request( $endpoint, $data, 'DELETE' );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        return true;
    }

    /**
     * Deletes multiple name servers.
     *
     * @param array $name_server_ids
     * @param array $args
     * @return bool|WP_Error
     */
    public function delete_name_servers( array $name_server_ids, array $args = array() ) {
        foreach ( $name_server_ids as $ns_id ) {
            $response = $this->delete_name_server( $ns_id );
            if ( is_wp_error( $response ) ) {
                return $response;
            }
        }
        return true;
    }

    /**
     * Deletes a record from a zone.
     *
     * @param KPDNS_Record $record
     * @param string $zone_id
     * @param array $args
     * @return KPDNS_Zone|WP_Error
     */
	public function delete_record( KPDNS_Record $record, string $zone_id, array $args = array() ) {
        $meta  = $record->get_meta();

		if ( ! isset( $meta['id'] ) ) {
            return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( 'Invalid record id.', 'keypress-dns' ) );
        }

        $endpoint_url = 'managed/' . $zone_id . '/records/' . $meta['id'];
        $data = array();
        $response = $this->post_request( $endpoint_url, $data, "DELETE" );
        if ( is_wp_error( $response ) ) {
            return $response;
        }
        return true;
	}

    /**
     * Edits a record by overriding the old record values with the new record values.
     *
     * @param KPDNS_Record $record
     * @param KPDNS_Record $new_record
     * @param string $zone_id
     * @param array $args
     * @return KPDNS_Zone|WP_Error
     */
	public function edit_record( KPDNS_Record $record, KPDNS_Record $new_record, string $zone_id, array $args = array() ) {

		$meta = $record->get_meta();
		if ( ! isset( $meta['id'] ) ) {
            return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( 'Invalid record id.', 'keypress-dns' ) );
        }

        $new_ttl = ! empty( $new_record->get_ttl() ) ? intval( $new_record->get_ttl() ) : 1;
        $endpoint = 'managed/'.$zone_id.'/records/'.$meta['id'];

        $type  = $new_record->get_type();
        $rdata = $new_record->get_rdata();
        $gtdlocation = 'DEFAULT';
        $data = array(
            'type'    => $type,
            'name'    => $new_record->get_name(),
            'ttl'     => $new_ttl,
            'id'      => $meta['id'],
            'gtdLocation' => $gtdlocation
        );
        
        switch ( $type ) {
            case KPDNS_Record::TYPE_CAA:
                $data['issuerCritical'] = intval( $rdata[ KPDNS_Record::RDATA_KEY_FLAG ] );
                $data['caaType']   = $rdata[ KPDNS_Record::RDATA_KEY_TAG ];
                $data['value'] = $rdata[ KPDNS_Record::RDATA_KEY_VALUE ];
                break;
            case KPDNS_Record::TYPE_MX:
                $data['mxLevel'] = intval( $rdata[ KPDNS_Record::RDATA_KEY_PRIORITY ] );
                $data['value']  = $rdata[KPDNS_Record::RDATA_KEY_MAIL_SERVER ];
                break;
            case KPDNS_Record::TYPE_SRV:
                $data['priority'] = intval( $rdata[ KPDNS_Record::RDATA_KEY_PRIORITY ] );
                $data['weight']   = intval( $rdata[ KPDNS_Record::RDATA_KEY_WEIGHT ] );
                $data['port']     = intval( $rdata[ KPDNS_Record::RDATA_KEY_PORT ] );
                $data['value']   = $rdata[ KPDNS_Record::RDATA_KEY_HOST ];
                break;
            default:
                $data['value'] = $rdata[ KPDNS_Record::RDATA_KEY_VALUE ];
        }

        $response = $this->post_request( $endpoint, $data , 'PUT' );

	}

    public function build_zone( array $zone ): ?KPDNS_Zone {
        $id          = isset( $zone['id'] ) ? $zone['id'] : '';
        $domain      = isset( $zone['domain'] ) ? $zone['domain'] : '';
        return new KPDNS_DNSME_Zone( $id, $domain );
    }

    /**
     * Parses the HTTP API response and return a KPDNS_Record object.
     *
     * @param $response_record
     */
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
                $rdata[ KPDNS_Record::RDATA_KEY_VALUE ] = $response_record->value;
                break;

            case KPDNS_Record::TYPE_CAA:
                $rdata[ KPDNS_Record::RDATA_KEY_FLAG ]  = $response_record->issuerCritical;
                $rdata[ KPDNS_Record::RDATA_KEY_TAG ]   = $response_record->caaType;
                $rdata[ KPDNS_Record::RDATA_KEY_VALUE ] = $response_record->value;
                break;

            case KPDNS_Record::TYPE_MX:
                $rdata[ KPDNS_Record::RDATA_KEY_PRIORITY ] = $response_record->mxLevel;
                $rdata[ KPDNS_Record::RDATA_KEY_MAIL_SERVER ] = $response_record->value;
                break;
			case KPDNS_Record::TYPE_SOA:
				$rdata[ KPDNS_Record::RDATA_KEY_NAME_SERVER ] = $response_record->name;
				$rdata[ KPDNS_Record::RDATA_KEY_EMAIL ] = $response_record->email;
				$rdata[ KPDNS_Record::RDATA_KEY_SERIAL_NUMBER ] = $response_record->serial;
				$rdata[ KPDNS_Record::RDATA_KEY_REFRESH ] =  $response_record->refresh;
				$rdata[ KPDNS_Record::RDATA_KEY_RETRY ] = $response_record->retry;
				$rdata[ KPDNS_Record::RDATA_KEY_TIME_TRANSFER ] = $response_record->negativeCache;
			   break;
           
		   
		   
		   
		   	case KPDNS_Record::TYPE_SRV:
                
                $rdata[ KPDNS_Record::RDATA_KEY_PRIORITY ] = $response_record->priority;
                $rdata[ KPDNS_Record::RDATA_KEY_WEIGHT ]   = $response_record->weight;
                $rdata[ KPDNS_Record::RDATA_KEY_PORT ]     = $response_record->port;
                $rdata[ KPDNS_Record::RDATA_KEY_HOST ]     = $response_record->value;
                break;
			}

		$meta['id'] = $response_record->id;
		$meta['value'] = $response_record->value;
		return new KPDNS_Record( $type, $name, $rdata, $ttl, $meta );

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

    public function check_default_ns() {
        $endpoint = 'vanity/';
        $response = $this->get_request( $endpoint );

        if ( is_wp_error( $response) ) {
            return $response;
        }

        if ( ! is_object( $response ) ) {
            return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( 'Invalid API response. Please try again.', 'keypress-dns' ) );
        }

        $count = 0;

        foreach ( $response->data as $ns ) {
            if ( $ns->default ) {
                $count++;
            }
        }

        if ( 1 === $count ) {
            return true;
        } elseif ( 1 > $count ) {
            $message = sprintf(
                __( 'Default Custom NS not defined. If you are planning to use Custom NS in the current setup, we highly recommend that you create a Custom NS <a href="%s">here</a>.', 'keypress-dns' ),
                sprintf(
                    '%1$s?page=%2$s&view=%3$s&name-server[default]=true',
                    is_multisite() ? network_admin_url( 'admin.php' ) : admin_url( 'admin.php'),
                    KPDNS_PAGE_NAME_SERVERS,
                    KPDNS_Page_Name_Servers::VIEW_ADD_NAME_SERVER
                )
            );
            return new WP_Error( KPDNS_ERROR_CODE_GENERIC, $message );
        } elseif ( 1 < $count ) {
            $message = __( 'You must define only one default NS.', 'keypress-dns' );
            return new WP_Error( KPDNS_ERROR_CODE_GENERIC, $message );
        }
    }
	
	 /**
     * Calculate the hexadecimal HMAC SHA1 hash of that string using Secret key as the hash key
     *
     * @param string $key
	 * @param string $data
	 * @param string $hashmethod
     */
	private function _hmac_sha1( $key, $data, $hashmethod ) {
		return hash_hmac("$hashmethod", $data, $key);
		return base64_encode(hash_hmac("$hashmethod", $data, $key, true));
		return hash_hmac ("sha1",$data,$key);
		
		$my_sign = hash_hmac("sha1", $data, base64_decode(strtr($key, '-_', '+/')), true);
		$my_sign = strtr(base64_encode($my_sign), '+/', '-_');
		return $my_sign;
		// Adjust key to exactly 64 bytes
		if (strlen($key) > 64) {
			$key = str_pad(sha1($key, true), 64, chr(0));
		}
		if (strlen($key) < 64) {
			$key = str_pad($key, 64, chr(0));
		}
	
		// Outter and Inner pad
		$opad = str_repeat(chr(0x5C), 64);
		$ipad = str_repeat(chr(0x36), 64);
	
		// Xor key with opad & ipad
		for ($i = 0; $i < strlen($key); $i++) {
			$opad[$i] = $opad[$i] ^ $key[$i];
			$ipad[$i] = $ipad[$i] ^ $key[$i];
		}
	
		return sha1($opad.sha1($ipad.$data, true));
	}
	/* 
	* common get request.
	*/
	private function get_request( $endpoint, $headers = array(), $args = array() ) {
	    $cached_request = $this->request_cache->get( $endpoint, $args );

        if ( $cached_request ) {
            $response = $cached_request;
        } else {
            $requestDate = date("r", time());
            $auth_method="sha1";
            $hmsc_key = $this->_hmac_sha1( $this->auth_params['secret_key'], $requestDate, $auth_method);

            if ( empty( $headers ) ) {
                $headers = array(
                    'content-type'			=>	'application/json',
                    'x-dnsme-apikey'		=>	$this->auth_params['api_key'],
                    'x-dnsme-hmac'			=>	$hmsc_key,
                    'x-dnsme-requestDate'	=>	$requestDate,
                );
            }
            /* Headers (include other parameters if any). */
            $params = array( "headers" => $headers );
            /* Endpoint URL. */
            $endpoint_url = $this->api_url . $endpoint;

            $new_timeout = 20;
            add_filter( 'http_request_timeout', function ( $timeout, $url ) use( $new_timeout ) { return $new_timeout; }, 10, 2 );

            /* GET request to Cloudflare API. */
            $response = wp_remote_get( $endpoint_url, $params );
            $this->request_cache->add( $endpoint, $args, $response );
        }

        if ( empty( $response ) ) {
            return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( 'Unexpected empty API response', 'keypress-dns' ) );
        }

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $response = json_decode( $response['body'] );

        if ( isset( $response->error ) ) {
            $wp_error = new WP_Error();
            foreach ( $response->error as $error ) {
                $wp_error->add( KPDNS_ERROR_CODE_GENERIC, $error );
            }
            return $wp_error;
        }

        if ( isset( $response->response ) && isset( $response->response->code ) && 200 != $response->response->code ) {
            if ( isset( $response->response->message ) ) {
                return new WP_Error( KPDNS_ERROR_CODE_GENERIC, $response->response->message );
            } else {
                return new WP_Error( KPDNS_ERROR_CODE_GENERIC, sprintf( __( 'Error %s: API response error', 'keypress-dns' ), $response->response->code ) );
            }
        }

        return $response;
	}

    /*
    * common post/delete/update request.
    */
    private function post_request( $endpoint, $data = array(), $method = "POST",$headers = array() ) {
        $requestDate = date("r", time());
        $auth_method="sha1";
        $hmsc_key = $this->_hmac_sha1( $this->auth_params['secret_key'], $requestDate, $auth_method );
        if ( empty( $headers ) ) {
            $headers = array(
                'content-type'        => 'application/json',
                'x-dnsme-apikey'	  => $this->auth_params['api_key'],
                'x-dnsme-hmac'        => $hmsc_key,
                'x-dnsme-requestDate' => $requestDate,
            );
        }

        /* Endpoint URL. */
        $endpoint_url = $this->api_url . $endpoint;

        /* Request array. */
        $params['method'] = $method;
        $params['headers'] = $headers;


        if ( ! empty( $data ) ) {
            $params['body'] = json_encode( $data );
        }

        $new_timeout = 20;
        add_filter( 'http_request_timeout', function ( $timeout, $url ) use( $new_timeout ) { return $new_timeout; }, 10, 2 );

        $response = wp_remote_post( $endpoint_url, $params );

        if ( empty( $response ) ) {
            return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( 'Unexpected empty API response', 'keypress-dns' ) );
        }

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $response = json_decode( $response['body'] );

        if ( isset( $response->error ) ) {
            $wp_error = new WP_Error();
            foreach ( $response->error as $error ) {
                $wp_error->add( KPDNS_ERROR_CODE_GENERIC, $error );
            }
            return $wp_error;
        }

        if ( isset( $response->response ) && isset( $response->response->code ) && 200 != $response->response->code ) {
            if ( isset( $response->response->message ) ) {
                return new WP_Error( KPDNS_ERROR_CODE_GENERIC, $response->response->message );
            } else {
                return new WP_Error( KPDNS_ERROR_CODE_GENERIC, sprintf( __( 'Error %s: API response error', 'keypress-dns' ), $response->response->code ) );
            }
        }

        return $response;
    }

	/**
	Parse Zone to create Zone object.
	*/
	private function _parse_zone( $zone ) {

        $parsed_zone = new KPDNS_DNSME_Zone( $zone->id, $zone->name );

        if ( isset( $zone->created) ) {
            $parsed_zone->created = $zone->created;
        }

        if ( isset( $zone->updated) ) {
            $parsed_zone->updated = $zone->updated;
        }

        if ( isset( $zone->pendingActionId ) ) {
            $parsed_zone->status = $zone->pendingActionId;
        }

        if ( isset( $zone->vanityId ) ) {
            $parsed_zone->vanity_id = $zone->vanityId;
        }

        if ( isset( $zone->vanityNameServers ) ) {
            $parsed_zone->vanity_name_servers = ( array ) $zone->vanityNameServers;
        }

        if ( isset( $zone->nameServers ) ) {
            $parsed_zone->name_servers = json_decode( json_encode( $zone->nameServers ), true );
        }

		return $parsed_zone;
    }

    public function build_name_server( array $name_server ) : ?KPDNS_Name_Server {
        $ns = parent::build_name_server( $name_server );

        if ( ! isset( $ns )  ) {
            return null;
        }

        $default = isset( $name_server['default' ] ) ? $name_server['default' ] : false;

        return new KPDNS_Name_Server( $ns->get_id(), $ns->get_domain(), $ns->get_ns(), $ns->get_zone_id(), $default );
    }

}