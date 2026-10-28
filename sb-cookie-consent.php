<?php
/**
 * Plugin Name:       SB Cookie Consent
 * Plugin URI:        https://softwarebrewery.net
 * Description:       Cookie consent banner with admin controls and theme-aware default colors. Developed by Software Brewery.
 * Version:           1.0.1
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Software Brewery
 * Author URI:        https://softwarebrewery.net
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       sb-cookie-consent
 * Domain Path:       /languages
 *
 * @package SB_Cookie_Consent
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SB_COOKIE_CONSENT_FILE', __FILE__ );
define( 'SB_COOKIE_CONSENT_VERSION', '1.0.1' );
define( 'SB_COOKIE_CONSENT_AUTHOR_URL', 'https://softwarebrewery.net' );

require_once __DIR__ . '/includes/class-settings.php';
require_once __DIR__ . '/includes/class-admin.php';
require_once __DIR__ . '/includes/class-frontend.php';

/**
 * Boots the plugin.
 */
function sb_cookie_consent_boot(): void {
	$settings = new SB_Cookie_Consent_Settings();

	if ( is_admin() ) {
		$admin = new SB_Cookie_Consent_Admin( $settings );
		$admin->register();
	}

	$frontend = new SB_Cookie_Consent_Frontend( $settings );
	$frontend->register();
}
add_action( 'plugins_loaded', 'sb_cookie_consent_boot' );
