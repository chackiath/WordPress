<?php

/**
 * Plugin Name:       Keypress DNS Manager
 * Plugin URI:        https://getkeypress.com/downloads/dns-manager
 * Description: 	  Helps you manage your DNS
 * Version:           1.3
 * Author:            KeyPress Media LLC
 * Author URI:        https://getkeypress.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       keypress-dns
 * Domain Path:       /languages
 *
 * Keypress DNS Manager is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * Keypress DNS Manager is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Keypress DNS Manager. If not, see <http://www.gnu.org/licenses/>.
 *
 *
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	 exit;
}

// Plugin loader.
require_once plugin_dir_path( __FILE__ ) . 'includes/class-kpdns.php';

/**
 * The main function that returns a KPDNS instance.
 *
 * @since  1.3
 * @return object|KPDNS The KPDNS Instance.
 */
function KPDNS() {
    return KPDNS::instance();
}

// Get KPDNS Running.
if ( is_admin() ) {
    KPDNS();
}
