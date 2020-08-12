<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'KPDNS_Name_Server' ) ) {

	class KPDNS_Name_Server implements KPDNS_Arrayable {

        protected $id;
        protected $domain;
        protected $ns;
        protected $zone_id;

		public function __construct( string $id, string $domain = '', array $ns = array(), string $zone_id = '' ) {
		    $this->id       = $id;
		    $this->domain   = $domain;
            $this->ns       = $ns;
		    $this->zone_id  = $zone_id;
		}

        /**
         * @return string
         */
        public function get_id(): string {
            return $this->id;
        }

        /**
         * @param string $id
         */
        public function set_id( string $id ): void {
            $this->id = $id;
        }

        /**
         * @return string
         */
        public function get_domain(): string {
            return $this->domain;
        }

        /**
         * @param string $domain
         */
        public function set_domain( string $domain ): void {
            $this->domain = $domain;
        }

        /*
        public function get_zone() {
            return $this->zone;
        }


        public function set_zone( KPDNS_Zone $zone ): void {
            $this->zone = $zone;
        }
        */

        /**
         * @return string
         */
        public function get_zone_id(): string {
            return $this->zone_id;
        }

        /**
         * @param string $zone_id
         */
        public function set_zone_id( string $zone_id ): void {
            $this->zone_id = $zone_id;
        }

        /**
         * @return array
         */
        public function get_ns(): array {
            return $this->ns;
        }

        /**
         * @param array $ns
         */
        public function set_ns( array $ns ): void {
            $this->ns = $ns;
        }

        /**
         * @return array
         */
        public function to_array(): array {
            return array(
                'id'         => $this->id,
                'domain'     => $this->domain,
                'ns'         => $this->ns,
                'zone-id'    => $this->zone_id,
                //'zone'       => $this->zone->to_array(),
            );
        }
	}
}