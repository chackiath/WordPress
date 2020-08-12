<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'KPDNS_Cloudflare_Record' ) ) {

    /**
     * Class KPDNS_Cloudflare_Record
     *
     * @see https://api.cloudflare.com/#dns-records-for-a-zone-properties
     *
     *   Cloudflares record example object:
     *
     *   {
     *       "id": "372e67954025e0ba6aaa6d586b9e0b59",
     *       "type": "MX",
     *       "name": "mx.example.com",
     *       "content": "198.51.100.4",
     *       "proxiable": true,
     *       "proxied": false,
     *       "ttl": 120,
     *       "locked": false,
     *       "zone_id": "023e105f4ecef8ad9ca31a8372d0c353",
     *       "zone_name": "example.com",
     *       "created_on": "2014-01-01T05:20:00.12345Z",
     *       "modified_on": "2014-01-01T05:20:00.12345Z",
     *       "data": {
     *           "flags": 1,
     *           "protocol": 3,
     *           "algorithm": 5
     *       },
     *       "meta": {
     *               "auto_added": true,
     *               "source": "primary"
     *       },
     *       "priority": 10
     *   }
     */
	class KPDNS_Cloudflare_Record extends KPDNS_Record {

        /**
         * @var string
         */
	    public $id;

        /**
         * @var string
         */
        public $content;

        /**
         * @var bool
         */
	    public $proxiable;

        /**
         * @var bool
         */
	    public $proxied;

        /**
         * @var bool
         */
	    public $locked;

        /**
         * @var string
         */
	    public $zone_id;

        /**
         * @var string
         */
	    public $zone_name;

        /**
         * @var string
         */
	    public $modified_on;

        /**
         * @var string
         */
	    public $created_on;

        /**
         * @var array
         */
        public $data;

        /**
         * @var array
         */
	    public $meta;

        /**
         * @var int
         */
        public $priority;

        /**
         * KPDNS_Cloudflare_Record constructor.
         *
         * @param string $type
         * @param string $name
         * @param array $rdata
         * @param int $ttl
         */
		public function __construct( string $type, string $name, array $rdata, int $ttl ) {
            parent::__construct( $type, $name, $rdata, $ttl );
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
        public function get_content(): string {
            return $this->content;
        }

        /**
         * @param string $content
         */
        public function set_content( string $content ): void {
            $this->content = $content;
        }

        /**
         * @return bool
         */
        public function is_proxiable(): bool {
            return $this->proxiable;
        }

        /**
         * @param bool $proxiable
         */
        public function set_proxiable( bool $proxiable ): void {
            $this->proxiable = $proxiable;
        }

        /**
         * @return bool
         */
        public function is_proxied(): bool {
            return $this->proxied;
        }

        /**
         * @param bool $proxied
         */
        public function set_proxied( bool $proxied ): void {
            $this->proxied = $proxied;
        }

        /**
         * @return bool
         */
        public function is_locked(): bool {
            return $this->locked;
        }

        /**
         * @param bool $locked
         */
        public function set_locked( bool $locked ): void {
            $this->locked = $locked;
        }

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
         * @return string
         */
        public function get_zone_name(): string {
            return $this->zone_name;
        }

        /**
         * @param string $zone_name
         */
        public function set_zone_name( string $zone_name ): void {
            $this->zone_name = $zone_name;
        }

        /**
         * @return string
         */
        public function get_modified_on(): string {
            return $this->modified_on;
        }

        /**
         * @param string $modified_on
         */
        public function set_modified_on( string $modified_on ): void {
            $this->modified_on = $modified_on;
        }

        /**
         * @return string
         */
        public function get_created_on(): string {
            return $this->created_on;
        }

        /**
         * @param string $created_on
         */
        public function set_created_on( string $created_on ): void {
            $this->created_on = $created_on;
        }

        /**
         * @return array
         */
        public function get_data(): array {
            return $this->data;
        }

        /**
         * @param array $data
         */
        public function set_data( array $data ): void {
            $this->data = $data;
        }

        /**
         * @return array
         */
        public function get_meta(): array {
            return $this->meta;
        }

        /**
         * @param array $meta
         */
        public function set_meta( array $meta ): void {
            $this->meta = $meta;
        }

        /**
         * @return int
         */
        public function get_priority(): int {
            return $this->priority;
        }

        /**
         * @param int $priority
         */
        public function set_priority( int $priority ): void {
            $this->priority = $priority;
        }
	}
}
