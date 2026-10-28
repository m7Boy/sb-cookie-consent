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
		?>
		<div class="wrap sb-cookie-consent-admin">
			<h1><?php echo esc_html__( 'SB Cookie Consent', 'sb-cookie-consent' ); ?></h1>
			<p><?php echo esc_html__( 'Показва банер за съгласие с бисквитки. Основните текстове, позиция и цветове се управляват оттук.', 'sb-cookie-consent' ); ?></p>
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
						<th scope="row">
							<label for="sb-cc-message"><?php echo esc_html__( 'Съобщение', 'sb-cookie-consent' ); ?></label>
						</th>
						<td>
							<textarea
								id="sb-cc-message"
								name="<?php echo esc_attr( $option ); ?>[message]"
								rows="3"
								class="large-text"
							><?php echo esc_textarea( (string) $settings['message'] ); ?></textarea>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="sb-cc-accept"><?php echo esc_html__( 'Бутон „Съгласявам се“', 'sb-cookie-consent' ); ?></label>
						</th>
						<td>
							<input
								id="sb-cc-accept"
								name="<?php echo esc_attr( $option ); ?>[accept_label]"
								type="text"
								class="regular-text"
								value="<?php echo esc_attr( (string) $settings['accept_label'] ); ?>"
							/>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Отказ', 'sb-cookie-consent' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( $option ); ?>[show_decline]" value="1" <?php checked( ! empty( $settings['show_decline'] ) ); ?> />
								<?php echo esc_html__( 'Показвай бутон за отказ.', 'sb-cookie-consent' ); ?>
							</label>
							<p>
								<label for="sb-cc-decline"><?php echo esc_html__( 'Текст на бутона', 'sb-cookie-consent' ); ?></label><br />
								<input
									id="sb-cc-decline"
									name="<?php echo esc_attr( $option ); ?>[decline_label]"
									type="text"
									class="regular-text"
									value="<?php echo esc_attr( (string) $settings['decline_label'] ); ?>"
								/>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="sb-cc-privacy-url"><?php echo esc_html__( 'Политика за поверителност', 'sb-cookie-consent' ); ?></label>
						</th>
						<td>
							<input
								id="sb-cc-privacy-url"
								name="<?php echo esc_attr( $option ); ?>[privacy_url]"
								type="url"
								class="regular-text"
								placeholder="https://"
								value="<?php echo esc_attr( (string) $settings['privacy_url'] ); ?>"
							/>
							<p>
								<label for="sb-cc-privacy-label"><?php echo esc_html__( 'Текст на връзката', 'sb-cookie-consent' ); ?></label><br />
								<input
									id="sb-cc-privacy-label"
									name="<?php echo esc_attr( $option ); ?>[privacy_label]"
									type="text"
									class="regular-text"
									value="<?php echo esc_attr( (string) $settings['privacy_label'] ); ?>"
								/>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="sb-cc-position"><?php echo esc_html__( 'Позиция', 'sb-cookie-consent' ); ?></label>
						</th>
						<td>
							<select id="sb-cc-position" name="<?php echo esc_attr( $option ); ?>[position]">
								<option value="bottom" <?php selected( $settings['position'], 'bottom' ); ?>><?php echo esc_html__( 'Долу', 'sb-cookie-consent' ); ?></option>
								<option value="top" <?php selected( $settings['position'], 'top' ); ?>><?php echo esc_html__( 'Горе', 'sb-cookie-consent' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row">
							<label for="sb-cc-days"><?php echo esc_html__( 'Валидност (дни)', 'sb-cookie-consent' ); ?></label>
						</th>
						<td>
							<input
								id="sb-cc-days"
								name="<?php echo esc_attr( $option ); ?>[cookie_days]"
								type="number"
								min="1"
								max="3650"
								step="1"
								class="small-text"
								value="<?php echo esc_attr( (string) $settings['cookie_days'] ); ?>"
							/>
							<p class="description"><?php echo esc_html__( 'Колко дни да се помни изборът на посетителя.', 'sb-cookie-consent' ); ?></p>
						</td>
					</tr>
				</table>

				<h2 class="title"><?php echo esc_html__( 'Цветове', 'sb-cookie-consent' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php echo esc_html__( 'От темата', 'sb-cookie-consent' ); ?></th>
						<td>
							<label>
								<input
									type="checkbox"
									id="sb-cc-use-theme"
									name="<?php echo esc_attr( $option ); ?>[use_theme_colors]"
									value="1"
									<?php checked( ! empty( $settings['use_theme_colors'] ) ); ?>
								/>
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
						<th scope="row"><label for="sb-cc-btn-bg"><?php echo esc_html__( 'Фон на „Съгласявам се“', 'sb-cookie-consent' ); ?></label></th>
						<td><input id="sb-cc-btn-bg" class="sb-cc-color" name="<?php echo esc_attr( $option ); ?>[button_bg]" type="text" value="<?php echo esc_attr( (string) $settings['button_bg'] ); ?>" data-default-color="<?php echo esc_attr( $theme['primary'] ); ?>" /></td>
					</tr>
					<tr class="sb-cc-color-row">
						<th scope="row"><label for="sb-cc-btn-text"><?php echo esc_html__( 'Текст на „Съгласявам се“', 'sb-cookie-consent' ); ?></label></th>
						<td><input id="sb-cc-btn-text" class="sb-cc-color" name="<?php echo esc_attr( $option ); ?>[button_text]" type="text" value="<?php echo esc_attr( (string) $settings['button_text'] ); ?>" data-default-color="<?php echo esc_attr( $theme['button_text'] ); ?>" /></td>
					</tr>
					<tr class="sb-cc-color-row">
						<th scope="row"><label for="sb-cc-decline-bg"><?php echo esc_html__( 'Фон на „Отказвам“', 'sb-cookie-consent' ); ?></label></th>
						<td>
							<input id="sb-cc-decline-bg" class="sb-cc-color" name="<?php echo esc_attr( $option ); ?>[decline_bg]" type="text" value="<?php echo esc_attr( (string) $settings['decline_bg'] ); ?>" data-default-color="transparent" />
							<p class="description"><?php echo esc_html__( 'Може да е „transparent“.', 'sb-cookie-consent' ); ?></p>
						</td>
					</tr>
					<tr class="sb-cc-color-row">
						<th scope="row"><label for="sb-cc-decline-text"><?php echo esc_html__( 'Текст на „Отказвам“', 'sb-cookie-consent' ); ?></label></th>
						<td><input id="sb-cc-decline-text" class="sb-cc-color" name="<?php echo esc_attr( $option ); ?>[decline_text]" type="text" value="<?php echo esc_attr( (string) $settings['decline_text'] ); ?>" data-default-color="<?php echo esc_attr( $theme['text'] ); ?>" /></td>
					</tr>
				</table>

				<?php submit_button( __( 'Запази промените', 'sb-cookie-consent' ) ); ?>
			</form>
		</div>
		<?php
	}
}
