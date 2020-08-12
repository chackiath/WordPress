<?php

abstract class KPDNS_API {
	public function get_credentials() {
		if ( ! defined( 'KPDNS_ENCRYPTION_KEY') ) {
			return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( 'Undefined encryption key. Please go to DNS Manager/Settings/DNS Provider, click on the "Create New Encryption Key" button and follow the instructions. <a href="' . add_query_arg( array( 'page' => KPDNS_PAGE_SETTINGS ), KPDNS_Page::get_admin_url() ) . '">Go to DNS Manager settings</a>', 'keypress-dns' ) );
		}

		$provider = KPDNS_Model::get_provider();

		if ( ! isset( $provider ) ) {
			return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( 'Undefined DNS provider. Please go to DNS Manager/Settings/DNS Provider and select a Managed DNS provider. <a href="' . add_query_arg( array( 'page' => KPDNS_PAGE_SETTINGS ), KPDNS_Page::get_admin_url() ) . '">Go to DNS Manager settings</a>.', 'keypress-dns' ) );
		}

		$credentials = $provider->get_credentials();

		if ( ! isset( $credentials ) ) {
			return new WP_Error( KPDNS_ERROR_CODE_GENERIC, __( 'Undefined credentials. Please go to DNS Manager/Settings/DNS Provider and enter your Managed DNS provider\'s credentials. <a href="' . add_query_arg( array( 'page' => KPDNS_PAGE_SETTINGS ), KPDNS_Page::get_admin_url() ) . '">Go to DNS Manager settings</a>.', 'keypress-dns' ) );
		}

		return $credentials;
	}

    public function build_record( array $record ): ?KPDNS_Record {

        if ( empty( $record ) ) {
            return null;
        }

        $name   = isset( $record['name'] ) ? rtrim( $record['name'], '.' ) : '';
        $type   = isset( $record['type'] ) ? $record['type'] : '';
        $ttl    = isset( $record['ttl'] ) ? $record['ttl'] : KPDNS_Record::DEFAULT_TTL_VALUE;

        if ( isset( $record['ttl-unit'] ) ) {
            $ttl = KPDNS_Utils::ttl_to_seconds( $ttl, $record['ttl-unit'] );
        }

        $rdata = isset( $record['rdata'] ) ? $record['rdata'] : KPDNS_Record::build_rdata( $record );

        return new KPDNS_Record( $type, $name, $rdata, $ttl );
    }

    public function build_name_server( array $name_server ): ?KPDNS_Name_Server {
        if ( empty( $name_server ) ) {
            return null;
        }

        $id         = isset( $name_server['id'] ) ? $name_server['id'] : '';
        $domain     = isset( $name_server['domain'] ) ? $name_server['domain'] : '';
        $ns         = isset( $name_server['ns'] ) ? $name_server['ns'] : '';
        $zone_id    = isset( $name_server['zone-id'] ) ? $name_server['zone-id'] : '';

        return new KPDNS_Name_Server( $id, $domain, $ns, $zone_id );
    }

    /**
     * Returns an associative array with all the zones.
     *
     * @param string $key
     * @param array $args
     * @return array|null
     */
    public function get_zones_map( string $key = 'domain', array $args = array() ) {

        // Valid keys.
        $keys = [ 'id', 'domain' ];
        if ( ! in_array( $key, $keys ) ) {
            return null;
        }

        $args['page'] = 'all';

        $all_zones = $this->list_zones( $args );

        if ( is_wp_error( $all_zones ) ) {
            return null;
        }

        $map = array();

        foreach ( $all_zones as $zone ) {

            switch ( $key ) {
                case 'id':
                    $map[ $zone->get_id() ] = $zone;
                    break;

                case 'domain':
                    $map[ $zone->get_domain() ] = $zone;
                    break;
            }
        }

        return $map;
    }

    public function find_record_in_list( KPDNS_Record $the_record, KPDNS_Records_List $list, $compare_args = array( 'type' => true, 'name' => true, 'rdata' => true, 'ttl' => true ) ) {

        foreach ( $list as $record ) {

            $found = false;

            if ( isset( $compare_args['type'] ) && $compare_args['type'] ) {
                if ( $record->type === $the_record->type ) {
                    $found = true;
                } else {
                    continue;
                }
            }

            if ( isset( $compare_args['name'] ) && $compare_args['name'] ) {
                $name1 = strpos( $record->name, '.' ) !== false ? substr( $record->name, 0, strpos( $record->name, '.' ) ) : $record->name;
                $name2 = strpos( $the_record->name, '.' ) !== false ? substr( $the_record->name, 0, strpos( $the_record->name, '.' ) ) : $the_record->name;

                if ( $name1 === $name2 ) {
                    $found = true;
                } else {
                    continue;
                }
            }

            if ( isset( $compare_args['rdata'] ) && $compare_args['rdata'] ) {
                if ( $record->rdata === $the_record->rdata ) {
                    $found = true;
                } else {
                    continue;
                }
            }

            if ( isset( $compare_args['ttl'] ) && $compare_args['ttl'] ) {
                if ( $record->ttl === $the_record->ttl ) {
                    $found = true;
                } else {
                    continue;
                }
            }

            if ( $found ) {
                return $record;
            }

        }

        return false;
    }

    public function get_ns_records_list( $zone_id ) {

        $ns_records_list = new KPDNS_Records_List();

        $records = $this->list_records( $zone_id );

        if ( is_wp_error( $records ) ) {
            return $records;
        }

        foreach ( $records as $record ) {
            if ( $record->get_type() === KPDNS_Record::TYPE_NS ) {
                $ns_records_list->add( $record );
            }
        }

        return $ns_records_list;
    }

    protected function maybe_add_name_server_records( $zone, $existing_records, $name_servers ) {
        // Create A and NS records for the Name Servers.
        $zone_ns = $zone->get_name_servers();
        $zone_id = $zone->get_id();
        $domain  = $zone->get_domain();

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
                    isset( $record->get_rdata()[ KPDNS_Record::RDATA_KEY_VALUE ] ) && $record->get_rdata()[ KPDNS_Record::RDATA_KEY_VALUE ] === $zone_ns[ $index ]->ipv4
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
                $ip = $zone_ns[ $index ]->ipv4;
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

        return true;
    }
}
