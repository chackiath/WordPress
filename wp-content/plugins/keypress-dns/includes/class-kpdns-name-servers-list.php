<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'KPDNS_Name_Servers_List' ) ) {

	final class KPDNS_Name_Servers_List extends KPDNS_List {

        public function add( KPDNS_Name_Server $name_server ): void {
            $this->objects[] = $name_server;
        }
	}
}
