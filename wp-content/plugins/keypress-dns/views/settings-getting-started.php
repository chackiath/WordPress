<?php
    $providers_config = KPDNS_Provider_Factory::get_providers_config();
?>
<h2><?php _e( 'Welcome to DNS Manager', 'keypress-dns' ) ?></h2>
<p><?php _e( 'Before you can get started managing your DNS settings, you\'ll need to complete the following steps:', 'keypress-dns' ) ?></p>
<ol>
    <li><?php _e( sprintf( '<a href="%s">Activate your plugin license</a>.', add_query_arg( array( 'page' => KPDNS_PAGE_SETTINGS, 'tab' => KPDNS_Page_Settings::TAB_LICENSE ), KPDNS_Page::get_admin_url() ) ), 'keypress-dns' ); ?></li>
    <li><?php _e( 'Sign up for a manged DNS account via one of our compatible integrations:', 'keypress-dns' ) ?>
        <ul>
            <?php foreach ( $providers_config as $id => $config ): ?>
                <?php if ( KPDNS_Provider_Factory::is_valid_provider_config( $id, $config ) ) : ?>
                    <li>
                        <a href="<?php echo esc_url( $config['url'] ) ?>" target="_blank"><?php echo $config['name'] ?></a>
                    </li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ul>
    </li>
    <li><?php _e( sprintf( 'Connect to your account on the. <a href="%s">DNS Provider tab</a>.', add_query_arg( array( 'page' => KPDNS_PAGE_SETTINGS, 'tab' => KPDNS_Page_Settings::TAB_PROVIDER ), KPDNS_Page::get_admin_url() ) ), 'keypress-dns' ); ?></li>
    <li><?php _e( sprintf( 'OPTIONAL: Enter your default records on the <a href="%s">Default Records tab</a>.', add_query_arg( array( 'page' => KPDNS_PAGE_SETTINGS, 'tab' => KPDNS_Page_Settings::TAB_DEFAULT_RECORDS ), KPDNS_Page::get_admin_url() ) ), 'keypress-dns' ); ?></li>
    <li><?php _e( sprintf( 'OPTIONAL: Add your custom name servers on the <a href="%s">Custom NS screen</a> (if your Managed DNS provider supports that feature).', add_query_arg( array( 'page' => KPDNS_PAGE_NAME_SERVERS ), KPDNS_Page::get_admin_url() ) ), 'keypress-dns' ); ?></li>
    <li><?php _e( sprintf( 'Create your Zones on the <a href="%s">DNS Zones screen</a> and add any additional DNS records.', add_query_arg( array( 'page' => KPDNS_PAGE_ZONES ), KPDNS_Page::get_admin_url() ) ), 'keypress-dns' ); ?></li>
    <li><?php _e( sprintf( 'You can now change the name servers on your domain\'s registrar to point to your managed DNS name servers or your custom name servers. After that, you can continue to manage all DNS settings in the appropriate zone on the <a href="%s" target="_blank">DNS Zones screen</a>.', add_query_arg( array( 'page' => KPDNS_PAGE_ZONES ), KPDNS_Page::get_admin_url() ) ), 'keypress-dns' ); ?></li>
</ol>
<p><?php _e( sprintf( 'For more detailed instructions, please visit our <a href="%s" target="_blank">support articles page</a>.', 'http://support.getkeypress.com/en/collections/1999339-dns-manager' ), 'keypress-dns' ); ?></p>