<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'KPDNS_DNSME_Zone' ) ) {

    /**
     * Class KPDNS_DNSME_Zone
     *
     * @since 1.1
     */
	class KPDNS_DNSME_Zone extends KPDNS_Zone {

        /**
         *  Name servers assigned to the domain by DNS Made Easy (System defined, unless vanity is applied).
         * @var array
         */
        public $name_servers;

        public $vanity_name_servers;

        /**
         * Indicator of whether or not this domain uses the Global Traffic Director service.
         * @var bool
         */
        public $gtd_enabled;

        /**
         * The ID of a custom SOA record.
         * @var int
         */
        public $soa_id;

        /**
         * The ID of a template applied to the domain.
         * @var int
         */
        public $template_id;

        /**
         * The ID of a vanity DNS configuration.
         * @var int
         */
        public $vanity_id;

        /**
         * The ID of an applied transfer ACL.
         * @var int
         */
        public $transfer_acl_id;

        /**
         * The ID of a domain folder.
         * @var int
         */
        public $folder_id;

        /**
         * The number of seconds since the domain was last updated in Epoch time.
         * @var int
         */
        public $updated;

        /**
         * The number of seconds since the domain was last created in Epoch time.
         * @var int
         */
        public $created;

        /**
         * The list of servers defined in an applied AXFR ACL.
         * @var array
         */
        public $axfr_server;

        /**
         * The name servers assigned to the domain at the registrar.
         * @var array
         */
        public $delegate_name_servers;


        public $status;


        /**
         * KPDNS_DNSME_Zone constructor.
         *
         * @param string $id
         * @param string $domain
         */
	    public function __construct( string $id, string $domain ) {
            parent::__construct( $id, $domain );
        }

        /**
         * @return array
         */
        public function get_name_servers(): ?array {
            return $this->name_servers;
        }

        /**
         * @param array $name_servers
         */
        public function set_name_servers( array $name_servers ): void {
            $this->name_servers = $name_servers;
        }

        /**
         * @return array
         */
        public function get_vanity_name_servers(): ?array {
            return $this->vanity_name_servers;
        }

        /**
         * @param array $vanity_name_servers
         */
        public function set_vanity_name_servers( array $vanity_name_servers ): void {
            $this->vanity_name_servers = $vanity_name_servers;
        }

        /**
         * @return bool
         */
        public function is_gtd_enabled(): bool {
            return $this->gtd_enabled;
        }

        /**
         * @param bool $gtd_enabled
         */
        public function set_gtd_enabled( bool $gtd_enabled ): void {
            $this->gtd_enabled = $gtd_enabled;
        }

        /**
         * @return int
         */
        public function get_soa_id(): ?int {
            return $this->soa_id;
        }

        /**
         * @param int $soa_id
         */
        public function set_soa_id( int $soa_id ): void {
            $this->soa_id = $soa_id;
        }

        /**
         * @return int
         */
        public function get_template_id(): ?int {
            return $this->template_id;
        }

        /**
         * @param int $template_id
         */
        public function set_template_id( int $template_id ): void {
            $this->template_id = $template_id;
        }

        /**
         * @return int
         */
        public function get_vanity_id(): ?int {
            return $this->vanity_id;
        }

        /**
         * @param int $vanity_id
         */
        public function set_vanity_id( int $vanity_id ): void {
            $this->vanity_id = $vanity_id;
        }

        /**
         * @return int
         */
        public function get_transfer_acl_id(): ?int {
            return $this->transfer_acl_id;
        }

        /**
         * @param int $transfer_acl_id
         */
        public function set_transfer_acl_id( int $transfer_acl_id ): void {
            $this->transfer_acl_id = $transfer_acl_id;
        }

        /**
         * @return int
         */
        public function get_folder_id(): ?int {
            return $this->folder_id;
        }

        /**
         * @param int $folder_id
         */
        public function set_folder_id( int $folder_id ): void {
            $this->folder_id = $folder_id;
        }

        /**
         * @return int
         */
        public function get_updated(): ?int {
            return $this->updated;
        }

        /**
         * @param int $updated
         */
        public function set_updated( int $updated ): void {
            $this->updated = $updated;
        }

        /**
         * @return int
         */
        public function get_created(): ?int {
            
            return $this->created;
        }

        /**
         * @param int $created
         */
        public function set_created(  $created ): void {
            $this->created = $created;
        }

        /**
         * @return array
         */
        public function get_axfr_server(): ?array {
            return $this->axfr_server;
        }

        /**
         * @param array $axfr_server
         */
        public function set_axfr_server( array $axfr_server ): void {
            $this->axfr_server = $axfr_server;
        }

        /**
         * @return array
         */
        public function get_delegate_name_servers(): ?array {
            return $this->delegate_name_servers;
        }

        /**
         * @param array $delegate_name_servers
         */
        public function set_delegate_name_servers( array $delegate_name_servers ): void {
            $this->delegate_name_servers = $delegate_name_servers;
        }

        public function get_status(): ?int {
            return $this->status;
        }

        public function set_status( int $status ) {
            $this->status = $status;
        }

        public function get_status_string(): string {
            switch ( $this->status ) {
                case 0:
                    return 'Active';
                case 1:
                    return 'Creating';
                case 2:
                    return 'Unknown (2)';
                case 3:
                    return 'Pending deletion';
                default:
                    return '-';
            }
        }

        public function to_array(): array {
            $array = parent::to_array();
            $array['name_servers']          = $this->name_servers;
            $array['gtd_enabled']           = $this->gtd_enabled;
            $array['soa_id']                = $this->soa_id;
            $array['template_id']           = $this->template_id;
            $array['vanity_id']             = $this->vanity_id;
            $array['transfer_acl_id']       = $this->transfer_acl_id;
            $array['folder_id']             = $this->folder_id;
            $array['updated']               = $this->updated;
            $array['created']               = $this->created;
            $array['axfr_server']           = $this->axfr_server;
            $array['delegate_name_servers'] = $this->delegate_name_servers;
            $array['status']                = $this->status;
            return $array;
        }
    }
}
