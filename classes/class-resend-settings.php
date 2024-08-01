<?php
/**
 * Resend settings class.
 *
 * @package CloudCatch\Resend
 */

namespace CloudCatch\Resend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Resend settings class.
 */
class Resend_Settings {

	/**
	 * Resend_Settings constructor.
	 */
	public function __construct() {
		register_setting( 'resend_settings', 'resend_settings' );
		add_settings_section( 'resend_main_section', '', '__return_empty_string', 'resend' );
	}

	/**
	 * Register a field.
	 *
	 * @param string $id          The ID of the field.
	 * @param string $type        The type of the field.
	 * @param string $name        The name of the field.
	 * @param string $description The description of the field.
	 * @param bool   $required    Whether the field is required.
	 *
	 * @return Resend_Settings
	 */
	public function registerField( string $id, string $type, string $name, string $description = '', bool $required = false ): Resend_Settings {
		$options = (array) get_option( 'resend_settings' );

		add_settings_field(
			$id,
			$name,
			function () use ( $id, $type, $description, $required, $options ) {
				$value = (string) ( isset( $options[ $id ] ) ? $options[ $id ] : '' );

				$required = $required ? 'required' : '';

				$description = $description ? sprintf( '<p class="description">%s</p>', wp_kses_post( $description ) ) : '';

				printf(
					'<input type="%s" class="regular-text" id="resend_%s" name="resend_settings[%s]" value="%s" %s />%s',
					esc_attr( $type ),
					esc_attr( $id ),
					esc_attr( $id ),
					esc_attr( $value ),
					$required, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					$description // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				);
			},
			'resend',
			'resend_main_section',
		);

		return $this;
	}

	/**
	 * Render the settings page.
	 *
	 * @return void
	 */
	public function renderSettings(): void {
		// Get current user email.
		$current_user = wp_get_current_user();

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Resend Settings', 'send-emails-with-resend' ); ?></h1>
			<form method="post" action="options.php">
				<?php
					settings_fields( 'resend_settings' );
					do_settings_sections( 'resend' );
					submit_button();
				?>
			</form>

			<hr style="margin: 2em 0;" />

			<h2><?php esc_html_e( 'Send Test Email', 'send-emails-with-resend' ); ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( add_query_arg( array( 'page' => 'resend' ), 'options-general.php' ) ) ); ?>">
				<p>
					<label for="resend_test_email" class="screen-reader-text"><?php esc_html_e( 'Email Address', 'send-emails-with-resend' ); ?></label>
					<input type="email" placeholder="<?php esc_attr_e( 'Email address', 'send-emails-with-resend' ); ?>" class="regular-text" id="resend_test_email" name="resend_test_email" value="<?php echo esc_attr( $current_user->user_email ); ?>" required />
				</p>
				<?php wp_nonce_field( 'resend_send_test_email', 'resend_send_test_email_nonce' ); ?>
				<?php submit_button( __( 'Send Test Email', 'send-emails-with-resend' ), 'secondary' ); ?>
			</form>
		</div>
		<?php
	}
}
