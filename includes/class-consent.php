<?php
/**
 * Consent cookie read / normalize helpers.
 *
 * @package SB_Cookie_Consent
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Parses the visitor consent cookie.
 */
class SB_Cookie_Consent_Consent {

	public const COOKIE = 'sb_cookie_consent';

	/**
	 * Default: only necessary until the visitor chooses.
	 *
	 * @return array<string, bool>
	 */
	public static function defaults(): array {
		return array(
			'necessary'   => true,
			'preferences' => false,
			'analytics'   => false,
			'marketing'   => false,
		);
	}

	/**
	 * Whether any consent choice was stored.
	 */
	public static function has_choice(): bool {
		return null !== self::raw();
	}

	/**
	 * Current categories (necessary always true).
	 *
	 * @return array<string, bool>
	 */
	public static function categories(): array {
		$raw = self::raw();
		if ( null === $raw ) {
			return self::defaults();
		}

		return self::normalize( $raw );
	}

	public static function allows( string $category ): bool {
		$category = sanitize_key( $category );
		if ( 'necessary' === $category ) {
			return true;
		}

		$cats = self::categories();

		return ! empty( $cats[ $category ] );
	}

	/**
	 * Raw cookie string or null.
	 */
	public static function raw(): ?string {
		if ( ! isset( $_COOKIE[ self::COOKIE ] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			return null;
		}

		$value = wp_unslash( (string) $_COOKIE[ self::COOKIE ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$value = rawurldecode( $value );

		return '' === $value ? null : $value;
	}

	/**
	 * @param string $raw Cookie value.
	 * @return array<string, bool>
	 */
	public static function normalize( string $raw ): array {
		$out = self::defaults();

		// Legacy simple values from 1.0.x.
		if ( 'accepted' === $raw ) {
			foreach ( SB_Cookie_Consent_Settings::CATEGORIES as $cat ) {
				$out[ $cat ] = true;
			}
			return $out;
		}
		if ( 'declined' === $raw ) {
			return $out;
		}

		$decoded = json_decode( $raw, true );
		if ( ! is_array( $decoded ) ) {
			return $out;
		}

		foreach ( SB_Cookie_Consent_Settings::CATEGORIES as $cat ) {
			if ( 'necessary' === $cat ) {
				$out[ $cat ] = true;
				continue;
			}
			$out[ $cat ] = ! empty( $decoded[ $cat ] );
		}

		return $out;
	}

	/**
	 * Delete first-party cookies matching inventory patterns for denied categories.
	 *
	 * @param SB_Cookie_Consent_Settings $settings Settings.
	 */
	public static function purge_denied( SB_Cookie_Consent_Settings $settings ): void {
		if ( ! self::has_choice() ) {
			return;
		}

		$host   = wp_parse_url( home_url(), PHP_URL_HOST );
		$host   = is_string( $host ) ? $host : '';
		$domain = $host ? '.' . ltrim( $host, '.' ) : '';

		foreach ( SB_Cookie_Consent_Settings::CATEGORIES as $category ) {
			if ( 'necessary' === $category || self::allows( $category ) ) {
				continue;
			}
			foreach ( $settings->cookie_names_for_category( $category ) as $pattern ) {
				self::expire_matching( $pattern, $domain );
			}
		}
	}

	/**
	 * @param string $pattern Cookie name or wildcard (*).
	 * @param string $domain  Leading-dot domain.
	 */
	private static function expire_matching( string $pattern, string $domain ): void {
		$regex = '/^' . str_replace( '\*', '.*', preg_quote( $pattern, '/' ) ) . '$/';

		foreach ( array_keys( $_COOKIE ) as $name ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$name = (string) $name;
			if ( self::COOKIE === $name ) {
				continue;
			}
			if ( ! preg_match( $regex, $name ) ) {
				continue;
			}
			setcookie( $name, '', time() - YEAR_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN ? COOKIE_DOMAIN : '' );
			if ( $domain ) {
				setcookie( $name, '', time() - YEAR_IN_SECONDS, '/', $domain );
			}
			unset( $_COOKIE[ $name ] );
		}
	}
}
