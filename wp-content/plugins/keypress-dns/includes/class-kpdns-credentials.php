<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'KPDNS_Credentials' ) ) {

    abstract class KPDNS_Credentials implements KPDNS_Arrayable {

    	const ENCRYPTED_FIELD_PLACEHOLDER = '# Encrypted data #'; // We don't want to translate it!

    	public $provider_id;

	    protected $fields;

	    /**
	     * KPDNS_Credentials constructor.
	     *
	     * @param $provider_id
	     */
    	public function __construct( $provider_id ) {
			$this->provider_id = $provider_id;
			$this->load_fields();
        }

        abstract public function save( $settings, $key );

        abstract public function render_fields();

	    abstract public function load_fields();

	    abstract public function to_array(): array;

	    public function delete() {
		    KPDNS_Model::delete_credentials( $this );
	    }

	    protected function get_decrypted_field_value( $value ) {

		    if ( defined( 'KPDNS_ENCRYPTION_KEY' ) ) {
			    $key = hex2bin(KPDNS_ENCRYPTION_KEY );
		    } elseif ( isset( $_GET[ KPDNS_Page_Settings::PARAM_KEY ] ) ) {
			    $key = hex2bin( sanitize_text_field( $_GET[ KPDNS_Page_Settings::PARAM_KEY ] ) );
		    } else {
			    $key = false;
		    }

		    /*
		    if ( $key ) {
			    $decrypted_value = KPDNS_Crypto::decrypt( $value, $key );
			    if ( $decrypted_value ) {
				    return $decrypted_value;
			    }
		    }
		    */

		    return self::ENCRYPTED_FIELD_PLACEHOLDER;
	    }
	}
}
