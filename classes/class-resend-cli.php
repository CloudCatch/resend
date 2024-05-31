<?php
/**
 * Resend WP_CLI class.
 *
 * @package CloudCatch\Resend
 */

namespace CloudCatch\Resend;

/**
 * Resend PHPMailer class.
 */
class Resend_CLI extends \WP_CLI_Command {

	/**
	 * Send an email.
	 */
	public function send_test( $args, $assoc_args ) {
		$recipient = get_option( 'admin_email' );

		$subject = 'Test email from Resend';

		$message = 'This is a test email from Resend.';

		$headers   = array();
		$headers[] = 'From: Test <info@test.co.uk>';
		$headers[] = 'Cc: copy_to_1@email.com';
		$headers[] = 'Cc: copy_to_2@email.com';

		$headers[] = 'Bcc: bcc_to_1@email.com';
		$headers[] = 'Bcc: bcc_to_2@email.com';

		// Add Reply-To header.
		$headers[] = 'Reply-To: david@dkjensen.com';

		$sent = wp_mail( $recipient, $subject, $message, $headers );

		if ( ! $sent ) {
			\WP_CLI::error( 'Email not sent.' );
		}

		\WP_CLI::success( 'Email sent.' );
	}
}
