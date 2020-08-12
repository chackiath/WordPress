<?php

$license = KPDNS_Model::get_license();

if ( ! $license ) {
    $key = isset( $_GET['key'] ) ? $_GET['key'] : '' ;
    $license = new KPDNS_License( $key );
}

if ( $license->get_status() !== 'valid' ) {
	?>
    <p class="kpdns-license-error">
		<?php _e( 'UPDATES UNAVAILABLE! Please enter a valid license key below to enable automatic updates.', 'keypress-dns' ); ?>
        &nbsp;<a href="<?php esc_attr_e( KPDNS_PLUGIN_PAGE_URL ); ?>" target="_blank"><?php _e( 'Subscribe Now', 'keypress-dns' ); ?>&nbsp;»</a>
    </p>
	<?php
}

?>

<div id="kpdns-license">
    <div class="kpdns-settings-wrap">
        <div class="kpdns-settings-content">
            <h3>
				<?php _e( 'Updates & Support Subscription', 'keypress-dns' ); ?>
                <span> — </span>
				<?php
				if ( $license->get_status() === 'valid' ) {
					?>
                    <span class="status-active"><?php _e( 'Active!', 'keypress-dns' ); ?></span>
					<?php
				} else {
					?>
                    <span class="status-inactive"><?php _e( 'Not Active!', 'keypress-dns' ); ?></span>
					<?php
				}
				?>
            </h3>
			<?php
			if ( $license->get_status() === 'valid' ) {
				?>
                <p>
                    <?php
                        if ( $license->get_expiration() === 'lifetime' ) {
                            _e( 'Your license is lifetime.', 'keypress-dns' );
                        } else {
	                        echo sprintf( __( 'Your license key expires on %s.', 'keypress-dns' ), strftime("%B %d, %Y", strtotime( $license->get_expiration() ) ) );
                        }
                    ?>
                </p>
				<?php
			} else {
				?>
                <p><?php _e( 'Enter your license key to enable remote updates and support.', 'keypress-dns' ); ?></p>
				<?php
			}


			if ( 'valid' === $license->get_status() ) {
				$action_name = KPDNS_Page_Settings::ACTION_DEACTIVATE_LICENSE;
				$nonce_name  = KPDNS_Page_Settings::NONCE_DEACTIVATE_LICENSE;
            } else {
			    $action_name = KPDNS_Page_Settings::ACTION_SAVE_LICENSE_KEY;
				$nonce_name  = KPDNS_Page_Settings::NONCE_SAVE_LICENSE_KEY;
            }

			?>
            <form method="post" action="<?php esc_attr_e( KPDNS_Page::get_form_action_url( $action_name ) ); ?>">

                <input type="hidden" name="kpdns_page" value="<?php echo $_GET['page'] ?>" />
                <input type="hidden" name="kpdns_tab" value="<?php echo $_GET['tab'] ?>" />

                <input id="kpdns_license_key" name="kpdns_license_key" type="text" class="regular-text"
                       value="<?php esc_attr_e( $license->key ); ?>"/>
                <?php wp_nonce_field( $action_name, $nonce_name ); ?>
                <div>
				<?php
                    if ( $license->get_status() === 'valid' ) {
                        submit_button( __( 'Deactivate & Delete License', 'keypress-dns' ), 'secondary' );
                    } else {
                        submit_button( __( 'Save & Activate License', 'keypress-dns' ) );
                    }
				?>
                </div>
            </form>
        </div>
    </div>
</div>