<?php
    $api = kpdns_get_api();

    if ( isset( $_GET['kpdns-settings'] ) && isset( $_GET['kpdns-settings'] ) ) {
        $kpdns_settings = $_GET['kpdns-settings'];
    } else {
        $kpdns_settings = KPDNS_Model::get_wp_ultimo_settings();
    }

    if ( $kpdns_settings ) {
        if ( isset( $kpdns_settings['wu-metabox'] ) ) {
            $wu_metabox_settings = $kpdns_settings['wu-metabox'];
        }

        if ( isset( $kpdns_settings['wu-modalbox'] ) ) {
            $wu_modalbox_settings = $kpdns_settings['wu-modalbox'];
        }

        if ( isset( $kpdns_settings['mapping'] ) ) {
            $wu_mapping_settings = $kpdns_settings['mapping'];
        }
    }

    $wu_text = KPDNS_WP_Ultimo::get_text();
?>

<?php if ( $api instanceof WP_Error ) : ?>
    <h3><?php _e( 'Some WP Ultimo settings require you to setup your DNS provider first. Please go to the "DNS Provider" tab and enter your settings.', 'keypress-dns' ); ?></h3>
<?php else : ?>
    <form method="post" action="<?php esc_attr_e( KPDNS_Page::get_form_action_url( KPDNS_Page_Settings::ACTION_SAVE_WP_ULTIMO_SETTINGS ) ); ?>">
        <table class="form-table">
            <tbody>
                <tr valign="top">
                    <th colspan="2">
                        <h3><?php _e( 'Mapping Method', 'keypress-dns' ) ?></h3>
                        <p><?php _e( 'How do you want users to point their domain to your site at their domain name registrar?', 'keypress-dns'); ?></p>
                        <div>
                            <input type="radio"
                                   name="kpdns-settings[mapping][method]"
                                   id ="kpdns-settings-mapping-method-a-record"
                                   value="a-record"
                                   class="kpdns-mapping-method-radio-btn"
                                <?php checked( ! isset( $wu_mapping_settings ) || ! isset( $wu_mapping_settings['method'] ) || $wu_mapping_settings['method'] === 'a-record' ); ?>
                            />
                            <label for="kpdns-settings-mapping-method-a-record"><?php _e( 'A Record (WP Ultimo default)', 'keypress-dns' ) ?></label>
                        </div>
                        <div>
                            <input type="radio"
                                   name="kpdns-settings[mapping][method]"
                                   id ="kpdns-settings-mapping-method-provider-ns"
                                   value="provider-ns"
                                   class="kpdns-mapping-method-radio-btn"
                                   <?php checked( isset( $wu_mapping_settings ) && isset( $wu_mapping_settings['method'] ) && $wu_mapping_settings['method'] === 'provider-ns' ); ?>
                            />
                            <label for="kpdns-settings-mapping-method-a-record"><?php _e( 'DNS Provider\'s NS', 'keypress-dns' ) ?></label>
                        </div>
                        <?php if( $api instanceof KPDNS_Custom_NS_API_Imp ) : ?>
                            <div>
                                <input type="radio"
                                       name="kpdns-settings[mapping][method]"
                                       id ="kpdns-settings-mapping-method-custom-ns"
                                       value="custom-ns"
                                       class="kpdns-mapping-method-radio-btn"
                                       <?php checked( isset( $wu_mapping_settings ) && isset( $wu_mapping_settings['method'] ) && $wu_mapping_settings['method'] === 'custom-ns' ); ?>
                                />
                                <label for="kpdns-settings-mapping-method-custom-ns"><?php _e( 'Custom NS (Make sure you have created your Custom NS before selecting this option)', 'keypress-dns' ) ?></label>
                            </div>
                        <?php endif; ?>
                    </th>
                </tr>
                <tr valign="top">
                    <th colspan="2">
                        <h3><?php _e( 'Custom Domain Metabox', 'keypress-dns' ) ?></h3>
                        <p><?php _e( 'You can modify the texts in the "Custom Domain" metabox that users see in their account.', 'keypress-dns' ); ?></p>
                    </th>
                </tr>
                <tr>
                    <th>
                        <label for="kpdns-settings-wu-metabox-title"><?php _e( 'Title', 'keypress-dns') ?></label>
                    </th>
                    <td>
                        <input type="text"
                               name="kpdns-settings[wu-metabox][title]"
                               id="kpdns-settings-wu-metabox-title"
                               value="<?php esc_attr_e( isset( $wu_metabox_settings ) && isset( $wu_metabox_settings['title'] ) ? $wu_metabox_settings['title'] : '' ); ?>"
                               class="regular-text"
                               placeholder="<?php echo esc_attr( KPDNS_Utils::get_text( 'metabox-title', $wu_text ) ); ?>">
                    </td>
                </tr>
                <tr>
                    <th>
                        <label for="kpdns-settings-wu-metabox-description-mapping-supported"><?php _e( 'Description (Domain Mapping Supported)', 'keypress-dns') ?></label>
                    </th>
                    <td>
                        <textarea
                                rows="5"
                                name="kpdns-settings[wu-metabox][description-mapping-supported]"
                                id="kpdns-settings-wu-metabox-description-mapping-supported"
                                class="regular-text"
                                placeholder="<?php echo esc_attr( KPDNS_Utils::get_text( 'metabox-description-mapping-supported', $wu_text ) ); ?>"
                        ><?php esc_html_e( stripslashes( isset( $wu_metabox_settings ) && isset( $wu_metabox_settings['description-mapping-supported'] ) ? $wu_metabox_settings['description-mapping-supported'] : '' ) );?></textarea>
                        <?php kpdns_render_shortcodes_description( $api ); ?>
                    </td>
                </tr>
                <tr>
                    <th>
                        <label for="kpdns-settings-wu-metabox-description-mapping-not-supported"><?php _e( 'Description (Domain Mapping NOT Supported)', 'keypress-dns') ?></label>
                    </th>
                    <td>
                        <textarea
                                rows="5"
                                name="kpdns-settings[wu-metabox][description-mapping-not-supported]"
                                id="kpdns-settings-wu-metabox-description-mapping-not-supported"
                                class="regular-text"
                                placeholder="<?php echo esc_attr( KPDNS_Utils::get_text( 'metabox-description-mapping-not-supported', $wu_text ) ); ?>"
                        ><?php esc_html_e( stripslashes( isset( $wu_metabox_settings ) && isset( $wu_metabox_settings['description-mapping-not-supported'] ) ? $wu_metabox_settings['description-mapping-not-supported'] : '' ) );?></textarea>
                        <?php kpdns_render_shortcodes_description( $api ); ?>
                    </td>
                </tr>
                <tr>
                    <th>
                        <label for="kpdns-settings-wu-settings-input-placeholcer"><?php _e( 'Input Placeholder', 'keypress-dns') ?></label>
                    </th>
                    <td>
                        <input type="text"
                               name="kpdns-settings[wu-metabox][input-placeholder]"
                               id="kpdns-settings-wu-settings-input-placeholcer"
                               value="<?php esc_attr_e( isset( $wu_metabox_settings ) && isset( $wu_metabox_settings['input-placeholder'] ) ? $wu_metabox_settings['input-placeholder'] : '' ); ?>"
                               class="regular-text"
                               placeholder="<?php echo esc_attr( KPDNS_Utils::get_text( 'metabox-input-placeholder', $wu_text ) ); ?>">
                    </td>
                </tr>
                <tr>
                    <th>
                        <label for="kpdns-settings-wu-metabox-after-input"><?php _e( 'After Input', 'keypress-dns') ?></label>
                    </th>
                    <td>
                        <?php
                            if ( isset( $wu_mapping_settings ) && isset( $wu_mapping_settings['method'] ) && $wu_mapping_settings['method'] === 'provider-ns' ) {
                                $after_input_placeholder = KPDNS_Utils::get_text( 'metabox-after-input-provider-ns', $wu_text );
                            } elseif( isset( $wu_mapping_settings ) && isset( $wu_mapping_settings['method'] ) && $wu_mapping_settings['method'] === 'custom-ns' ) {
                                $after_input_placeholder = KPDNS_Utils::get_text( 'metabox-after-input-custom-ns', $wu_text );
                            } else {
                                $after_input_placeholder = KPDNS_Utils::get_text( 'metabox-after-input-a-record', $wu_text );
                            }
                        ?>
                        <textarea
                                rows="5"
                                name="kpdns-settings[wu-metabox][after-input]"
                                id="kpdns-settings-wu-metabox-after-input"
                                class="regular-text"
                                placeholder="<?php echo esc_attr( $after_input_placeholder ); ?>"
                                data-a-record-placeholder="<?php echo esc_attr( KPDNS_Utils::get_text( 'metabox-after-input-a-record', $wu_text ) ); ?>"
                                data-provider-ns-placeholder="<?php echo esc_attr( KPDNS_Utils::get_text( 'metabox-after-input-provider-ns', $wu_text ) ); ?>"
                                data-custom-ns-placeholder="<?php echo esc_attr( KPDNS_Utils::get_text( 'metabox-after-input-custom-ns', $wu_text ) ); ?>"
                        ><?php esc_html_e( stripslashes( isset( $wu_metabox_settings ) && isset( $wu_metabox_settings['after-input'] ) ? $wu_metabox_settings['after-input'] : '' ) );?></textarea>
                        <?php kpdns_render_shortcodes_description( $api ); ?>
                    </td>
                </tr>
                <tr>
                    <th>
                        <label for="kpdns-settings-wu-metabox-submit-button"><?php _e( 'Submit Button', 'keypress-dns') ?></label>
                    </th>
                    <td>
                        <input type="text"
                               name="kpdns-settings[wu-metabox][submit-button]"
                               id="kpdns-settings-wu-metabox-submit-button"
                               value="<?php esc_attr_e( isset( $wu_metabox_settings ) && isset( $wu_metabox_settings['submit-button'] ) ? $wu_metabox_settings['submit-button'] : '' ); ?>"
                               class="regular-text"
                               placeholder="<?php echo esc_attr( KPDNS_Utils::get_text( 'metabox-submit-button', $wu_text ) ); ?>">
                    </td>
                </tr>
                <tr valign="top">
                    <th colspan="2">
                        <h3><?php _e( 'Modal Popup', 'keypress-dns' ) ?></h3>
                        <p><?php _e( 'You can modify the texts in the modal box that pops up when users submit the custom domain metabox form.', 'keypress-dns' ); ?></p>
                    </th>
                </tr>
                <tr>
                    <th>
                        <label for="kpdns-settings-modalbox-title"><?php _e( 'Title', 'keypress-dns') ?></label>
                    </th>
                    <td>
                        <input type="text"
                               name="kpdns-settings[wu-modalbox][title]"
                               id="kpdns-settings-wu-modalbox-title"
                               value="<?php esc_attr_e( isset( $wu_modalbox_settings ) && isset( $wu_modalbox_settings['title'] ) ? $wu_modalbox_settings['title'] : '' ); ?>"
                               class="regular-text"
                               placeholder="<?php echo esc_attr( KPDNS_Utils::get_text( 'modalbox-title', $wu_text ) ); ?>">
                    </td>
                </tr>
                <tr>
                    <th>
                        <label for="kpdns-settings-wu-modalbox-text"><?php _e( 'Text', 'keypress-dns') ?></label>
                    </th>
                    <td>
                        <?php
                            if ( isset( $wu_mapping_settings ) && isset( $wu_mapping_settings['method'] ) && $wu_mapping_settings['method'] === 'provider-ns' ) {
                                $modalbox_text = KPDNS_Utils::get_text( 'modalbox-text-provider-ns', $wu_text );
                            } elseif( isset( $wu_mapping_settings ) && isset( $wu_mapping_settings['method'] ) && $wu_mapping_settings['method'] === 'custom-ns' ) {
                                $modalbox_text = KPDNS_Utils::get_text( 'modalbox-text-custom-ns', $wu_text );
                            } else {
                                $modalbox_text = KPDNS_Utils::get_text( 'modalbox-text-a-record', $wu_text );
                            }
                        ?>
                        <textarea
                                rows="5"
                                name="kpdns-settings[wu-modalbox][text]"
                                id="kpdns-settings-wu-modalbox-text"
                                class="regular-text"
                                placeholder="<?php echo esc_attr( $modalbox_text ); ?>"
                                data-a-record-placeholder="<?php echo esc_attr( KPDNS_Utils::get_text( 'modalbox-text-a-record', $wu_text ) ); ?>"
                                data-provider-ns-placeholder="<?php echo esc_attr( KPDNS_Utils::get_text( 'modalbox-text-provider-ns', $wu_text ) ); ?>"
                                data-custom-ns-placeholder="<?php echo esc_attr( KPDNS_Utils::get_text( 'modalbox-text-custom-ns', $wu_text ) ); ?>"
                        ><?php esc_html_e( stripslashes( isset( $wu_modalbox_settings ) && isset( $wu_modalbox_settings['text'] ) ? $wu_modalbox_settings['text'] : '' ) );?></textarea>
                        <?php kpdns_render_shortcodes_description( $api ); ?>
                    </td>
                </tr>
            </tbody>
        </table>
        <?php
            wp_nonce_field( KPDNS_Page_Settings::ACTION_SAVE_WP_ULTIMO_SETTINGS, KPDNS_Page_Settings::NONCE_SAVE_WP_ULTIMO_SETTINGS );
            submit_button( __( 'Save Settings', 'keypress-dns' ) );
        ?>
    </form>
<?php endif; ?>

<?php
    function kpdns_render_shortcodes_description( $api ) {
        ?>
            <p class="description">
                <?php
                if ( $api instanceof KPDNS_Custom_NS_API_Imp ) {
                    _e( 'Available shortcodes: {{ip-address}}, {{custom-ns}}, {{domain}}', 'keypress-dns' );
                } else {
                    _e( 'Available shortcodes: {{ip-address}}, {{domain}}', 'keypress-dns' );
                }
                ?>
            </p>
        <?php
    }
?>

