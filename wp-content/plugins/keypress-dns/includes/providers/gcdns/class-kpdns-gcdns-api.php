<?php

class KPDNS_GCDNS_API extends KPDNS_API implements KPDNS_API_Imp {

	private $client;

	private $service;

	private $project;

	public function __construct( KPDNS_GCDNS_Credentials $credentials ) {

		include_once KPDNS_PLUGIN_DIR . 'vendor/autoload.php';

		$this->project = '';
		$this->client = new Google_Client();
		$this->service = new Google_Service_Dns( $this->client );

		$upload = wp_upload_dir();
        $upload_dir = $upload['basedir'] . '/kpdns';

		try {

		    if ( ! is_dir( $upload_dir ) )
		       mkdir( $upload_dir, 0700 );

			$filename = md5( uniqid() ) . '.tmp';
 			$path = wp_normalize_path( $upload_dir . '/' .$filename );

			$service_account_json = KPDNS_Crypto::decrypt( $credentials->get_service_account_json(), hex2bin( KPDNS_ENCRYPTION_KEY ) );

			if ( ! $service_account_json ) {
				return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( 'Invalid encryption key. Please go to DNS Manager/Settings/DNS Provider, click on the "Create New Encryption Key" button and follow the instructions. <a href="' . add_query_arg( array( 'page' => KPDNS_PAGE_SETTINGS ), KPDNS_Page::get_admin_url() ) . '">Go to DNS Manager settings</a>', 'keypress-dns' ) );
			}

			$service_account = json_decode( $service_account_json );
 			$this->project = $service_account ? $service_account->project_id : '';

 			//@file_put_contents( $path, json_encode( $key_json ) );
 			@file_put_contents( $path, $service_account_json );

 			$this->client->addScope( 'https://www.googleapis.com/auth/ndev.clouddns.readwrite' );
 	 		$this->client->setAuthConfig( $path );

 			@unlink( $path );

		} catch( Exception $e ) {
			return new WP_Error( KPDNS_ERROR_CODE_GENERIC, $e->getMessage() );
 		}

	}

    /**
     * @param KPDNS_Zone $zone
     * @param array $args
     * @return WP_Error
     */
	public function add_zone( KPDNS_Zone $zone, array $args = array() ) {

		$domain      = $zone->get_domain();
        $name        = $zone->get_name();
		$description = $zone->get_description();

		// Add a dot at the end if not present (GCDNS issue)
		if ( substr( $domain, -1) !== '.' ) {
            $domain .= '.';
        }

		// Add a default description if not present
		if ( $description == '') {
            $description = __('Zone created by DNS Manager plugin', 'keypress-dns');
        }

		// Create the Zone object
		$requestBody = new Google_Service_Dns_ManagedZone();
		$requestBody->setName( $name );
		$requestBody->setDnsName( $domain );
		$requestBody->setDescription( $description );

		try {

			// Google_Service_Dns_ManagedZone
			$gcdns_res = $this->service->managedZones->create( $this->project, $requestBody);

			// Success! Return zone id.
			return $this->_parse_zone($gcdns_res);

		} catch ( Google_Service_Exception $e) {

			// Something went wrong
			//$response = $this->_get_exception_response( $e );

			return new WP_Error( KPDNS_ERROR_CODE_GENERIC, $e->getMessage() );
		}
	}

	/*
	 *
	 * @return KPDNS_RESPONSE
	 *
	 */
	public function delete_zone( string $zone_id, array $args = array() ) {

		// If the zone does not exist, die.
		$zone = $this->_get_zone( $zone_id );

		if ( null == $zone )
            return new WP_Error( KPDNS_ERROR_CODE_GENERIC, sprinf( __( 'Zone %s does not exist.', 'keypress-dns' ), $zone_id ) );
		try {

			// We need to delete the zone's resource record sets...

			// Get the resource record sets associated to the zone
			$rrsets = $this->service->resourceRecordSets;
			$rrsets_list = $rrsets->listResourceRecordSets( $this->project, $zone_id );
			$gcdns_rrsets = $rrsets_list->getRrsets();

			// Create a Resource Record Set
			// $rrset = $this->_create_resource_record_set( $record );
			$rrsets_to_delete = array();

			foreach ( $gcdns_rrsets as $rrset ) {

				// We can't delete NS and SOA records at the Apex
				if ( ( $rrset->getName() != $zone->getDnsName() ) ||
						 ( KPDNS_Record::TYPE_NS != $rrset->getType() ) &&
						 ( KPDNS_Record::TYPE_SOA != $rrset->getType() ) ) { // Not apex

					$rrsets_to_delete[] = $rrset;
				}

			}

			// If there are records to delete
			if ( 0 < count( $rrsets_to_delete ) ) {
				// Create a Change deleting our Resource Record Set
				$change = new Google_Service_Dns_Change();
				$change->kind = "dns#change";
				$change->setDeletions( $rrsets_to_delete );

				// Try to do the change
				$this->service->changes->create( $this->project, $zone_id, $change );
			}

			// Now we can delete the zone...

			// GuzzleHttp\Psr7\Response
			$this->service->managedZones->delete($this->project, $zone_id);

			// Success!
			return true;

		} catch ( Google_Service_Exception $e ) {

			// Something went wrong
			return new WP_Error( KPDNS_ERROR_CODE_GENERIC, $e->getMessage() );
		}
	}

	/*
	 *
	 * @return KPDNS_RESPONSE
	 *
	 */
    /*
	public function delete_zones() {

		if( isset( $_POST['dns_manager_zones'] ) ) {
			$messages = array();
			$response = new KPDNS_RESPONSE();
			$zones = $_POST['dns_manager_zones'];

			foreach ( $zones as $id ) {
				try {
					//GuzzleHttp\Psr7\Response
					$gcdns_res = $this->service->managedZones->delete($this->project, $id);

				} catch ( Google_Service_Exception $e) {

					// Something went wrong
					$messages[] = __( 'Unexpected response received from Google Cloud DNS:', 'keypress-dns' );

					// Add error messages
					foreach ( $e->getErrors() as $error ) {
						$messages[] = $error['message'];
					}

					$response->set_updated( false );
					$response->set_messages( $messages );

					return $response;
				}

			}

			//Success!!!
			$response->set_updated( true );
			$messages[] = __( 'Zones deleted', 'keypress-dns' );
			$response->set_messages( $messages );

			return $response;

		} else {
			return null;
		}

	}
    */

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

	public function edit_zone( KPDNS_Zone $zone, array $args = array() ) {

	    $zone_id = $zone->get_id();
		$zone_to_edit = $this->_get_zone( $zone_id );


		if ( null == $zone_to_edit ) {
            wp_die( 'Unexpected error: zone not found.' , 'keypress-dns');
        }

		if ( $zone_to_edit->description !== $zone->get_description() ) {
			$zone_to_edit->description = $zone->get_description();
		}

		try {

			//Google_Service_Dns_Operation
			$this->service->managedZones->update( $this->project, $zone_id, $zone_to_edit );

			// Success!
			return true;

		} catch ( Google_Service_Exception $e) {
			return new WP_Error( KPDNS_ERROR_CODE_GENERIC, $e->getMessage() );
		}
	}

    /**
     * @param string $zone_id
     * @param array $args = array()
     * @return KPDNS_Zone|WP_Error|null
     */
    public function get_zone( string $zone_id, array $args = array() ) {
        try {
            $result = $this->service->managedZones->get( $this->project, $zone_id );
            $zone = $this->_parse_zone( $result );
            if ( kpdns_is_primary_zone( $zone ) ) {
                $zone->set_primary( true );
            }
            return $zone;

        } catch ( Google_Service_Exception $e) {
            return new WP_Error( KPDNS_ERROR_CODE_GENERIC, $e->getMessage() );
        }
    }

	/*
	 *
	 *
	 * @return array $zone
	 *
	 */
	public function get_zoneX( string $zone_id, array $args = array() ) {

		// Get the zone
		$gcdns_zone = $this->_get_zone( $zone_id );

		if( null == $gcdns_zone )
			wp_die( 'Unexpected error: zone not found.' , 'keypress-dns');

		try {

			//Get the resource record sets associated to the zone
			$rrsets = $this->service->resourceRecordSets;
			$rrsets_list = $rrsets->listResourceRecordSets( $this->project, $zone_id );
			$gcdns_rrsets = $rrsets_list->getRrsets();

			//Initialize an array of records
			$records = array();

			foreach ( $gcdns_rrsets as $rrset ) {
				$records[] = array(
					'name' 		=> $rrset->getName(),
					'type' 		=> $rrset->getType(),
					'value' 	=> $rrset->getRrdatas(),
					'ttl' 		=> $rrset->getTtl(),
				);
			}

			$zone = array(
 			 'id' => $zone_id,
 			 'name' => $gcdns_zone->getName(),
 			 'domain' => $gcdns_zone->getDnsName(),
 			 'description' => $gcdns_zone->getDescription(),
 			 'creation-time' => $gcdns_zone->getCreationTime(),
			 'records' => $records,
 		 );

		 return $zone;

		} catch ( Google_Service_Exception $e) {
			return new WP_Error( KPDNS_ERROR_CODE_GENERIC, $e->getMessage() );
		}
	}

    public function get_zone_by_domain( string $domain, array $args = array() ) {
	    return null;
    }

    /**
     * @param array $args
     * @return KPDNS_Zones_List|WP_Error
     */
	public function list_zones( array $args = array() ) {

         try {
             $result = $this->service->managedZones->listManagedZones( $this->project, $gc_args = array() );
         } catch ( Google_Service_Exception $e) {
             return new WP_Error( KPDNS_ERROR_CODE_GENERIC, $e->getMessage() );
         }

        $zones_list  = new KPDNS_Zones_List();

        $search_terms = KPDNS_Utils::get_search_terms( $args );

        foreach ( $result as $gcdns_zone ) {
            $zone = $this->_parse_zone( $gcdns_zone );

            // If there are search terms but don't match the zone name, don't add it to the list.
            if ( $search_terms && strpos( $zone->get_domain(), $search_terms ) === false ) {
                continue;
            }

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

	public function add_record( KPDNS_Record $record, string $zone_id, array $args = array() ) {

		$zone = $this->_get_zone( $zone_id );

		if ( ! isset( $zone ) ) {
			return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __('Unexpected error: Invalid zone Id.', 'keypress-dns' ) );
		}

		// We need to prepare some fields for Google API...
		$this->_prepare_record( $record );

        //Create a Change adding our new Resource Record Set
        $change = new Google_Service_Dns_Change();
        $change->kind = "dns#change";

        $rrsets = $this->service->resourceRecordSets;
        $params = array( 'type' => $record->get_type(), 'name' => $record->get_name() );

        try {
            $rrsets_list = $rrsets->listResourceRecordSets( $this->project, $zone_id, $params );
        } catch ( Google_Service_Exception $e) {
            return new WP_Error( KPDNS_ERROR_CODE_GENERIC, $e->getMessage() );
        }

		if ( empty( $rrsets_list->getRrsets() ) ) { // No records with the same type and name.
            $rrset = $this->_create_resource_record_set( $record );

            $change->setAdditions( [ $rrset ] );
        } else { // Already existed records with the same type and name.
            $gcdns_rrsets = $rrsets_list->getRrsets();

            // Delete old RR Sets.
            $change->setDeletions( $gcdns_rrsets );

            $rrset = $this->_create_resource_record_set( $record );
            $rrset->setRrdatas( array_merge( $rrset->getRrdatas(), $gcdns_rrsets[0]['rrdatas'] ) );

            // Add new RR Sets.
            $change->setAdditions( [ $rrset ] );
        }

		try {

			//Google_Service_Dns_Change
			$response = $this->service->changes->create( $this->project, $zone['id'], $change );
			// Success!
			return $response;

		} catch ( Google_Service_Exception $e) {
			return new WP_Error( KPDNS_ERROR_CODE_GENERIC, $e->getMessage() );
		}
	}

	private function _get_resource_record_set( KPDNS_Record $record ) {

    }

	public function delete_record( KPDNS_Record $record, string $zone_id, array $args = array() ) {

	    $this->_prepare_record( $record );

        $rrsets = $this->service->resourceRecordSets;
        $params = array( 'type' => $record->get_type(), 'name' => $record->get_name() );

        try {
            $rrsets_list = $rrsets->listResourceRecordSets( $this->project, $zone_id, $params );
        } catch ( Google_Service_Exception $e) {
            return new WP_Error( KPDNS_ERROR_CODE_GENERIC, $e->getMessage() );
        }

        //Create a Change deleting our Resource Record Set
        $change = new Google_Service_Dns_Change();
        $change->kind = "dns#change";


        if ( 1 === count( $rrsets_list->getRrsets()[0]['rrdatas'] ) ) { // The record is the only one of its type and name.
            //Create a Resource Record Set
            $rrset = $this->_create_resource_record_set( $record );
            $change->setDeletions( [ $rrset ] );

        } else { // There are records with the same type and name.
            $rrset = $rrsets_list->getRrsets()[0];

            // Delete old RR Set.
            $change->setDeletions( [ $rrset ] );

            // Prepare additions.
            $new_rrset = clone $rrset;
            $new_rrset_rrdatas = $new_rrset->getRrdatas();

            foreach ( $new_rrset_rrdatas as $key => $rrdata ) {
                if ( $rrdata === $this->_get_formatted_value( $record) ) {
                    // Remove the record value.
                    unset( $new_rrset_rrdatas[ $key ] );
                }
            }

            // Reset indexes after unset.
            $new_rrset_rrdatas = array_values( $new_rrset_rrdatas );

            $new_rrset->setRrdatas( $new_rrset_rrdatas );

            // Add new RR Set.
            $change->setAdditions( [ $new_rrset ] );
        }


		try {

			//Google_Service_Dns_Change
			$response = $this->service->changes->create( $this->project, $zone_id, $change );

			// Success!
			return $response;

		} catch ( Google_Service_Exception $e) {
			return new WP_Error( KPDNS_ERROR_CODE_GENERIC, $e->getMessage() );
		}
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

	public function edit_record( KPDNS_Record $record, KPDNS_Record $new_record, string $zone_id, array $args ) {

	    $delete_result = $this->delete_record( $record, $zone_id, $args );
	    if ( is_wp_error( $delete_result ) ) {
	        return $delete_result;
        }

	    $add_result = $this->add_record( $new_record, $zone_id, $args );

        if ( is_wp_error( $add_result ) ) {
            // Restore old record.
            $restore_result = $this->add_record( $record, $zone_id, $args );
            if ( is_wp_error( $restore_result ) ) {
                return $restore_result;
            }
        }

        return $add_result;

        /*

		// We need to prepare some fields for Google API...
		$this->_prepare_record( $record );
		$this->_prepare_record( $new_record );

		//Create a Resource Record Set to delete
		$rrset = $this->_create_resource_record_set( $record );

		//Create a Resource Record Set to add
		$new_rrset = $this->_create_resource_record_set( $new_record );

		//Create a Change
		$change = new Google_Service_Dns_Change();
		$change->kind = "dns#change";
		$change->setDeletions( [ $rrset ] );
		$change->setAdditions( [ $new_rrset ] );

		try {

			//Google_Service_Dns_Change
			$response = $this->service->changes->create( $this->project, $zone_id, $change );

			// Success!
			return $response;

		} catch ( Google_Service_Exception $e) {
			return new WP_Error( KPDNS_ERROR_CODE_GENERIC, $e->getMessage() );
		}
        */
	}

    public function list_records( string $zone_id, array $args = array()  ) {

        try {

            //Get the resource record sets associated to the zone
            $rrsets = $this->service->resourceRecordSets;
            $params = array();

            if ( isset( $args['type'] ) && isset( $args['name'] ) ) {
                $params['type'] = $args['type'];
                $params['name'] = $args['name'];
            }

            $rrsets_list = $rrsets->listResourceRecordSets( $this->project, $zone_id, $params );

            $gcdns_rrsets = $rrsets_list->getRrsets();

            $records_list = new KPDNS_Records_List();

            foreach ( $gcdns_rrsets as $rrset ) {
                $type  = $rrset->getType();
                $name  = $rrset->getName();
                $ttl   = $rrset->getTtl();

                foreach ( $rrset->getRrdatas() as $gcdns_record ) {
                    $value = $gcdns_record;
                    $record = $this->_parse_response_record( $type, $name, $ttl, $value );
                    $records_list->add( $record );
                }
            }


        } catch ( Google_Service_Exception $e) {
            return new WP_Error( KPDNS_ERROR_CODE_GENERIC, $e->getMessage() );
        }

        $records_list->sort();

        return $records_list;
    }

	public function get_service() {
		return $this->service;
	}

	//TODO Make this better
	public function check_credentials() {

		try {
			//Return the zone
			$response = $this->service->projects->get($this->project);

			return KPDNS_OPTION_CREDENTIALS_VALID;

		} catch ( Google_Service_Exception $e) {
			return KPDNS_OPTION_CREDENTIALS_NOT_VALID;
		}
	}

	public function validate_zone_id( $id ) {
		return preg_match( '/^\d+$/', $id );
	}

	/**
	 * Returns list of name servers.
	 *
	 * Google Cloud does not have this feature yet.
	 *
	 * @author Martín Di Felice
	 * @return array List of name servers.
	 */
	public function list_name_servers() {
		return null;
	}

	/**
	 * Returns a name server configuration.
	 *
	 * Google Cloud does not have this feature yet.
	 *
	 * @author Martín Di Felice
	 * @param string $id Name server name.
	 * @return array Name server configuration.
	 */
	public function get_name_server( $id ) {
		return null;
	}

	/**
	 * Adds a name server.
	 *
	 * @author Martín Di Felice
	 * @param array $name_server Name server.
	 * @return KPDNS_RESPONSE Response.
	 */
	public function add_name_server( $name_server ) {
		$response = array(
			'updated'  => '',
			'messages' => array(
				__( 'Method not implemented.', 'keypress-dns' ),
			),
		);

		return $response;
	}

	/**
	 * Edits a name server.
	 *
	 * @author Martín Di Felice
	 * @param array $name_server Name server.
	 * @return KPDNS_RESPONSE Response.
	 */
	public function edit_name_server( $name_server ) {
		$response = array(
			'updated'  => '',
			'messages' => array(
				__( 'Method not implemented.', 'keypress-dns' ),
			),
		);

		return $response;
	}

	/**
	 * Deletes a name server.
	 *
	 * @author Martín Di Felice
	 * @param string $id Name server Id.
	 * @return KPDNS_RESPONSE Response.
	 */
	public function delete_name_server( $id ) {
		$response = array(
			'updated'  => '',
			'messages' => array(
				__( 'Method not implemented.', 'keypress-dns' ),
			),
		);

		return $response;
	}
	/**
	 * Returns if this provider has a particular feature.
	 *
	 * @author Martín Di Felice
	 * @param string $feature_id Feature ID.
	 * @return boolean If the feature is available.
	 */
	public function has_feature( $feature_id ) {
		switch ( $feature_id ) {
			case KPDNS_Provider_Factory::FEATURE_WHITE_LABEL_NS :
				return false;
            case KPDNS_Provider_Factory::FEATURE_ZONE_DESCRIPTION :
                return true;
		}
		return false;
	}

	private function _get_zone( $id ) {
		try {
			//Return the zone
			return $this->service->managedZones->get( $this->project, $id );

		} catch ( Google_Service_Exception $e) {
			//error_log( 'Unexpected error received from Google Cloud DNS:');
			//error_log( $e );
			return null;
		}

	}

	private function _prepare_record( KPDNS_Record &$record ) {
		//GCDNS expects a . at the end of the record name
        $name = $record->get_name();
		if( substr( $name, -1) != '.' ) {
            $record->set_name( $name . '.' );
        }

        //GCDNS expects a . at the end of a CNAME or MX record value
        switch( $record->get_type() ) {

            case KPDNS_Record::TYPE_CNAME:
            case KPDNS_Record::TYPE_NS:
            case KPDNS_Record::TYPE_PTR:
                $value = $record->get_rdata()[ KPDNS_Record::RDATA_KEY_VALUE ];
                if( '.' != substr( $value, -1) ) {
                    $record->set_rdata( array( KPDNS_Record::RDATA_KEY_VALUE => $value . '.' ) );
                }
                break;

            case KPDNS_Record::TYPE_MX:
                $rdata = $record->get_rdata();
                if( '.' != substr( $rdata[ KPDNS_Record::RDATA_KEY_MAIL_SERVER ], -1) ) {
                    $rdata[ KPDNS_Record::RDATA_KEY_MAIL_SERVER ] .= '.';
                    $record->set_rdata( $rdata );
                }
                break;

            case KPDNS_Record::TYPE_SRV:
                $rdata = $record->get_rdata();
                if( '.' != substr( $rdata[ KPDNS_Record::RDATA_KEY_HOST ], -1) ) {
                    $rdata[ KPDNS_Record::RDATA_KEY_HOST ] .= '.';
                    $record->set_rdata( $rdata );
                }
                break;
        }
	}

	private function _create_resource_record_set( KPDNS_Record $record ) {
		//Create a Resource Record Set to delete
		$rrset = new Google_Service_Dns_ResourceRecordSet();
		$rrset->setKind( 'dns#resourceRecordSet' );
		$rrset->setName( $record->get_name() );
		$rrset->setSignatureRrdatas( null );
		$rrset->setTtl( $record->get_ttl() );
		$rrset->setType($record->get_type() );

        $rrset->setRrdatas( array( $this->_get_formatted_value( $record ) ) );


		return $rrset;
	}

	private function _get_exception_response( $e ) {
		// Something went wrong
		$messages = array( __( 'Unexpected response received from Google Cloud DNS:', 'keypress-dns' ), );

		// Add error messages
		foreach ( $e->getErrors() as $error ) {
			$messages[] = $error['message'];
		}

		// Create response array
		$response = array(
			'updated'  => false,
			'messages' => $messages,
		);

		return $response;
	}

    /**
     * @param array $zone
     * @return KPDNS_Zone|null
     */
    public function build_zone( array $zone ): ?KPDNS_Zone {

        $id          = isset( $zone['id'] ) ? $zone['id'] : '';
        $domain      = isset( $zone['domain'] ) ? $zone['domain'] : '';
        $name        = isset( $zone['name'] ) ? $zone['name'] : ! empty( $domain ) ? str_replace( '.', '', $domain ): '';
        $description = isset( $zone['description'] ) ? $zone['description'] : '';
        $visibility  = isset( $zone['visibility'] ) ? $zone['visibility'] : KPDNS_GCDNS_Zone::VISIBILITY_PUBLIC;

        return new KPDNS_GCDNS_Zone( $id, $domain, $name, $description, $visibility );
    }

    private function _parse_zone( Google_Service_Dns_ManagedZone $result ) {
        $zone_arr = array(
            'id'          => $result->getId(),
            'name'        => $result->getName(),
            'domain'      => $result->getDnsName(),
            'description' => $result->getDescription(),
            'visibility'  => $result->getVisibility(),
            //'creation-time' => $result->getCreationTime(),
        );

        return $this->build_zone( $zone_arr );
    }

    // TODO duplicated in R53 API. Improve.
    private function _parse_response_record( $type, $name, $ttl, $value ) {

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
                $rdata[ KPDNS_Record::RDATA_KEY_VALUE ] = $value;
                break;

            case KPDNS_Record::TYPE_CAA:
                $values = explode( ' ', $value );
                $rdata[ KPDNS_Record::RDATA_KEY_FLAG ]  = $values[0];
                $rdata[ KPDNS_Record::RDATA_KEY_TAG ]   = $values[1];
                $rdata[ KPDNS_Record::RDATA_KEY_VALUE ] = $values[2];
                break;

            case KPDNS_Record::TYPE_MX:
                $values = explode( ' ', $value );
                $rdata[ KPDNS_Record::RDATA_KEY_PRIORITY ]    = $values[0];
                $rdata[ KPDNS_Record::RDATA_KEY_MAIL_SERVER ] = $values[1];
                break;

            case KPDNS_Record::TYPE_SOA:
                $values = explode( ' ', $value );
                $rdata[ KPDNS_Record::RDATA_KEY_NAME_SERVER ]   = $values[0];
                $rdata[ KPDNS_Record::RDATA_KEY_EMAIL ]         = $values[1];
                $rdata[ KPDNS_Record::RDATA_KEY_SERIAL_NUMBER ] = $values[2];
                $rdata[ KPDNS_Record::RDATA_KEY_REFRESH ]       = $values[3];
                $rdata[ KPDNS_Record::RDATA_KEY_RETRY ]         = $values[4];
                $rdata[ KPDNS_Record::RDATA_KEY_TIME_TRANSFER ] = $values[5];
                break;

            case KPDNS_Record::TYPE_SRV:
                $values = explode( ' ', $value );
                //$rdata[ KPDNS_Record::RDATA_KEY_SERVICE ]  = $values[0];
                //$rdata[ KPDNS_Record::RDATA_KEY_PROTOCOL ] = $values[1];
                $rdata[ KPDNS_Record::RDATA_KEY_PRIORITY ] = $values[0];
                $rdata[ KPDNS_Record::RDATA_KEY_WEIGHT ]   = $values[1];
                $rdata[ KPDNS_Record::RDATA_KEY_PORT ]     = $values[2];
                $rdata[ KPDNS_Record::RDATA_KEY_HOST ]     = $values[3];
                break;
        }

        return new KPDNS_Record( $type, $name, $rdata, $ttl, $meta );;
    }

    // TODO Duplicated in AR53.
    private function _get_formatted_value( KPDNS_Record $record ): string {

        $type  = $record->get_type();
        $rdata = $record->get_rdata();

        switch( $type ) {

            case KPDNS_Record::TYPE_A:
            case KPDNS_Record::TYPE_AAAA:
            case KPDNS_Record::TYPE_CNAME:
            case KPDNS_Record::TYPE_NS:
            case KPDNS_Record::TYPE_PTR:
                return $rdata[ KPDNS_Record::RDATA_KEY_VALUE ];

            case KPDNS_Record::TYPE_SPF:
            case KPDNS_Record::TYPE_TXT:
                return KPDNS_Utils::maybe_add_quotation_marks( $rdata[ KPDNS_Record::RDATA_KEY_VALUE ] );

            case KPDNS_Record::TYPE_CAA:
                return implode( ' ', array(
                    $rdata[ KPDNS_Record::RDATA_KEY_FLAG ],
                    $rdata[ KPDNS_Record::RDATA_KEY_TAG ],
                    KPDNS_Utils::maybe_add_quotation_marks( $rdata[ KPDNS_Record::RDATA_KEY_VALUE ] )
                ) );

            case KPDNS_Record::TYPE_MX:
                return implode( ' ', array(
                    $rdata[ KPDNS_Record::RDATA_KEY_PRIORITY ],
                    $rdata[ KPDNS_Record::RDATA_KEY_MAIL_SERVER ]
                ) );

            case KPDNS_Record::TYPE_SOA:
                return implode( ' ', array(
                    $rdata[ KPDNS_Record::RDATA_KEY_NAME_SERVER ],
                    $rdata[ KPDNS_Record::RDATA_KEY_EMAIL ],
                    $rdata[ KPDNS_Record::RDATA_KEY_SERIAL_NUMBER ],
                    $rdata[ KPDNS_Record::RDATA_KEY_REFRESH ],
                    $rdata[ KPDNS_Record::RDATA_KEY_RETRY ],
                    $rdata[ KPDNS_Record::RDATA_KEY_TIME_TRANSFER ]
                ) );

            case KPDNS_Record::TYPE_SRV:
                return implode( ' ', array(
                    //$rdata[ KPDNS_Record::RDATA_KEY_SERVICE ],
                    //$rdata[ KPDNS_Record::RDATA_KEY_PROTOCOL ],
                    $rdata[ KPDNS_Record::RDATA_KEY_PRIORITY ],
                    $rdata[ KPDNS_Record::RDATA_KEY_WEIGHT ],
                    $rdata[ KPDNS_Record::RDATA_KEY_PORT ],
                    $rdata[ KPDNS_Record::RDATA_KEY_HOST ]
                ) );

            default:
                return '';
        }
    }

}
