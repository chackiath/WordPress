<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'KPDNS_ClouDNS_Credentials' ) ) {

    class KPDNS_ClouDNS_Credentials extends KPDNS_Credentials {

    	private $auth_id;

    	private $auth_password;

	    /**
	     * KPDNS_Credentials_Cloudflare constructor.
	     *
	     */
    	public function __construct() {

    		$provider_id = KPDNS_Provider_Factory::CLOUDNS;

		    parent::__construct( $provider_id );
        }

        public function to_array(): array {
    	    return array(
                'auth_id'       => $this->auth_id,
                'auth_password' => $this->auth_password,
            );
        }

        public function render_fields() {
			?>
	        <table class="form-table">
		        <tbody>
                    <tr class="kpdns-<?php esc_attr_e( $this->provider_id ) ?>-field">
                        <th>
                            <label for="kpdns-settings-credentials-<?php esc_attr_e( $this->provider_id ); ?>-auth-id"><?php _e( 'Auth Id', 'keypress-dns' ); ?></label>
                        </th>
                        <td >
                            <input type="text" name="kpdns-settings[credentials][<?php esc_attr_e( $this->provider_id ); ?>][auth_id]" id="kpdns-settings-credentials-<?php esc_attr_e( $this->provider_id ) ?>-auth-id" class="regular-text ltr" value="<?php echo isset( $this->auth_id ) ? esc_attr( $this->get_decrypted_field_value( $this->auth_id ) ) : ''; ?>" />
                        </td>
                    </tr>
                    <tr class="kpdns-<?php esc_attr_e( $this->provider_id ) ?>-field">
                        <th>
                            <label for="kpdns-settings-credentials-<?php esc_attr_e( $this->provider_id ); ?>-auth-password"><?php _e( 'Auth Password', 'keypress-dns' ); ?></label>
                        </th>
                        <td >
                            <input type="text" name="kpdns-settings[credentials][<?php esc_attr_e( $this->provider_id ); ?>][auth_password]" id="kpdns-settings-credentials-<?php esc_attr_e( $this->provider_id ) ?>-auth-password" class="regular-text ltr" value="<?php echo isset( $this->auth_password ) ? esc_attr( $this->get_decrypted_field_value( $this->auth_password ) ) : ''; ?>" />
                        </td>
                    </tr>
		        </tbody>
	        </table>
			<?php
        }

        public function load_fields() {
            $credentials = KPDNS_Model::get_credentials( $this->provider_id );
            if ( $credentials ) {
                if ( isset( $credentials['auth_id'] ) ) {
		            $this->auth_id = $credentials['auth_id'];
                }

                if ( isset( $credentials['auth_password'] ) ) {
                    $this->auth_password = $credentials['auth_password'];
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
            if ( ! isset( $settings ) || ! isset( $settings['auth_id'] )  || ! isset( $settings['auth_password'] ) ) {
                return __( 'Invalid credentials settings.', 'keypress-dns' );
            }

            $this->auth_id       = sanitize_text_field( wp_unslash( trim( $settings['auth_id'] ) ) );
            $this->auth_password = sanitize_text_field( wp_unslash( trim( $settings['auth_password'] ) ) );

	        KPDNS_Model::save_credentials( $this, $key );

            return true;
        }

        public function get_auth_id() {
            return $this->auth_id;
        }

        public function get_auth_password() {
            return $this->auth_password;
        }
    }
}
