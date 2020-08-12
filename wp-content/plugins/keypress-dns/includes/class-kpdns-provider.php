<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'KPDNS_Provider' ) ) {

	class KPDNS_Provider {

		public $id;
		public $name;
		public $url;
		protected $credentials;
		public $api;

		public function __construct( $id, $name, $url, $credentials, $api ) {
			$this->id          = $id;
			$this->name        = $name;
            $this->url         = $url;
            $this->credentials = $credentials;
            $this->api         = $api;
		}

		/**
		 * @return bool|String error message.
		 */
		public function save() {
			return KPDNS_Model::save_provider( $this );
		}

		public function delete() {
			return KPDNS_Model::delete_provider( $this );
		}

        public function set_credentials( $credentials ) {
            $this->credentials = $credentials;
        }

		public function get_credentials() {
			return $this->credentials;
		}

		public function has_credentials() {
			KPDNS_Model::has_credentials( $this->id );
		}

        public function set_api( $api ) {
            $this->api = $api;
        }

        public function get_api() {
            return $this->api;
        }
	}
}
