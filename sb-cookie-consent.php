<?php
/**
 * Plugin Name:       SB Cookie Consent
 * Plugin URI:        https://softwarebrewery.net
 * Description:       GDPR-oriented cookie consent with categories, cookie inventory, policy shortcode, reopen control, and script gating. Developed by Software Brewery.
 * Version:           1.2.0
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
define( 'SB_COOKIE_CONSENT_VERSION', '1.2.0' );
define( 'SB_COOKIE_CONSENT_AUTHOR_URL', 'https://softwarebrewery.net' );

require_once __DIR__ . '/includes/class-settings.php';
require_once __DIR__ . '/includes/class-consent.php';
require_once __DIR__ . '/includes/class-blocker.php';
require_once __DIR__ . '/includes/class-policy.php';
require_once __DIR__ . '/includes/class-scanner.php';
require_once __DIR__ . '/includes/class-admin.php';
require_once __DIR__ . '/includes/class-frontend.php';

/**
 * Boots the plugin.
 */
function sb_cookie_consent_boot(): void {
	$settings = new SB_Cookie_Consent_Settings();

	$blocker = new SB_Cookie_Consent_Blocker( $settings );
	$blocker->register();

	$policy = new SB_Cookie_Consent_Policy( $settings );
	$policy->register();

	$frontend = new SB_Cookie_Consent_Frontend( $settings );
	$frontend->register();

	if ( is_admin() ) {
		$admin = new SB_Cookie_Consent_Admin( $settings );
		$admin->register();
	}
}
add_action( 'plugins_loaded', 'sb_cookie_consent_boot' );

/**
 * Public helper: whether a category is currently allowed.
 */
function sb_cookie_consent_allows( string $category ): bool {
	return SB_Cookie_Consent_Consent::allows( $category );
}
