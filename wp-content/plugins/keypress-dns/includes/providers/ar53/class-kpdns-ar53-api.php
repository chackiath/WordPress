<?php

class KPDNS_AR53_API extends KPDNS_API implements KPDNS_API_Imp, KPDNS_Custom_NS_API_Imp {

	private $client;

	private $credentials;

    private $request_cache;

	public function __construct( KPDNS_Credentials $credentials ) {
	    $this->credentials = $credentials;
		require_once KPDNS_PLUGIN_DIR . 'vendor/autoload.php';
		$this->client = $this->_get_client();
        $this->request_cache = new KPDNS_Request_Cache();
	}

    /**
     * @param KPDNS_Zone $zone
     * @param array $args
     * @return KPDNS_Zone|WP_Error
     */
	public function add_zone( KPDNS_Zone $zone, array $args = array() ) {

		try {
		    $description = $zone->get_description();

            // Add a default description if not present
            if ( $description == '') {
                $description = __('Zone created by DNS Manager plugin', 'keypress-dns');
            }

			$caller_reference = md5( serialize( $zone ) . ( time() / 300 ) );
			$arguments = array(
				'CallerReference' => $caller_reference,
				'HostedZoneConfig' => array(
					'Comment' => $description,
				),
				'Name' => $zone->get_domain(),
			);

			$custom_ns_id = null;
			if ( isset( $args['custom-ns'] ) && ! empty( $args['custom-ns'] ) ) {
                $arguments['DelegationSetId'] = $args['custom-ns'];
                $custom_ns_id = $args['custom-ns'];
			}

			$result = $this->client->createHostedZone( $arguments );

			$zone_id = $result['HostedZone']['Id'];

			if ( isset( $custom_ns_id ) ) {

                $name_server = $this->get_name_server( $custom_ns_id );

                if ( is_wp_error( $name_server ) ) {
                    return $name_server;
                }

                $records = $this->list_records( $name_server->get_zone_id() );

                if ( is_wp_error( $records ) ) {
                    return $records;
                }

                $ns_array = array();

                foreach ( $records as $record ) {
                    if ( $record->get_type() === KPDNS_Record::TYPE_NS ) {
                         $rdata = $record->get_rdata();
                         if ( isset( $rdata[ KPDNS_Record::RDATA_KEY_VALUE ] ) ) {
                             $ns_array[] = $rdata[ KPDNS_Record::RDATA_KEY_VALUE ];
                         };
                    }
                }

                if ( ! empty( $ns_array ) ) {
                    $this->_update_ns_and_soa_records( $zone_id, $zone->get_domain(), $ns_array );
                }
            }



            /*

            $name_servers = array();
            foreach ( $name_server->get_zone()->get_records( KPDNS_Record::TYPE_NS ) as $ns_record ) {
                $name_servers[] = $ns_record->get_value();
            }
            */

            return $this->_parse_zone( $result );
		} catch ( Aws\Route53\Exception\Route53Exception $e ) {
			return new WP_Error( KPDNS_ERROR_CODE_GENERIC, $e->getAwsErrorMessage() );
		}
	}

	/*
	 *
	 * @return KPDNS_RESPONSE
	 *
	 */
	public function delete_zone( string $zone_id, array $args = array() ) {

		try {
		 	$changes = array();

			$result = $this->client->listResourceRecordSets(
				array(
					'HostedZoneId' => $zone_id,
				)
			);

			foreach ( $result['ResourceRecordSets'] as $ar53_record_set ) {
				if ( KPDNS_Record::TYPE_NS !== $ar53_record_set['Type'] &&
				     KPDNS_Record::TYPE_SOA !== $ar53_record_set['Type'] ) {
					$changes[] = array(
						'Action'            => 'DELETE',
						'ResourceRecordSet' => $ar53_record_set,
					);
				}
			}

			if ( ! empty( $changes ) ) {
				$this->client->changeResourceRecordSets(
					array(
						'HostedZoneId' => $zone_id,
						'ChangeBatch'  => array(
							'Changes' => $changes,
						)
					)
				);
			}

			$this->client->deleteHostedZone( array(
				'Id' => $zone_id,
			) );

			return true;
		} catch ( Aws\Route53\Exception\Route53Exception $e ) {
			return new WP_Error( KPDNS_ERROR_CODE_GENERIC, $e->getAwsErrorMessage() );
		}
	}

	public function edit_zone( KPDNS_Zone $zone, array $args = array() ) {
        /**
         * @see https://docs.aws.amazon.com/Route53/latest/APIReference/API_CreateHostedZone.html
         * Important: You can't convert a public hosted zone to a private hosted zone or vice versa. Instead, you must create a new hosted zone with the same name and create new resource record sets.
         */
		try {

		    $old_zone = $this->get_zone( $zone->get_id(), array() );

		    if ( is_wp_error( $old_zone ) ) {
		        return $old_zone;
            }

		    if ( $old_zone->get_description() !== $zone->get_description() ) {
                $this->client->updateHostedZoneComment( array(
                    'Id' => $zone->get_id(),
                    'Comment' => $zone->get_description(),
                ) );
            }

		    // TODO
            if ( $old_zone->is_private() !== $zone->is_private() ) {

                // get old zones's records

                // delete old zone

                // create new zone

                // create records
            }

			return true;
		} catch ( Aws\Route53\Exception\Route53Exception $e ) {
			return new WP_Error( KPDNS_ERROR_CODE_GENERIC, $e->getAwsErrorMessage() );
		}
	}

	/*
	 *
	 *
	 * @return array $zone
	 *
	 */
	public function get_zone( string $zone_id, array $args = array() ) {

		try {
			$result = $this->client->getHostedZone( array(
				'Id' => $zone_id,
			) );

			$zone = $this->_parse_zone( $result['HostedZone'] );

            if ( kpdns_is_primary_zone( $zone ) ) {
                $zone->set_primary( true );
            }

			return $zone;
		} catch ( Exception $e ) {
			return new WP_Error( KPDNS_ERROR_CODE_GENERIC, $e->getMessage() );
		}
	}

    public function get_zone_by_domain( string $domain, array $args = array() ) {

        try {
            $result = $this->client->listHostedZonesByName();

            if ( empty( $result['HostedZones'] ) ) {
                return null;
            }

            $found = false;
            foreach ( $result['HostedZones'] as $key => $hosted_zone ) {
                if ( $hosted_zone['Name'] === $domain ) {
                    $found = true;
                    break;
                }
            }

            if ( ! $found ) {
                return null;
            }

            $zone = $this->_parse_zone( $result['HostedZones'][ $key ] );

            if ( kpdns_is_primary_zone( $zone ) ) {
                $zone->set_primary( true );
            }

            $records = new KPDNS_Records_List();

            $result = $this->client->ListResourceRecordSets( array(
                'HostedZoneId' => $zone->get_id(),
            ) );

            foreach ( $result['ResourceRecordSets'] as $ar53_record_set ) {
                foreach ( $ar53_record_set['ResourceRecords'] as $ar53_record ) {
                    $record = new KPDNS_Record( $ar53_record_set['Name'], $ar53_record_set['Type'], $ar53_record['Value'], $ar53_record_set['TTL'] );
                    $records->add( $record );
                }
            }

            $zone->set_records( $records );

            return $zone;

        } catch ( Exception $e ) {
            return new WP_Error( KPDNS_ERROR_CODE_GENERIC, $e->getMessage() );
        }
    }

    /**
     * @return KPDNS_Zones_List
     */
	public function list_zones( array $args = array() ) {

		try {
			$result = $this->client->listHostedZones();
		} catch ( Exception $e ) {
			return new WP_Error( KPDNS_ERROR_CODE_GENERIC, $e->getMessage() );
		}

        $search_terms = KPDNS_Utils::get_search_terms( $args );
		$zones_list   = new KPDNS_Zones_List();

        //$custom_ns_array = $this->get_custom_ns_array();

		foreach ( $result['HostedZones'] as $ar53_zone ) {
		    $zone = $this->_parse_zone( $ar53_zone );
            // If there are search terms but don't match the zone name, don't add it to the list.
            if ( $search_terms && strpos( $zone->get_domain(), $search_terms ) === false ) {
                continue;
            }

            if ( kpdns_is_primary_zone( $zone ) ) {
                $zone->set_primary( true );
            }

            if ( isset( $custom_ns_array ) && in_array( $zone->get_domain(), $custom_ns_array ) ) {
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

	public function add_record( KPDNS_Record $record, string $zone_id, array $args = array() ) {
	    /*
       $existing_values = $this->_get_record_existing_values( $zone_id, $record->type, $record->name );
       $record_value    = KPDNS_Record::get_formatted_value( $record );
       if ( empty( $existing_values ) ) { // Create
           $changes = array(
               $this->_get_record_change_array('CREATE',  $record->type, $record->name, array( $record_value ), $record->ttl ),
           );
       } else { // Update
           $existing_values[] = $record_value;
           $changes = array(
               $this->_get_record_change_array('UPSERT',  $record->type, $record->name, $existing_values, $record->ttl ),
           );
       }
       */

        $values   = $this->_get_record_existing_values( $zone_id, $record->type, $record->name );
        $values[] = self::_get_formatted_value( $record );

        $changes = array(
            $this->_get_record_change_array('UPSERT',  $record->type, $record->name, $values, $record->ttl ),
        );

        return $this->request_changes( $zone_id, $changes );
	}

	public function delete_record( KPDNS_Record $record, string $zone_id, array $args = array() ) {

        $existing_values = $this->_get_record_existing_values( $zone_id, $record->type, $record->name );

        if ( empty( $existing_values ) || 1 === count( $existing_values ) ) { // Delete
            $changes = array(
                $this->_get_record_change_array('DELETE',  $record->type, $record->name, $existing_values, $record->ttl ),
            );
        } else { // Update

            if ( $record->type === KPDNS_Record::TYPE_NS && count( $existing_values ) === 1 ) {
                return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( 'There must be at least one NS record.', 'keypress-dns' ) );
            }

            $val = self::_get_formatted_value( $record );

            foreach ( $existing_values as $index => $existing_value ) {
                if ( $existing_value === $val ) {
                    unset( $existing_values[ $index ] );
                    break;
                }
            }

            $changes = array(
                $this->_get_record_change_array('UPSERT',  $record->type, $record->name, $existing_values, $record->ttl ),
            );
        }

        return $this->request_changes( $zone_id, $changes );
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

	public function edit_record( KPDNS_Record $record, KPDNS_Record $new_record, string $zone_id, array $args = array() ) {

        //$old_record_value = KPDNS_Record::get_formatted_value( $record );
        //$new_record_value = KPDNS_Record::get_formatted_value( $new_record );

        /*
	    if ( $record->name === $new_record->name ) {
            $values = $this->_get_record_existing_values( $zone_id, $record->type, $record->name );

            foreach ( $values as $index => $value ) {
                if ( $value === $old_record_value ) {
                    unset( $values[ $index ] );
                    $values[] = $new_record_value;
                    break;
                }
            }

            $changes = array(
                $this->_get_record_change_array('UPSERT',  $new_record->type, $new_record->name, $values, $new_record->ttl ),
            );

            return $this->request_changes( $zone_id, $changes );
        } else {

            // Add new record

            $result = $this->add_record( $new_record, $zone_id, $args );

            if ( is_wp_error( $result ) ) {
                return $result;
            }

            return $this->delete_record( $record, $zone_id, $args );

        }
        */

        $old_record_value = self::_get_formatted_value( $record );
        $new_record_value = self::_get_formatted_value( $new_record );

        $values = $this->_get_record_existing_values( $zone_id, $record->type, $record->name );

        foreach ( $values as $index => $value ) {
            if ( $value === $old_record_value ) {
                unset( $values[ $index ] );
                $values[] = $new_record_value;
                break;
            }
        }

        $changes = array(
            $this->_get_record_change_array('UPSERT',  $new_record->type, $new_record->name, $values, $new_record->ttl ),
        );

        return $this->request_changes( $zone_id, $changes );
	}

    public function list_records( string $zone_id, array $args = array()  ) {

        try {
            $result = $this->client->ListResourceRecordSets( array(
                'HostedZoneId' => $zone_id,
            ) );

        } catch ( Exception $e ) {
            return new WP_Error( KPDNS_ERROR_CODE_GENERIC, $e->getMessage() );
        }

        $records_list = new KPDNS_Records_List();

        foreach ( $result['ResourceRecordSets'] as $ar53_record_set ) {
            $type  = $ar53_record_set['Type'];
            $name  = $ar53_record_set['Name'];
            $ttl   = $ar53_record_set['TTL'];

            foreach ( $ar53_record_set['ResourceRecords'] as $ar53_record ) {

                $value = $ar53_record['Value'];

                $record = self::_parse_response_record( $type, $name, $ttl, $value );
                $records_list->add( $record );
            }
        }

        $records_list->sort();

        return $records_list;
    }

	//TODO
	public function check_credentials() {
		try {
			$this->list_zones();

			return KPDNS_OPTION_CREDENTIALS_VALID;
		} catch ( Exception $e ) {
			return KPDNS_OPTION_CREDENTIALS_NOT_VALID;
		}
	}


	public function list_name_servers( array $args = array() ) {
        $cached_request = $this->request_cache->get( 'list_name_servers', $args );
        if ( $cached_request ) {
            return $cached_request;
        } else {
            $response = $this->_get_name_servers( $args );
            $this->request_cache->add( 'list_name_servers', $args, $response );
            return $response;
        }
	}

	private function _get_name_servers( array $args = array() ) {
		$name_servers_list = new KPDNS_Name_Servers_List();

		try {
			$result = $this->client->listReusableDelegationSets();

            $search_terms      = KPDNS_Utils::get_search_terms( $args );

			foreach ( $result['DelegationSets'] as $ar53_rds ) {
				$name_server = $this->get_name_server( $ar53_rds['Id'] );

				if ( is_wp_error( $name_server ) ) {
				    continue;
                }

                if ( $search_terms && strpos( $name_server->get_domain(), $search_terms ) === false ) {
                    continue;
                }

				if ( $name_server /*&& ! empty( $name_server->get_ns_records() )*/ ) {
                    $name_servers_list->add( $name_server );
				}
			}
		} catch ( Exception $e ) {
			return new WP_Error( KPDNS_ERROR_CODE_GENERIC, $e->getMessage() );
		}

        $current_page = KPDNS_Utils::get_current_page( $args );

        $name_servers_list->maybe_paginate( $current_page );

		return $name_servers_list;
	}


	/**
	 * Returns a name server configuration.
	 *
	 * @author Martín Di Felice
	 * @param string $name_server_id Name server Id.
	 */
	public function get_name_server( string $name_server_id, array $args = array() ) {
		return $this->_get_name_server( $name_server_id, $args );
	}

	private function _get_name_server( string $name_server_id, array $args = array() ) {

		$name_server = null;

		try {
			$result = $this->client->listHostedZones(
				array(
					'DelegationSetId' => $name_server_id,
				)
			);

			if ( ! empty( $result['HostedZones'] ) ) {

			    $zone = $this->get_zone( $result['HostedZones'][0]['Id'] );
			    if ( is_wp_error( $zone ) ) {
			        $zone = null;
                }
                /*
                $zone    = $result['HostedZones'][0];


                $domain  = $zone['Name'];
                $zone_id = $zone['Id'];
                */

                $result = $this->client->listResourceRecordSets(
                    array(
                        'HostedZoneId' => $zone->get_id(),
                    )
                );

                $ns_records  = array();

                foreach ( $result['ResourceRecordSets'] as $ar53_record_set ) {
                    if ( KPDNS_Record::TYPE_NS === $ar53_record_set['Type'] ) {
                        foreach ( $ar53_record_set['ResourceRecords'] as $ar53_record ) {
                            $ns_records[] = $ar53_record['Value'];
                        }
                    }
                }

                return new KPDNS_Name_Server( $name_server_id, $zone->get_domain(), $ns_records, $zone->get_id() );

			} else {
				//$zone = null;
                return null;
			}

            //$name_server = new KPDNS_Name_Server( $name_server_id, $zone );

		} catch ( Exception $e ) {
			return new WP_Error( KPDNS_ERROR_CODE_GENERIC, $e->getMessage() );
		}
	}

	/*
	public function add_name_server( $name_servers ) {

		$client = $this->client;

		if ( is_wp_error( $client ) ) {
			return $client;
		}

		$zone_name = 'farruquito.com';

		$reusable_delegation_set_id = null;
		$zone_id                    = null;
		$name_servers_map           = array();

		try {
			$caller_reference = md5( serialize( $name_servers ) . ( time() / 300 ) );

			$result = $client->createReusableDelegationSet(
				array(
					'CallerReference' => $caller_reference,
				)
			);

			$reusable_delegation_set_id  = $result['DelegationSet']['Id'];
			$delegation_set_name_servers = $result['DelegationSet']['NameServers'];

			foreach ( $delegation_set_name_servers as $index => $ns_host ) {
				$name_server_map = array();

				$dns_records = dns_get_record( $ns_host );

				foreach ( $dns_records as $dns_record ) {
					switch ( $dns_record['type'] ) {
						case KPDNS_Record::TYPE_A:
						case KPDNS_Record::TYPE_A:
							$name_server_map[ $dns_record['type'] ] = $dns_record['ip'];

							break;
					}
				}

				if ( ! empty( $name_server_map ) && isset( $name_servers[ $index ] ) ) {
					$name_servers_map[ $name_servers[ $index ] ] = $name_server_map;
				}
			}

			$result = $client->createHostedZone(
				array(
					'CallerReference'  => $caller_reference,
					'DelegationSetId'  => $reusable_delegation_set_id,
					'HostedZoneConfig' => array(
						'Comment' => __( 'Zone created for your Custom NS. ', 'keypress-dns' ),
					),
					'Name'             => $zone_name,
				)
			);

			$zone_id = $result['HostedZone']['Id'];
			$changes = array();

			foreach ( $name_servers_map as $white_label_name_server => $ips ) {
				foreach ( $ips as $type => $ip ) {
					$changes[] = array(
						'Action'            => 'CREATE',
						'ResourceRecordSet' => array(
							'Name'            => $white_label_name_server,
							'ResourceRecords' => array(
								array(
									'Value' => $ip,
								),
							),
							'TTL'             => 60,
							'Type'            => $type,
						),
					);
				}
			}

			$this->_update_ns_and_soa_records( $zone_id, $zone_name, array_keys( $name_servers_map ), $changes );

			return $reusable_delegation_set_id;
		} catch ( Aws\Route53\Exception\Route53Exception $e ) {
			try {
				if ( $zone_id ) {
					$client->deleteHostedZone(
						array(
							'Id' => $zone_id,
						)
					);
				}

				if ( $reusable_delegation_set_id ) {
					$client->deleteReusableDelegationSet(
						array(
							'Id' => $reusable_delegation_set_id,
						)
					);
				}
			} catch ( Exception $e ) {
				return new WP_Error( KPDNS_ERROR_CODE_GENERIC, $e->getMessage() );
			}
			return new WP_Error( KPDNS_ERROR_CODE_GENERIC, $e->getAwsErrorMessage() );
		}
	}
	*/
	/**
	 * Adds a name server.
	 *
	 * @author Martín Di Felice
	 * @param string $domain
	 */
	public function add_name_server( string $domain, array $name_servers, array $args = array() ) {

        $zone = $this->get_zone_by_domain( $domain );
        $zone_id                    = isset( $zone ) && ! is_wp_error( $zone ) ? $zone->get_id() : null;
		$reusable_delegation_set_id = null;
		$name_servers_map           = array();

		try {
			$caller_reference = md5( $domain . ( time() / 300 ) );

			$result = $this->client->createReusableDelegationSet(
				array(
					'CallerReference' => $caller_reference,
				)
			);

			$reusable_delegation_set_id           = $result['DelegationSet']['Id'];
			$reusable_delegation_set_name_servers = $result['DelegationSet']['NameServers'];

			foreach ( $reusable_delegation_set_name_servers as $index => $name_server_domain ) {
				$name_server_map = array();

				$dns_records = dns_get_record( $name_server_domain );

				foreach ( $dns_records as $dns_record ) {
					switch ( $dns_record['type'] ) {
						case KPDNS_Record::TYPE_A:
						case KPDNS_Record::TYPE_A:
							$name_server_map[ $dns_record['type'] ] = $dns_record['ip'];

							break;
					}
				}

				if ( ! empty( $name_server_map ) ) {
                    $name_servers_map[ $name_servers[ $index ] ] = $name_server_map;
				}
			}

			if ( ! isset( $zone ) )  {
                $result = $this->client->createHostedZone(
                    array(
                        'CallerReference'  => $caller_reference,
                        'DelegationSetId'  => $reusable_delegation_set_id,
                        'HostedZoneConfig' => array(
                            'Comment' => sprintf(
                                __( 'Zone created for Custom NS %s. ', 'keypress-dns' ),
                                $domain
                            ),
                        ),
                        'Name'             => $domain,
                    )
                );

                $zone_id = $result['HostedZone']['Id'];
                $is_new_zone = true;
            }

			$changes = array();

			foreach ( $name_servers_map as $white_label_name_server => $ips ) {
				foreach ( $ips as $type => $ip ) {
					$changes[] = array(
						'Action'            => 'CREATE',
						'ResourceRecordSet' => array(
							'Name'            => $white_label_name_server,
							'ResourceRecords' => array(
								array(
									'Value' => $ip,
								),
							),
							'TTL'             => 60,
							'Type'            => $type,
						),
					);
				}
			}

			$this->_update_ns_and_soa_records( $zone_id, $domain, array_keys( $name_servers_map ), $changes );

			return $reusable_delegation_set_id;
		} catch ( Aws\Route53\Exception\Route53Exception $e ) {
			try {
				if ( isset( $is_new_zone ) && $is_new_zone ) {
					$this->client->deleteHostedZone(
						array(
							'Id' => $zone_id,
						)
					);
				}

				if ( $reusable_delegation_set_id ) {
					$this->client->deleteReusableDelegationSet(
						array(
							'Id' => $reusable_delegation_set_id,
						)
					);
				}
			} catch ( Exception $e ) {
				return new WP_Error( KPDNS_ERROR_CODE_GENERIC, $e->getMessage() );
			}
			return new WP_Error( KPDNS_ERROR_CODE_GENERIC, $e->getAwsErrorMessage() );
		}
	}

	/**
	 * Edits a name server.
	 *
	 * @author Martín Di Felice
	 * @param KPDNS_Name_Server $name_server Name server.
	 * @return KPDNS_RESPONSE Response.
	 */
	public function edit_name_server( KPDNS_Name_Server $name_server, array $args = array() ) {

		return array(
			'updated'        => true,
			'messages'       => array( __( 'Name server edited', 'keypress-dns' ) ),
			'name-server-id' => $name_server['id'],
		);
	}

	/**
	 * Deletes a name server.
	 *
	 * @author Martín Di Felice
	 * @param string $name_server_id Name server Id.
	 */
	public function delete_name_server( string $name_server_id, array $args = array() ) {

        $name_server = $this->_get_name_server( $name_server_id );

        if ( is_wp_error( $name_server ) ) {
            return $name_server;
        }

        /*
        $zone = $name_server->get_zone();

        if ( isset( $zone ) ) {
            $delete_zone = $this->delete_zone( $zone->get_id() );

            if ( is_wp_error( $delete_zone ) ) {
                return $delete_zone;
            }
        }
        */

		try {
			$this->client->deleteReusableDelegationSet( array(
				'Id' => $name_server_id,
			) );

			return true;
		} catch ( Aws\Route53\Exception\Route53Exception $e ) {
			return new WP_Error( KPDNS_ERROR_CODE_GENERIC, $e->getAwsErrorMessage() );
		}
	}

    private function _get_record_existing_values( $zone_id, $record_type, $record_name ) {
        $existing_values = array();

        try {
            $result = $this->client->ListResourceRecordSets( array(
                'HostedZoneId' => $zone_id,
            ) );

        } catch ( Exception $e ) {
            return $existing_values;
        }

        foreach ( $result['ResourceRecordSets'] as $ar53_record_set ) {
            if ( $ar53_record_set['Type'] === $record_type && rtrim( $ar53_record_set['Name'], '.' ) === rtrim( $record_name, '.' ) ) {
                foreach ( $ar53_record_set['ResourceRecords'] as $ar53_record ) {
                    $existing_values[] = $ar53_record['Value'];
                }
                break;
            }
        }

        return $existing_values;
    }

	private function _change_record( string $action, string $zone_id, KPDNS_Record $record, KPDNS_Record $old_record = null ) {

		switch ( $action ) {
			case 'DELETE':
			case 'CREATE':
				$changes = array(
					$this->_get_record_change_array( $record, $action ),
				);
				break;
			case 'UPSERT':
				if ( $record->get_name() === $old_record->get_name() ) {
					$changes = array(
						$this->_get_record_change_array( $record, $action ),
					);
				} else {
					$changes = array(
						$this->_get_record_change_array( $old_record, 'DELETE' ),
						$this->_get_record_change_array( $record, 'CREATE' ),
					);
				}
				break;

            default:
                wp_die( __( 'Invalid action: ', 'keypress-dns' ) . $action );
		}

		return $this->request_changes( $zone_id, $changes );
	}

    private function request_changes( string $zone_id, array $changes ) {
        try {
            $this->client->changeResourceRecordSets( array(
                'HostedZoneId' => $zone_id,
                'ChangeBatch' => array(
                    'Changes' => isset( $changes ) ? $changes : array(),
                ),
            ) );

        } catch ( Aws\Route53\Exception\Route53Exception $e ) {
            return new WP_Error( KPDNS_ERROR_CODE_GENERIC, $e->getAwsErrorMessage() );
        }

        return true;
    }

	/*
	private function _get_record_values_array( KPDNS_Record $record ) {
		$values = array();

		foreach ( $record->get_values() as $value ) {
			$values[] = array(
				'Value' => $value,
			);
		}

		return $values;
	}
	*/

	private function _get_record_value( KPDNS_Record $record ) {
        if ($record instanceof KPDNS_Record_CAA ) {
            $value = "{$record->flag} {$record->tag} \"{$record->value}\"";
        } elseif ( $record instanceof KPDNS_Record_MX ) {
            $value = "{$record->priority} {$record->value}";
        } elseif( $record instanceof KPDNS_Record_SOA ) {
            $value = "{$record->name_server} {$record->admin_email} {$record->serial_number} {$record->refresh_time} {$record->retry_interval} {$record->time_to_transfer}";
        } elseif( $record instanceof KPDNS_Record_SRV ) {
            $value = "{$record->priority} {$record->weight} {$record->port} {$record->value}";
        } else {
            $value = $record->value;
        }
        return $value;
    }

	private function _get_record_change_array( string $action, string $record_type, string $record_name, array $record_values, int $record_ttl ) {

	    $resource_records = array();

	    foreach ( $record_values as $value ) {
            $resource_records[] = array( 'Value' => $value );
        }

		$array = array(
			'Action' => $action,
			'ResourceRecordSet' => array(
				'Name' => $record_name,
				'ResourceRecords' => $resource_records,
				'TTL' => $record_ttl,
				'Type' => $record_type,
			),
		);

	    return $array;
	}

	private function _parse_zone( $ar53_zone ) {

	    $zone = array(
	        'id' => $ar53_zone['Id'],
            'domain' => $ar53_zone['Name'],
            'description' => isset( $ar53_zone['Config']['Comment'] ) ? $ar53_zone['Config']['Comment'] : '',
            'private' => isset( $ar53_zone['Config']['PrivateZone'] ) ? $ar53_zone['Config']['PrivateZone'] : 'false',
        );

		return $this->build_zone( $zone );
	}

	private function _get_zone_name_server( $zone_id ) {
		$name_server = null;
		$client = $this->client;

		try {
			$result = $client->getHostedZone( array(
				'Id' => $zone_id,
			) );

			$name_servers = $this->_get_name_servers();

			foreach ( $name_servers as $possible_name_server ) {
				if ( isset( $result['DelegationSet']['Id'] ) ) {
					$delegation_set_id = $result['DelegationSet']['Id'];

					if ( $delegation_set_id === $possible_name_server['id'] ) {
						$name_server = $possible_name_server;

						break;
					}
				}
			}
			return $name_server;
		} catch ( Exception $e ) {
			return new WP_Error( KPDNS_ERROR_CODE_GENERIC, $e->getMessage() );
		}
	}

	private function _update_ns_and_soa_records( $zone_id, $domain, $name_servers, $other_changes = null ) {
		$changes = $other_changes ? $other_changes : array();

		if ( ! empty( $name_servers ) ) {
			$result = $this->client->listResourceRecordSets(
				array(
					'HostedZoneId' => $zone_id,
				)
			);

			foreach ( $result['ResourceRecordSets'] as $ar53_record_set ) {
				if ( KPDNS_Record::TYPE_SOA === $ar53_record_set['Type'] && ! empty( $ar53_record_set['ResourceRecords'] ) ) {
					$soa_record = preg_replace(
						'/^[^\s]+/',
						$name_servers[0],
						$ar53_record_set['ResourceRecords'][0]['Value']
					);

					$changes[] = array(
						'Action'            => 'UPSERT',
						'ResourceRecordSet' => array(
							'Name'            => $domain,
							'ResourceRecords' => array(
								array(
									'Value' => $soa_record,
								),
							),
							'TTL'             => 60,
							'Type'            => KPDNS_Record::TYPE_SOA,
						),
					);

					break;
				}
			}

			$changes[] = array(
				'Action'            => 'UPSERT',
				'ResourceRecordSet' => array(
					'Name'            => $domain,
					'ResourceRecords' => array_map(
						function( $name_server ) {
							return array(
								'Value' => $name_server,
							);
						},
						$name_servers
					),
					'TTL'             => 60,
					'Type'            => KPDNS_Record::TYPE_NS,
				),
			);
		}

		if ( ! empty( $changes ) ) {
			$this->client->changeResourceRecordSets(
				array(
					'HostedZoneId' => $zone_id,
					'ChangeBatch'  => array(
						'Changes' => $changes,
					)
				)
			);
		}
	}

	private function _get_client() {

		$credentials = $this->credentials;

		if ( is_wp_error( $credentials ) ) {
			return $credentials;
		}

		$access_key_id     = KPDNS_Crypto::decrypt( $credentials->get_access_key_id(), hex2bin( KPDNS_ENCRYPTION_KEY ) );
		$secret_access_key = KPDNS_Crypto::decrypt( $credentials->get_secret_access_key(), hex2bin( KPDNS_ENCRYPTION_KEY ) );

		if ( ! $access_key_id || ! $secret_access_key ) {
			return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( 'Invalid encryption key. Please go to DNS Manager/Settings/DNS Provider, click on the "Create New Encryption Key" button and follow the instructions. <a href="' . add_query_arg( array( 'page' => KPDNS_PAGE_SETTINGS ), KPDNS_Page::get_admin_url() ) . '">Go to DNS Manager settings</a>', 'keypress-dns' ) );
		}

		//$credentials_json_decoded  = json_decode( $credentials_json_decrypted, true );

		$cr = array(
			'region'  => 'global',
			'version' => 'latest',
			'credentials' => array(
				'key'    => $access_key_id,
				'secret' => $secret_access_key
			)
		);

		try {
			$client = new Aws\Route53\Route53Client( $cr );
		} catch ( Exception $e ) {
			return new WP_Error( KPDNS_ERROR_CODE_GENERIC, $e->getMessage() );
		}

		return $client;
	}

    /**
     * @param array $zone
     * @return KPDNS_Zone|null
     */
    public function build_zone( array $zone ): ?KPDNS_Zone {

        $id          = isset( $zone['id'] ) ? $zone['id'] : '';
        $domain      = isset( $zone['domain'] ) ? $zone['domain'] : '';
        $description = isset( $zone['description'] ) ? $zone['description'] : '';
        $private     = isset( $zone['private'] ) ? $zone['private'] : false;

        return new KPDNS_AR53_Zone( $id, $domain, $description, $private );
    }

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
                $rdata[ KPDNS_Record::RDATA_KEY_SERVICE ]  = $values[0];
                $rdata[ KPDNS_Record::RDATA_KEY_PROTOCOL ] = $values[1];
                $rdata[ KPDNS_Record::RDATA_KEY_PRIORITY ] = $values[2];
                $rdata[ KPDNS_Record::RDATA_KEY_WEIGHT ]   = $values[3];
                $rdata[ KPDNS_Record::RDATA_KEY_PORT ]     = $values[4];
                $rdata[ KPDNS_Record::RDATA_KEY_HOST ]     = $values[5];
                break;
        }

        $name = str_replace( '\052', '*', $name );

        return new KPDNS_Record( $type, $name, $rdata, $ttl, $meta );;
    }
}
