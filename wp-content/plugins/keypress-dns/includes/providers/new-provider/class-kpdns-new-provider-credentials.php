<?php

/**
 * Class KPDNS_Credentials_New_Provider
 *
 * Template for new providers.
 *
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'KPDNS_New_Provider_Credentials' ) ) {

    class KPDNS_New_Provider_Credentials extends KPDNS_Credentials {

    	private $auth_field_1;

    	private $auth_field_2;

	    /**
	     * KPDNS_Credentials_New_Provider constructor.
	     *
	     */
    	public function __construct() {

    		$provider_id = KPDNS_Provider_Factory::NEW_PROVIDER;

		    parent::__construct( $provider_id );
        }

        public function to_array(): array {
    	    return array(
                'auth_field_1' => $this->auth_field_1,
                'auth_field_2' => $this->auth_field_2,
            );
        }

        public function render_fields() {
			?>
	        <table class="form-table">
		        <tbody>
                    <tr class="kpdns-<?php esc_attr_e( $this->provider_id ) ?>-field">
                        <th>
                            <label for="kpdns-settings-credentials-<?php esc_attr_e( $this->provider_id ); ?>-auth-field-1"><?php _e( 'Auth Field 1', 'keypress-dns' ); ?></label>
                        </th>
                        <td >
                            <input type="text" name="kpdns-settings[credentials][<?php esc_attr_e( $this->provider_id ); ?>][auth_field_1]" id="kpdns-settings-credentials-<?php esc_attr_e( $this->provider_id ) ?>-auth-field-1" class="regular-text ltr" value="<?php echo isset( $this->auth_field_1 ) ? esc_attr( $this->get_decrypted_field_value( $this->auth_field_1 ) ) : ''; ?>" />
                        </td>
                    </tr>
                    <tr class="kpdns-<?php esc_attr_e( $this->provider_id ) ?>-field">
                        <th>
                            <label for="kpdns-settings-credentials-<?php esc_attr_e( $this->provider_id ); ?>-auth-field-2"><?php _e( 'Auth Field 2', 'keypress-dns' ); ?></label>
                        </th>
                        <td >
                            <input type="text" name="kpdns-settings[credentials][<?php esc_attr_e( $this->provider_id ); ?>][auth_field_2]" id="kpdns-settings-credentials-<?php esc_attr_e( $this->provider_id ) ?>-auth-field-2" class="regular-text ltr" value="<?php echo isset( $this->auth_field_2 ) ? esc_attr( $this->get_decrypted_field_value( $this->auth_field_2 ) ) : ''; ?>" />
                        </td>
                    </tr>
		        </tbody>
	        </table>
			<?php
        }

        public function load_fields() {
            $credentials = KPDNS_Model::get_credentials( $this->provider_id );
            if ( $credentials ) {
                if ( isset( $credentials['auth_field_1'] ) ) {
		            $this->auth_field_1 = $credentials['auth_field_1'];
                }

                if ( isset( $credentials['auth_field_2'] ) ) {
                    $this->auth_field_2 = $credentials['auth_field_2'];
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
            if ( ! isset( $settings ) || ! isset( $settings['auth_field_1'] )  || ! isset( $settings['auth_field_2'] ) ) {
                return __( 'Invalid credentials settings.', 'keypress-dns' );
            }

            $this->auth_field_1 = sanitize_text_field( wp_unslash( trim( $settings['auth_field_1'] ) ) );
            $this->auth_field_2 = sanitize_text_field( wp_unslash( trim( $settings['auth_field_2'] ) ) );

	        KPDNS_Model::save_credentials( $this, $key );

            return true;
        }

        public function get_auth_field_1() {
            return $this->auth_field_1;
        }

        public function get_auth_field_2() {
            return $this->auth_field_2;
        }
    }
}
