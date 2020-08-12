<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'KPDNS_AR53_Zone' ) ) {

    /**
     * Class KPDNS_AR53_Zone
     *
     * @since 1.1
     */
	class KPDNS_AR53_Zone extends KPDNS_Zone {

        /**
         *
         * @var bool
         */
	    private $private;

        /**
         * @var string
         */
	    private $description;


	    public function __construct( string $id, string $domain, string $description = '', bool $private = false ) {

	        parent::__construct( $id, $domain );

            $this->description = $description;
	        $this->private     = $private;

	        //$this->readonly = true;
        }

        /**
         * @return bool
         */
        public function is_private(): bool {
            return $this->private;
        }

        /**
         * @param bool $private
         */
        public function set_private(bool $private): void {
            $this->private = $private;
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

        public function to_array(): array{
            $array = parent::to_array();
            $array['private']     = $this->private;
            $array['description'] = $this->description;
            return $array;
        }
    }
}
