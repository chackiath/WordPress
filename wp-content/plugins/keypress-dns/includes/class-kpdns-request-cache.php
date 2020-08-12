<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'KPDNS_Request_Cache' ) ) {
    class KPDNS_Request_Cache {
        private $requests = [];

        public function add( string $endpoint, array $args, $response ) {
            $request = array(
                'endpoint' => $endpoint,
                'args'     => $args,
                'response' => $response,
            );
            $this->requests[ $endpoint ] = $request;
        }

        public function get( string $endpoint, array $args ) {
            if ( isset( $this->requests[ $endpoint ] ) &&
                $args === $this->requests[ $endpoint ]['args'] ) {
                return $this->requests[ $endpoint ]['response'];
            }
            return false;
        }
    }
}