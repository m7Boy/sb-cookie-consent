<?php
/**
 * Cookie policy shortcode and persistent site link.
 *
 * @package SB_Cookie_Consent
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders the cookie inventory for the policy page.
 */
class SB_Cookie_Consent_Policy {

	private SB_Cookie_Consent_Settings $settings;

	public function __construct( SB_Cookie_Consent_Settings $settings ) {
		$this->settings = $settings;
	}

	public function register(): void {
		add_shortcode( 'sb_cookie_policy', array( $this, 'shortcode' ) );
		add_action( 'wp_footer', array( $this, 'render_persistent_link' ), 4 );
	}

	/**
	 * Full cookie list by category for the cookie policy page.
	 *
	 * @param array<string, string>|string $atts Shortcode attributes.
	 */
	public function shortcode( $atts = array() ): string {
		unset( $atts );
		$settings = $this->settings->all();
		$grouped  = $this->settings->cookies_by_category();
		$labels   = $settings['category_labels'];
		$descs    = $settings['category_descriptions'];

		ob_start();
		?>
		<div class="sb-cookie-policy">
			<?php foreach ( SB_Cookie_Consent_Settings::CATEGORIES as $category ) : ?>
				<section class="sb-cookie-policy__category" id="sb-cookie-policy-<?php echo esc_attr( $category ); ?>">
					<h2 class="sb-cookie-policy__title"><?php echo esc_html( (string) ( $labels[ $category ] ?? $category ) ); ?></h2>
					<p class="sb-cookie-policy__desc"><?php echo esc_html( (string) ( $descs[ $category ] ?? '' ) ); ?></p>
					<?php if ( empty( $grouped[ $category ] ) ) : ?>
						<p class="sb-cookie-policy__empty"><?php echo esc_html__( 'В момента няма регистрирани бисквитки в тази категория.', 'sb-cookie-consent' ); ?></p>
					<?php else : ?>
						<table class="sb-cookie-policy__table">
							<thead>
								<tr>
									<th><?php echo esc_html__( 'Име', 'sb-cookie-consent' ); ?></th>
									<th><?php echo esc_html__( 'Доставчик', 'sb-cookie-consent' ); ?></th>
									<th><?php echo esc_html__( 'Цел', 'sb-cookie-consent' ); ?></th>
									<th><?php echo esc_html__( 'Срок', 'sb-cookie-consent' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $grouped[ $category ] as $cookie ) : ?>
									<tr>
										<td><code><?php echo esc_html( (string) $cookie['name'] ); ?></code></td>
										<td><?php echo esc_html( (string) $cookie['provider'] ); ?></td>
										<td><?php echo esc_html( (string) $cookie['purpose'] ); ?></td>
										<td><?php echo esc_html( (string) $cookie['duration'] ); ?></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php endif; ?>
				</section>
			<?php endforeach; ?>
			<p class="sb-cookie-policy__manage">
				<button type="button" class="sb-cookie-policy__open" data-sb-cc="reopen">
					<?php echo esc_html( (string) $settings['reopen_label'] ); ?>
				</button>
			</p>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Always-visible link to the cookie policy (footer).
	 */
	public function render_persistent_link(): void {
		if ( is_admin() || ! $this->settings->enabled() ) {
			return;
		}

		$settings = $this->settings->all();
		if ( empty( $settings['show_policy_link'] ) ) {
			return;
		}

		$url = (string) $settings['cookie_policy_url'];
		if ( '' === $url ) {
			return;
		}
		?>
		<div class="sb-cookie-consent-policy-link" role="navigation" aria-label="<?php echo esc_attr__( 'Политика за бисквитки', 'sb-cookie-consent' ); ?>">
			<a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( (string) $settings['cookie_policy_label'] ); ?></a>
		</div>
		<?php
	}
}
