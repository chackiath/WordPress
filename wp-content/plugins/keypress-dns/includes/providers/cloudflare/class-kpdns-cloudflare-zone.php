<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'KPDNS_Cloudflare_Zone' ) ) {

    /**
     * Class KPDNS_Cloudflare_Zone
     *
     * @see https://api.cloudflare.com/#zone-properties
     *
     */
	class KPDNS_Cloudflare_Zone extends KPDNS_Zone {

	    public $development_mode;


        /*
            "original_name_servers": [
                "ns1.originaldnshost.com",
                "ns2.originaldnshost.com"
            ]
         */
	    public $original_name_servers;

	    public $original_registrar;

	    public $original_dnshost;

	    public $created_on;

	    public $modified_on;

	    public $activated_on;

	    /*
	        "owner": {
                "id": {},
                "email": {},
                "type": "user"
              }
	     */
	    public $owner;

	    /*
	        "account": {
                "id": "01a7362d577a6c3019a474fd6f485823",
                "name": "Demo Account"
              }
	     */
	    public $account;

	    /*
	        "permissions": [
                "#zone:read",
                "#zone:edit"
              ]
	     */
	    public $permissions;

        /**
         * @var KPDNS_Cloudflare_Plan
         */
	    public $plan;

        /**
         * @var KPDNS_Cloudflare_Plan
         */
	    public $plan_pending;

	    public $status;

	    public $paused;

	    public $type;

	    /*
	        "name_servers": [
                "tony.ns.cloudflare.com",
                "woz.ns.cloudflare.com"
              ]
	     */
	    public $name_servers;

	    public $description;

		public function __construct( string $id, string $domain_name ) {
			parent::__construct( $id, $domain_name );
		}

        /**
         * @return mixed
         */
        public function get_development_mode() {
            return $this->development_mode;
        }

        /**
         * @param mixed $development_mode
         */
        public function set_development_mode( $development_mode ): void {
            $this->development_mode = $development_mode;
        }

        /**
         * @return mixed
         */
        public function get_original_name_servers() {
            return $this->original_name_servers;
        }

        /**
         * @param mixed $original_name_servers
         */
        public function set_original_name_servers( $original_name_servers ): void {
            $this->original_name_servers = $original_name_servers;
        }

        /**
         * @return mixed
         */
        public function get_original_registrar() {
            return $this->original_registrar;
        }

        /**
         * @param mixed $original_registrar
         */
        public function set_original_registrar( $original_registrar ): void {
            $this->original_registrar = $original_registrar;
        }

        /**
         * @return mixed
         */
        public function get_original_dnshost() {
            return $this->original_dnshost;
        }

        /**
         * @param mixed $original_dnshost
         */
        public function set_original_dnshost( $original_dnshost ): void {
            $this->original_dnshost = $original_dnshost;
        }

        /**
         * @return mixed
         */
        public function get_created_on() {
            return $this->created_on;
        }

        /**
         * @param mixed $created_on
         */
        public function set_created_on( $created_on ): void {
            $this->created_on = $created_on;
        }

        /**
         * @return mixed
         */
        public function get_modified_on() {
            return $this->modified_on;
        }

        /**
         * @param mixed $modified_on
         */
        public function set_modified_on( $modified_on ): void {
            $this->modified_on = $modified_on;
        }

        /**
         * @return mixed
         */
        public function get_activated_on() {
            return $this->activated_on;
        }

        /**
         * @param mixed $activated_on
         */
        public function set_activated_on( $activated_on ): void {
            $this->activated_on = $activated_on;
        }

        /**
         * @return mixed
         */
        public function get_owner() {
            return $this->owner;
        }

        /**
         * @param mixed $owner
         */
        public function set_owner( $owner ): void {
            $this->owner = $owner;
        }

        /**
         * @return mixed
         */
        public function get_account() {
            return $this->account;
        }

        /**
         * @param mixed $account
         */
        public function set_account( $account ): void {
            $this->account = $account;
        }

        /**
         * @return mixed
         */
        public function get_permissions() {
            return $this->permissions;
        }

        /**
         * @param mixed $permissions
         */
        public function set_permissions( $permissions ): void {
            $this->permissions = $permissions;
        }

        /**
         * @return KPDNS_Cloudflare_Plan
         */
        public function get_plan(): KPDNS_Cloudflare_Plan {
            return $this->plan;
        }

        /**
         * @param KPDNS_Cloudflare_Plan $plan
         */
        public function set_plan( KPDNS_Cloudflare_Plan $plan ): void {
            $this->plan = $plan;
        }

        /**
         * @return KPDNS_Cloudflare_Plan
         */
        public function get_plan_pending(): KPDNS_Cloudflare_Plan {
            return $this->plan_pending;
        }

        /**
         * @param KPDNS_Cloudflare_Plan $plan_pending
         */
        public function set_plan_pending( KPDNS_Cloudflare_Plan $plan_pending ): void {
            $this->plan_pending = $plan_pending;
        }

        /**
         * @return mixed
         */
        public function get_status() {
            return $this->status;
        }

        /**
         * @param mixed $status
         */
        public function set_status( $status ): void {
            $this->status = $status;
        }

        /**
         * @return mixed
         */
        public function is_paused() {
            return $this->paused;
        }

        /**
         * @param mixed $paused
         */
        public function set_paused( $paused ): void {
            $this->paused = $paused;
        }

        /**
         * @return mixed
         */
        public function get_type() {
            return $this->type;
        }

        /**
         * @param mixed $type
         */
        public function set_type( $type ): void {
            $this->type = $type;
        }

        /**
         * @return mixed
         */
        public function get_name_servers() {
            return $this->name_servers;
        }

        /**
         * @param mixed $name_servers
         */
        public function set_name_servers( $name_servers ): void {
            $this->name_servers = $name_servers;
        }
	}
}
