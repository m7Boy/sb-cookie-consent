<?php
/**
 * Front-end cookie banner and reopen control.
 *
 * @package SB_Cookie_Consent
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the banner and enqueues assets.
 */
class SB_Cookie_Consent_Frontend {

	private SB_Cookie_Consent_Settings $settings;

	public function __construct( SB_Cookie_Consent_Settings $settings ) {
		$this->settings = $settings;
	}

	public function register(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'wp_head', array( $this, 'print_bootstrap' ), 1 );
		add_action( 'wp_footer', array( $this, 'render' ), 5 );
	}

	public function enqueue(): void {
		if ( is_admin() || ! $this->settings->enabled() ) {
			return;
		}

		$settings = $this->settings->all();

		wp_enqueue_style(
			'sb-cookie-consent',
			plugins_url( 'assets/frontend.css', SB_COOKIE_CONSENT_FILE ),
			array(),
			SB_COOKIE_CONSENT_VERSION
		);

		$css = sprintf(
			'.sb-cookie-consent,.sb-cookie-consent-reopen,.sb-cookie-consent-policy-link{--sb-cc-bg:%1$s;--sb-cc-text:%2$s;--sb-cc-btn-bg:%3$s;--sb-cc-btn-text:%4$s;--sb-cc-decline-bg:%5$s;--sb-cc-decline-text:%6$s;}',
			esc_attr( (string) $settings['bg_color'] ),
			esc_attr( (string) $settings['text_color'] ),
			esc_attr( (string) $settings['button_bg'] ),
			esc_attr( (string) $settings['button_text'] ),
			esc_attr( (string) $settings['decline_bg'] ),
			esc_attr( (string) $settings['decline_text'] )
		);
		wp_add_inline_style( 'sb-cookie-consent', $css );

		wp_enqueue_script(
			'sb-cookie-consent',
			plugins_url( 'assets/frontend.js', SB_COOKIE_CONSENT_FILE ),
			array(),
			SB_COOKIE_CONSENT_VERSION,
			true
		);

		$purge = array();
		foreach ( SB_Cookie_Consent_Settings::CATEGORIES as $cat ) {
			if ( 'necessary' === $cat ) {
				continue;
			}
			$purge[ $cat ] = $this->settings->cookie_names_for_category( $cat );
		}

		wp_localize_script(
			'sb-cookie-consent',
			'sbCookieConsent',
			array(
				'cookieName'   => SB_Cookie_Consent_Consent::COOKIE,
				'days'         => (int) $settings['cookie_days'],
				'cookiePath'   => defined( 'COOKIEPATH' ) && COOKIEPATH ? COOKIEPATH : '/',
				'cookieDomain' => defined( 'COOKIE_DOMAIN' ) && COOKIE_DOMAIN ? COOKIE_DOMAIN : '',
				'categories'   => SB_Cookie_Consent_Consent::categories(),
				'hasChoice'    => SB_Cookie_Consent_Consent::has_choice(),
				'purge'        => $purge,
				'i18n'         => array(
					'empty' => __( 'Няма регистрирани бисквитки в тази категория.', 'sb-cookie-consent' ),
				),
			)
		);
	}

	/**
	 * Early consent state for other scripts in the head.
	 */
	public function print_bootstrap(): void {
		if ( is_admin() || ! $this->settings->enabled() ) {
			return;
		}

		$cats = SB_Cookie_Consent_Consent::categories();
		$data = array(
			'hasChoice'  => SB_Cookie_Consent_Consent::has_choice(),
			'categories' => $cats,
		);
		printf(
			'<script id="sb-cookie-consent-bootstrap">window.sbCC=%1$s;window.sbCC.has=function(c){c=String(c||"");if(c==="necessary")return!0;return!!(window.sbCC.categories&&window.sbCC.categories[c]);};(function(){try{var m=document.cookie.match(/(?:^|; )sb_cookie_consent=([^;]*)/);if(!m)return;var raw=decodeURIComponent(m[1]);var ok=raw==="accepted"||raw==="declined"||(raw.charAt(0)==="{"&&raw.indexOf("necessary")!==-1);if(!ok)return;window.sbCC.hasChoice=!0;document.addEventListener("DOMContentLoaded",function(){var b=document.getElementById("sb-cookie-consent");var r=document.getElementById("sb-cookie-consent-reopen");if(b)b.setAttribute("hidden","hidden");if(r)r.removeAttribute("hidden");});}catch(e){}})();</script>' . "\n",
			wp_json_encode( $data )
		);
	}

	public function render(): void {
		if ( is_admin() || ! $this->settings->enabled() ) {
			return;
		}

		$settings  = $this->settings->all();
		$grouped   = $this->settings->cookies_by_category();
		$labels    = $settings['category_labels'];
		$descs     = $settings['category_descriptions'];
		$consent   = SB_Cookie_Consent_Consent::categories();
		$has_choice = SB_Cookie_Consent_Consent::has_choice();
		$position  = 'top' === $settings['position'] ? 'top' : 'bottom';
		$policy_url = (string) $settings['cookie_policy_url'];
		?>
		<?php if ( ! empty( $settings['show_reopen'] ) ) : ?>
			<button
				type="button"
				id="sb-cookie-consent-reopen"
				class="sb-cookie-consent-reopen"
				data-sb-cc="reopen"
				aria-label="<?php echo esc_attr( (string) $settings['reopen_label'] ); ?>"
				title="<?php echo esc_attr( (string) $settings['reopen_label'] ); ?>"
				<?php echo $has_choice ? '' : ' hidden'; ?>
			>
				<span class="sb-cookie-consent-reopen__icon" aria-hidden="true">
					<svg width="28" height="28" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" focusable="false">
						<path fill="#e8b56a" d="M12 2.1c-5.45 0-9.9 4.45-9.9 9.9s4.45 9.9 9.9 9.9 9.9-4.45 9.9-9.9c0-1.35-.28-2.63-.78-3.8-.48.18-.99.28-1.52.28-2.15 0-3.9-1.75-3.9-3.9 0-.55.12-1.08.32-1.56A9.86 9.86 0 0012 2.1z"/>
						<circle cx="8.1" cy="10.1" r="1.2" fill="#6b4226"/>
						<circle cx="13.9" cy="9" r="1" fill="#6b4226"/>
						<circle cx="10.3" cy="14.3" r="1.15" fill="#6b4226"/>
						<circle cx="15.7" cy="14.9" r="0.95" fill="#6b4226"/>
						<circle cx="7.3" cy="15.7" r="0.8" fill="#6b4226"/>
						<circle cx="12.2" cy="11.8" r="0.7" fill="#6b4226"/>
					</svg>
				</span>
			</button>
		<?php endif; ?>

		<div
			id="sb-cookie-consent"
			class="sb-cookie-consent sb-cookie-consent--<?php echo esc_attr( $position ); ?>"
			role="dialog"
			aria-modal="true"
			aria-labelledby="sb-cookie-consent-title"
			<?php echo $has_choice ? ' hidden' : ''; ?>
		>
			<div class="sb-cookie-consent__panel">
				<div class="sb-cookie-consent__header">
					<p id="sb-cookie-consent-title" class="sb-cookie-consent__message"><?php echo esc_html( (string) $settings['message'] ); ?></p>
					<?php if ( '' !== $policy_url ) : ?>
						<p class="sb-cookie-consent__policy">
							<a href="<?php echo esc_url( $policy_url ); ?>"><?php echo esc_html( (string) $settings['cookie_policy_label'] ); ?></a>
						</p>
					<?php endif; ?>
				</div>

				<div
					id="sb-cookie-consent-details"
					class="sb-cookie-consent__details"
					hidden
				>
					<div class="sb-cookie-consent__categories" role="group" aria-label="<?php echo esc_attr__( 'Категории бисквитки', 'sb-cookie-consent' ); ?>">
						<?php foreach ( SB_Cookie_Consent_Settings::CATEGORIES as $category ) : ?>
							<details class="sb-cookie-consent__cat">
								<summary class="sb-cookie-consent__cat-summary">
									<span class="sb-cookie-consent__cat-label"><?php echo esc_html( (string) ( $labels[ $category ] ?? $category ) ); ?></span>
									<label class="sb-cookie-consent__switch" onclick="event.stopPropagation();">
										<input
											type="checkbox"
											data-sb-cc-category="<?php echo esc_attr( $category ); ?>"
											<?php checked( ! empty( $consent[ $category ] ) ); ?>
											<?php disabled( 'necessary' === $category ); ?>
										/>
										<span class="sb-cookie-consent__switch-ui" aria-hidden="true"></span>
										<span class="screen-reader-text"><?php echo esc_html( (string) ( $labels[ $category ] ?? $category ) ); ?></span>
									</label>
								</summary>
								<p class="sb-cookie-consent__cat-desc"><?php echo esc_html( (string) ( $descs[ $category ] ?? '' ) ); ?></p>
								<?php if ( empty( $grouped[ $category ] ) ) : ?>
									<p class="sb-cookie-consent__cat-empty"><?php echo esc_html__( 'Няма регистрирани бисквитки в тази категория.', 'sb-cookie-consent' ); ?></p>
								<?php else : ?>
									<ul class="sb-cookie-consent__cookie-list">
										<?php foreach ( $grouped[ $category ] as $cookie ) : ?>
											<li>
												<strong><code><?php echo esc_html( (string) $cookie['name'] ); ?></code></strong>
												<?php if ( ! empty( $cookie['provider'] ) ) : ?>
													<span class="sb-cookie-consent__meta"><?php echo esc_html( (string) $cookie['provider'] ); ?></span>
												<?php endif; ?>
												<?php if ( ! empty( $cookie['purpose'] ) ) : ?>
													<span class="sb-cookie-consent__purpose"><?php echo esc_html( (string) $cookie['purpose'] ); ?></span>
												<?php endif; ?>
												<?php if ( ! empty( $cookie['duration'] ) ) : ?>
													<span class="sb-cookie-consent__meta"><?php echo esc_html( (string) $cookie['duration'] ); ?></span>
												<?php endif; ?>
											</li>
										<?php endforeach; ?>
									</ul>
								<?php endif; ?>
							</details>
						<?php endforeach; ?>
					</div>
				</div>

				<div class="sb-cookie-consent__actions">
					<?php if ( ! empty( $settings['show_decline'] ) ) : ?>
						<button type="button" class="sb-cookie-consent__btn sb-cookie-consent__btn--decline" data-sb-cc="decline">
							<?php echo esc_html( (string) $settings['decline_label'] ); ?>
						</button>
					<?php endif; ?>
					<button
						type="button"
						class="sb-cookie-consent__btn sb-cookie-consent__btn--settings"
						data-sb-cc="settings"
						aria-expanded="false"
						aria-controls="sb-cookie-consent-details"
					>
						<?php echo esc_html( (string) ( $settings['settings_label'] ?? __( 'Настройки', 'sb-cookie-consent' ) ) ); ?>
					</button>
					<button type="button" class="sb-cookie-consent__btn sb-cookie-consent__btn--save" data-sb-cc="save" hidden>
						<?php echo esc_html( (string) $settings['save_label'] ); ?>
					</button>
					<button type="button" class="sb-cookie-consent__btn sb-cookie-consent__btn--accept" data-sb-cc="accept">
						<?php echo esc_html( (string) $settings['accept_label'] ); ?>
					</button>
				</div>
			</div>
		</div>
		<?php
	}
}
