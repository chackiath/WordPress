<?php

/**
 * Class KPDNS_New_Provider_API
 *
 * Template for new providers.
 *
 */

class KPDNS_New_Provider_API extends KPDNS_API implements KPDNS_API_Imp {

	public function __construct() {

		$credentials = $this->get_credentials();

		if ( is_wp_error( $credentials ) ) {
			return $credentials;
		}

		require_once KPDNS_PLUGIN_DIR . 'vendor/autoload.php';

		$auth_field_1 = KPDNS_Crypto::decrypt( $credentials->get_auth_field_1(), hex2bin( KPDNS_ENCRYPTION_KEY ) );
		$auth_field_2 = KPDNS_Crypto::decrypt( $credentials->get_auth_field_2(), hex2bin( KPDNS_ENCRYPTION_KEY ) );

	}


    /**
     * Adds a new zone.
     *
     * @param KPDNS_Zone $zone
     * @return KPDNS_Zone|WP_Error
     */
	public function add_zone( KPDNS_Zone $zone, ?KPDNS_Name_Server $name_server ) {

		try {
		    // TODO Should return the id of the created zone.
			return KPDNS_Mock_Factory::generate_zone_id();

		} catch ( \Cloudflare\API\Adapter\ResponseException $e ) {
			return new WP_Error( KPDNS_ERROR_CODE_GENERIC, $e->getMessage() );
		}
	}


    /**
     * Deletes a DNS zone.
     *
     * @param string $zone_id
     * @return bool|WP_Error
     */
	public function delete_zone( string $zone_id ) {

		try {
            // Return true if there are no errors.
			return true;
        } catch ( \Cloudflare\API\Adapter\ResponseException $e ) {
            return new WP_Error( KPDNS_ERROR_CODE_GENERIC, $e->getMessage() );
        }
	}


    /**
     * Edits a DNS zone.
     *
     * @param KPDNS_Zone $zone
     * @return bool|WP_Error
     */
	public function edit_zone( KPDNS_Zone $zone ) {
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
	public function get_zone( string $id ) {
		try {
		    // TODO should return the zone with the given id.
			return KPDNS_Mock_Factory::get_zone( $id );
        } catch ( \Cloudflare\API\Adapter\ResponseException $e ) {
            return new WP_Error( KPDNS_ERROR_CODE_GENERIC, $e->getMessage() );
        }
	}


    /**
     * Gets a zone by domain name.
     *
     * @param string $domain
     * @return KPDNS_Zone|WP_Error
     */
    public function get_zone_by_domain( string $domain ) {
        try {
            // TODO should return the zone with the given domain.
            return KPDNS_Mock_Factory::get_zone( '', $domain );

        } catch ( \Cloudflare\API\Adapter\ResponseException $e ) {
            return new WP_Error( KPDNS_ERROR_CODE_GENERIC, $e->getMessage() );
        }
    }


    /**
     * Gets a list of zones.
     *
     * @return KPDNS_Zones_List|WP_Error
     */
	public function list_zones() {

        try {
            return KPDNS_Mock_Factory::get_zones_list();
        } catch ( \Cloudflare\API\Adapter\ResponseException $e ) {
            return new WP_Error( KPDNS_ERROR_CODE_GENERIC, $e->getMessage() );
        }
	}


    /**
     * Adds a record to a zone.
     *
     * @param KPDNS_Record $record
     * @param string $zone_id
     * @return bool|WP_Error
     */
	public function add_record( KPDNS_Record $record, string $zone_id ) {
        try {

            return true;
        } catch ( \Cloudflare\API\Adapter\ResponseException $e ) {
            return new WP_Error( KPDNS_ERROR_CODE_GENERIC, $e->getMessage() );
        }
	}


    /**
     * Deletes a record from a zone.
     *
     * @param KPDNS_Record $record
     * @param string $zone_id
     * @return KPDNS_Zone|WP_Error
     */
	public function delete_record( KPDNS_Record $record, string $zone_id ) {
        try {

            // TODO should return the zone with id=$zone_id.
            return KPDNS_Mock_Factory::get_zone( $zone_id );
        } catch ( \Cloudflare\API\Adapter\ResponseException $e ) {
            return new WP_Error( KPDNS_ERROR_CODE_GENERIC, $e->getMessage() );
        }
	}


    /**
     * Edits a record by overriding the old record values with the new record values.
     *
     * @param KPDNS_Record $record
     * @param KPDNS_Record $new_record
     * @param string $zone_id
     * @return KPDNS_Zone|WP_Error
     */
	public function edit_record( KPDNS_Record $record, KPDNS_Record $new_record, string $zone_id ) {
        try {

            // TODO should return the zone with id=$zone_id.
            return KPDNS_Mock_Factory::get_zone( $zone_id );
        } catch ( \Cloudflare\API\Adapter\ResponseException $e ) {
            return new WP_Error( KPDNS_ERROR_CODE_GENERIC, $e->getMessage() );
        }
	}


    /**
     * Lists custom name servers.
     *
     * @return KPDNS_Name_Servers_List|WP_Error
     */
	public function list_name_servers() {
        // TODO DO NOT IMPLENT!!!
        try {
            return KPDNS_Mock_Factory::get_name_servers_list();
        } catch ( \Cloudflare\API\Adapter\ResponseException $e ) {
            return new WP_Error( KPDNS_ERROR_CODE_GENERIC, $e->getMessage() );
        }
	}


    /**
     * Gets a custom name server by id.
     *
     * @param string $name_server_id
     * @return KPDNS_Name_Server|WP_Error
     */
	public function get_name_server( string $name_server_id ) {
        // TODO DO NOT IMPLENT!!!
        try {
            return KPDNS_Mock_Factory::get_name_server( $name_server_id );
        } catch ( \Cloudflare\API\Adapter\ResponseException $e ) {
            return new WP_Error( KPDNS_ERROR_CODE_GENERIC, $e->getMessage() );
        }
	}


    /**
     * Adds a new custom name server.
     * @param string $domain
     * @param array $name_servers
     * @return string|WP_Error
     */
	public function add_name_server( string $domain, array $name_servers ) {
        // TODO DO NOT IMPLENT!!!
        try {
            return KPDNS_Mock_Factory::generate_name_server_id();
        } catch ( \Cloudflare\API\Adapter\ResponseException $e ) {
            return new WP_Error( KPDNS_ERROR_CODE_GENERIC, $e->getMessage() );
        }
	}


    /**
     * Edits a custom name server.
     *
     * @param KPDNS_Name_Server $name_server
     * @return KPDNS_Name_Server|WP_Error
     */
	public function edit_name_server( KPDNS_Name_Server $name_server ) {
        // TODO DO NOT IMPLENT!!!
        try {
            return $name_server;
        } catch ( \Cloudflare\API\Adapter\ResponseException $e ) {
            return new WP_Error( KPDNS_ERROR_CODE_GENERIC, $e->getMessage() );
        }
	}


    /**
     * @param string $name_server_id
     * @return bool|WP_Error
     */
	public function delete_name_server( string $name_server_id ) {
        // TODO DO NOT IMPLENT!!!
        try {
            return true;
        } catch ( \Cloudflare\API\Adapter\ResponseException $e ) {
            return new WP_Error( KPDNS_ERROR_CODE_GENERIC, $e->getMessage() );
        }
	}


    /**
     * @param $feature_id
     * @return bool
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

}
