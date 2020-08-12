<?php
/**
 * KeyPress DNS/WP Ultimo integration - Custom Domain
 *
 * @since      0.1.0
 * @package    DNSM
 * @subpackage dns-manager/assets/templates
 * @author     Martín Di Felice <mdifelice@live.com.ar>
 *
 */

$plan             = wu_get_current_site()->get_plan();
$enabled          = false;
$custom_domain    = '';
//$create_www_cname = false;

$wu_settings = KPDNS_Model::get_wp_ultimo_settings();

if ( $plan ) {
	$enabled = $plan->custom_domain;

	if ( isset( $_POST['custom-domain'] ) ) {
		$custom_domain    = sanitize_text_field( $_POST['custom-domain'] );
		//$create_www_cname = isset( $_POST['create-www-cname'] ) && 'yes' === $_POST['create-www-cname'];
	} else {
		$custom_domain = wu_get_current_site()->get_meta( 'custom-domain' );
	}
}

$wu_settings   = KPDNS_Model::get_wp_ultimo_settings();
$wu_shortcodes = KPDNS_WP_Ultimo::get_shortcodes();
$wu_text       = KPDNS_WP_Ultimo::get_text();

if ( $wu_settings ) {
    if ( isset( $wu_settings['wu-metabox'] ) ) {
        $wu_metabox_settings = $wu_settings['wu-metabox'];
    }

    if ( isset( $wu_settings['wu-modalbox'] ) ) {
        $wu_modalbox_settings = $wu_settings['wu-modalbox'];
    }

    if ( isset( $wu_settings['mapping'] ) ) {
        $wu_mapping_settings = $wu_settings['mapping'];
    }
}

?>
<form id="kpdns-wu-custom-domain" method="post">
	<ul class="wu_status_list">
		<li class="full">
			<p>
                <?php
                    if ( $enabled ) {
                        if ( isset( $wu_metabox_settings ) && isset( $wu_metabox_settings['description-mapping-supported'] ) && ! empty( $wu_metabox_settings['description-mapping-supported'] ) ) {
                            echo KPDNS_Utils::replace_shortcodes( $wu_metabox_settings['description-mapping-supported'], $wu_shortcodes );
                        } else {
                            echo KPDNS_Utils::get_text( 'metabox-description-mapping-supported', $wu_text );
                        }
                    } else {
                        if ( isset( $wu_metabox_settings ) && isset( $wu_metabox_settings['description-mapping-not-supported'] ) && ! empty( $wu_metabox_settings['description-mapping-not-supported'] ) ) {
                            echo KPDNS_Utils::replace_shortcodes( $wu_metabox_settings['description-mapping-not-supported'], $wu_shortcodes );
                        } else {
                            echo KPDNS_Utils::get_text( 'metabox-description-mapping-not-supported', $wu_text );
                        }
                    }
                ?>
            </p>
        </li>
		<li class="full">
			<p>
				<input type="text" <?php disabled( ! $enabled ); ?>
                       value="<?php echo esc_attr( $custom_domain ); ?>"
                       class="regular-text" name="custom-domain"
                       placeholder="<?php esc_attr_e( isset( $wu_metabox_settings ) && isset( $wu_metabox_settings['input-placeholder'] ) && ! empty( $wu_metabox_settings['input-placeholder'] ) ? $wu_metabox_settings['input-placeholder'] : 'yourcustomdomain.com' ); ?>">
            </p>
		</li>
        <?php if( false ) : ?>
            <li class="full">
                <p>
                    <input type="checkbox" <?php disabled( ! $enabled ); ?> value="yes" name="create-www-cname" id="kpdns-wu-create-www-cname"<?php checked( $create_www_cname ); ?> />
                    <label for="kpdns-wu-create-www-cname"><?php echo wp_kses( __( 'Check this box if you want to create the subdomain <code>www</code> for your website.', 'keypress-dns' ), array( 'code' => true ) ); ?></label>
                </p>
            </li>
        <?php endif; ?>
		<?php if ( $enabled ) : ?>
            <li class="full">
                <p>
                    <?php
                        if ( isset( $wu_metabox_settings ) && isset( $wu_metabox_settings['after-input'] ) && ! empty( $wu_metabox_settings['after-input'] ) ) {
                            echo esc_html( KPDNS_Utils::replace_shortcodes( $wu_metabox_settings['after-input'], $wu_shortcodes ) );
                        } else {
                            if ( isset( $wu_mapping_settings ) && isset( $wu_mapping_settings['method'] ) && $wu_mapping_settings['method'] === 'provider-ns' ) {
                                echo esc_html( KPDNS_Utils::replace_shortcodes( KPDNS_Utils::get_text( 'metabox-after-input-provider-ns', $wu_text ), $wu_shortcodes ) );
                            } elseif( isset( $wu_mapping_settings ) && isset( $wu_mapping_settings['method'] ) && $wu_mapping_settings['method'] === 'custom-ns' ) {
                                echo esc_html( KPDNS_Utils::replace_shortcodes( KPDNS_Utils::get_text( 'metabox-after-input-custom-ns', $wu_text ), $wu_shortcodes ) );
                            } else { // A Record (Default)
                                echo esc_html( KPDNS_Utils::replace_shortcodes( KPDNS_Utils::get_text( 'metabox-after-input-a-record', $wu_text ), $wu_shortcodes ) );
                            }
                        }
                    ?>
                </p>
            </li>
		<?php endif; ?>
		<li class="full">
			<p class="sub">
                <?php
                    if ( isset( $wu_modalbox_settings ) && isset( $wu_modalbox_settings['text'] ) && ! empty( $wu_modalbox_settings['text'] ) ) {
                        $modalbox_text = KPDNS_Utils::replace_shortcodes( $wu_modalbox_settings['text'], $wu_shortcodes );
                    } else {
                        if ( isset( $wu_mapping_settings ) && isset( $wu_mapping_settings['method'] ) && $wu_mapping_settings['method'] === 'provider-ns' ) {
                            $modalbox_text = KPDNS_Utils::replace_shortcodes( KPDNS_Utils::get_text( 'modalbox-text-provider-ns', $wu_text ), $wu_shortcodes );
                        } elseif( isset( $wu_mapping_settings ) && isset( $wu_mapping_settings['method'] ) && $wu_mapping_settings['method'] === 'custom-ns' ) {
                            $modalbox_text = KPDNS_Utils::replace_shortcodes( KPDNS_Utils::get_text( 'modalbox-text-custom-ns', $wu_text ), $wu_shortcodes );
                        } else { // A Record (Default)
                            $modalbox_text = KPDNS_Utils::replace_shortcodes( KPDNS_Utils::get_text( 'modalbox-text-a-record', $wu_text ), $wu_shortcodes );
                        }
                    }
                ?>
				<button data-target="#kpdns-wu-custom-domain"
                        data-title="<?php echo esc_attr( isset( $wu_modalbox_settings['title'] ) && ! empty( $wu_modalbox_settings['title'] ) ? $wu_modalbox_settings['title'] : __( 'Are you sure?', 'keypress-dns' ), 'wp-ultimo' ); ?>"
                        data-text="<?php echo esc_attr( $modalbox_text ); ?>"
                        data-form="true" <?php disabled( !$enabled ); ?>
                        class="wu-confirm button <?php echo $enabled ? 'button-primary' : ''; ?> button-streched"
                        type="submit">
                    <?php esc_html_e( isset( $wu_metabox_settings ) && isset( $wu_metabox_settings['submit-button'] ) && ! empty( $wu_metabox_settings['submit-button'] ) ? $wu_metabox_settings['submit-button'] : __( 'Set Custom Domain', 'keypress-dns' ) ); ?>
				</button>
			</p>
		</li>
	</ul>
	<?php
        if ( $enabled ) {
            wp_nonce_field( KPDNS_WP_Ultimo::ACTION_WU_SAVE_CUSTOM_DOMAIN );
        }
    ?>
</form>