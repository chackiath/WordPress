<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'KPDNS_Zone' ) ) {

    /**
     * Class KPDNS_Zone
     *
     * @since 1.0
     *
     */
	class KPDNS_Zone implements KPDNS_Arrayable {

        /**
         * Zone id.
         *
         * @var string
         */
	    protected $id;

        /**
         * Domain name.
         *
         * @var string
         */
        protected $domain;

        /**
         * @var bool
         */
        protected $readonly = false;

        protected $primary = false;

        protected $custom_ns = false;

        /**
         * KPDNS_Zone constructor.
         *
         * @param string $id
         * @param string $domain
         */
		public function __construct( string $id, string $domain ) {
            $this->id     = $id;
            $this->domain = $domain;
		}

        /**
         * @return string
         */
        public function get_id() {
            return $this->id;
        }

        /**
         * @param string $id
         */
        public function set_id( string $id ) {
            $this->id = $id;
        }

        /**
         * @return string
         */
        public function get_domain() {
            return $this->domain;
        }

        /**
         * @param string $domain
         */
        public function set_domain( string $domain ) {
            $this->domain = $domain;
        }

        public function to_array(): array {
            return array(
                'id'        => $this->id,
                'domain'    => $this->domain,
                'readonly'  => $this->readonly,
                'primary'   => $this->primary,
                'custom_ns' => $this->custom_ns,
            );
        }

        public function is_readonly() {
            return $this->readonly;
        }

        public function set_readonly( bool $readonly ) {
            $this->readonly = $readonly;
        }

        public function is_primary() {
            return $this->primary;
        }

        public function set_primary( bool $is_primary ) {
            $this->primary = $is_primary;
        }

        public function is_custom_ns() {
            return $this->custom_ns;
        }

        public function set_custom_ns( bool $is_custom_ns ) {
            $this->custom_ns = $is_custom_ns;
        }
    }
}
