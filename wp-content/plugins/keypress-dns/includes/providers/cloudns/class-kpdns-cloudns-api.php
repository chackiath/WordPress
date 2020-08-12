<?php

class KPDNS_ClouDNS_API extends KPDNS_API implements KPDNS_API_Imp, KPDNS_Custom_NS_API_Imp {

    const CUSTOM_NS_GROUP = 'custom-ns';

    private $cloudns;
    private $ns_type = 'premium';

    private $api_url = 'https://api.cloudns.net/';
    private $auth_params;

	public function __construct( KPDNS_Credentials $credentials ) {

		require_once KPDNS_PLUGIN_DIR . 'vendor/autoload.php';

        $auth_id       = KPDNS_Crypto::decrypt( $credentials->get_auth_id(), hex2bin( KPDNS_ENCRYPTION_KEY ) );
        $auth_password = KPDNS_Crypto::decrypt( $credentials->get_auth_password(), hex2bin( KPDNS_ENCRYPTION_KEY ) );

        $this->cloudns = new \tvorwachs\ClouDNS\ClouDNS();
        $options = array(
            'authId'       => $auth_id,
            'authPassword' => $auth_password,
            'authType'     => 'auth-id'
        );
        $this->cloudns->setOptions( $options );

        $this->auth_params = array(
            'auth-id'       => $auth_id,
            'auth-password' => $auth_password,
        );
	}


    /**
     * Adds a new zone.
     *
     * @param KPDNS_Zone $zone
     * @return KPDNS_Zone|WP_Error
     */
	public function add_zone( KPDNS_Zone $zone, array $args = array() ) {

		try {

		    $domain    = $zone->get_domain();
            $zone_type = 'master';
            $master_ip = '';
            $ns_array  = array();

            if ( isset( $args['custom-ns'] ) && ! empty( $args['custom-ns'] ) ) {
                $custom_ns = $args['custom-ns'];

                $records = $this->list_records( $custom_ns );

                if ( is_wp_error( $records ) ) {
                    return $records;
                }

                foreach ( $records as $record ) {
                    if ( $record->get_type() === KPDNS_Record::TYPE_NS ) {
                        $ns_array[] = $record->get_rdata()[ KPDNS_Record::RDATA_KEY_VALUE ];
                    }
                }

                // ClouDNS handles SOA so there is no need to do anything else.
            }

            $zones_endpoint = $this->cloudns->zones();
            $result_zone    = $zones_endpoint->registerZone( $domain, $zone_type, $ns_array, $master_ip );

            if ( ! isset( $result_zone ) || ! isset( $result_zone['status'] ) || $result_zone['status'] !== 'Success' ) {
                $message = isset( $result_zone['statusDescription'] ) ? $result_zone['statusDescription'] : __( 'The zone could not be created.', 'keypress-dns' );
                return new WP_Error( KPDNS_ERROR_CODE_GENERIC, $message );
            }

            if ( $zone->get_status() === KPDNS_ClouDNS_Zone::ZONE_STATUS_INACTIVE ) {
                $zones_endpoint->setZoneStatus( $domain, KPDNS_ClouDNS_Zone::ZONE_STATUS_INACTIVE );
            }

            if ( isset( $args['copy-records'] ) && 'true' === $args['copy-records'] ) {
                //error_log( 'RECORDS:' . print_r( dns_get_record( $zone->get_domain() ), true ) );
            }

			return $zone;

		} catch ( Exception $e ) {
			return new WP_Error( KPDNS_ERROR_CODE_GENERIC, $e->getMessage() );
		}
	}


    /**
     * Deletes a DNS zone.
     *
     * @param string $zone_id
     * @return bool|WP_Error
     */
	public function delete_zone( string $zone_id, array $args = array() ) {

		try {
            $zones_endpoint  = $this->cloudns->zones();
            $result_zone     = $zones_endpoint->deleteZone( $zone_id );

            if ( ! isset( $result_zone ) || ! isset( $result_zone['status'] ) || $result_zone['status'] !== 'Success' ) {
                $message = isset( $result_zone['statusDescription'] ) ? $result_zone['statusDescription'] : __( 'The zone could not be created.', 'keypress-dns' );
                return new WP_Error( KPDNS_ERROR_CODE_GENERIC, $message );
            }

			return true;
        } catch ( Exception $e ) {
            return new WP_Error( KPDNS_ERROR_CODE_GENERIC, $e->getMessage() );
        }
	}


    /**
     * Edits a DNS zone.
     *
     * @param KPDNS_Zone $zone
     * @return bool|WP_Error
     */
	public function edit_zone( KPDNS_Zone $zone, array $args = array() ) {

	    if ( ! $zone instanceof KPDNS_ClouDNS_Zone ) {
            return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( '$zone parameter must be an instance of KPDNS_ClouDNS_Zone.', 'keypress-dns' ) );
        }

		try {
            $zones_endpoint  = $this->cloudns->zones();
            $zones_endpoint->setZoneStatus( $zone->get_domain(), $zone->get_status() );
			return true;
        } catch ( Exception $e ) {
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
		try {

            $zones_endpoint  = $this->cloudns->zones();
            $result_zone     = $zones_endpoint->getZoneInfo( $id );

            if ( ! isset( $result_zone ) || $result_zone['status'] === 'Failed' ) {
                $message = isset( $result_zone['statusDescription'] ) ? $result_zone['statusDescription'] : __( 'The zone could not be found.', 'keypress-dns' );
                return new WP_Error( KPDNS_ERROR_CODE_GENERIC, $message );
            }

            $zone = new KPDNS_ClouDNS_Zone( $result_zone['name'], $result_zone['type'], $result_zone['zone'], $result_zone['status'] );

            if ( kpdns_is_primary_zone( $zone ) ) {
                $zone->set_primary( true );
            }

            return $zone;

        } catch ( Exception $e ) {
            return new WP_Error( KPDNS_ERROR_CODE_GENERIC, $e->getMessage() );
        }
	}


    /**
     * Gets a zone by domain name.
     *
     * @param string $domain
     * @return KPDNS_Zone|WP_Error
     */
    public function get_zone_by_domain( string $domain, array $args = array() ) {
        return $this->get_zone( $domain );
    }


    /**
     * Gets a list of zones.
     *
     * @param array $args
     * @return KPDNS_Zones_List|WP_Error
     */
	public function list_zones( array $args = array() ) {

	    $url = $this->api_url . 'dns/list-zones.json';
        $params = array(
            'rows-per-page' => KPDNS_Utils::get_items_per_page(), // Results per page. Can be 10, 20, 30, 50 or 100.
        );

        $params['page'] = isset( $args['page'] ) ? $args['page'] : 1;

        if ( isset( $args['search'] ) ) {
            $params['search'] = $args['search'];
        }

        $request_params = array_merge( $this->auth_params, $params );

        $request = new KPDNS_Request( $url );
        $request->set_params( $request_params );
        $response = $request->post();

        if ( is_wp_error( $response ) ) {
            return new WP_Error( KPDNS_ERROR_CODE_GENERIC, $response->get_error_message() );
        }

        $response_body = json_decode( $response['body'], true );

        $response_error = $this->check_response_error( $response_body );

        if ( is_wp_error( $response_error ) ) {
            return $response_error;
        }

        $zones_list = new KPDNS_Zones_List();
        $zones_list->set_current_page( $params['page'] );

        if ( ! empty( $response_body ) ) {
            foreach ( $response_body as $zone ) {
                $the_zone = new KPDNS_ClouDNS_Zone( $zone['name'], $zone['type'], $zone['zone'], $zone['status'] );
                if ( kpdns_is_primary_zone( $the_zone ) ) {
                    $the_zone->set_primary( true );
                }
                $zones_list->add( $the_zone );
            }
            $search        = isset( $args['search'] ) ? $args['search'] : '';
            $group_id      = isset( $args['group-id'] ) ? $args['group-id'] : -1;
            $pages_count   = $this->_get_pages_count( $params['rows-per-page'], $search, $group_id );
            $zones_stats   = $this->get_zones_statics();

            if ( is_wp_error( $pages_count) ) {
                return $pages_count;
            } else {
                $zones_list->set_pages_count( $pages_count );
            }

            if ( is_wp_error( $zones_stats) ) {
                return $zones_stats;
            } else {
                $zones_list->set_total_items( $zones_stats['count'] );
            }

        } else {
            $zones_list->set_pages_count( 1 );
            $zones_list->set_total_items( 0 );
        }

        return $zones_list;
	}


    /**
     * Adds a record to a zone.
     *
     * @param KPDNS_Record $record
     * @param string $zone_id
     * @return KPDNS_Zone|WP_Error
     */
	public function add_record( KPDNS_Record $record, string $zone_id, array $args = array() ) {
        try {

            $zones_endpoint   = $this->cloudns->zones();
            $records_endpoint = $zones_endpoint->records();

            $name    = rtrim( str_replace( $zone_id, '', $record->get_name() ), '.' );
            $type    = $record->get_type();
            $value   = '';
            $options = array();

            switch( $type ) {

                case KPDNS_Record::TYPE_A:
                case KPDNS_Record::TYPE_AAAA:
                case KPDNS_Record::TYPE_CNAME:
                case KPDNS_Record::TYPE_NS:
                case KPDNS_Record::TYPE_PTR:
                case KPDNS_Record::TYPE_SPF:
                case KPDNS_Record::TYPE_TXT:
                    $value = $record->get_rdata()[ KPDNS_Record::RDATA_KEY_VALUE ];
                    break;

                case KPDNS_Record::TYPE_CAA:
                    $options['caa_flag'] = $record->get_rdata()[ KPDNS_Record::RDATA_KEY_FLAG ];
                    $options['caa_type'] = $record->get_rdata()[ KPDNS_Record::RDATA_KEY_TAG ];
                    $options['caa_value'] = $record->get_rdata()[ KPDNS_Record::RDATA_KEY_VALUE ];
                    break;

                case KPDNS_Record::TYPE_MX:
                    $value = $record->get_rdata()[ KPDNS_Record::RDATA_KEY_MAIL_SERVER ];
                    $options['priority'] = $record->get_rdata()[ KPDNS_Record::RDATA_KEY_PRIORITY ];
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
                    $options['service'] = $record->get_rdata()[ KPDNS_Record::RDATA_KEY_SERVICE ];
                    $options['protocol'] = $record->get_rdata()[ KPDNS_Record::RDATA_KEY_PROTOCOL ];
                    $options['priority'] = $record->get_rdata()[ KPDNS_Record::RDATA_KEY_PRIORITY ];
                    $options['weight'] = $record->get_rdata()[ KPDNS_Record::RDATA_KEY_WEIGHT ];
                    $options['port'] = $record->get_rdata()[ KPDNS_Record::RDATA_KEY_PORT ];
                    $value = $record->get_rdata()[ KPDNS_Record::RDATA_KEY_HOST ];
                    break;
            }

            $result = $records_endpoint->addRecord( $zone_id, $type, $name, $value, $record->get_ttl(), $options );

            if ( ! isset( $result ) || ! isset( $result['status'] ) || $result['status'] !== 'Success' ) {
                $message = isset( $result['statusDescription'] ) ? $result['statusDescription'] : __( 'The record could not be created.', 'keypress-dns' );
                return new WP_Error( KPDNS_ERROR_CODE_GENERIC, $message );
            }

            return true;

        } catch ( Exception $e ) {
            return new WP_Error( KPDNS_ERROR_CODE_GENERIC, $e->getMessage() );
        }
	}


    /**
     * Deletes a record from a zone.
     *
     * @param KPDNS_Record $record
     * @param string $zone_id
     * @return bool|WP_Error
     */
	public function delete_record( KPDNS_Record $record, string $zone_id, array $args = array() ) {
        try {

            $zones_endpoint   = $this->cloudns->zones();
            $records_endpoint = $zones_endpoint->records();
            $result_records   = $records_endpoint->listRecords( $zone_id );

            if ( ! isset( $result_records ) || ( isset( $result_records['status'] ) && $result_records['status'] === 'Failed' ) ) {
                $message = isset( $result['statusDescription'] ) ? $result_records['statusDescription'] : __( 'The record could not be deleted.', 'keypress-dns' );
                return new WP_Error( KPDNS_ERROR_CODE_GENERIC, $message );
            }

            $record_id = $record->get_meta()['id'];

            $result = $records_endpoint->deleteRecord( $zone_id, $record_id );

            if ( ! isset( $result ) || ! isset( $result['status'] ) || $result['status'] !== 'Success' ) {
                $message = isset( $result['statusDescription'] ) ? $result['statusDescription'] : __( 'The record could not be deletd due to an unexpected error.', 'keypress-dns' );
                return new WP_Error( KPDNS_ERROR_CODE_GENERIC, $message );
            }

            return true;

        } catch ( Exception $e ) {
            return new WP_Error( KPDNS_ERROR_CODE_GENERIC, $e->getMessage() );
        }
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
     * Edits a record by overriding the old record values with the new record values.
     *
     * @param KPDNS_Record $record
     * @param KPDNS_Record $new_record
     * @param string $zone_id
     * @return KPDNS_Zone|WP_Error
     */
	public function edit_record( KPDNS_Record $record, KPDNS_Record $new_record, string $zone_id, array $args = array() ) {

        try {

            $zones_endpoint   = $this->cloudns->zones();
            $records_endpoint = $zones_endpoint->records();

            $domain     = $zone_id;
            $id         = $record->get_meta()['id'];
            $ttl        = $new_record->get_ttl();
            $host       = $new_record->get_name();
            $type       = $record->get_type();
            $value      = '';
            $options    = array();

            // remove .domain.com
            // remove domain.com
            $host       = str_replace( '.' . $domain, '', $host );
            $host       = str_replace( $domain, '', $host );

            switch( $type ) {

                case KPDNS_Record::TYPE_A:
                case KPDNS_Record::TYPE_AAAA:
                case KPDNS_Record::TYPE_CNAME:
                case KPDNS_Record::TYPE_NS:
                case KPDNS_Record::TYPE_PTR:
                case KPDNS_Record::TYPE_SPF:
                case KPDNS_Record::TYPE_TXT:
                    $value = $new_record->get_rdata()[ KPDNS_Record::RDATA_KEY_VALUE ];
                    break;

                case KPDNS_Record::TYPE_CAA:
                    $options['caa_flag']  = $new_record->get_rdata()[ KPDNS_Record::RDATA_KEY_FLAG ];
                    $options['caa_type']  = $new_record->get_rdata()[ KPDNS_Record::RDATA_KEY_TAG ];
                    $options['caa_value'] = $new_record->get_rdata()[ KPDNS_Record::RDATA_KEY_VALUE ];
                    break;

                case KPDNS_Record::TYPE_MX:
                    $value = $record->get_rdata()[ KPDNS_Record::RDATA_KEY_MAIL_SERVER ];
                    $options['priority'] = $new_record->get_rdata()[ KPDNS_Record::RDATA_KEY_PRIORITY ];
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
                    $options['service']  = $new_record->get_rdata()[ KPDNS_Record::RDATA_KEY_SERVICE ];
                    $options['protocol'] = $new_record->get_rdata()[ KPDNS_Record::RDATA_KEY_PROTOCOL ];
                    $options['priority'] = $new_record->get_rdata()[ KPDNS_Record::RDATA_KEY_PRIORITY ];
                    $options['weight']   = $new_record->get_rdata()[ KPDNS_Record::RDATA_KEY_WEIGHT ];
                    $options['port']     = $new_record->get_rdata()[ KPDNS_Record::RDATA_KEY_PORT ];
                    $value               = $new_record->get_rdata()[ KPDNS_Record::RDATA_KEY_HOST ];
                    break;
            }

            $result = $records_endpoint->modifyRecord( $domain, $id, $host, $value, $ttl, $options );

            if ( ! isset( $result ) || ! isset( $result['status'] ) || $result['status'] !== 'Success' ) {
                $message = isset( $result['statusDescription'] ) ? $result['statusDescription'] : __( 'The record could not be updated due to an unexpected error.', 'keypress-dns' );
                return new WP_Error( KPDNS_ERROR_CODE_GENERIC, $message );
            }

             return self::get_zone( $zone_id );
        } catch ( Exception $e ) {
            return new WP_Error( KPDNS_ERROR_CODE_GENERIC, $e->getMessage() );
        }
	}

	public function list_records( string $zone_id, array $args = array()  ) {

	    $domain           = $zone_id;
        $zones_endpoint   = $this->cloudns->zones();
        $records_endpoint = $zones_endpoint->records();
        $result_records   = $records_endpoint->listRecords( $domain );

        $records_list     = new KPDNS_Records_List();

        foreach ( $result_records as $cloudns_record ) {
            //$record = new KPDNS_ClouDNS_Record( $cloudns_record['id'], $host, $cloudns_record['type'], $cloudns_record['record'], $cloudns_record['ttl'], $cloudns_record['failover'] );
            $record = $this->_parse_response_record( $cloudns_record, $domain );
            $records_list->add( $record );
        }

        $records_list->sort();

        return $records_list;
    }


    /**
     * Lists custom name servers.
     *
     * @return KPDNS_Name_Servers_List|WP_Error
     */
	public function list_name_servers( array $args = array() ) {

	    // TODO Improve, it's too slow. Add name servers zones to a group named CUSTOM_NS_GROUP.

        try {
            $endpoint            = $this->cloudns;
            $zones_endpoint      = $endpoint->zones();
            $records_endpoint    = $zones_endpoint->records();

            $num_pages           = 0;
            $page                = 0;
            $rows                = 10;

            $ns_ips              = array( KPDNS_Record::TYPE_A => array(), 'AAA' => array() );
            $result_name_servers = $endpoint->listNameServers();

            // Collect the ips of the name servers provided by ClouDNS.
            foreach ( $result_name_servers as $name_server ) {
                $ns_ips[ KPDNS_Record::TYPE_A ][]   = $name_server['ip4'];
                $ns_ips[ KPDNS_Record::TYPE_AAAA ][] = $name_server['ip6'];
            }

            $num_A_records      = 4; // ClouDNS provides four A records for their Name Servers. /*count( $ns_ips[KPDNS_Record::TYPE_A] );*/
            $num_AAA_records    = 4; // ClouDNS provides four AAAA records  for their Name Servers. /*count( $ns_ips['AAA'] );*/
            $name_servers_zones = array();

            // Iterate over the zones pages.
            while ( $num_pages > $page || $page == 0 ) {
                $result_zones = $zones_endpoint->listZones( ++$page, $rows );
                $num_pages = $result_zones['Pages'];

                foreach( $result_zones['Data'] as $zone ) {

                    $A_records_matches    = 0;
                    $AAA_records_matches  = 0;

                    if ( ! isset( $zone['name'] ) ) {
                        continue;
                    }

                    $records = $records_endpoint->listRecords( $zone['name'] );

                    // Check the zone's records. If the zone's A or AAA records match the ips of our ClouDNS name servers, the zone belongs to a name server.
                    foreach ( $records as $record ) {
                        if ( isset( $record['type'] ) ) {
                            switch( $record['type'] ) {
                                case KPDNS_Record::TYPE_A:
                                    if ( in_array( $record['record'], $ns_ips[KPDNS_Record::TYPE_A] ) ) {
                                        $A_records_matches++;
                                    }
                                    break;
                                case 'AAA':
                                    if ( in_array( $record['record'], $ns_ips['AAA'] ) ) {
                                        $AAA_records_matches++;
                                    }
                                    break;
                            }
                        }
                    }

                    if (
                        ( $A_records_matches > 0 && $A_records_matches >= $num_A_records ) ||
                        ( $AAA_records_matches > 0 && $AAA_records_matches >= $num_AAA_records )
                    ) {
                        $name_servers_zones[ $zone['name'] ]['zone'] = $zone;
                        $name_servers_zones[ $zone['name'] ]['records'] = $records;
                    }
                }
            }

            $name_servers_list = new KPDNS_Name_Servers_List();

            /*
            $ns = array();
            foreach ( $result_name_servers as $name_server ) {
               if ( $name_server['type'] === $this->ns_type ) {
                    $ns[] = $name_server['name'];
               }
            }
            */

            foreach ( $name_servers_zones as $domain => $name_servers_zone ) {
                $ns = array();
                foreach ( $name_servers_zone as $records ) {
                    foreach ( $records as $record ) {
                        if ( isset( $record['type'] ) && $record['type'] === KPDNS_Record::TYPE_NS ) {
                            $ns[] = $record['record'];
                        }
                    }
                }
                $name_server = new KPDNS_Name_Server( $domain, $domain, $ns, $domain );
                $name_servers_list->add( $name_server );
            }

            return $name_servers_list;
        } catch ( Exception $e ) {
            return new WP_Error( KPDNS_ERROR_CODE_GENERIC, $e->getMessage() );
        }
	}


    /**
     * Gets a custom name server by id.
     *
     * @param string $name_server_id
     * @return KPDNS_Name_Server|WP_Error
     */
	public function get_name_server( string $name_server_id, array $args = array() ) {
        try {
            $zone = $this->get_zone( $name_server_id );
            return new KPDNS_Name_Server( $name_server_id, $zone );
        } catch ( Exception $e ) {
            return new WP_Error( KPDNS_ERROR_CODE_GENERIC, $e->getMessage() );
        }
	}


    /**
     * Adds a new custom name server.
     * @param string $domain
     * @param array $name_servers
     * @param array $args
     * @return string|WP_Error
     */
	public function add_name_server( string $domain, array $name_servers, array $args = array() ) {
        try {

            $zones_endpoint = $this->cloudns->zones();

            $zone = $this->get_zone_by_domain( $domain );

            // If the zone doesn't exist, create it.
            if ( ! $zone instanceof KPDNS_ClouDNS_Zone ) {
                $result_zone = $zones_endpoint->registerZone( $domain, 'master', $name_servers, '' );
                if ( ! isset( $result_zone ) || ! isset( $result_zone['status'] ) || $result_zone['status'] !== 'Success' ) {
                    $message = isset( $result_zone['statusDescription'] ) ? $result_zone['statusDescription'] : __( 'The zone for the custom name server could not be created.', 'keypress-dns' );
                    return new WP_Error( KPDNS_ERROR_CODE_GENERIC, $message );
                }
            }

            $records = $this->list_records( $domain );

            if ( is_wp_error( $records ) ) {
                return $records;
            }

            $records_endpoint = $zones_endpoint->records();

            // List the name servers associated to the user account.
            $result_name_servers = $this->cloudns->listNameServers();

            $index = 0;

            $compare_args = array(
                'type' => true,
                'name' => true,
            );

            // Create the corresponding A, AAA and NS records for the name servers.
            foreach( $result_name_servers as $ns ) {

                if ( $ns['type'] === $args['type'] ) { // Free / Premium

                    $name = rtrim( str_replace( $domain, '', $name_servers[ $index ] ), '.' );

                    /**
                     * A Record
                     */
                    $new_a_record = new KPDNS_Record( KPDNS_Record::TYPE_A, rtrim( $name_servers[ $index ], '.' ), array( KPDNS_Record::RDATA_KEY_VALUE => $ns['ip4'] ), 3600 );

                    // A record exists -> overwrite
                    $old_a_record = $this->find_record_in_list( $new_a_record, $records, $compare_args );
                    if ( $old_a_record ) {
                        $this->edit_record( $old_a_record, $new_a_record, $domain );
                    } else { // Create A Record
                        $this->add_record( $new_a_record, $domain );
                    }

                    /**
                     * AAAA record
                     */
                    $new_aaaa_record = new KPDNS_Record( KPDNS_Record::TYPE_AAAA, $name, array( KPDNS_Record::RDATA_KEY_VALUE => $ns['ip6'] ), 3600 );
                    // AAAA record exists -> overwrite
                    $old_aaaa_record = $this->find_record_in_list( $new_aaaa_record, $records, $compare_args );
                    if ( $old_aaaa_record ) {
                        $this->edit_record( $old_aaaa_record, $new_aaaa_record, $domain );
                    } else { // Create AAAA Record
                        $this->add_record( $new_aaaa_record, $domain );
                    }

                    /**
                     * NS record
                     */
                    $new_ns_record = new KPDNS_Record( KPDNS_Record::TYPE_NS, $domain, array( KPDNS_Record::RDATA_KEY_VALUE => $name ), 3600 );
                    // NS record exists -> overwrite
                    $old_ns_record = $this->find_record_in_list( $new_ns_record, $records, $compare_args );
                    if ( $old_ns_record ) {
                        $this->edit_record( $old_ns_record, $new_ns_record, $domain );
                    } else { // Create NS Record
                        $this->add_record( $new_ns_record, $domain );
                    }

                    $index++;
                }
            }

            $name_server = new KPDNS_Name_Server( $domain, $domain, array(), $domain );

            return $name_server;
        } catch ( Exception $e ) {
            return new WP_Error( KPDNS_ERROR_CODE_GENERIC, $e->getMessage() );
        }
	}


    /**
     * Edits a custom name server.
     *
     * @param KPDNS_Name_Server $name_server
     * @return KPDNS_Name_Server|WP_Error
     */
	public function edit_name_server( KPDNS_Name_Server $name_server, array $args = array() ) {
        try {
            return $name_server;
        } catch ( Exception $e ) {
            return new WP_Error( KPDNS_ERROR_CODE_GENERIC, $e->getMessage() );
        }
	}


    /**
     * @param string $name_server_id
     * @return bool|WP_Error
     */
	public function delete_name_server( string $name_server_id, array $args = array() ) {
        try {
            return $this->delete_zone( $name_server_id );
        } catch ( Exception $e ) {
            return new WP_Error( KPDNS_ERROR_CODE_GENERIC, $e->getMessage() );
        }
	}

    public function delete_name_servers( array $name_server_ids, array $args = array() ) {
        // TODO Improve
        foreach( $name_server_ids as $id ) {
            $result = $this->delete_name_server( $id, $args );
            if ( is_wp_error( $result ) ) {
                return $result;
            }
        }
        return true;
    }

    /**
     * @param array $zone
     * @return KPDNS_Zone|null
     */
	public function build_zone( array $zone ): ?KPDNS_Zone {
        if ( empty( $zone ) || ! isset( $zone['domain'] ) ) {
            return null;
        }

        $domain = $zone['domain'];
        $type   = isset( $zone['type'] ) ? $zone['type'] : KPDNS_ClouDNS_Zone::ZONE_TYPE_MASTER;
        $master = isset( $zone['master'] ) ? $zone['master'] : KPDNS_ClouDNS_Zone::DEFAULT_MASTER_ZONE_VALUE;
        $status = isset( $zone['status'] ) ? $zone['status'] : KPDNS_ClouDNS_Zone::ZONE_STATUS_ACTIVE;

	    return new KPDNS_ClouDNS_Zone( $domain, $type, $master, $status );
    }

    private function _get_pages_count( $rows_per_page, $search = '', $group_id = -1 ) {
        $url = $this->api_url . 'dns/get-pages-count.json';
        $params = array(
            'rows-per-page' => $rows_per_page,
        );

        if ( '' !== $search ) {
            $params['search'] = $search;
        }

        if ( -1 !== $group_id ) {
            $params['group-id'] = $group_id;
        }

        $request_params = array_merge( $this->auth_params, $params );

        $request = new KPDNS_Request( $url );
        $request->set_params( $request_params );
        $response = $request->post();

        if ( is_wp_error( $response ) ) {
            return new WP_Error( KPDNS_ERROR_CODE_GENERIC, $response->get_error_message() );
        }

        $response_body = json_decode( $response['body'], true );

        $response_error = $this->check_response_error( $response_body );

        if ( is_wp_error( $response_error ) ) {
            return $response_error;
        }

        return $response_body;
    }

    private function get_zones_statics() {
        $url = $this->api_url . 'dns/get-zones-stats.json';

        $request = new KPDNS_Request( $url );
        $request->set_params( $this->auth_params );
        $response = $request->post();

        if ( is_wp_error( $response ) ) {
            return new WP_Error( KPDNS_ERROR_CODE_GENERIC, $response->get_error_message() );
        }

        $response_body = json_decode( $response['body'], true );

        $response_error = $this->check_response_error( $response_body );

        if ( is_wp_error( $response_error ) ) {
            return $response_error;
        }

        return $response_body;
    }

    private function check_response_error( $response_body ) {
        if ( ! isset( $response_body ) || isset( $response_body['status'] ) && $response_body['status'] === 'Failed' ) {
            $message = isset( $response_body['statusDescription'] ) ? $response_body['statusDescription'] : __( 'Unexpected error, please try again or contact the plugin authors.' );
            return new WP_Error( KPDNS_ERROR_CODE_GENERIC, $message );
        }
        return false;
    }

    public function build_record( array $record ): ?KPDNS_Record {

        $the_record = parent::build_record( $record );

        if ( isset( $record['meta'] ) ) {
            $meta = $record['meta'];
        } else {
            $meta = array();

            if ( isset( $record['dynamicurl_status'] ) ) {
                $meta['dynamicurl_status'] = $record['dynamicurl_status'];
            }

            if ( isset( $record['failover'] ) ) {
                $meta['failover'] = $record['failover'];
            }

            if ( isset( $record['status'] ) ) {
                $meta['status'] = $record['status'];
            }
        }

        $the_record->set_meta( $meta );

        return $the_record;
    }

    private function _parse_response_record( $response_record, $domain ) {

        $type  = $response_record['type'];
        $name  = $response_record['host'] . ( ! empty( $response_record['host'] ) ? '.' : '' ) . $domain;
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
                $rdata[ KPDNS_Record::RDATA_KEY_VALUE ] = $response_record['record'];
                break;

            case KPDNS_Record::TYPE_CAA:
                $rdata[ KPDNS_Record::RDATA_KEY_FLAG ]  = $response_record['caa_flag'];
                $rdata[ KPDNS_Record::RDATA_KEY_TAG ]   = $response_record['caa_type'];
                $rdata[ KPDNS_Record::RDATA_KEY_VALUE ] = $response_record['caa_value'];
                break;

            case KPDNS_Record::TYPE_MX:
                $rdata[ KPDNS_Record::RDATA_KEY_PRIORITY ] = $response_record['priority'];
                $rdata[ KPDNS_Record::RDATA_KEY_MAIL_SERVER ] = $response_record['record'];
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
                $host = explode( '.', $response_record['host'] );
                $service = $host[0];
                $protocol = strtoupper( str_replace( '_', '', $host[1] ) );
                $rdata[ KPDNS_Record::RDATA_KEY_SERVICE ]  = $service;
                $rdata[ KPDNS_Record::RDATA_KEY_PROTOCOL ] = $protocol;
                $rdata[ KPDNS_Record::RDATA_KEY_PRIORITY ] = $response_record['priority'];
                $rdata[ KPDNS_Record::RDATA_KEY_WEIGHT ]   = $response_record['weight'];
                $rdata[ KPDNS_Record::RDATA_KEY_PORT ]     = $response_record['port'];
                $rdata[ KPDNS_Record::RDATA_KEY_HOST ]     = $response_record['record'];
                break;
        }

        $meta['id'] = $response_record['id'];

        if ( isset( $response_record['dynamicurl_status'] ) ) {
            $meta['dynamicurl_status'] = $response_record['dynamicurl_status'];
        }

        if ( isset( $response_record['failover'] ) ) {
            $meta['failover'] = $response_record['failover'];
        }

        if ( isset( $response_record['status'] ) ) {
            $meta['status'] = $response_record['status'];
        }

	    return new KPDNS_Record( $type, $name, $rdata, $response_record['ttl'], $meta );
    }

}
