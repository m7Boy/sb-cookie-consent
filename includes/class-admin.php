<?php
/**
 * Settings screen.
 *
 * @package SB_Cookie_Consent
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings page under Settings.
 */
class SB_Cookie_Consent_Admin {

	private SB_Cookie_Consent_Settings $settings;

	private string $hook_suffix = '';

	public function __construct( SB_Cookie_Consent_Settings $settings ) {
		$this->settings = $settings;
	}

	public function register(): void {
		add_action( 'admin_menu', array( $this, 'add_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'wp_ajax_sb_cookie_consent_scan', array( $this, 'ajax_scan' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( SB_COOKIE_CONSENT_FILE ), array( $this, 'action_links' ) );
	}

	public function add_page(): void {
		$this->hook_suffix = (string) add_options_page(
			__( 'SB Cookie Consent', 'sb-cookie-consent' ),
			__( 'SB Cookie Consent', 'sb-cookie-consent' ),
			'manage_options',
			'sb-cookie-consent',
			array( $this, 'render_page' )
		);
	}

	public function register_settings(): void {
		register_setting(
			'sb_cookie_consent',
			SB_Cookie_Consent_Settings::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this->settings, 'sanitize' ),
				'default'           => $this->settings->defaults(),
				'show_in_rest'      => false,
			)
		);
	}

	public function enqueue( string $hook_suffix ): void {
		if ( $hook_suffix !== $this->hook_suffix ) {
			return;
		}

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );

		wp_enqueue_style(
			'sb-cookie-consent-admin',
			plugins_url( 'assets/admin.css', SB_COOKIE_CONSENT_FILE ),
			array( 'wp-color-picker' ),
			SB_COOKIE_CONSENT_VERSION
		);

		wp_enqueue_script(
			'sb-cookie-consent-admin',
			plugins_url( 'assets/admin.js', SB_COOKIE_CONSENT_FILE ),
			array( 'jquery', 'wp-color-picker' ),
			SB_COOKIE_CONSENT_VERSION,
			true
		);

		wp_localize_script(
			'sb-cookie-consent-admin',
			'sbCookieConsentAdmin',
			array(
				'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'sb_cookie_consent_scan' ),
				'scanning' => __( 'Сканиране…', 'sb-cookie-consent' ),
				'scanError'=> __( 'Сканирането не успя. Опитайте отново.', 'sb-cookie-consent' ),
				'categories' => array(
					'necessary'   => __( 'Необходими', 'sb-cookie-consent' ),
					'preferences' => __( 'Предпочитания', 'sb-cookie-consent' ),
					'analytics'   => __( 'Статистика', 'sb-cookie-consent' ),
					'marketing'   => __( 'Маркетинг', 'sb-cookie-consent' ),
				),
			)
		);
	}

	/**
	 * AJAX: detect known third-party cookies from scripts/plugins/homepage.
	 */
	public function ajax_scan(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Нямате права.', 'sb-cookie-consent' ),
				),
				403
			);
		}

		check_ajax_referer( 'sb_cookie_consent_scan', 'nonce' );

		$existing = array();
		if ( isset( $_POST['existing'] ) && is_array( $_POST['existing'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			foreach ( wp_unslash( $_POST['existing'] ) as $row ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				if ( ! is_array( $row ) ) {
					continue;
				}
				$name = isset( $row['name'] ) ? sanitize_text_field( (string) $row['name'] ) : '';
				if ( '' === $name ) {
					continue;
				}
				$existing[] = array(
					'name'     => $name,
					'category' => isset( $row['category'] ) ? sanitize_key( (string) $row['category'] ) : '',
				);
			}
		} else {
			$existing = $this->settings->all()['cookies'];
		}

		$scanner = new SB_Cookie_Consent_Scanner();
		$result  = $scanner->scan( is_array( $existing ) ? $existing : array() );

		wp_send_json_success(
			array(
				'cookies'  => $result['cookies'],
				'services' => $result['services'],
				'message'  => sprintf(
					/* translators: 1: number of cookies, 2: number of services */
					__( 'Добавени предложения: %1$d (услуги: %2$d). Прегледайте и запазете.', 'sb-cookie-consent' ),
					count( $result['cookies'] ),
					count( $result['services'] )
				),
			)
		);
	}

	/**
	 * @param string[] $links Plugin row links.
	 * @return string[]
	 */
	public function action_links( array $links ): array {
		$url = admin_url( 'options-general.php?page=sb-cookie-consent' );
		array_unshift(
			$links,
			'<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'sb-cookie-consent' ) . '</a>'
		);

		$links[] = '<a href="' . esc_url( SB_COOKIE_CONSENT_AUTHOR_URL ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Software Brewery', 'sb-cookie-consent' ) . '</a>';

		return $links;
	}

	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings = $this->settings->all();
		$option   = SB_Cookie_Consent_Settings::OPTION;
		$theme    = $this->settings->theme_colors();
		$cookies  = is_array( $settings['cookies'] ) ? $settings['cookies'] : array();
		?>
		<div class="wrap sb-cookie-consent-admin">
			<h1><?php echo esc_html__( 'SB Cookie Consent', 'sb-cookie-consent' ); ?></h1>
			<p><?php echo esc_html__( 'Банер с категории, реален контрол на скриптове/бисквитки, политика за бисквитки и бутон за промяна на избора.', 'sb-cookie-consent' ); ?></p>
			<p>
				<?php
				echo wp_kses(
					sprintf(
						/* translators: %s: Software Brewery website link. */
						__( 'Developed by %s.', 'sb-cookie-consent' ),
						'<a href="' . esc_url( SB_COOKIE_CONSENT_AUTHOR_URL ) . '" target="_blank" rel="noopener noreferrer">Software Brewery</a>'
					),
					array(
						'a' => array(
							'href'   => true,
							'target' => true,
							'rel'    => true,
						),
					)
				);
				?>
			</p>

			<form action="options.php" method="post">
				<?php settings_fields( 'sb_cookie_consent' ); ?>

				<h2 class="title"><?php echo esc_html__( 'Общи', 'sb-cookie-consent' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php echo esc_html__( 'Банер', 'sb-cookie-consent' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( $option ); ?>[enabled]" value="1" <?php checked( ! empty( $settings['enabled'] ) ); ?> />
								<?php echo esc_html__( 'Показвай банера за бисквитки на сайта.', 'sb-cookie-consent' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="sb-cc-message"><?php echo esc_html__( 'Съобщение', 'sb-cookie-consent' ); ?></label></th>
						<td>
							<textarea id="sb-cc-message" name="<?php echo esc_attr( $option ); ?>[message]" rows="3" class="large-text"><?php echo esc_textarea( (string) $settings['message'] ); ?></textarea>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="sb-cc-accept"><?php echo esc_html__( 'Бутон „Приемам всички“', 'sb-cookie-consent' ); ?></label></th>
						<td>
							<input id="sb-cc-accept" name="<?php echo esc_attr( $option ); ?>[accept_label]" type="text" class="regular-text" value="<?php echo esc_attr( (string) $settings['accept_label'] ); ?>" />
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="sb-cc-settings"><?php echo esc_html__( 'Бутон „Настройки“', 'sb-cookie-consent' ); ?></label></th>
						<td>
							<input id="sb-cc-settings" name="<?php echo esc_attr( $option ); ?>[settings_label]" type="text" class="regular-text" value="<?php echo esc_attr( (string) ( $settings['settings_label'] ?? __( 'Настройки', 'sb-cookie-consent' ) ) ); ?>" />
							<p class="description"><?php echo esc_html__( 'Отваря категориите бисквитки в банера.', 'sb-cookie-consent' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="sb-cc-save"><?php echo esc_html__( 'Бутон „Запази избора“', 'sb-cookie-consent' ); ?></label></th>
						<td>
							<input id="sb-cc-save" name="<?php echo esc_attr( $option ); ?>[save_label]" type="text" class="regular-text" value="<?php echo esc_attr( (string) $settings['save_label'] ); ?>" />
						</td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Само необходими', 'sb-cookie-consent' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( $option ); ?>[show_decline]" value="1" <?php checked( ! empty( $settings['show_decline'] ) ); ?> />
								<?php echo esc_html__( 'Показвай бутон за отказ на незадължителните.', 'sb-cookie-consent' ); ?>
							</label>
							<p>
								<label for="sb-cc-decline"><?php echo esc_html__( 'Текст на бутона', 'sb-cookie-consent' ); ?></label><br />
								<input id="sb-cc-decline" name="<?php echo esc_attr( $option ); ?>[decline_label]" type="text" class="regular-text" value="<?php echo esc_attr( (string) $settings['decline_label'] ); ?>" />
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="sb-cc-policy-url"><?php echo esc_html__( 'Политика за бисквитки', 'sb-cookie-consent' ); ?></label></th>
						<td>
							<input id="sb-cc-policy-url" name="<?php echo esc_attr( $option ); ?>[cookie_policy_url]" type="url" class="regular-text" placeholder="https://" value="<?php echo esc_attr( (string) $settings['cookie_policy_url'] ); ?>" />
							<p class="description">
								<?php echo esc_html__( 'Създайте страница и поставете шорткода', 'sb-cookie-consent' ); ?>
								<code>[sb_cookie_policy]</code>
								<?php echo esc_html__( 'за пълния списък по категории.', 'sb-cookie-consent' ); ?>
							</p>
							<p>
								<label for="sb-cc-policy-label"><?php echo esc_html__( 'Текст на връзката', 'sb-cookie-consent' ); ?></label><br />
								<input id="sb-cc-policy-label" name="<?php echo esc_attr( $option ); ?>[cookie_policy_label]" type="text" class="regular-text" value="<?php echo esc_attr( (string) $settings['cookie_policy_label'] ); ?>" />
							</p>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( $option ); ?>[show_policy_link]" value="1" <?php checked( ! empty( $settings['show_policy_link'] ) ); ?> />
								<?php echo esc_html__( 'Постоянен линк към политиката в долния край на сайта.', 'sb-cookie-consent' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Промяна на избора', 'sb-cookie-consent' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( $option ); ?>[show_reopen]" value="1" <?php checked( ! empty( $settings['show_reopen'] ) ); ?> />
								<?php echo esc_html__( 'Показвай икона за повторно отваряне на банера след съгласие/отказ.', 'sb-cookie-consent' ); ?>
							</label>
							<p>
								<label for="sb-cc-reopen"><?php echo esc_html__( 'Етикет (aria/title)', 'sb-cookie-consent' ); ?></label><br />
								<input id="sb-cc-reopen" name="<?php echo esc_attr( $option ); ?>[reopen_label]" type="text" class="regular-text" value="<?php echo esc_attr( (string) $settings['reopen_label'] ); ?>" />
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="sb-cc-position"><?php echo esc_html__( 'Позиция', 'sb-cookie-consent' ); ?></label></th>
						<td>
							<select id="sb-cc-position" name="<?php echo esc_attr( $option ); ?>[position]">
								<option value="bottom" <?php selected( $settings['position'], 'bottom' ); ?>><?php echo esc_html__( 'Долу', 'sb-cookie-consent' ); ?></option>
								<option value="top" <?php selected( $settings['position'], 'top' ); ?>><?php echo esc_html__( 'Горе', 'sb-cookie-consent' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="sb-cc-days"><?php echo esc_html__( 'Валидност (дни)', 'sb-cookie-consent' ); ?></label></th>
						<td>
							<input id="sb-cc-days" name="<?php echo esc_attr( $option ); ?>[cookie_days]" type="number" min="1" max="3650" step="1" class="small-text" value="<?php echo esc_attr( (string) $settings['cookie_days'] ); ?>" />
						</td>
					</tr>
				</table>

				<h2 class="title"><?php echo esc_html__( 'Категории', 'sb-cookie-consent' ); ?></h2>
				<table class="form-table" role="presentation">
					<?php foreach ( SB_Cookie_Consent_Settings::CATEGORIES as $cat ) : ?>
						<tr>
							<th scope="row"><?php echo esc_html( (string) $settings['category_labels'][ $cat ] ); ?></th>
							<td>
								<input type="text" class="regular-text" name="<?php echo esc_attr( $option ); ?>[category_labels][<?php echo esc_attr( $cat ); ?>]" value="<?php echo esc_attr( (string) $settings['category_labels'][ $cat ] ); ?>" />
								<p>
									<textarea class="large-text" rows="2" name="<?php echo esc_attr( $option ); ?>[category_descriptions][<?php echo esc_attr( $cat ); ?>]"><?php echo esc_textarea( (string) $settings['category_descriptions'][ $cat ] ); ?></textarea>
								</p>
							</td>
						</tr>
					<?php endforeach; ?>
				</table>

				<h2 class="title"><?php echo esc_html__( 'Регистър на бисквитките', 'sb-cookie-consent' ); ?></h2>
				<p class="description">
					<?php echo esc_html__( 'Попълнете ръчно или сканирайте сайта за известни услуги (GA, Meta Pixel, GTM, Hotjar и др.). Script handle(и) блокират WP скриптове без съгласие. Име с * е маска при изтриване.', 'sb-cookie-consent' ); ?>
				</p>
				<p>
					<button type="button" class="button button-secondary" id="sb-cc-scan-cookies">
						<?php echo esc_html__( 'Сканирай и добави автоматично', 'sb-cookie-consent' ); ?>
					</button>
					<span id="sb-cc-scan-status" class="sb-cc-scan-status" role="status" aria-live="polite"></span>
				</p>
				<div class="sb-cc-cookies-wrap">
					<table class="widefat striped sb-cc-cookies" id="sb-cc-cookies">
						<thead>
							<tr>
								<th class="sb-cc-col-name"><?php echo esc_html__( 'Име / маска', 'sb-cookie-consent' ); ?></th>
								<th class="sb-cc-col-cat"><?php echo esc_html__( 'Категория', 'sb-cookie-consent' ); ?></th>
								<th class="sb-cc-col-provider"><?php echo esc_html__( 'Доставчик', 'sb-cookie-consent' ); ?></th>
								<th class="sb-cc-col-purpose"><?php echo esc_html__( 'Цел', 'sb-cookie-consent' ); ?></th>
								<th class="sb-cc-col-duration"><?php echo esc_html__( 'Срок', 'sb-cookie-consent' ); ?></th>
								<th class="sb-cc-col-handles"><?php echo esc_html__( 'Script handles', 'sb-cookie-consent' ); ?></th>
								<th class="sb-cc-col-actions"></th>
							</tr>
						</thead>
						<tbody>
							<?php
							if ( ! $cookies ) {
								$cookies = array(
									array(
										'name'           => '',
										'category'       => 'analytics',
										'provider'       => '',
										'purpose'        => '',
										'duration'       => '',
										'script_handles' => '',
									),
								);
							}
							foreach ( $cookies as $i => $cookie ) :
								$this->render_cookie_row( (int) $i, $cookie, $option );
							endforeach;
							?>
						</tbody>
					</table>
				</div>
				<p>
					<button type="button" class="button" id="sb-cc-add-cookie"><?php echo esc_html__( 'Добави бисквитка', 'sb-cookie-consent' ); ?></button>
				</p>

				<template id="sb-cc-cookie-row-tpl">
					<?php
					$this->render_cookie_row(
						'__i__',
						array(
							'name'           => '',
							'category'       => 'analytics',
							'provider'       => '',
							'purpose'        => '',
							'duration'       => '',
							'script_handles' => '',
						),
						$option
					);
					?>
				</template>

				<h2 class="title"><?php echo esc_html__( 'Цветове', 'sb-cookie-consent' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php echo esc_html__( 'От темата', 'sb-cookie-consent' ); ?></th>
						<td>
							<label>
								<input type="checkbox" id="sb-cc-use-theme" name="<?php echo esc_attr( $option ); ?>[use_theme_colors]" value="1" <?php checked( ! empty( $settings['use_theme_colors'] ) ); ?> />
								<?php echo esc_html__( 'Използвай главните цветове от активната тема (по подразбиране).', 'sb-cookie-consent' ); ?>
							</label>
							<p class="description">
								<?php
								echo esc_html(
									sprintf(
										/* translators: 1: bg color, 2: text color, 3: primary color */
										__( 'Текущи от тема: фон %1$s, текст %2$s, бутон %3$s.', 'sb-cookie-consent' ),
										$theme['bg'],
										$theme['text'],
										$theme['primary']
									)
								);
								?>
							</p>
						</td>
					</tr>
					<tr class="sb-cc-color-row">
						<th scope="row"><label for="sb-cc-bg"><?php echo esc_html__( 'Фон на банера', 'sb-cookie-consent' ); ?></label></th>
						<td><input id="sb-cc-bg" class="sb-cc-color" name="<?php echo esc_attr( $option ); ?>[bg_color]" type="text" value="<?php echo esc_attr( (string) $settings['bg_color'] ); ?>" data-default-color="<?php echo esc_attr( $theme['bg'] ); ?>" /></td>
					</tr>
					<tr class="sb-cc-color-row">
						<th scope="row"><label for="sb-cc-text"><?php echo esc_html__( 'Цвят на текста', 'sb-cookie-consent' ); ?></label></th>
						<td><input id="sb-cc-text" class="sb-cc-color" name="<?php echo esc_attr( $option ); ?>[text_color]" type="text" value="<?php echo esc_attr( (string) $settings['text_color'] ); ?>" data-default-color="<?php echo esc_attr( $theme['text'] ); ?>" /></td>
					</tr>
					<tr class="sb-cc-color-row">
						<th scope="row"><label for="sb-cc-btn-bg"><?php echo esc_html__( 'Фон на основния бутон', 'sb-cookie-consent' ); ?></label></th>
						<td><input id="sb-cc-btn-bg" class="sb-cc-color" name="<?php echo esc_attr( $option ); ?>[button_bg]" type="text" value="<?php echo esc_attr( (string) $settings['button_bg'] ); ?>" data-default-color="<?php echo esc_attr( $theme['primary'] ); ?>" /></td>
					</tr>
					<tr class="sb-cc-color-row">
						<th scope="row"><label for="sb-cc-btn-text"><?php echo esc_html__( 'Текст на основния бутон', 'sb-cookie-consent' ); ?></label></th>
						<td><input id="sb-cc-btn-text" class="sb-cc-color" name="<?php echo esc_attr( $option ); ?>[button_text]" type="text" value="<?php echo esc_attr( (string) $settings['button_text'] ); ?>" data-default-color="<?php echo esc_attr( $theme['button_text'] ); ?>" /></td>
					</tr>
					<tr class="sb-cc-color-row">
						<th scope="row"><label for="sb-cc-decline-bg"><?php echo esc_html__( 'Фон на „Само необходими“', 'sb-cookie-consent' ); ?></label></th>
						<td>
							<input id="sb-cc-decline-bg" class="sb-cc-color" name="<?php echo esc_attr( $option ); ?>[decline_bg]" type="text" value="<?php echo esc_attr( (string) $settings['decline_bg'] ); ?>" data-default-color="transparent" />
						</td>
					</tr>
					<tr class="sb-cc-color-row">
						<th scope="row"><label for="sb-cc-decline-text"><?php echo esc_html__( 'Текст на „Само необходими“', 'sb-cookie-consent' ); ?></label></th>
						<td><input id="sb-cc-decline-text" class="sb-cc-color" name="<?php echo esc_attr( $option ); ?>[decline_text]" type="text" value="<?php echo esc_attr( (string) $settings['decline_text'] ); ?>" data-default-color="<?php echo esc_attr( $theme['text'] ); ?>" /></td>
					</tr>
				</table>

				<?php submit_button( __( 'Запази промените', 'sb-cookie-consent' ) ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * @param int|string           $index  Row index.
	 * @param array<string,string> $cookie Row data.
	 * @param string               $option Option name.
	 */
	private function render_cookie_row( $index, array $cookie, string $option ): void {
		$i = (string) $index;
		?>
		<tr class="sb-cc-cookie-row">
			<td class="sb-cc-col-name"><input type="text" name="<?php echo esc_attr( $option ); ?>[cookies][<?php echo esc_attr( $i ); ?>][name]" value="<?php echo esc_attr( (string) ( $cookie['name'] ?? '' ) ); ?>" placeholder="_ga" /></td>
			<td class="sb-cc-col-cat">
				<select name="<?php echo esc_attr( $option ); ?>[cookies][<?php echo esc_attr( $i ); ?>][category]">
					<?php
					$cat_labels = $this->settings->default_category_labels();
					foreach ( SB_Cookie_Consent_Settings::CATEGORIES as $cat ) :
						?>
						<option value="<?php echo esc_attr( $cat ); ?>" <?php selected( ( $cookie['category'] ?? '' ), $cat ); ?>><?php echo esc_html( (string) ( $cat_labels[ $cat ] ?? $cat ) ); ?></option>
					<?php endforeach; ?>
				</select>
			</td>
			<td class="sb-cc-col-provider"><input type="text" name="<?php echo esc_attr( $option ); ?>[cookies][<?php echo esc_attr( $i ); ?>][provider]" value="<?php echo esc_attr( (string) ( $cookie['provider'] ?? '' ) ); ?>" /></td>
			<td class="sb-cc-col-purpose"><input type="text" name="<?php echo esc_attr( $option ); ?>[cookies][<?php echo esc_attr( $i ); ?>][purpose]" value="<?php echo esc_attr( (string) ( $cookie['purpose'] ?? '' ) ); ?>" /></td>
			<td class="sb-cc-col-duration"><input type="text" name="<?php echo esc_attr( $option ); ?>[cookies][<?php echo esc_attr( $i ); ?>][duration]" value="<?php echo esc_attr( (string) ( $cookie['duration'] ?? '' ) ); ?>" /></td>
			<td class="sb-cc-col-handles"><input type="text" name="<?php echo esc_attr( $option ); ?>[cookies][<?php echo esc_attr( $i ); ?>][script_handles]" value="<?php echo esc_attr( (string) ( $cookie['script_handles'] ?? '' ) ); ?>" placeholder="google-analytics" /></td>
			<td class="sb-cc-col-actions"><button type="button" class="button-link-delete sb-cc-remove-cookie"><?php echo esc_html__( 'Премахни', 'sb-cookie-consent' ); ?></button></td>
		</tr>
		<?php
	}
}
