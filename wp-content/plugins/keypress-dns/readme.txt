=== Customize WP UI ===
Author URI: https://getkeypress.com/
Plugin URI: https://getkeypress.com/downloads/dns-manager
Contributors: Asier Moreno
Tags: multisite, waas, dns
Requires at least: 4.4
Tested up to: 5.4.1
Stable tag: 5.4.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html


== Description ==

With DNS Manager, you'll connect your WordPress dashboard with a compatible managed DNS provider to create zones and records for you customer's websites directly from the WordPress dashboard.

== Installation ==

1. Activate the plugin
2. Go to (Network Admin) DNS Manager > Settings and set up your managed DNS provider's credentials.
3. Manage your DNS zones remotely.

== Changelog ==

= 0.1.0: June 10, 2018 =
* Keypress DNS Manager is born. This is a first pre-alpha release only available for the plugin's founding members.
* Provides the basic file structure and implements the license management system by EDD Software Licensing.

= 0.9.0: October, 29, 2019 =

* Official beta release.

= 1.0.0: November, 26, 2019 =

* Official release.

= 1.0.1: November, 27, 2019 =

* Added support for single installs.

= 1.0.2: December, 2, 2019 =

* Performance enhancements.

= 1.0.2.1: December, 13, 2019 =

* Fixed error when creating a zone without a custom NS.

= 1.0.3: December, 19, 2019 =

* Added support for Google Cloud DNS.

= 1.0.3.1: December, 20, 2019 =

* Added "Getting Started" tab.

= 1.1: February, 18, 2020 =

* Added support for ClouDNS.
* Added "Getting Started" tab in the settings page.
* Improved Custom NS creation screens.
* Improved record creation.
* Many internal improvements.
* Added labels to differentiate the primary zone and those corresponding to custom name servers.
* Added modal popups before performing critical tasks.
* Added the ability to perform bulk actions: delete zones, update A records and update AAAA records.

= 1.2: March, 3, 2020 =

* Added support for CloudFlare.
* Fixed error in name field when editing a record.
* Fixed errors when adding/editing MX, CAA and SRV.
* Added new filters and actions.

= 1.2.1: March, 9, 2020 =

* Fixed error when listing or adding custom name servers after upgrading to version 1.2.

= 1.2.2: April, 17, 2020 =

* Fixed error that made WP Ultimo settings tab not to be displayed.
* Added the ability to edit WP Ultimo's domain mapping metabox texts.
* Added the ability to enable the creation of CNAME records for each subdomain in the primary DNS zone.
* Performance enhancements.

= 1.3: May, 19, 2020 =

* Added support for DNS Made Easy.
* Added the ability to search for zones and custom NS.
* Added the ability to bulk delete custom NS.
* Added pagination to zones and custom NS lists.
* Primary zone and custom NS lockout to prevent accidental deletion.