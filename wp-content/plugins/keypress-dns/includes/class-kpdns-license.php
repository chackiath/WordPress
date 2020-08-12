<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'KPDNS_License' ) ) {

    final class KPDNS_License {

    	public $key;

    	private $status;

    	private $expiration;

    	public function __construct( $key = '', $status = null, $expiration = null ) {
			$this->key = $key;

			if ( isset( $status ) ) {
				$this->status = $status;
			}

			if ( isset( $expiration ) ) {
				$this->expiration = $expiration;
			}
        }

	    public function save() {
		    return KPDNS_Model::save_license( $this );
	    }

	    public function delete() {
		    return KPDNS_Model::delete_license( $this );
	    }

		public function remote_activate( $url, $item_id ) {

			$response = $this->remote_request( $url, $item_id, 'activate_license' );

			$return = new stdClass();

			// make sure the response came back okay
			if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
				$return->success = false;
				$return->error = 'response-error';
			} else {
				$response_obj = json_decode( wp_remote_retrieve_body( $response ) );
				$return->success = $response_obj->success;

				if ( ! $return->success ) {
					$return->error = $response_obj->error;
				} else {
					$return->status  = $response_obj->license;
					$return->expiration = $response_obj->expires;
				}
			}

			return $return;
		}

		public function remote_deactivate( $url, $item_id ) {

			$response = $this->remote_request( $url, $item_id, 'deactivate_license' );

			$return = new stdClass();

			// make sure the response came back okay
			if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
				$return->success = false;
				$return->error = 'response-error';
			} else {
				$response_obj = json_decode( wp_remote_retrieve_body( $response ) );
				$return->success = $response_obj->success;

				if ( ! $return->success ) {
					$return->error = $response_obj->error;
				}
			}

			return $return;
		}

		private function remote_request( $url, $item_id, $action ) {
			// data to send in our API request
			$api_params = array(
				'edd_action' => $action,
				'license'    => $this->key,
				//'item_name'  => urlencode( KPDNS_PLUGIN_NAME ), // the name of our product in EDD
				'item_id'    => $item_id,
				'url'        => home_url()
			);

			// Call the custom API.
			$response = wp_remote_post( $url, array(
				'timeout'   => 15,
				'sslverify' => false,
				'body'      => $api_params
			) );

			return $response;
		}

		public function get_error_message( $error_code ) {
			switch ( $error_code ) {
				case 'expired' :
					return __( 'Your license key is expired.', 'keypress-dns' );

				case 'revoked' :
					return __( 'Your license key has been disabled.', 'keypress-dns' );

				case 'missing' :
					return __( 'Invalid license key.', 'keypress-dns' );

				case 'invalid' :
					return __( 'Invalid license key.', 'keypress-dns' );

				case 'site_inactive':
					return __( 'Your license key is not active for this URL.', 'keypress-dns' );

				case 'item_name_mismatch' :
					return __( 'This appears to be an invalid license key.', 'keypress-dns' );

				case 'no_activations_left':
					return __( 'Your license key has reached its activation limit.', 'keypress-dns' );

				case 'response-error':
				default :
					return __( 'An error occurred, please try again.', 'keypress-dns' );
			}
		}

	    public function set_status( $status ) {
    		$this->status = $status;
	    }

	    public function get_status() {
    		if ( isset( $this->status ) ) {
    			return $this->status;
		    }
    		return false;
	    }

	    public function set_expiration( $expiration ) {
		    $this->expiration = $expiration;
	    }

	    public function get_expiration() {
		    if ( isset( $this->expiration ) ) {
			    return $this->expiration;
		    }
		    return false;
	    }
	}
}
