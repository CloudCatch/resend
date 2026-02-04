<?php
//phpcs:ignoreFile

/**
 * Resend PHPMailer class.
 *
 * @package CloudCatch\Resend
 */

namespace CloudCatch\Resend;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Resend;
use Resend\Client;

/**
 * Simple fallback logger for environments without compatible PSR-3.
 */
class Resend_Logger {
	/**
	 * Log an error message.
	 *
	 * @param string $message The log message.
	 * @param array  $context The log context.
	 * @return void
	 */
	public function error( string $message, array $context = array() ): void {
		$context_string = '';

		if ( ! empty( $context ) ) {
			$context_string = ' ' . wp_json_encode( $context );
		}

		error_log( '[Resend] ' . $message . $context_string );
	}

	/**
	 * Log an informational message.
	 *
	 * @param string $message The log message.
	 * @param array  $context The log context.
	 * @return void
	 */
	public function info( string $message, array $context = array() ): void {
		$context_string = '';

		if ( ! empty( $context ) ) {
			$context_string = ' ' . wp_json_encode( $context );
		}

		error_log( '[Resend] ' . $message . $context_string );
	}
}

/**
 * Resend PHPMailer class.
 */
class Resend_PHPMailer extends \PHPMailer\PHPMailer\PHPMailer {

	/**
	 * The logger.
	 *
	 * @var Resend_Logger|Logger|null
	 */
	protected $logger;

	/**
	 * The Resend instance.
	 *
	 * @var ?Resend\Client
	 */
	protected $resend;

	/**
	 * Resend_PHPMailer constructor.
	 *
	 * @param bool|null $exceptions The exceptions.
	 */
	public function __construct( $exceptions = null ) {
		$this->setupLogger();

		parent::__construct( $exceptions );
	}

	/**
	 * Initialize the Resend client.
	 *
	 * @return Client
	 */
	public function resend() {
		if ( ! $this->resend ) {
			$settings = $this->getSettings();

			$this->resend = Resend::client( (string) $settings['api_key'] );
		}

		return $this->resend;
	}

	/**
	 * Initialize the logger.
	 *
	 * @return void
	 */
	protected function setupLogger(): void {
		if ( ! $this->logger ) {
			$this->logger = new Resend_Logger();

			if ( $this->supportsPsrLogV3() ) {
				$this->logger = new Logger( 'resend' );
				
				$this->logger->pushHandler(
					new StreamHandler(
						wp_upload_dir()['basedir'] . '/resend/resend.log',
						100
					)
				);
			}

			// Allow third parties to push additional handlers, etc.
			do_action_ref_array( 'resend_logger', array( &$this->logger ) );
		}
	}

	/**
	 * Determine if PSR/Log v3 is available (required by Monolog v3).
	 *
	 * @return bool
	 */
	protected function supportsPsrLogV3(): bool {
		if ( ! interface_exists( 'Psr\\Log\\LoggerInterface' ) ) {
			return false;
		}

		try {
			$method = new \ReflectionMethod( 'Psr\\Log\\LoggerInterface', 'error' );
			if ( ! $method->hasReturnType() ) {
				return false;
			}

			$return_type = $method->getReturnType();
			if ( $return_type instanceof \ReflectionNamedType && 'void' !== $return_type->getName() ) {
				return false;
			}
		} catch ( \Throwable $e ) {
			return false;
		}

		return true;
	}

	/**
	 * Get the API key.
	 *
	 * @return string
	 */
	protected function formatFrom(): string {
		$settings = $this->getSettings();

		$from_email = (string) $settings['from_email'];
		$from_name  = (string) $settings['from_name'];

		if ( empty( $from_name ) ) {
			return $from_email;
		}

		return sprintf( '%s <%s>', $from_name, $from_email );
	}

	/**
	 * Format the PHPMailer recipients.
	 *
	 * @param string $type The recipient type.
	 *
	 * @throws \Exception If the recipient type is invalid.
	 *
	 * @return array
	 */
	protected function formatRecipients( $type = 'to' ): array {
		$recipients = array();

		if ( ! property_exists( $this, $type ) ) {
			throw new \Exception( 'Invalid recipient type.' );
		}

		/** @var array<array-key, string|array<array-key, string>> $property */
		$property = $this->$type;

		foreach ( $property as $recipient ) {
			if ( is_array( $recipient ) ) {
				$recipients[] = $recipient[0];
			} else {
				$recipients[] = $recipient;
			}
		}

		return $recipients;
	}

	/**
	 * Format the PHPMailer attachments.
	 *
	 * @return array<array-key, array<string, string>>
	 */
	protected function formatAttachments(): array {
		$attachments = array();

		foreach ( $this->attachment as $attachment ) {
			/**
			 * @var array{
			 *     0: string,
			 *     1: string,
			 *     2: string,
			 *     3: string,
			 *     4: string,
			 *     5: bool,
			 *     6: string,
			 *     7: string
			 * } $attachment
			 */
			$content = $attachment[0];

			if ( ! $attachment[5] ) {
				$content = $this->encodeFile( $attachment[0] );
			}

			$attachments[] = array(
				'content'  => $content,
				'filename' => $attachment[1],
				'type'     => $attachment[4],
			);
		}

		return $attachments;
	}

	/**
	 * Check if the email was sent successfully.
	 *
	 * @param array $email The response from Resend.
	 * @return bool
	 */
	protected function emailSuccessful( array $email ): bool {
		if ( isset( $email['id'] ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Resend the email.
	 *
	 * @param string $header The email header.
	 * @param string $body   The email body.
	 *
	 * @throws \PHPMailer\PHPMailer\Exception To be sent back to PHPMailer to catch.
	 *
	 * @return bool
	 */
	protected function resendSend( string $header, string $body ): bool {
		// Debug mode: skip actual API call but process everything else.
		if ( defined( 'RESEND_DEBUG_MODE' ) ) {
			$email = array(
				'id'      => 'test_' . uniqid(),
				'from'    => $this->formatFrom(),
				'subject' => $this->Subject,
				'to'      => $this->formatRecipients(),
			);

			$this->logger->info(
				'[DEBUG MODE] Email not sent',
				array(
					'id'         => $email['id'],
					'from'       => $email['from'],
					'to'         => $email['to'],
					'subject'    => $email['subject'],
					'recipients' => $this->formatRecipients(),
					'cc'         => $this->formatRecipients( 'cc' ),
					'bcc'        => $this->formatRecipients( 'bcc' ),
				)
			);

			return true;
		}

		try {
			$email = $this->resend()->emails->send(
				array(
					'from'        => $this->formatFrom(),
					'subject'     => $this->Subject,
					'html'        => $this->Body,
					'to'          => $this->formatRecipients(),
					'bcc'         => $this->formatRecipients( 'bcc' ),
					'cc'          => $this->formatRecipients( 'cc' ),
					'reply_to'    => $this->formatRecipients( 'ReplyTo' ),
					'attachments' => $this->formatAttachments(),
				)
			)->toArray();
		} catch ( \Exception $e ) {
			$email = array(
				'message' => $e->getMessage(),
			);
		}

		if ( ! $this->emailSuccessful( $email ) ) {
			throw new \PHPMailer\PHPMailer\Exception( esc_html( (string) $email['message'] ) );
		}

		return true;
	}

	/**
	 * Log the error.
	 *
	 * @param string $message The log message.
	 * @param int    $level  The PHPMailer debug level.
	 * @return void
	 */
	protected function log( $message, $level ) {
		$this->logger->error( $message );
	}

	/**
	 * Get Resend settings.
	 *
	 * @return array<array-key, mixed>
	 */
	protected function getSettings(): array {
		$default_settings = array(
			'api_key'    => '',
			'from_email' => '',
			'from_name'  => '',
		);

		$settings = (array) get_option( 'resend_settings', $default_settings );

		return wp_parse_args( $settings, $default_settings );
	}
}
