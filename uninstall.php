<?php
/**
 * Removes plugin options when the plugin is deleted from the dashboard.
 *
 * @package SB_Cookie_Consent
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'sb_cookie_consent_settings' );
