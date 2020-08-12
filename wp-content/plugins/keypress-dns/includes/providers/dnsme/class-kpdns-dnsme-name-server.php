<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'KPDNS_DNSME_Name_Server' ) ) {

	class KPDNS_DNSME_Name_Server extends KPDNS_Name_Server {

        private $default;

		public function __construct( string $id, string $domain = '', array $ns = array(), string $zone_id = '', bool $default = false ) {
		    parent::__construct( $id, $domain, $ns, $zone_id );
		    $this->default = $default;
		}

        /**
         * @return bool
         */
        public function is_default(): bool {
            return $this->default;
        }

        /**
         * @param bool $default
         */
        public function set_default( bool $default ): void {
            $this->default = $default;
        }

        /**
         * @return array
         */
        public function to_array(): array {
            $array = parent::to_array();
            $array['default'] = $this->default;
            return $array;
        }
	}
}