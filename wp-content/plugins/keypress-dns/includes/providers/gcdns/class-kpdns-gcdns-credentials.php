<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'KPDNS_GCDNS_Credentials' ) ) {

    class KPDNS_GCDNS_Credentials extends KPDNS_Credentials {

    	private $service_account_json;

	    /**
	     * KPDNS_Credentials_AR53 constructor.
	     *
	     */
    	public function __construct() {

    		$provider_id = KPDNS_Provider_Factory::GOOGLE_CLOUD_DNS;

		    parent::__construct( $provider_id );
        }

        public function to_array(): array {
    	    return array(
                'service_account_json' => $this->service_account_json,
            );
        }

        public function render_fields() {
			?>
	        <table class="form-table">
		        <tbody>
                    <tr class="kpdns-<?php esc_attr_e( $this->provider_id ) ?>-field">
                        <th>
                            <label for="kpdns-settings-credentials-<?php esc_attr_e( $this->provider_id ); ?>-service-account-json"><?php _e( 'Service Account JSON', 'keypress-dns' ); ?></label>
                        </th>
                        <td >
                            <textarea name="kpdns-settings[credentials][<?php esc_attr_e( $this->provider_id ); ?>][service_account_json]" id="kpdns-settings-credentials-<?php esc_attr_e( $this->provider_id ) ?>-service-account-json" rows="10" cols="40""><?php echo isset( $this->service_account_json ) ? esc_attr( $this->get_decrypted_field_value( $this->service_account_json ) ) : ''; ?></textarea>
                        </td>
                    </tr>
		        </tbody>
	        </table>
			<?php
        }


        public function load_fields() {
            $credentials = KPDNS_Model::get_credentials( $this->provider_id );
            if ( $credentials ) {
                if ( isset( $credentials['service_account_json'] ) ) {
		            $this->service_account_json = $credentials['service_account_json'];
                }
            }
        }

	    /**
	     * @param $settings
	     * @param $key
	     *
	     * @return bool|string error message.
	     */
        public function save( $settings, $key ) {
    	    // Validate
            if ( ! isset( $settings ) || ! isset( $settings['service_account_json'] ) ) {
                return __( 'Invalid credentials settings.', 'keypress-dns' );
            }

            $this->service_account_json = sanitize_textarea_field( wp_unslash( trim( $settings['service_account_json'] ) ) );
	        //$this->service_account_json = KPDNS_Crypto::encrypt( $this->service_account_json, $key );

	        KPDNS_Model::save_credentials( $this, $key );

            return true;
        }

        public function get_service_account_json() {
            return $this->service_account_json;
        }
    }
}
