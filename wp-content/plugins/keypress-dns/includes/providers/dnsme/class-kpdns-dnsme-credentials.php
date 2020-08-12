<?php

/**
 * Class KPDNS_Credentials_DNSME
 *
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'KPDNS_DNSME_Credentials' ) ) {

    class KPDNS_DNSME_Credentials extends KPDNS_Credentials {

    	private $api_key;

    	private $secret_key;

	    /**
	     * KPDNS_Credentials_DNSME constructor.
	     *
	     */
    	public function __construct() {

    		$provider_id = KPDNS_Provider_Factory::DNSME;

		    parent::__construct( $provider_id );
        }

        public function to_array(): array {
    	    return array(
                'api_key'    => $this->api_key,
                'secret_key' => $this->secret_key,
            );
        }

        public function render_fields() {
			?>
	        <table class="form-table">
		        <tbody>
                    <tr class="kpdns-<?php esc_attr_e( $this->provider_id ) ?>-field">
                        <th>
                            <label for="kpdns-settings-credentials-<?php esc_attr_e( $this->provider_id ); ?>-api-key"><?php _e( 'API Key', 'keypress-dns' ); ?></label>
                        </th>
                        <td >
                            <input type="text" name="kpdns-settings[credentials][<?php esc_attr_e( $this->provider_id ); ?>][api_key]" id="kpdns-settings-credentials-<?php esc_attr_e( $this->provider_id ) ?>-api-key" class="regular-text ltr" value="<?php echo isset( $this->api_key ) ? esc_attr( $this->get_decrypted_field_value( $this->api_key ) ) : ''; ?>" />
                        </td>
                    </tr>
                    <tr class="kpdns-<?php esc_attr_e( $this->provider_id ) ?>-field">
                        <th>
                            <label for="kpdns-settings-credentials-<?php esc_attr_e( $this->provider_id ); ?>-secret-key"><?php _e( 'Secret Key', 'keypress-dns' ); ?></label>
                        </th>
                        <td >
                            <input type="text" name="kpdns-settings[credentials][<?php esc_attr_e( $this->provider_id ); ?>][secret_key]" id="kpdns-settings-credentials-<?php esc_attr_e( $this->provider_id ) ?>-secret-key" class="regular-text ltr" value="<?php echo isset( $this->secret_key ) ? esc_attr( $this->get_decrypted_field_value( $this->secret_key ) ) : ''; ?>" />
                        </td>
                    </tr>
		        </tbody>
	        </table>
			<?php
        }

        public function load_fields() {
            $credentials = KPDNS_Model::get_credentials( $this->provider_id );
            if ( $credentials ) {
                if ( isset( $credentials['api_key'] ) ) {
		            $this->api_key = $credentials['api_key'];
                }

                if ( isset( $credentials['secret_key'] ) ) {
                    $this->secret_key = $credentials['secret_key'];
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
            if ( ! isset( $settings ) || ! isset( $settings['api_key'] )  || ! isset( $settings['secret_key'] ) ) {
                return __( 'Invalid credentials settings.', 'keypress-dns' );
            }

            $this->api_key    = sanitize_text_field( wp_unslash( trim( $settings['api_key'] ) ) );
            $this->secret_key = sanitize_text_field( wp_unslash( trim( $settings['secret_key'] ) ) );

	        KPDNS_Model::save_credentials( $this, $key );

            return true;
        }

        public function get_api_key() {
            return $this->api_key;
        }

        public function get_secret_key() {
            return $this->secret_key;
        }
    }
}
