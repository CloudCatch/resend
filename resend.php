<?php
/**
 * Plugin Name:       Resend
 * Description:       Send emails using the Resend PHP SDK
 * Requires at least: 6.0.0
 * Requires PHP:      8.1
 * Version:           0.0.0-development
 * Author:            CloudCatch LLC
 * Author URI:        https://cloudcatch.io
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       resend
 *
 * @package CloudCatch\Resend
 */

namespace CloudCatch\Resend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load Composer dependencies.
require_once __DIR__ . '/vendor/autoload.php';

// Load CLI.
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once __DIR__ . '/classes/class-resend-cli.php';

	\WP_CLI::add_command( 'resend', __NAMESPACE__ . '\Resend_CLI' );
}

/**
 * Handle PHPMailer.
 *
 * @param \PHPMailer\PHPMailer\PHPMailer $phpmailer The PHPMailer instance.
 *
 * @return void
 */
function handle_phpmailer( &$phpmailer ) {
	require_once __DIR__ . '/classes/class-resend-phpmailer.php';

	$resend         = new Resend_PHPMailer();
	$resend->Mailer = 'resend';

	$old_phpmailer   = $phpmailer;
	$old_recipients  = $old_phpmailer->getToAddresses();
	$old_cc          = $old_phpmailer->getCcAddresses();
	$old_bcc         = $old_phpmailer->getBccAddresses();
	$old_reply_to    = $old_phpmailer->getReplyToAddresses();
	$old_attachments = $old_phpmailer->getAttachments();

	$phpmailer              = $resend;
	$phpmailer->Mailer      = 'resend';
	$phpmailer->SMTPDebug   = 2;
	$phpmailer->Debugoutput = array( $phpmailer, 'log' );

	/**
	 * Add recipients
	 *
	 * @var array<array-key, string|array<array-key, string>> $old_recipients
	 */
	foreach ( $old_recipients as $recipient ) {
		$phpmailer->addAddress( $recipient[0], $recipient[1] );
	}

	/**
	 * Add CC
	 *
	 * @var array<array-key, string|array<array-key, string>> $old_cc
	 */
	foreach ( $old_cc as $cc ) {
		$phpmailer->addCC( $cc[0], $cc[1] );
	}

	/**
	 * Add BCC
	 *
	 * @var array<array-key, string|array<array-key, string>> $old_bcc
	 */
	foreach ( $old_bcc as $bcc ) {
		$phpmailer->addBCC( $bcc[0], $bcc[1] );
	}

	/**
	 * Add reply-to
	 *
	 * @var array<array-key, string|array<array-key, string>> $old_reply_to
	 */
	foreach ( $old_reply_to as $reply_to ) {
		$phpmailer->addReplyTo( $reply_to[0], $reply_to[1] );
	}

	/**
	 * Add attachments
	 *
	 * @var array<int, array{ 0: string, 1: string, 2: string, 3: string, 4: string, 5: bool, 6: string, 7: string }> $old_attachments
	 */
	foreach ( $old_attachments as $attachment ) {
		$phpmailer->addAttachment( $attachment[0], $attachment[2], $attachment[3], $attachment[4], $attachment[6] );
	}

	$phpmailer->Subject = $old_phpmailer->Subject;
	$phpmailer->Body    = $old_phpmailer->Body;
}
/** @psalm-suppress InvalidArgument */
add_action( 'phpmailer_init', __NAMESPACE__ . '\handle_phpmailer', 1000, 1 );

/**
 * Register the settings page.
 *
 * @return void
 */
function admin_settings() {

	require_once __DIR__ . '/classes/class-resend-settings.php';

	$settings = new Resend_Settings();
	$settings
		/* translators: %s: Resend.com API URL */
		->registerField( 'api_key', 'password', 'API Key', sprintf( __( 'You can find your API key here: %s', 'resend' ), make_clickable( 'https://resend.com/api-keys' ) ), true )
		->registerField( 'from_email', 'email', 'From Email', esc_html__( 'The email domain should match a verified sending domain.', 'resend' ), true )
		->registerField( 'from_name', 'text', 'From Name', '', false );

	add_options_page(
		esc_html__( 'Resend', 'resend' ),
		esc_html__( 'Resend', 'resend' ),
		'manage_options',
		'resend',
		array( $settings, 'renderSettings' )
	);
}
add_action( 'admin_menu', __NAMESPACE__ . '\admin_settings', 10, 0 );

/**
 * Send a test email.
 *
 * @return void
 */
function send_test_email(): void {
	$security = isset( $_POST['resend_send_test_email_nonce'] ) && is_scalar( $_POST['resend_send_test_email_nonce'] ) ? sanitize_key( wp_unslash( $_POST['resend_send_test_email_nonce'] ?? '' ) ) : '';
	$email    = isset( $_POST['resend_test_email'] ) && is_scalar( $_POST['resend_test_email'] ) ? sanitize_email( wp_unslash( $_POST['resend_test_email'] ) ) : '';

	if ( ! $security ) {
		return;
	}

	if ( false === wp_verify_nonce( $security, 'resend_send_test_email' ) ) {
		wp_die( esc_html__( 'Nonce verification failed.', 'resend' ) );
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to do this.', 'resend' ) );
	}

	if ( ! $email ) {
		wp_die( esc_html__( 'No email address provided.', 'resend' ) );
	}

	if ( false === is_email( $email ) ) {
		wp_die( esc_html__( 'Invalid email address.', 'resend' ) );
	}

	$sent = wp_mail(
		$email,
		__( 'Test Email', 'resend' ),
		__( 'This is a test email from Resend.', 'resend' )
	);

	if ( ! $sent ) {
		add_settings_error( 'resend', 'resend_send_test_email', esc_html__( 'The email could not be sent.', 'resend' ) );
	} else {
		add_settings_error( 'resend', 'resend_send_test_email', esc_html__( 'The email was sent.', 'resend' ), 'updated' );
	}

	set_transient( 'settings_errors', get_settings_errors(), 30 );

	$goback = add_query_arg( 'settings-updated', 'true', wp_get_referer() );

	wp_safe_redirect( $goback );
	exit;
}
add_action( 'admin_init', __NAMESPACE__ . '\send_test_email', 10, 0 );
