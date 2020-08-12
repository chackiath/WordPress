<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'KPDNS_GCDNS_Zone' ) ) {

    /**
     * Class KPDNS_GCDNS_Zone
     *
     * @since 1.1
     */
	class KPDNS_GCDNS_Zone extends KPDNS_Zone {

	    const VISIBILITY_PUBLIC  = 'public';
	    const VISIBILITY_PRIVATE = 'private';

        /**
         *
         * @var string
         */
	    public $visibility;

        /**
         * @var string
         */
	    public $description;

        /**
         * @var string
         */
	    public $name;

        /**
         * KPDNS_GCDNS_Zone constructor.
         * @param string $id
         * @param string $domain
         * @param string $name
         * @param string $description
         * @param string $visibility
         */
	    public function __construct( string $id, string $domain, string $name, string $description = '', string $visibility = self::VISIBILITY_PUBLIC ) {
	        parent::__construct( $id, $domain );
            $this->name        = $name;
            $this->description = $description;
	        $this->visibility  = $visibility;

            //$this->readonly = true;
        }

        /**
         * @return string
         */
        public function get_visibility(): string {
            return $this->visibility;
        }

        /**
         * @param string $visibility
         */
        public function set_visibility(string $visibility): void {
            $this->visibility = $visibility;
        }

        /**
         * @return string
         */
        public function get_description(): string {
            return $this->description;
        }

        /**
         * @param string $description
         */
        public function set_description(string $description): void {
            $this->description = $description;
        }

        /**
         * @return string
         */
        public function get_name(): string {
            return $this->name;
        }

        /**
         * @param string $name
         */
        public function set_name(string $name): void {
            $this->name = $name;
        }

        /**
         * @return array
         */
        public function to_array(): array{
            $array = parent::to_array();
            $array['name']        = $this->name;
            $array['visibility']  = $this->visibility;
            $array['description'] = $this->description;
            return $array;
        }
    }
}
