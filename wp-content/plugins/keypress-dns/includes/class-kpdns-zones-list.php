<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'KPDNS_Zones_List' ) ) {

	final class KPDNS_Zones_List extends KPDNS_List {

        public function add( KPDNS_Zone $zone ): void {
            $this->objects[] = $zone;
        }
    }
}
