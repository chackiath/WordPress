<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'KPDNS_Records_List' ) ) {

	final class KPDNS_Records_List extends KPDNS_List {

        public function add( KPDNS_Record $record ): void {
            $this->objects[] = $record;
        }

        public function sort() {
            usort( $this->objects, function( KPDNS_Record $a, KPDNS_Record $b ) {
                return strcmp( $a->get_type(), $b->get_type() );
            });
        }
	}
}
