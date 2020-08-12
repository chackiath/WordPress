<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'KPDNS_ClouDNS_Name_Server' ) ) {

	class KPDNS_ClouDNS_Name_Server extends KPDNS_Name_Server {

        const NS_TYPE_PREMIUM = 'premium';
        const NS_TYPE_FREE    = 'free';

        private $type;

        public function __construct( string $id, ?KPDNS_Zone $zone, $type = self::NS_TYPE_FREE ) {
            parent::__construct($id, $zone);
        }

        /**
         * @return string
         */
        public function get_type(): string {
            return $this->type;
        }

        /**
         * @param string $type
         */
        public function set_type( string $type ): void{
            $this->type = $type;
        }

        public static function get_name_server_types() {
            return array(
                self::NS_TYPE_PREMIUM => array(
                    'id' => self::NS_TYPE_PREMIUM,
                    'label' => __( 'Premium', 'keypress-dns' ),
                ),
                self::NS_TYPE_FREE => array(
                    'id' => self::NS_TYPE_FREE,
                    'label' => __( 'Free', 'keypress-dns' ),
                ),
            );
        }
    }
}
