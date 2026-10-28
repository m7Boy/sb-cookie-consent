<?php
/**
 * Detects common third-party cookies / script handles on the site.
 *
 * @package SB_Cookie_Consent
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds suggested cookie inventory rows from registered scripts and known catalogs.
 */
class SB_Cookie_Consent_Scanner {

	/**
	 * Known services: match WP handles / script URLs / active plugins, then suggest cookies.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function catalog(): array {
		$catalog = array(
			array(
				'id'             => 'ga4',
				'detect_handles' => array( 'google-gtag', 'gtag', 'ga-gtag', 'google-analytics', 'google_gtagjs', 'woocommerce-google-analytics', 'exactmetrics', 'monsterinsights' ),
				'detect_src'     => array( 'googletagmanager.com/gtag', 'google-analytics.com/analytics', 'google-analytics.com/ga.js', 'googletagmanager.com/gtag/js' ),
				'detect_plugins' => array( 'google-site-kit/', 'google-analytics-for-wordpress/', 'google-analytics-premium/', 'exactmetrics-premium/', 'woocommerce-google-analytics-integration/' ),
				'cookies'        => array(
					$this->row( '_ga', 'analytics', 'Google Analytics', __( 'Различава потребителите.', 'sb-cookie-consent' ), __( '2 години', 'sb-cookie-consent' ), 'google-gtag,gtag,google-analytics' ),
					$this->row( '_ga_*', 'analytics', 'Google Analytics', __( 'Запазва състоянието на сесията.', 'sb-cookie-consent' ), __( '2 години', 'sb-cookie-consent' ), '' ),
					$this->row( '_gid', 'analytics', 'Google Analytics', __( 'Различава потребителите.', 'sb-cookie-consent' ), __( '24 часа', 'sb-cookie-consent' ), '' ),
					$this->row( '_gat*', 'analytics', 'Google Analytics', __( 'Ограничава честотата на заявките.', 'sb-cookie-consent' ), __( '1 минута', 'sb-cookie-consent' ), '' ),
				),
			),
			array(
				'id'             => 'gtm',
				'detect_handles' => array( 'gtm', 'google-tag-manager', 'google_tag_manager' ),
				'detect_src'     => array( 'googletagmanager.com/gtm.js' ),
				'detect_plugins' => array( 'duracelltomi-google-tag-manager/', 'gtm-kit/' ),
				'cookies'        => array(
					$this->row( '_gcl_au', 'marketing', 'Google Tag Manager', __( 'Експерименти / рекламна ефективност.', 'sb-cookie-consent' ), __( '3 месеца', 'sb-cookie-consent' ), 'gtm,google-tag-manager' ),
				),
			),
			array(
				'id'             => 'facebook',
				'detect_handles' => array( 'facebook-pixel', 'fb-pixel', 'fbevents', 'meta-pixel' ),
				'detect_src'     => array( 'connect.facebook.net', 'fbevents.js' ),
				'detect_plugins' => array( 'pixelyoursite/', 'official-facebook-pixel/', 'facebook-for-woocommerce/' ),
				'cookies'        => array(
					$this->row( '_fbp', 'marketing', 'Meta (Facebook)', __( 'Доставя реклами / Pixel.', 'sb-cookie-consent' ), __( '3 месеца', 'sb-cookie-consent' ), 'facebook-pixel,fbevents,meta-pixel' ),
					$this->row( '_fbc', 'marketing', 'Meta (Facebook)', __( 'Съхранява click ID от реклами.', 'sb-cookie-consent' ), __( '3 месеца', 'sb-cookie-consent' ), '' ),
					$this->row( 'fr', 'marketing', 'Meta (Facebook)', __( 'Реклама и измерване.', 'sb-cookie-consent' ), __( '3 месеца', 'sb-cookie-consent' ), '' ),
				),
			),
			array(
				'id'             => 'hotjar',
				'detect_handles' => array( 'hotjar', 'hotjar-js' ),
				'detect_src'     => array( 'static.hotjar.com', 'script.hotjar.com' ),
				'detect_plugins' => array( 'hotjar/' ),
				'cookies'        => array(
					$this->row( '_hjSessionUser_*', 'analytics', 'Hotjar', __( 'Запазва уникален потребителски ID.', 'sb-cookie-consent' ), __( '1 година', 'sb-cookie-consent' ), 'hotjar' ),
					$this->row( '_hjSession_*', 'analytics', 'Hotjar', __( 'Държи данни за текущата сесия.', 'sb-cookie-consent' ), __( '30 минути', 'sb-cookie-consent' ), '' ),
					$this->row( '_hjAbsoluteSessionInProgress', 'analytics', 'Hotjar', __( 'Открива първа посетена страница в сесията.', 'sb-cookie-consent' ), __( '30 минути', 'sb-cookie-consent' ), '' ),
				),
			),
			array(
				'id'             => 'linkedin',
				'detect_handles' => array( 'linkedin-insight', 'li-insight' ),
				'detect_src'     => array( 'snap.licdn.com', 'linkedin.com/px' ),
				'detect_plugins' => array(),
				'cookies'        => array(
					$this->row( 'li_sugr', 'marketing', 'LinkedIn', __( 'Browser Identifier.', 'sb-cookie-consent' ), __( '3 месеца', 'sb-cookie-consent' ), 'linkedin-insight' ),
					$this->row( 'AnalyticsSyncHistory', 'marketing', 'LinkedIn', __( 'Синхронизация на реклами.', 'sb-cookie-consent' ), __( '1 месец', 'sb-cookie-consent' ), '' ),
					$this->row( 'bcookie', 'marketing', 'LinkedIn', __( 'Browser cookie за сигурност / проследяване.', 'sb-cookie-consent' ), __( '1 година', 'sb-cookie-consent' ), '' ),
					$this->row( 'lidc', 'marketing', 'LinkedIn', __( 'Избор на дата център.', 'sb-cookie-consent' ), __( '1 ден', 'sb-cookie-consent' ), '' ),
				),
			),
			array(
				'id'             => 'tiktok',
				'detect_handles' => array( 'tiktok-pixel', 'ttq' ),
				'detect_src'     => array( 'analytics.tiktok.com' ),
				'detect_plugins' => array(),
				'cookies'        => array(
					$this->row( '_ttp', 'marketing', 'TikTok', __( 'Проследяване / реклами.', 'sb-cookie-consent' ), __( '13 месеца', 'sb-cookie-consent' ), 'tiktok-pixel' ),
					$this->row( '_tt_enable_cookie', 'marketing', 'TikTok', __( 'Разрешава TikTok cookies.', 'sb-cookie-consent' ), __( '13 месеца', 'sb-cookie-consent' ), '' ),
				),
			),
			array(
				'id'             => 'microsoft_ads',
				'detect_handles' => array( 'uetq', 'bing-ads', 'microsoft-ads' ),
				'detect_src'     => array( 'bat.bing.com' ),
				'detect_plugins' => array(),
				'cookies'        => array(
					$this->row( '_uetsid', 'marketing', 'Microsoft Advertising', __( 'Сесия за UET.', 'sb-cookie-consent' ), __( '1 ден', 'sb-cookie-consent' ), 'uetq,bing-ads' ),
					$this->row( '_uetvid', 'marketing', 'Microsoft Advertising', __( 'Уникален посетител за UET.', 'sb-cookie-consent' ), __( '13 месеца', 'sb-cookie-consent' ), '' ),
					$this->row( 'MUID', 'marketing', 'Microsoft', __( 'Уникален идентификатор на браузъра.', 'sb-cookie-consent' ), __( '1 година', 'sb-cookie-consent' ), '' ),
				),
			),
			array(
				'id'             => 'youtube',
				'detect_handles' => array( 'youtube', 'youtube-iframe-api' ),
				'detect_src'     => array( 'youtube.com/iframe_api', 'youtube.com/embed', 'www.youtube.com/iframe_api' ),
				'detect_plugins' => array(),
				'cookies'        => array(
					$this->row( 'VISITOR_INFO1_LIVE', 'preferences', 'YouTube / Google', __( 'Оценка на честотната лента.', 'sb-cookie-consent' ), __( '6 месеца', 'sb-cookie-consent' ), 'youtube' ),
					$this->row( 'YSC', 'preferences', 'YouTube / Google', __( 'Сесия при вградено видео.', 'sb-cookie-consent' ), __( 'Сесия', 'sb-cookie-consent' ), '' ),
					$this->row( 'PREF', 'preferences', 'YouTube / Google', __( 'Предпочитания при възпроизвеждане.', 'sb-cookie-consent' ), __( '8 месеца', 'sb-cookie-consent' ), '' ),
				),
			),
			array(
				'id'             => 'matomo',
				'detect_handles' => array( 'matomo', 'piwik' ),
				'detect_src'     => array( 'matomo.js', 'piwik.js', '/matomo/' ),
				'detect_plugins' => array( 'matomo/', 'wp-piwik/' ),
				'cookies'        => array(
					$this->row( '_pk_id*', 'analytics', 'Matomo', __( 'Разпознава посетителя.', 'sb-cookie-consent' ), __( '13 месеца', 'sb-cookie-consent' ), 'matomo,piwik' ),
					$this->row( '_pk_ses*', 'analytics', 'Matomo', __( 'Данни за текущата сесия.', 'sb-cookie-consent' ), __( '30 минути', 'sb-cookie-consent' ), '' ),
				),
			),
			array(
				'id'             => 'clarity',
				'detect_handles' => array( 'clarity', 'microsoft-clarity' ),
				'detect_src'     => array( 'clarity.ms' ),
				'detect_plugins' => array( 'microsoft-clarity/' ),
				'cookies'        => array(
					$this->row( '_clck', 'analytics', 'Microsoft Clarity', __( 'Уникален потребителски ID.', 'sb-cookie-consent' ), __( '1 година', 'sb-cookie-consent' ), 'clarity,microsoft-clarity' ),
					$this->row( '_clsk', 'analytics', 'Microsoft Clarity', __( 'Свързва посещения в една сесия.', 'sb-cookie-consent' ), __( '1 ден', 'sb-cookie-consent' ), '' ),
					$this->row( 'CLID', 'analytics', 'Microsoft Clarity', __( 'Идентифицира първото посещение.', 'sb-cookie-consent' ), __( '1 година', 'sb-cookie-consent' ), '' ),
				),
			),
		);

		/**
		 * Filters the auto-detect cookie catalog.
		 *
		 * @param array<int, array<string, mixed>> $catalog Catalog entries.
		 */
		return (array) apply_filters( 'sb_cookie_consent_scan_catalog', $catalog );
	}

	/**
	 * @param string $name Cookie name/mask.
	 * @param string $category Category slug.
	 * @param string $provider Provider label.
	 * @param string $purpose Purpose text.
	 * @param string $duration Duration text.
	 * @param string $handles Comma-separated handles.
	 * @return array<string, string>
	 */
	private function row( string $name, string $category, string $provider, string $purpose, string $duration, string $handles ): array {
		return array(
			'name'           => $name,
			'category'       => $category,
			'provider'       => $provider,
			'purpose'        => $purpose,
			'duration'       => $duration,
			'script_handles' => $handles,
		);
	}

	/**
	 * Scan registered scripts + active plugins + front-end HTML and return new cookie rows.
	 *
	 * @param array<int, array<string, string>> $existing Current inventory.
	 * @return array{cookies: array<int, array<string, string>>, services: string[], checked_handles: int, checked_srcs: int}
	 */
	public function scan( array $existing ): array {
		global $wp_scripts;

		if ( ! ( $wp_scripts instanceof WP_Scripts ) ) {
			wp_scripts();
		}

		$handles = array();
		$srcs    = array();

		if ( $wp_scripts instanceof WP_Scripts ) {
			foreach ( $wp_scripts->registered as $handle => $obj ) {
				$handles[] = (string) $handle;
				if ( ! empty( $obj->src ) ) {
					$srcs[] = (string) $obj->src;
				}
			}

			$queued = is_array( $wp_scripts->queue ) ? $wp_scripts->queue : array();
			foreach ( $queued as $handle ) {
				$handles[] = (string) $handle;
			}
		}

		foreach ( $this->front_script_srcs() as $src ) {
			$srcs[] = $src;
		}

		$handles = array_values( array_unique( array_map( 'strtolower', $handles ) ) );
		$srcs    = array_values( array_unique( $srcs ) );
		$plugins = $this->active_plugin_basenames();

		$existing_keys = array();
		foreach ( $existing as $row ) {
			if ( ! is_array( $row ) || empty( $row['name'] ) ) {
				continue;
			}
			$existing_keys[ $this->key( (string) $row['name'], (string) ( $row['category'] ?? '' ) ) ] = true;
		}

		$found_cookies  = array();
		$found_services = array();

		foreach ( $this->catalog() as $service ) {
			if ( ! $this->service_detected( $service, $handles, $srcs, $plugins ) ) {
				continue;
			}

			$found_services[] = (string) ( $service['id'] ?? 'unknown' );
			$matched_handles  = $this->matched_handles( $service, $handles );

			foreach ( (array) ( $service['cookies'] ?? array() ) as $cookie ) {
				if ( ! is_array( $cookie ) || empty( $cookie['name'] ) ) {
					continue;
				}
				$key = $this->key( (string) $cookie['name'], (string) ( $cookie['category'] ?? '' ) );
				if ( isset( $existing_keys[ $key ] ) ) {
					continue;
				}

				$row = array(
					'name'           => (string) $cookie['name'],
					'category'       => (string) ( $cookie['category'] ?? 'analytics' ),
					'provider'       => (string) ( $cookie['provider'] ?? '' ),
					'purpose'        => (string) ( $cookie['purpose'] ?? '' ),
					'duration'       => (string) ( $cookie['duration'] ?? '' ),
					'script_handles' => (string) ( $cookie['script_handles'] ?? '' ),
				);

				// Prefer actually registered handles when we detected them.
				if ( $matched_handles && '' === trim( $row['script_handles'] ) ) {
					$row['script_handles'] = implode( ',', $matched_handles );
				} elseif ( $matched_handles ) {
					$declared = preg_split( '/[\s,]+/', $row['script_handles'] ) ?: array();
					$keep     = array_values( array_intersect( array_map( 'strtolower', $declared ), $handles ) );
					$row['script_handles'] = $keep ? implode( ',', $keep ) : implode( ',', $matched_handles );
				}

				$existing_keys[ $key ] = true;
				$found_cookies[]       = $row;
			}
		}

		return array(
			'cookies'         => $found_cookies,
			'services'        => array_values( array_unique( $found_services ) ),
			'checked_handles' => count( $handles ),
			'checked_srcs'    => count( $srcs ),
		);
	}

	/**
	 * Pull script URLs from the public homepage HTML.
	 *
	 * @return string[]
	 */
	private function front_script_srcs(): array {
		$url      = home_url( '/' );
		$response = wp_remote_get(
			$url,
			array(
				'timeout'     => 8,
				'redirection' => 3,
				'user-agent'  => 'SB Cookie Consent Scanner',
			)
		);

		if ( is_wp_error( $response ) ) {
			return array();
		}

		$body = (string) wp_remote_retrieve_body( $response );
		if ( '' === $body ) {
			return array();
		}

		$srcs = array();
		if ( preg_match_all( '/<script[^>]+src=["\']([^"\']+)["\']/i', $body, $matches ) ) {
			foreach ( $matches[1] as $src ) {
				$srcs[] = (string) $src;
			}
		}

		// Inline loaders often reference vendors without a script src.
		$inline_needles = array(
			'googletagmanager.com/gtag',
			'googletagmanager.com/gtm.js',
			'connect.facebook.net',
			'fbevents.js',
			'static.hotjar.com',
			'clarity.ms',
			'bat.bing.com',
			'analytics.tiktok.com',
			'snap.licdn.com',
			'matomo.js',
		);
		foreach ( $inline_needles as $needle ) {
			if ( false !== stripos( $body, $needle ) ) {
				$srcs[] = $needle;
			}
		}

		return $srcs;
	}

	/**
	 * @param array<string, mixed> $service Catalog service.
	 * @param string[]             $handles Lowercased handles.
	 * @param string[]             $srcs Script URLs.
	 * @param string[]             $plugins Active plugin basenames.
	 */
	private function service_detected( array $service, array $handles, array $srcs, array $plugins ): bool {
		foreach ( (array) ( $service['detect_handles'] ?? array() ) as $needle ) {
			$needle = strtolower( (string) $needle );
			foreach ( $handles as $handle ) {
				if ( $handle === $needle || str_contains( $handle, $needle ) ) {
					return true;
				}
			}
		}

		foreach ( (array) ( $service['detect_src'] ?? array() ) as $needle ) {
			$needle = strtolower( (string) $needle );
			foreach ( $srcs as $src ) {
				if ( str_contains( strtolower( $src ), $needle ) ) {
					return true;
				}
			}
		}

		foreach ( (array) ( $service['detect_plugins'] ?? array() ) as $needle ) {
			$needle = strtolower( (string) $needle );
			foreach ( $plugins as $plugin ) {
				if ( str_contains( $plugin, $needle ) ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * @param array<string, mixed> $service Catalog service.
	 * @param string[]             $handles Lowercased handles.
	 * @return string[]
	 */
	private function matched_handles( array $service, array $handles ): array {
		$matched = array();
		foreach ( (array) ( $service['detect_handles'] ?? array() ) as $needle ) {
			$needle = strtolower( (string) $needle );
			foreach ( $handles as $handle ) {
				if ( $handle === $needle || str_contains( $handle, $needle ) ) {
					$matched[] = $handle;
				}
			}
		}

		return array_values( array_unique( $matched ) );
	}

	/**
	 * @return string[]
	 */
	private function active_plugin_basenames(): array {
		if ( ! function_exists( 'get_option' ) ) {
			return array();
		}
		$active = get_option( 'active_plugins', array() );
		if ( ! is_array( $active ) ) {
			return array();
		}

		return array_map( 'strtolower', array_map( 'strval', $active ) );
	}

	private function key( string $name, string $category ): string {
		return strtolower( $category . '|' . $name );
	}
}
