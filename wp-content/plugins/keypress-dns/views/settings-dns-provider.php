<?php
    $providers_config = KPDNS_Provider_Factory::get_providers_config();
    $provider_id      = KPDNS_Model::get_provider_id();
?>

<form method="post" action="<?php esc_attr_e( KPDNS_Page::get_form_action_url( KPDNS_Page_Settings::ACTION_SAVE_PROVIDER_SETTINGS ) ); ?>" id="kpdns-provider-settings-form">
    <table class="form-table">
        <tbody>
            <tr valign="top">
                <th colspan="2">
                        <!--<h3><?php _e( 'DNS Provider', 'keypress-dns') ?></h3>-->
                </th>
            </tr>
            <tr>
                <th>
                    <label for="kpdns-settings-provider"><?php _e( 'DNS Provider', 'keypress-dns') ?></label>
                </th>
                <td>
                    <select name="kpdns-settings[provider]" id="kpdns-settings-provider" style="width: 200px;">
                        <option value="-1">---</option>
                        <?php foreach ( $providers_config as $id => $config ): ?>
                            <?php if ( KPDNS_Provider_Factory::is_valid_provider_config( $id, $providers_config ) ) : ?>
                                <option value="<?php esc_attr_e( $id ) ?>" <?php selected( $provider_id, $id ) ?>><?php esc_html_e( $config['name'] ); ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                    <span id="kpdns-provider-spinner" class="spinner" style="float: none;"></span>
                    <p class="description"><?php _e( 'Choose your managed DNS service provider.', 'keypress-dns') ?></p>
                </td>
            </tr>
        </tbody>
    </table>
    <div id="kpdns-credentials">
        <?php
            if ( isset( $provider_id ) ) {
                $provider = KPDNS_Provider_Factory::create( $provider_id );
                if ( isset( $provider ) ) {
                    echo $provider->get_credentials()->render_fields();
                }
            }
        ?>
    </div>
    <?php wp_nonce_field( KPDNS_Page_Settings::ACTION_SAVE_PROVIDER_SETTINGS, KPDNS_Page_Settings::NONCE_SAVE_PROVIDER_SETTINGS ); ?>
    <div id="kpdns-provider-settings-buttons">
        <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e('Save Settings', 'keypress-dns'); ?>">
        <?php if ( isset( $provider_id ) ): ?>
            <input type="button" name="<?php esc_attr_e( KPDNS_Page_Settings::PARAM_CREATE_KEY ); ?>" id="kpdns-create-new-encryption-key-btn" class="button button-secondary" value="<?php _e('Create New Encryption Key', 'keypress-dns'); ?>">
	    <?php endif; ?>
    </div>
</form>