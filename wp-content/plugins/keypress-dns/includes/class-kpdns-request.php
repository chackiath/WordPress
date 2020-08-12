<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'KPDNS_Request' ) ) {

    /**
     * Class KPDNS_Request
     *
     * @since 1.1
     *
     */
	class KPDNS_Request {

	    const METHOD_POST   = "POST";
	    const METHOD_GET    = 'GET';
        const METHOD_PUT    = 'PUT';
        const METHOD_DELETE = 'DELETE';

	    private $url;

        private $headers;

        private $params;

		public function __construct( $url ) {
            $this->url = $url;
		}

		public function set_headers( array $headers ) {
		    if ( empty( $headers ) ) {
		        return;
            }
		    $this->headers = $headers;
        }

        public function set_params( array $params ) {
            if ( empty( $params ) ) {
                return;
            }
            $this->params = $params;
        }

        public function post() {
            return $this->request( self::METHOD_POST );
        }

        public function get() {
            return $this->request( self::METHOD_GET );
        }

        public function put() {
            return $this->request( self::METHOD_PUT );
        }

        public function delete() {
            return $this->request( self::METHOD_DELETE );
        }

        private function request( $method ) {

            $args = array(
                'method' => $method,
            );

            if ( ! empty( $this->headers ) ) {
                $args['headers'] = $this->headers;
            } else {
                $args['headers'] = array(
                    'Content-Type' => 'application/json',
                );
            }

            if ( ! empty( $this->params ) ) {
                $args['body'] = json_encode( $this->params );
            }

            return wp_remote_request( $this->url, $args );
        }
    }
}
