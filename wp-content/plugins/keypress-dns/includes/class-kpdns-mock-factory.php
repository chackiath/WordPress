<?php

if ( ! class_exists( 'KPDNS_Mock_Factory' ) ) {

	final class KPDNS_Mock_Factory {

	    public static function get_zone( string $id = '', string $domain = '', string $description = '', KPDNS_Records_List $records = null ): KPDNS_Zone {

		    if ( '' === $id ) {
		        $id = uniqid();
            }

            if ( '' === $domain ) {
                $domain = self::generate_random_domain();
            }

		    $mockup_zone = new KPDNS_Zone( $id, $domain );

            return $mockup_zone;
        }

        public static function get_zones_list( array $zones = null ): KPDNS_Zones_List {

            $zones_list = new  KPDNS_Zones_List();

            if ( ! isset( $zones ) ) {
                $zones = array(
                    self::get_zone(),
                    self::get_zone(),
                    self::get_zone(),
                    self::get_zone(),
                    self::get_zone(),
                );
            }

            $total_items = 0;
            foreach ( $zones as $zone ) {
                $zones_list->add( $zone );
                $total_items++;
            }
            $zones_list->set_total_items( $total_items );

            return $zones_list;
        }

        public static function get_record( string $name = '', string $type = '', string $value = '', int $ttl = 60 ): KPDNS_Record {
	        if ( '' === $type ) {
	            $type = KPDNS_Record::TYPE_A;
            }

	        if ( '' === $name ) {
	            $name = self::generate_random_domain();
            }

            return new KPDNS_Record( $type, $name, array(), $ttl );
        }

        public static function get_records_list(  array $records = null  ): KPDNS_Records_List {
	        $records_list = new  KPDNS_Records_List();

            if ( ! isset( $records ) ) {
                $domain = self::generate_random_domain();

                $records = array(
                    self::get_record( $domain, KPDNS_Record::TYPE_A, self::generate_random_ip() ),
                    self::get_record( "www.{$domain}", KPDNS_Record::TYPE_A, $domain ),
                    self::get_record( "ns1.{$domain}", KPDNS_Record::TYPE_NS, self::generate_random_ip() ),
                    self::get_record( "ns1.{$domain}", KPDNS_Record::TYPE_NS, self::generate_random_ip() ),
                );
            }

            foreach ( $records as $record ) {
                $records_list->add( $record );
            }

            return $records_list;
        }

        public static function get_name_server( string $id  = '', KPDNS_Zone $zone = null ): KPDNS_Name_Server {
            if ( '' === $id ) {
                $id = self::generate_zone_id();
            }

            if ( ! isset( $zone ) ) {
                $zone = self::get_zone();
            }

            return new KPDNS_Name_Server( $id, $zone );
        }

        public static function get_name_servers_list(  array $name_servers = null ): KPDNS_Name_Servers_List {
            $name_servers_list = new  KPDNS_Name_Servers_List();

            if ( ! isset( $name_servers ) ) {
                $domain = self::generate_random_domain();

                $name_servers = array(
                    self::get_name_server(),
                    self::get_name_server(),
                    self::get_name_server(),
                );
            }

            foreach ( $name_servers as $name_server ) {
                $name_servers_list->add( $name_server );
            }

            return $name_servers_list;
        }

        public static function generate_zone_id() {
	        return 'zone_' . uniqid();
        }

        public static function generate_name_server_id() {
            return 'name_server_' . uniqid();
        }

        public static function generate_random_ip() {
	        //return "".mt_rand(0,255).".".mt_rand(0,255).".".mt_rand(0,255).".".mt_rand(0,255);
            return mt_rand(0,255) . '.' . mt_rand(0,255) . '.' . mt_rand(0,255) . '.' . mt_rand(0,255);
        }

        public static function generate_random_domain(): string {
            $characters = '0123456789abcdefghijklmnopqrstuvwxyz';
            $charactersLength = strlen( $characters );
            $randomString = '';
            $length = 10;
            for ($i = 0; $i < $length; $i++) {
                $randomString .= $characters[rand(0, $charactersLength - 1)];
            }
            return $randomString . '.com';
        }
	}
}