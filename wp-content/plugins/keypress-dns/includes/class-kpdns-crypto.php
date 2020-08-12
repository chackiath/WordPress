<?php
/**
 * Description
 *
 * @package ${NAMESPACE}
 * @since 1.0.0
 * @author Asier Moreno
 * @link https://getkeypress.com
 * @license GNU General Public License 2.0+
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'KPDNS_Crypto' ) ) {

	/**
	 * Class KPDNS_Crypto
	 *
	 * @see https://make.wordpress.org/core/2019/05/17/security-in-5-2/
	 * @see https://deliciousbrains.com/php-encryption-methods/
	 * @see https://torquemag.io/2016/10/storing-encrypted-data-wordpress-database/
	 */
	final class KPDNS_Crypto {
		/**
		 * @param string $message
		 * @param string $key
		 * @return string
		 */
		public static function encrypt( $message, $key ) {
			$nonce = random_bytes(24);
			return base64_encode(
				$nonce . sodium_crypto_aead_xchacha20poly1305_ietf_encrypt(
					$message,
					$nonce,
					$nonce,
					$key
				)
			);
		}

		/**
		 * @param string $message
		 * @param string $key
		 * @return string
		 */
		public static function decrypt( $message, $key ) {
			$decoded = base64_decode($message);
			$nonce = substr($decoded, 0, 24);
			$ciphertext = substr($decoded, 24);
			return sodium_crypto_aead_xchacha20poly1305_ietf_decrypt(
				$ciphertext,
				$nonce,
				$nonce,
				$key
			);
		}

		public static function keygen() {
			return sodium_crypto_stream_keygen();
		}

	}
}