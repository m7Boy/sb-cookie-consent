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

	public const CATEGORIES = array( 'necessary', 'preferences', 'analytics', 'marketing' );

	/**
	 * Default values. Colors prefer theme global styles when available.
	 *
	 * @return array<string, mixed>
	 */
	public function defaults(): array {
		$theme = $this->theme_colors();

		return array(
			'enabled'              => true,
			'message'              => __( 'Използваме бисквитки, за да осигурим работата на сайта и (с ваше съгласие) за предпочитания, статистика и маркетинг. Можете да приемете всички, да откажете незадължителните или да изберете по категории.', 'sb-cookie-consent' ),
			'accept_label'         => __( 'Приемам всички', 'sb-cookie-consent' ),
			'decline_label'        => __( 'Само необходими', 'sb-cookie-consent' ),
			'settings_label'       => __( 'Настройки', 'sb-cookie-consent' ),
			'save_label'           => __( 'Запази избора', 'sb-cookie-consent' ),
			'show_decline'         => true,
			'cookie_policy_url'    => '',
			'cookie_policy_label'  => __( 'Политика за бисквитки', 'sb-cookie-consent' ),
			'show_policy_link'     => true,
			'show_reopen'          => true,
			'reopen_label'         => __( 'Настройки за бисквитки', 'sb-cookie-consent' ),
			'position'             => 'bottom',
			'cookie_days'          => 365,
			'bg_color'             => $theme['bg'],
			'text_color'           => $theme['text'],
			'button_bg'            => $theme['primary'],
			'button_text'          => $theme['button_text'],
			'decline_bg'           => 'transparent',
			'decline_text'         => $theme['text'],
			'use_theme_colors'     => true,
			'category_labels'      => $this->default_category_labels(),
			'category_descriptions'=> $this->default_category_descriptions(),
			'cookies'              => $this->default_cookies(),
		);
	}

	/**
	 * @return array<string, string>
	 */
	public function default_category_labels(): array {
		return array(
			'necessary'   => __( 'Необходими', 'sb-cookie-consent' ),
			'preferences' => __( 'Предпочитания', 'sb-cookie-consent' ),
			'analytics'   => __( 'Статистика', 'sb-cookie-consent' ),
			'marketing'   => __( 'Маркетинг', 'sb-cookie-consent' ),
		);
	}

	/**
	 * @return array<string, string>
	 */
	public function default_category_descriptions(): array {
		return array(
			'necessary'   => __( 'Нужни за основната работа на сайта и запомняне на вашия избор за бисквитки. Не могат да се изключат.', 'sb-cookie-consent' ),
			'preferences' => __( 'Запазват настройки като език или регион.', 'sb-cookie-consent' ),
			'analytics'   => __( 'Помагат да разберем как се използва сайтът (анонимна или агрегирана статистика).', 'sb-cookie-consent' ),
			'marketing'   => __( 'Използват се за реклама и проследяване между сайтове.', 'sb-cookie-consent' ),
		);
	}

	/**
	 * Starter inventory: WordPress / plugin essentials; other categories empty on purpose.
	 *
	 * @return array<int, array<string, string>>
	 */
	public function default_cookies(): array {
		return array(
			array(
				'name'         => 'sb_cookie_consent',
				'category'     => 'necessary',
				'provider'     => 'SB Cookie Consent',
				'purpose'      => __( 'Запазва избора ви за категории бисквитки.', 'sb-cookie-consent' ),
				'duration'     => __( 'До 365 дни (според настройката)', 'sb-cookie-consent' ),
				'script_handles' => '',
			),
			array(
				'name'         => 'wordpress_test_cookie',
				'category'     => 'necessary',
				'provider'     => 'WordPress',
				'purpose'      => __( 'Проверява дали браузърът приема бисквитки.', 'sb-cookie-consent' ),
				'duration'     => __( 'Сесия', 'sb-cookie-consent' ),
				'script_handles' => '',
			),
			array(
				'name'         => 'wordpress_logged_in_*',
				'category'     => 'necessary',
				'provider'     => 'WordPress',
				'purpose'      => __( 'Поддържа влизането в администрацията / акаунта.', 'sb-cookie-consent' ),
				'duration'     => __( 'Сесия / според WordPress', 'sb-cookie-consent' ),
				'script_handles' => '',
			),
			array(
				'name'         => 'wp-settings-*',
				'category'     => 'necessary',
				'provider'     => 'WordPress',
				'purpose'      => __( 'Запазва настройки на интерфейса за влезли потребители.', 'sb-cookie-consent' ),
				'duration'     => __( '1 година', 'sb-cookie-consent' ),
				'script_handles' => '',
			),
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

		// Migrate old privacy fields once.
		if ( empty( $saved['cookie_policy_url'] ) && ! empty( $saved['privacy_url'] ) ) {
			$saved['cookie_policy_url'] = $saved['privacy_url'];
		}
		if ( empty( $saved['cookie_policy_label'] ) && ! empty( $saved['privacy_label'] ) ) {
			$saved['cookie_policy_label'] = $saved['privacy_label'];
		}

		$defaults = $this->defaults();
		$settings = array_merge( $defaults, $saved );

		$settings['category_labels']       = array_merge( $defaults['category_labels'], is_array( $saved['category_labels'] ?? null ) ? $saved['category_labels'] : array() );
		$settings['category_descriptions'] = array_merge( $defaults['category_descriptions'], is_array( $saved['category_descriptions'] ?? null ) ? $saved['category_descriptions'] : array() );

		if ( ! isset( $saved['cookies'] ) || ! is_array( $saved['cookies'] ) ) {
			$settings['cookies'] = $defaults['cookies'];
		} else {
			$settings['cookies'] = $saved['cookies'];
		}

		if ( ! empty( $settings['use_theme_colors'] ) ) {
			$theme                    = $this->theme_colors();
			$settings['bg_color']     = $theme['bg'];
			$settings['text_color']   = $theme['text'];
			$settings['button_bg']    = $theme['primary'];
			$settings['button_text']  = $theme['button_text'];
			$settings['decline_text'] = $theme['text'];
		}

		return $settings;
	}

	public function enabled(): bool {
		return ! empty( $this->all()['enabled'] );
	}

	/**
	 * Cookies grouped by category (always includes every category key).
	 *
	 * @return array<string, array<int, array<string, string>>>
	 */
	public function cookies_by_category(): array {
		$grouped = array();
		foreach ( self::CATEGORIES as $category ) {
			$grouped[ $category ] = array();
		}

		foreach ( $this->all()['cookies'] as $cookie ) {
			if ( ! is_array( $cookie ) ) {
				continue;
			}
			$category = isset( $cookie['category'] ) ? sanitize_key( (string) $cookie['category'] ) : 'necessary';
			if ( ! isset( $grouped[ $category ] ) ) {
				$category = 'necessary';
			}
			$grouped[ $category ][] = $cookie;
		}

		return $grouped;
	}

	/**
	 * Map of WP script handle => category for gating.
	 *
	 * @return array<string, string>
	 */
	public function gated_handles(): array {
		$map = array();

		foreach ( $this->all()['cookies'] as $cookie ) {
			if ( ! is_array( $cookie ) || empty( $cookie['script_handles'] ) ) {
				continue;
			}
			$category = isset( $cookie['category'] ) ? sanitize_key( (string) $cookie['category'] ) : '';
			if ( ! in_array( $category, self::CATEGORIES, true ) || 'necessary' === $category ) {
				continue;
			}
			$handles = preg_split( '/[\s,]+/', (string) $cookie['script_handles'] ) ?: array();
			foreach ( $handles as $handle ) {
				$handle = sanitize_key( $handle );
				if ( '' !== $handle ) {
					$map[ $handle ] = $category;
				}
			}
		}

		/**
		 * Filters script handles gated by cookie category.
		 *
		 * @param array<string, string> $map Handle => category.
		 */
		return (array) apply_filters( 'sb_cookie_consent_gated_handles', $map );
	}

	/**
	 * Cookie name patterns to remove when a category is off.
	 *
	 * @param string $category Category slug.
	 * @return string[]
	 */
	public function cookie_names_for_category( string $category ): array {
		$names = array();
		foreach ( $this->all()['cookies'] as $cookie ) {
			if ( ! is_array( $cookie ) ) {
				continue;
			}
			if ( ( $cookie['category'] ?? '' ) !== $category ) {
				continue;
			}
			$name = isset( $cookie['name'] ) ? trim( (string) $cookie['name'] ) : '';
			if ( '' !== $name && 'sb_cookie_consent' !== $name ) {
				$names[] = $name;
			}
		}

		return $names;
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
		$days = max( 1, min( 3650, $days ) );

		$labels = $defaults['category_labels'];
		$descs  = $defaults['category_descriptions'];
		if ( isset( $input['category_labels'] ) && is_array( $input['category_labels'] ) ) {
			foreach ( self::CATEGORIES as $cat ) {
				if ( isset( $input['category_labels'][ $cat ] ) ) {
					$labels[ $cat ] = sanitize_text_field( (string) $input['category_labels'][ $cat ] );
				}
			}
		}
		if ( isset( $input['category_descriptions'] ) && is_array( $input['category_descriptions'] ) ) {
			foreach ( self::CATEGORIES as $cat ) {
				if ( isset( $input['category_descriptions'][ $cat ] ) ) {
					$descs[ $cat ] = sanitize_textarea_field( (string) $input['category_descriptions'][ $cat ] );
				}
			}
		}

		return array(
			'enabled'               => ! empty( $input['enabled'] ),
			'message'               => isset( $input['message'] ) ? sanitize_textarea_field( (string) $input['message'] ) : $defaults['message'],
			'accept_label'          => isset( $input['accept_label'] ) ? sanitize_text_field( (string) $input['accept_label'] ) : $defaults['accept_label'],
			'decline_label'         => isset( $input['decline_label'] ) ? sanitize_text_field( (string) $input['decline_label'] ) : $defaults['decline_label'],
			'settings_label'        => isset( $input['settings_label'] ) ? sanitize_text_field( (string) $input['settings_label'] ) : $defaults['settings_label'],
			'save_label'            => isset( $input['save_label'] ) ? sanitize_text_field( (string) $input['save_label'] ) : $defaults['save_label'],
			'show_decline'          => ! empty( $input['show_decline'] ),
			'cookie_policy_url'     => isset( $input['cookie_policy_url'] ) ? esc_url_raw( (string) $input['cookie_policy_url'] ) : '',
			'cookie_policy_label'   => isset( $input['cookie_policy_label'] ) ? sanitize_text_field( (string) $input['cookie_policy_label'] ) : $defaults['cookie_policy_label'],
			'show_policy_link'      => ! empty( $input['show_policy_link'] ),
			'show_reopen'           => ! empty( $input['show_reopen'] ),
			'reopen_label'          => isset( $input['reopen_label'] ) ? sanitize_text_field( (string) $input['reopen_label'] ) : $defaults['reopen_label'],
			'position'              => $position,
			'cookie_days'           => $days,
			'bg_color'              => $this->sanitize_color( $input['bg_color'] ?? '', $defaults['bg_color'] ),
			'text_color'            => $this->sanitize_color( $input['text_color'] ?? '', $defaults['text_color'] ),
			'button_bg'             => $this->sanitize_color( $input['button_bg'] ?? '', $defaults['button_bg'] ),
			'button_text'           => $this->sanitize_color( $input['button_text'] ?? '', $defaults['button_text'] ),
			'decline_bg'            => $this->sanitize_color( $input['decline_bg'] ?? '', 'transparent', true ),
			'decline_text'          => $this->sanitize_color( $input['decline_text'] ?? '', $defaults['decline_text'] ),
			'use_theme_colors'      => ! empty( $input['use_theme_colors'] ),
			'category_labels'       => $labels,
			'category_descriptions' => $descs,
			'cookies'               => $this->sanitize_cookies( $input['cookies'] ?? array() ),
		);
	}

	/**
	 * @param mixed $rows Raw cookie rows.
	 * @return array<int, array<string, string>>
	 */
	private function sanitize_cookies( $rows ): array {
		if ( ! is_array( $rows ) ) {
			return array();
		}

		$clean = array();
		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$name = isset( $row['name'] ) ? sanitize_text_field( (string) $row['name'] ) : '';
			if ( '' === $name ) {
				continue;
			}
			$category = isset( $row['category'] ) ? sanitize_key( (string) $row['category'] ) : 'necessary';
			if ( ! in_array( $category, self::CATEGORIES, true ) ) {
				$category = 'necessary';
			}
			$clean[] = array(
				'name'           => $name,
				'category'       => $category,
				'provider'       => isset( $row['provider'] ) ? sanitize_text_field( (string) $row['provider'] ) : '',
				'purpose'        => isset( $row['purpose'] ) ? sanitize_text_field( (string) $row['purpose'] ) : '',
				'duration'       => isset( $row['duration'] ) ? sanitize_text_field( (string) $row['duration'] ) : '',
				'script_handles' => isset( $row['script_handles'] ) ? sanitize_text_field( (string) $row['script_handles'] ) : '',
			);
		}

		return $clean;
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
