<?php
/**
 * Gates registered scripts until their category is allowed.
 *
 * @package SB_Cookie_Consent
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Rewrites script tags for handles mapped to optional cookie categories.
 */
class SB_Cookie_Consent_Blocker {

	private SB_Cookie_Consent_Settings $settings;

	public function __construct( SB_Cookie_Consent_Settings $settings ) {
		$this->settings = $settings;
	}

	public function register(): void {
		add_filter( 'script_loader_tag', array( $this, 'filter_script_tag' ), 20, 3 );
		add_action( 'init', array( $this, 'purge' ), 1 );
	}

	public function purge(): void {
		if ( is_admin() || ! $this->settings->enabled() ) {
			return;
		}
		SB_Cookie_Consent_Consent::purge_denied( $this->settings );
	}

	/**
	 * @param string $tag    Full script tag.
	 * @param string $handle Script handle.
	 * @param string $src    Script URL.
	 */
	public function filter_script_tag( string $tag, string $handle, string $src ): string {
		if ( is_admin() || ! $this->settings->enabled() ) {
			return $tag;
		}

		$map = $this->settings->gated_handles();
		if ( ! isset( $map[ $handle ] ) ) {
			return $tag;
		}

		$category = $map[ $handle ];
		if ( SB_Cookie_Consent_Consent::allows( $category ) ) {
			return $tag;
		}

		// Delay execution until consent; frontend JS will activate matching tags.
		$tag = preg_replace( '/\stype=(["\'])(?:text\/javascript|module)\1/i', '', $tag ) ?? $tag;
		$tag = preg_replace( '/<script\b/i', '<script type="text/plain" data-sb-cc-category="' . esc_attr( $category ) . '" data-sb-cc-handle="' . esc_attr( $handle ) . '"', $tag, 1 ) ?? $tag;

		return $tag;
	}
}
