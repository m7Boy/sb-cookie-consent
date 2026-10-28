<?php
/**
 * Front-end cookie banner.
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

	private const COOKIE_NAME = 'sb_cookie_consent';

	private SB_Cookie_Consent_Settings $settings;

	public function __construct( SB_Cookie_Consent_Settings $settings ) {
		$this->settings = $settings;
	}

	public function register(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
		// Before wp_print_footer_scripts (priority 20), so the DOM node exists when JS runs.
		add_action( 'wp_footer', array( $this, 'render' ), 5 );
	}

	/**
	 * Whether the visitor already stored a consent choice.
	 */
	private function has_choice(): bool {
		if ( empty( $_COOKIE[ self::COOKIE_NAME ] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			return false;
		}

		$value = sanitize_key( wp_unslash( (string) $_COOKIE[ self::COOKIE_NAME ] ) );

		return in_array( $value, array( 'accepted', 'declined' ), true );
	}

	public function enqueue(): void {
		if ( is_admin() || ! $this->settings->enabled() || $this->has_choice() ) {
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
			'.sb-cookie-consent{--sb-cc-bg:%1$s;--sb-cc-text:%2$s;--sb-cc-btn-bg:%3$s;--sb-cc-btn-text:%4$s;--sb-cc-decline-bg:%5$s;--sb-cc-decline-text:%6$s;}',
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

		wp_localize_script(
			'sb-cookie-consent',
			'sbCookieConsent',
			array(
				'cookieName' => self::COOKIE_NAME,
				'days'       => (int) $settings['cookie_days'],
			)
		);
	}

	public function render(): void {
		if ( is_admin() || ! $this->settings->enabled() || $this->has_choice() ) {
			return;
		}

		$settings = $this->settings->all();
		$position = 'top' === $settings['position'] ? 'top' : 'bottom';
		?>
		<div
			id="sb-cookie-consent"
			class="sb-cookie-consent sb-cookie-consent--<?php echo esc_attr( $position ); ?>"
			role="dialog"
			aria-live="polite"
			aria-label="<?php echo esc_attr__( 'Съгласие с бисквитки', 'sb-cookie-consent' ); ?>"
		>
			<div class="sb-cookie-consent__inner">
				<div class="sb-cookie-consent__text">
					<p class="sb-cookie-consent__message"><?php echo esc_html( (string) $settings['message'] ); ?></p>
					<?php if ( ! empty( $settings['privacy_url'] ) ) : ?>
						<p class="sb-cookie-consent__privacy">
							<a href="<?php echo esc_url( (string) $settings['privacy_url'] ); ?>">
								<?php echo esc_html( (string) $settings['privacy_label'] ); ?>
							</a>
						</p>
					<?php endif; ?>
				</div>
				<div class="sb-cookie-consent__actions">
					<?php if ( ! empty( $settings['show_decline'] ) ) : ?>
						<button type="button" class="sb-cookie-consent__btn sb-cookie-consent__btn--decline" data-sb-cc="decline">
							<?php echo esc_html( (string) $settings['decline_label'] ); ?>
						</button>
					<?php endif; ?>
					<button type="button" class="sb-cookie-consent__btn sb-cookie-consent__btn--accept" data-sb-cc="accept">
						<?php echo esc_html( (string) $settings['accept_label'] ); ?>
					</button>
				</div>
			</div>
		</div>
		<?php
	}
}
