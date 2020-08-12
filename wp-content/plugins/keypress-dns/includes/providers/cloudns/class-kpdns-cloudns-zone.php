<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'KPDNS_ClouDNS_Zone' ) ) {

    /**
     * Class KPDNS_ClouDNS_Zone
     *
     * @since 1.1
     */
	class KPDNS_ClouDNS_Zone extends KPDNS_Zone {

	    const ZONE_TYPE_MASTER          = 'master';
	    const ZONE_TYPE_SLAVE           = 'slave';
	    const ZONE_TYPE_PARKED          = 'parked';
	    const ZONE_TYPE_GEODNS          = 'geodns';

        const DEFAULT_MASTER_ZONE_VALUE = 'domain';

	    const ZONE_STATUS_ACTIVE        = 1;
	    const ZONE_STATUS_INACTIVE      = 0;

        /**
         * Zone type: master/slave/parked/geodns.
         *
         * @var string
         */
	    private $type;

        /**
         * Master zone. If the zone is master, $master_zone = "domain".
         *
         * @var string
         */
	    private $master;

        /**
         * Status. 1 = active, 0 = inactive.
         *
         * @var int
         */
	    private $status;

	    /*
		public function __construct( string $id, string $domain_name, string $description, ?KPDNS_Records_List $records = null ) {
			parent::__construct( $id, $domain_name, $description, $records );
		}
	    */

        /**
         * KPDNS_ClouDNS_Zone constructor.
         *
         * @param string $domain
         * @param string $type
         * @param string $master
         * @param int $status
         */
	    public function __construct( string $domain, string $type = self::ZONE_TYPE_MASTER, string $master = self::DEFAULT_MASTER_ZONE_VALUE, int $status = self::ZONE_STATUS_ACTIVE ) {

	        // ClouDNS doesn't identify zones by id so we use the domain.
	        $id = $domain;

	        parent::__construct( $id, $domain );

	        $this->type   = $type;
	        $this->master = $master;
	        $this->status = $status;
        }

        /**
         * @return string
         */
        public function get_type() {
            return $this->type;
        }

        /**
         * @param string $type
         */
        public function set_type( string $type): void {
            $this->type = $type;
        }

        /**
         * @return string
         */
        public function get_master() {
            return $this->master;
        }

        /**
         * @param string $master
         */
        public function set_master_zone( string $master ): void {
            $this->master = $master;
        }

        /**
         * @return int
         */
        public function get_status() {
            return $this->status;
        }

        /**
         * @param int $status
         */
        public function set_status( int $status): void {
            $this->status = $status;
        }

        public static function get_zone_types() {
            return array(
                self::ZONE_TYPE_MASTER => array(
                    'id' => self::ZONE_TYPE_MASTER,
                    'label' => __( 'Master', 'keypress-dns' ),
                ),
                self::ZONE_TYPE_SLAVE => array(
                    'id' => self::ZONE_TYPE_SLAVE,
                    'label' => __( 'Slave', 'keypress-dns' ),
                ),
                self::ZONE_TYPE_PARKED => array(
                    'id' => self::ZONE_TYPE_PARKED,
                    'label' => __( 'Parked', 'keypress-dns' ),
                ),
                self::ZONE_TYPE_GEODNS => array(
                    'id' => self::ZONE_TYPE_GEODNS,
                    'label' => __( 'Geodns', 'keypress-dns' ),
                ),
            );
        }

        public static function get_zone_statuses() {
            return array(
                self::ZONE_STATUS_ACTIVE => array(
                    'id' => self::ZONE_STATUS_ACTIVE,
                    'label' => __( 'Active', 'keypress-dns' ),
                ),
                self::ZONE_STATUS_INACTIVE => array(
                    'id' => self::ZONE_STATUS_INACTIVE,
                    'label' => __( 'Inactive', 'keypress-dns' ),
                ),
            );
        }

        public function to_array(): array{
            $array = parent::to_array();
            $array['type']   = $this->type;
            $array['master'] = $this->master;
            $array['status'] = $this->status;
            return $array;
        }
    }
}
