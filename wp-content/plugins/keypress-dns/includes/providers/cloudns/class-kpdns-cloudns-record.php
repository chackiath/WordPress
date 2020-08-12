<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'KPDNS_ClouDNS_Record' ) ) {

    /**
     * Class KPDNS_ClouDNS_Record
     *
     * @since 1.1
     */
	class KPDNS_ClouDNS_Record extends KPDNS_Record {

        const DEFAULT_FAILOVER_VALUE = 0;

        /**
         * @var string
         */
	    private $id;

        /**
         * @var int
         */
	    private $failover;

        /**
         * KPDNS_ClouDNS_Record constructor.
         *
         * @param string $id
         * @param string $host
         * @param string $type
         * @param string $record
         * @param int $ttl
         * @param int $failover
         */
	    public function __construct( string $id, string $host, string $type, string $record, int $ttl, int $failover ) {
	        parent::__construct( $type, $host, $record, $ttl );
            $this->id       = $id;
            $this->failover = $failover;
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
        public function set_id(string $id): void {
            $this->id = $id;
        }

        /**
         * @return int
         */
        public function get_failover(): int {
            return $this->failover;
        }

        /**
         * @param int $failover
         */
        public function set_failover(int $failover): void {
            $this->failover = $failover;
        }

        /**
         * @return array
         */
        public function to_array(): array {
            $array = parent::to_array();
            $array['id']       = $this->id;
            $array['failover'] = $this->failover;
            return $array;
        }
    }
}
