<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'KPDNS_AR53_Credentials' ) ) {

    class KPDNS_AR53_Credentials extends KPDNS_Credentials {

    	private $access_key_id;

    	private $secret_access_key;

	    /**
	     * KPDNS_Credentials_AR53 constructor.
	     *
	     */
    	public function __construct() {

    		$provider_id = KPDNS_Provider_Factory::AMAZON_ROUTE_53;

		    parent::__construct( $provider_id );
        }

        public function to_array(): array {
    	    return array(
                'access_key_id'        => $this->access_key_id,
                'secret_access_key'    => $this->secret_access_key,
            );
        }

        public function render_fields() {
			?>
	        <table class="form-table">
		        <tbody>
                    <tr class="kpdns-<?php esc_attr_e( $this->provider_id ) ?>-field">
                        <th>
                            <label for="kpdns-settings-credentials-<?php esc_attr_e( $this->provider_id ); ?>-access-key-id"><?php _e( 'Access Key ID', 'keypress-dns' ); ?></label>
                        </th>
                        <td >
                            <input type="text" name="kpdns-settings[credentials][<?php esc_attr_e( $this->provider_id ); ?>][access_key_id]" id="kpdns-settings-credentials-<?php esc_attr_e( $this->provider_id ) ?>-access-key-id" class="regular-text ltr" value="<?php echo isset( $this->access_key_id ) ? esc_attr( $this->get_decrypted_field_value( $this->access_key_id ) ) : ''; ?>" />
                        </td>
                    </tr>
                    <tr class="kpdns-<?php esc_attr_e( $this->provider_id ) ?>-field">
                        <th>
                            <label for="kpdns-settings-credentials-<?php esc_attr_e( $this->provider_id ); ?>-secret-access-key"><?php _e( 'Secret Access Key', 'keypress-dns' ); ?></label>
                        </th>
                        <td >
                            <input type="text" name="kpdns-settings[credentials][<?php esc_attr_e( $this->provider_id ); ?>][secret_access_key]" id="kpdns-settings-credentials-<?php esc_attr_e( $this->provider_id ) ?>-secret-access-key" class="regular-text ltr" value="<?php echo isset( $this->secret_access_key ) ? esc_attr( $this->get_decrypted_field_value( $this->secret_access_key ) ) : ''; ?>" />
                        </td>
                    </tr>
		        </tbody>
	        </table>
			<?php
        }

        public function load_fields() {
            $credentials = KPDNS_Model::get_credentials( $this->provider_id );
            if ( $credentials ) {
                if ( isset( $credentials['access_key_id'] ) ) {
		            $this->access_key_id = $credentials['access_key_id'];
                }

	            if ( isset( $credentials['secret_access_key'] ) ) {
		            $this->secret_access_key = $credentials['secret_access_key'];
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
            if ( ! isset( $settings ) || ! isset( $settings['access_key_id'] ) || ! isset( $settings['secret_access_key'] ) ) {
                return __( 'Invalid credentials settings.', 'keypress-dns' );
            }

            $this->access_key_id = sanitize_text_field( wp_unslash( trim( $settings['access_key_id'] ) ) );
	        //$this->access_key_id = KPDNS_Crypto::encrypt( $this->access_key_id, $key );

	        $this->secret_access_key = sanitize_text_field( wp_unslash( trim( $settings['secret_access_key'] ) ) );
	        //$this->secret_access_key = KPDNS_Crypto::encrypt( $this->secret_access_key, $key );

	        KPDNS_Model::save_credentials( $this, $key );

            return true;
        }

        public function get_access_key_id() {
            return $this->access_key_id;
        }

	    public function get_secret_access_key() {
		    return $this->secret_access_key;
	    }
    }
}
