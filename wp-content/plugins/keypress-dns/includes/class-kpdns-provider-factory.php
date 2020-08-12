<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

// TODO Improve KPDNS_Provider and KPDNS_Provider_Factory
if ( ! class_exists( 'KPDNS_Provider_Factory' ) ) {

	final class KPDNS_Provider_Factory {

		const GOOGLE_CLOUD_DNS = 'gcdns';
		const AMAZON_ROUTE_53  = 'ar53';
		const CLOUDFLARE       = 'cloudflare';
		const CLOUDNS          = 'cloudns';
        const DNSME            = 'dnsme';
        const NEW_PROVIDER     = 'new_provider';

		public static function create( $provider_id ) {

		    switch ( $provider_id ) {
                case self::GOOGLE_CLOUD_DNS :
                    return new KPDNS_GCDNS();

                case self::DNSME :
                    return new KPDNS_DNSME();

                case self::AMAZON_ROUTE_53 :
                    return new KPDNS_AR53();

                case self::CLOUDFLARE :
                    return new KPDNS_Cloudflare();

                case self::CLOUDNS :
                    return new KPDNS_ClouDNS();

                case self::NEW_PROVIDER :
                    return new KPDNS_NEW_PROVIDER();

                default:
                    return null;
            }
		}

		public static function get_providers() {
			$providers_config = self::get_providers_config();
			$providers        = array();

			foreach ( $providers_config as $id => $config ) {
				$providers[ $id ] = self::create( $id );
			}

			return $providers;
		}

        /**
         * Returns an associative array of providers configuration.
         *
         * @return array
         */
		public static function get_providers_config() {
            $config = array(
                self::AMAZON_ROUTE_53 => array(
                    'id'                => self::AMAZON_ROUTE_53,
                    'name'              => __( 'Amazon Route 53', 'keypress-dns' ),
                    'url'               => 'https://aws.amazon.com/route53/',
                    'class'         => 'KPDNS_AR53_API',
                    'api-path'          => KPDNS_PLUGIN_DIR . 'includes/providers/ar53/class-kpdns-ar53-api.php',
                    'hooks-class'       => 'KPDNS_AR53_Hooks',
                    'hooks-path'        => KPDNS_PLUGIN_DIR . 'includes/providers/ar53/class-kpdns-ar53-hooks.php',
                    'credentials-class' => 'KPDNS_AR53_Credentials',
                    'credentials-path'  => KPDNS_PLUGIN_DIR . 'includes/providers/ar53/class-kpdns-ar53-credentials.php',
                ),
                self::CLOUDFLARE => array(
                    'id'                => self::CLOUDFLARE,
                    'name'              => __( 'Cloudflare', 'keypress-dns' ),
                    'url'               => 'https://www.cloudflare.com/dns/',
                    'class'         => 'KPDNS_Cloudflare_API',
                    'api-path'          => KPDNS_PLUGIN_DIR . 'includes/providers/cloudflare/class-kpdns-cloudflare-api.php',
                    'hooks-class'       => 'KPDNS_Cloudflare_Hooks',
                    'hooks-path'        => KPDNS_PLUGIN_DIR . 'includes/providers/cloudflare/class-kpdns-cloudflare-hooks.php',
                    'credentials-class' => 'KPDNS_Cloudflare_Credentials',
                    'credentials-path'  => KPDNS_PLUGIN_DIR . 'includes/providers/cloudflare/class-kpdns-cloudflare-credentials.php',
                ),
                self::CLOUDNS => array(
                    'id'                => self::CLOUDNS,
                    'name'              => __( 'ClouDNS', 'keypress-dns' ),
                    'url'               => 'https://www.cloudns.net/',
                    'class'         => 'KPDNS_ClouDNS_API',
                    'api-path'          => KPDNS_PLUGIN_DIR . 'includes/providers/cloudns/class-kpdns-cloudns-api.php',
                    'hooks-class'       => 'KPDNS_ClouDNS_Hooks',
                    'hooks-path'        => KPDNS_PLUGIN_DIR . 'includes/providers/cloudns/class-kpdns-cloudns-hooks.php',
                    'credentials-class' => 'KPDNS_ClouDNS_Credentials',
                    'credentials-path'  => KPDNS_PLUGIN_DIR . 'includes/providers/cloudns/class-kpdns-cloudns-credentials.php',
                ),
                self::DNSME => array(
                    'id'                => self::DNSME,
                    'name'              => __( 'DNS Made Easy', 'keypress-dns' ),
                    'url'               => 'https://dnsmadeeasy.com/',
                    'class'         => 'KPDNS_DNSME_API',
                    'api-path'          => KPDNS_PLUGIN_DIR . 'includes/providers/dnsme/class-kpdns-dnsme-api.php',
                    'hooks-class'       => 'KPDNS_DNSME_Hooks',
                    'hooks-path'        => KPDNS_PLUGIN_DIR . 'includes/providers/dnsme/class-kpdns-dnsme-hooks.php',
                    'credentials-class' => 'KPDNS_DNSME_Credentials',
                    'credentials-path'  => KPDNS_PLUGIN_DIR . 'includes/providers/dnsme/class-kpdns-dnsme-credentials.php',
                ),
                self::GOOGLE_CLOUD_DNS => array(
                    'id'                => self::GOOGLE_CLOUD_DNS,
                    'name'              => __( 'Google Cloud DNS', 'keypress-dns' ),
                    'url'               => 'https://cloud.google.com/dns/',
                    'class'         => 'KPDNS_GCDNS_API',
                    'api-path'          => KPDNS_PLUGIN_DIR . 'includes/providers/gcdns/class-kpdns-gcdns-api.php',
                    'hooks-class'       => 'KPDNS_GCDNS_Hooks',
                    'hooks-path'        => KPDNS_PLUGIN_DIR . 'includes/providers/gcdns/class-kpdns-gcdns-hooks.php',
                    'credentials-class' => 'KPDNS_GCDNS_credentials',
                    'credentials-path'  => KPDNS_PLUGIN_DIR . 'includes/providers/gcdns/class-kpdns-gcdns-credentials.php',
                ),
                /*
                self::NEW_PROVIDER => array(
                    'id'                => self::NEW_PROVIDER,
                    'name'              => __( 'New Provider', 'keypress-dns' ),
                    'url'               => 'https://newprovider.com',
                    'class'         => 'KPDNS_New_Provider_API',
                    'api-path'          => KPDNS_PLUGIN_DIR . 'includes/providers/new-provider/class-kpdns-new-provider-api.php',
                    'hooks-class'       => 'KPDNS_New_Provider_Hooks',
                    'hooks-path'        => KPDNS_PLUGIN_DIR . 'includes/providers/new-provider/class-kpdns-new-provider-hooks.php',
                    'credentials-class' => 'KPDNS_New_Provider_Credentials',
                    'credentials-path'  => KPDNS_PLUGIN_DIR . 'includes/providers/new-provider/class-kpdns-new-provider-credentials.php',
                ),
                */
            );

            /**
             * Filters the providers configuration.
             *
             * @param array $config Providers configuration
             */
            $config = apply_filters( '', $config );

            return $config;
        }

        public static function is_valid_provider_config( $provider_id, $config ) {

		    if ( ! isset( $provider_id ) || empty( $provider_id ) || ! isset( $config ) || empty( $config ) ) {
                return false;
            }

            if (
                isset( $config[ $provider_id ] ) &&
                isset( $config[ $provider_id ]['credentials-path'] ) &&
                isset( $config[ $provider_id ]['name'] ) &&
                isset( $config[ $provider_id ]['credentials-class'] ) &&
                isset( $config[ $provider_id ]['url'] ) &&
                file_exists( $config[ $provider_id ]['credentials-path'] ) &&
                class_exists( $config[ $provider_id ]['credentials-class'] )
            ) {
                return true;
            }

            return false;
        }
	}
}
