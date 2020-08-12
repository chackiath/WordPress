<?php
/**
 * This file prepares all the integration with the WP Ultimo plugin (in case it
 * is installed).
 *
 * @author     Martín Di Felice
 * @since      0.1.0
 * @package    DNS_MANAGER
 * @subpackage DNS_MANAGER/includes
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'KPDNS_WP_Ultimo' ) ) {

	class KPDNS_WP_Ultimo extends WU_Domain_Mapping {

		const ACTION_WU_SAVE_CUSTOM_DOMAIN = 'kpdns-wu-save-custom-domain';

		public static function register_hooks() {
			add_action( 'wu-my-account-page', __CLASS__ . '::override_custom_domain_metabox' );
			add_action( 'load-toplevel_page_wu-my-account', __CLASS__ . '::process_custom_domain', 999 );
			add_action( 'mercator.mapping.deleted', __CLASS__ . '::delete_custom_domain_zone' );
            add_action( 'mercator.mapping.created', __CLASS__ . '::create_custom_domain_zone' );
            add_action( 'mercator.mapping.updated', __CLASS__ . '::update_custom_domain_zone', 10, 2 );
            //add_action( 'kpdns_after_delete_zone' , __CLASS__ . '::after_delete_zone', 10 );
		}

		public static function override_custom_domain_metabox() {

			$api = kpdns_get_api();

			if ( is_wp_error( $api ) ) {
				return;
			}

			remove_meta_box( 'wp-ultimo-custom-domain', 'wu-my-account', 'side' );

			$wu_settings = KPDNS_Model::get_wp_ultimo_settings();

			if ( $wu_settings && isset( $wu_settings['wu-metabox'] ) && isset( $wu_settings['wu-metabox']['title'] ) && ! empty( $wu_settings['wu-metabox']['title'] ) ) {
			    $title = $wu_settings['wu-metabox']['title'];
            } else {
			    $title = KPDNS_Utils::get_text( 'metabox-title', self::get_text() );
            }

			add_meta_box(
				'kpdns-wp-ultimo-custom-domain',
				$title,
				function () { KPDNS_Page::render_view( 'wu-custom-domain-metabox' ); },
				'wu-my-account',
				'side',
				'high'
			);
		}

		public static function process_custom_domain() {

			if ( ! isset( $_POST['_wpnonce'] ) || ! isset( $_POST['custom-domain'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], KPDNS_WP_Ultimo::ACTION_WU_SAVE_CUSTOM_DOMAIN ) ) {
				return;
			}

			// Clean URL
			$url         = trim( strtolower( parent::remove_scheme( $_POST['custom-domain'] ) ) );
			$network_url = strtolower( parent::remove_scheme( get_site_url( get_current_site()->blog_id ) ) );

			/**
			 * @since  1.1.3 Validate the domain, of course
			 */
			if ( preg_match('/^(?!\-)(?:[a-zA-Z\d\-]{0,62}[a-zA-Z\d]\.){1,126}(?!\d+)[a-zA-Z\d]{1,63}$/', $url ) && ! empty( $url ) ) {

				/**
				 * Check if it already exists
				 */
				if ( Mercator\Mapping::get_by_domain( $url ) ) {
					// Domain Exists already
					WP_Ultimo()->add_message( __( 'That domain is already being used by other account.', 'wp-ultimo' ), 'error' );
				} else if ( $url === $network_url || strpos( $url, $network_url ) !== false ) {
					// Prevent invalid domains
					WP_Ultimo()->add_message( __( 'This domain name is not valid.', 'wp-ultimo' ), 'error' );
				} else {

				    /**
                     * All good, let's map the domain
                     */

                    $error = false;



					if ( $error ) {
						WP_Ultimo()->add_message( $error, 'error' );
					} else {

						$site = wu_get_current_site();

						// Save field in the site
						$site->set_custom_domain( $url );

						/**
						 * @since  1.2.1 Makes sure it gets added as an active one
						 */
						if ( $mapping = Mercator\Mapping::get_by_domain( $url ) ) {
							$mapping->set_active( true );
						} // end if;

						/**
						 * Creates the admin notification email about the domain mapping
						 * @since 1.5.4
						 */
						WU_Mail()->send_template(
							'domain_mapping',
							get_network_option( null, 'admin_email' ),
							array(
								'user_name'              => $site->site_owner->data->display_name,
								'user_site_name'         => get_bloginfo('name'),
								'user_site_url'          => get_home_url($site->ID),
								'mapped_domain'          => $url,
								'alias_panel_url'        => network_admin_url('admin.php?action=mercator-aliases&id=') . $site->ID,
								'user_account_page_link' => get_admin_url($site->ID, 'admin.php?page=wu-my-account'),
							)
						);

						/**
						 * @since 1.6.0 Hook for after a mapped domain is added
						 */
						do_action( 'wu_after_domain_mapping', $url, $site->ID, $site->site_owner->ID );

                        $wu_settings = KPDNS_Model::get_wp_ultimo_settings();

                        if ( $wu_settings && isset( $wu_settings['mapping'] ) && isset( $wu_settings['mapping']['method'] ) && $wu_settings['mapping']['method'] === 'provider-ns' ) {

                            $success_message = __( 'Custom domain updated successfully! Please contact the site admin for more info about how to get your domain\'s Name Servers.', 'keypress-dns');

                            $api = kpdns_get_api();

                            if ( ! is_wp_error( $api ) ) {
                                $mapped_zone = KPDNS_Model::get_wp_ultimo_mapped_zone( $url );

                                if ( $mapped_zone ) {

                                    $records = $api->list_records( $mapped_zone['id'] );
                                    if ( ! is_wp_error( $records ) ) {
                                        $ns = array();
                                        foreach ( $records as $record ) {
                                            if ( $record->get_type() === KPDNS_Record::TYPE_NS ) {
                                                $rdata = $record->get_rdata();
                                                if ( isset( $rdata ) && ! empty( $rdata ) && isset( $rdata[ KPDNS_Record::RDATA_KEY_VALUE ] )  ) {
                                                    $ns[] = rtrim( $rdata[ KPDNS_Record::RDATA_KEY_VALUE ], '.' );
                                                }
                                            }
                                        }
                                        $success_message = sprintf( __( 'Custom domain updated successfully! Please, replace your domain\'s Name Servers with these: %s', 'keypress-dns'), implode( ', ', $ns ) );
                                    }
                                }
                            }
                        } else {
                            $success_message = __( 'Custom domain updated successfully!', 'keypress-dns');
                        }

                        // Add Success Message
                        WP_Ultimo()->add_message( $success_message );

					} // end if;
				}
			} elseif ( empty( $url ) ) {
				$site = wu_get_current_site();

				$current_domain = $site->get_meta( 'custom-domain' );

				//$this->delete_custom_domain_zone();

				if ( empty( $current_domain ) ) {
					return WP_Ultimo()->add_message( __( 'You need to enter a valid domain address.', 'wp-ultimo' ), 'error' );
				} // end if;

				// Save field in the site
				$site->set_custom_domain( '' );

				/**
				 * @since  1.2.1 Makes sure it gets added as a active one
				 */
				if ( $mapping = Mercator\Mapping::get_by_domain($url) ) {
					$mapping->delete();
				} // end if;

				/**
				 * @since 1.6.1 After the domain map is removed
				 */
				do_action('wu_after_domain_mapping_removed', $url, $site->ID, $site->site_owner->ID);

				WP_Ultimo()->add_message( __( 'Custom domain removed successfully.', 'wp-ultimo') );
			} else {
				// Add Error Message
				WP_Ultimo()->add_message( __( 'You need to enter a valid domain address.', 'wp-ultimo' ), 'error' );
			}
		}

		public static function create_custom_domain_zone( $mapping ) {

            $wu_settings    = KPDNS_Model::get_wp_ultimo_settings();

            if ( $wu_settings && isset( $wu_settings['mapping'] ) && isset( $wu_settings['mapping']['method'] ) && $wu_settings['mapping']['method'] === 'custom-ns' || $wu_settings['mapping']['method'] === 'provider-ns') {

                $api = kpdns_get_api();

                if ( is_wp_error( $api ) ) {
                    return false;
                }

                $args = array();

                $zone = array(
                    'domain' => $mapping->get_domain(),
                );

                if ( $api instanceof KPDNS_Custom_NS_API_Imp ) {
                    $default_ns = KPDNS_Model::get_default_ns();

                    if ( $default_ns && is_array( $default_ns ) && isset( $default_ns['ns'] ) && ! empty( $default_ns['id'] ) ) {
                        $args = array(
                            'custom-ns' => $default_ns['id'],
                        );
                    }
                }

                $the_zone = $api->build_zone( $zone );
                $zone_id = $api->add_zone( $the_zone, $args );

                if ( ! is_wp_error( $zone_id ) ) {
                    $zone['id'] = $zone_id;
                    KPDNS_Utils::maybe_add_default_records( $zone );
                    KPDNS_Model::save_wp_ultimo_mapped_zone( $zone );
                    return true;
                }
            }

            return false;
        }

		public static function delete_custom_domain_zone( $mapping ) {
            $domain      = $mapping->get_domain();
            $mapped_zone = KPDNS_Model::get_wp_ultimo_mapped_zone( $domain );

            if ( ! $mapped_zone ) {
                return false;
            }

            $zone_id  = $mapped_zone['id'];
            $api      = kpdns_get_api();

            if ( is_wp_error( $api ) ) {
                return $api;
            }

            KPDNS_Model::delete_wp_ultimo_mapped_zone( $domain );

            return $api->delete_zone( $zone_id );
        }

        public static function update_custom_domain_zone( $mapping, $old_mapping ) {
            if ( self::create_custom_domain_zone( $mapping ) ) {
                self::delete_custom_domain_zone( $old_mapping );
            }
        }

        public static function get_shortcodes() {
            $shortcodes = array(
                '{{ip-address}}'  => WU_Settings::get_setting('network_ip') ? WU_Settings::get_setting('network_ip') : $_SERVER['SERVER_ADDR'],
                '{{domain}}'      => KPDNS_Utils::get_site_domain(),
            );

            $api = kpdns_get_api();
            if( $api instanceof KPDNS_Custom_NS_API_Imp ) {

                $default_ns = KPDNS_Model::get_default_ns();

                if ( $default_ns && is_array( $default_ns ) && isset( $default_ns['ns'] ) ) {
                    $shortcodes['{{custom-ns}}'] = implode(', ', $default_ns['ns'] );
                } else {
                    $shortcodes['{{custom-ns}}'] = '* please contact support *';
                }
            }

            return $shortcodes;
        }

        public static function get_text() {
		    return array(
                'metabox-title'                             => __( 'Custom Domain', 'keypress-dns'),
                'metabox-description-mapping-supported'     => __( 'You can use a custom domain with your website.', 'keypress-dns'),
                'metabox-description-mapping-not-supported' => __( 'Your plan does not support custom domains. You can upgrade your plan to have access to this feature.', 'keypress-dns'),
                'metabox-input-placeholder'                 => __( 'yourdomain.com', 'keypress-dns' ),
                'metabox-after-input-a-record'              => __( 'Point an A Record to the following IP Address {{ip-address}}. You can also create a CNAME record on your domain pointing to our domain {{domain}}.', 'keypress-dns' ),
                'metabox-after-input-provider-ns'           => __( 'After you set your custom domain, you must copy the Name Servers that will appear at the top of the screen and replace your domain\'s Name Servers with them.', 'keypress-dns'),
                'metabox-after-input-custom-ns'             => __( 'Point your Name Servers to {{custom-ns}}.', 'keypress-dns'),
                'metabox-submit-button'                     => __( 'Set Custom Domain', 'keypress-dns'),
                'modalbox-title'                            => __( 'Are you sure?', 'keypress-dns'),
                'modalbox-text-a-record'                    => __( 'This action can make your site inaccessible if your DNS configuration was not properly set up. Please make sure your domain DNS configuration is pointing to the right IP address and that enough time has passed for that change to propagate.', 'keypress-dns' ),
                'modalbox-text-provider-ns'                 => __( 'This action can make your site inaccessible if your DNS configuration was not properly set up. Please make sure your domain NS records are pointing to the right Name Servers and that enough time has passed for that change to propagate.', 'keypress-dns' ),
                'modalbox-text-custom-ns'                   => __( 'This action can make your site inaccessible if your DNS configuration was not properly set up. Please make sure your domain NS records are pointing to the right Name Servers and that enough time has passed for that change to propagate.', 'keypress-dns' ),
            );
        }
	}
}
