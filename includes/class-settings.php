<?php
/**
 * Stored options for SB Cookie Consent.
 *
 * @package SB_Cookie_Consent
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reads and sanitizes the plugin option.
 */
class SB_Cookie_Consent_Settings {

	public const OPTION = 'sb_cookie_consent_settings';

	/**
	 * Default values. Colors prefer theme global styles when available.
	 *
	 * @return array<string, mixed>
	 */
	public function defaults(): array {
		$theme = $this->theme_colors();

		return array(
			'enabled'           => true,
			'message'           => __( 'Този сайт използва бисквитки, за да подобри вашето преживяване. С продължаване се съгласявате с използването им.', 'sb-cookie-consent' ),
			'accept_label'      => __( 'Съгласявам се', 'sb-cookie-consent' ),
			'decline_label'     => __( 'Отказвам', 'sb-cookie-consent' ),
			'show_decline'      => true,
			'privacy_url'       => '',
			'privacy_label'     => __( 'Политика за поверителност', 'sb-cookie-consent' ),
			'position'          => 'bottom',
			'cookie_days'       => 365,
			'bg_color'          => $theme['bg'],
			'text_color'        => $theme['text'],
			'button_bg'         => $theme['primary'],
			'button_text'       => $theme['button_text'],
			'decline_bg'        => 'transparent',
			'decline_text'      => $theme['text'],
			'use_theme_colors'  => true,
		);
	}

	/**
	 * Pull primary palette colors from the active theme (theme.json / global styles).
	 *
	 * @return array{bg:string,text:string,primary:string,button_text:string}
	 */
	public function theme_colors(): array {
		$fallback = array(
			'bg'          => '#1e1e1e',
			'text'        => '#ffffff',
			'primary'     => '#2271b1',
			'button_text' => '#ffffff',
		);

		if ( ! function_exists( 'wp_get_global_settings' ) ) {
			return $fallback;
		}

		$palette = wp_get_global_settings( array( 'color', 'palette' ) );
		$colors  = array();

		if ( is_array( $palette ) ) {
			foreach ( $palette as $group ) {
				if ( ! is_array( $group ) ) {
					continue;
				}
				foreach ( $group as $swatch ) {
					if ( ! is_array( $swatch ) || empty( $swatch['slug'] ) || empty( $swatch['color'] ) ) {
						continue;
					}
					$colors[ (string) $swatch['slug'] ] = (string) $swatch['color'];
				}
			}
		}

		// Banner uses a dark strip by default: theme contrast/foreground as bg, light text.
		$bg = $this->pick_color(
			$colors,
			array( 'contrast', 'foreground', 'black', 'dark-gray', 'primary' ),
			$fallback['bg']
		);

		$text = $this->is_light( $bg ) ? '#1e1e1e' : '#ffffff';

		$primary = $this->pick_color(
			$colors,
			array( 'primary', 'vivid-cyan-blue', 'secondary', 'accent', 'button' ),
			$fallback['primary']
		);

		// Avoid unreadable button when primary matches the banner background.
		if ( strtolower( $primary ) === strtolower( $bg ) ) {
			$primary = $fallback['primary'];
		}

		$button_text = $this->is_light( $primary ) ? '#1e1e1e' : '#ffffff';

		/**
		 * Filters theme-derived default colors for the cookie banner.
		 *
		 * @param array{bg:string,text:string,primary:string,button_text:string} $colors Resolved colors.
		 */
		return (array) apply_filters(
			'sb_cookie_consent_theme_colors',
			array(
				'bg'          => $bg,
				'text'        => $text,
				'primary'     => $primary,
				'button_text' => $button_text,
			)
		);
	}

	/**
	 * @param array<string, string> $colors Palette keyed by slug.
	 * @param string[]              $slugs  Preferred slug order.
	 * @param string                $default Fallback hex/rgb.
	 */
	private function pick_color( array $colors, array $slugs, string $default ): string {
		foreach ( $slugs as $slug ) {
			if ( ! empty( $colors[ $slug ] ) ) {
				return $this->normalize_color( (string) $colors[ $slug ], $default );
			}
		}

		return $default;
	}

	/**
	 * Normalize CSS color to a hex string when possible.
	 */
	private function normalize_color( string $color, string $fallback ): string {
		$color = trim( $color );

		if ( preg_match( '/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $color ) ) {
			return strtolower( $color );
		}

		if ( preg_match( '/^rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)/i', $color, $m ) ) {
			return sprintf( '#%02x%02x%02x', (int) $m[1], (int) $m[2], (int) $m[3] );
		}

		return $fallback;
	}

	private function is_light( string $hex ): bool {
		$hex = ltrim( $hex, '#' );
		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}
		if ( 6 !== strlen( $hex ) ) {
			return false;
		}

		$r = hexdec( substr( $hex, 0, 2 ) );
		$g = hexdec( substr( $hex, 2, 2 ) );
		$b = hexdec( substr( $hex, 4, 2 ) );

		// Relative luminance threshold.
		return ( ( $r * 299 ) + ( $g * 587 ) + ( $b * 114 ) ) / 1000 >= 160;
	}

	/**
	 * Effective settings (saved values, with live theme colors when opted in).
	 *
	 * @return array<string, mixed>
	 */
	public function all(): array {
		$saved = get_option( self::OPTION, array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}

		$settings = array_merge( $this->defaults(), $saved );

		if ( ! empty( $settings['use_theme_colors'] ) ) {
			$theme                     = $this->theme_colors();
			$settings['bg_color']      = $theme['bg'];
			$settings['text_color']    = $theme['text'];
			$settings['button_bg']     = $theme['primary'];
			$settings['button_text']   = $theme['button_text'];
			$settings['decline_text']  = $theme['text'];
		}

		return $settings;
	}

	public function enabled(): bool {
		return ! empty( $this->all()['enabled'] );
	}

	/**
	 * Sanitizes the Settings API payload.
	 *
	 * @param mixed $input Raw option value.
	 * @return array<string, mixed>
	 */
	public function sanitize( $input ): array {
		if ( ! is_array( $input ) ) {
			$input = array();
		}

		$defaults  = $this->defaults();
		$positions = array( 'bottom', 'top' );
		$position  = isset( $input['position'] ) ? sanitize_key( (string) $input['position'] ) : 'bottom';
		if ( ! in_array( $position, $positions, true ) ) {
			$position = 'bottom';
		}

		$days = isset( $input['cookie_days'] ) ? absint( $input['cookie_days'] ) : 365;
		if ( $days < 1 ) {
			$days = 1;
		}
		if ( $days > 3650 ) {
			$days = 3650;
		}

		return array(
			'enabled'          => ! empty( $input['enabled'] ),
			'message'          => isset( $input['message'] ) ? sanitize_textarea_field( (string) $input['message'] ) : $defaults['message'],
			'accept_label'     => isset( $input['accept_label'] ) ? sanitize_text_field( (string) $input['accept_label'] ) : $defaults['accept_label'],
			'decline_label'    => isset( $input['decline_label'] ) ? sanitize_text_field( (string) $input['decline_label'] ) : $defaults['decline_label'],
			'show_decline'     => ! empty( $input['show_decline'] ),
			'privacy_url'      => isset( $input['privacy_url'] ) ? esc_url_raw( (string) $input['privacy_url'] ) : '',
			'privacy_label'    => isset( $input['privacy_label'] ) ? sanitize_text_field( (string) $input['privacy_label'] ) : $defaults['privacy_label'],
			'position'         => $position,
			'cookie_days'      => $days,
			'bg_color'         => $this->sanitize_color( $input['bg_color'] ?? '', $defaults['bg_color'] ),
			'text_color'       => $this->sanitize_color( $input['text_color'] ?? '', $defaults['text_color'] ),
			'button_bg'        => $this->sanitize_color( $input['button_bg'] ?? '', $defaults['button_bg'] ),
			'button_text'      => $this->sanitize_color( $input['button_text'] ?? '', $defaults['button_text'] ),
			'decline_bg'       => $this->sanitize_color( $input['decline_bg'] ?? '', 'transparent', true ),
			'decline_text'     => $this->sanitize_color( $input['decline_text'] ?? '', $defaults['decline_text'] ),
			'use_theme_colors' => ! empty( $input['use_theme_colors'] ),
		);
	}

	/**
	 * @param mixed  $value Raw color.
	 * @param string $fallback Default.
	 * @param bool   $allow_transparent Allow "transparent".
	 */
	private function sanitize_color( $value, string $fallback, bool $allow_transparent = false ): string {
		$value = is_string( $value ) ? trim( $value ) : '';

		if ( $allow_transparent && 'transparent' === strtolower( $value ) ) {
			return 'transparent';
		}

		$hex = sanitize_hex_color( $value );
		if ( $hex ) {
			return strtolower( $hex );
		}

		return $fallback;
	}
}
