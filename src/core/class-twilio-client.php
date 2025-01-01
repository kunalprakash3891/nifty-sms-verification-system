<?php
/**
 * Twilio client implementation.
 *
 * @package    Nifty_SMS_Verification_System
 * @subpackage Handlers
 * @copyright  Copyright (c) 2024, Brajesh Singh
 * @license    https://www.gnu.org/licenses/gpl.html GNU Public License
 * @author     Brajesh Singh
 * @since      1.0.0
 */

namespace Nifty_SMS_Verification_System\Core;

use Twilio\Rest\Client;
use Twilio\Rest\Verify\V2\ServiceContext;
use WP_Error;
use Exception;

// Do not allow direct access over web.
defined( 'ABSPATH' ) || exit;

/**
 * Twilio client.
 */
class Twilio_Client {


	/**
	 * The Twilio Account SID.
	 *
	 * @var string
	 */
	private string $sid;

	/**
	 * The Twilio Auth Token.
	 *
	 * @var string
	 */
	private string $token;

	/**
	 * The Twilio Service SID.
	 *
	 * @var string
	 */
	private string $service_sid;

	/**
	 * Constructor to initialize Twilio client credentials.
	 */
	public function __construct() {
		$this->sid         = niftysvs_get_twilio_sid();
		$this->token       = niftysvs_get_twilio_token();
		$this->service_sid = niftysvs_get_twilio_service_sid();
	}

	/**
	 * Checks if the Twilio service is enabled.
	 *
	 * @return bool True if enabled, false otherwise.
	 */
	public function is_enabled(): bool {
		return $this->sid && $this->token && $this->service_sid && niftsvs_is_verification_enabled();
	}

	/**
	 * Checks for the valid phone number.
	 *
	 * @param string $phone_number The phone number to lookup.
	 *
	 * @return object|WP_Error The phone number details or WP_Error on failure.
	 */
	public function lookup_phone_number( string $phone_number ) {

		if ( ! $this->is_enabled() ) {
			return new WP_Error( 'niftysvs_service_not_available', __( 'Service not available.', 'nifty-sms-verification-system' ), array( 'status' => 400 ) );
		}

		try {
			$client  = $this->get_client();
			$details = $client->lookups->v2->phoneNumbers( $phone_number )->fetch();

		} catch ( Exception $e ) {
			return new WP_Error( 'niftysvs_twilio_lookup_error', $e->getMessage(), array( 'status' => 400 ) );
		}

		if ( ! $details->valid ) {
			return new WP_Error( 'niftysvs_twilio_invalid_phone_number_error', __( 'Invalid phone number.', 'nifty-sms-verification-system' ), array( 'status' => 400 ) );
		} elseif ( ! empty( $details->validation_errors ) ) {
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			return new WP_Error( 'niftysvs_twilio_phone_number_validation_error', join( ',', $details->validationErrors ), array( 'status' => 400 ) );
		}

		return $details;
	}

	/**
	 * Sends an OTP to the given phone number.
	 *
	 * @param string $phone_number The phone number to send OTP to.
	 *
	 * @return string|null|WP_Error Void on success, WP_Error on failure.
	 */
	public function send_otp( string $phone_number ) {

		if ( ! $this->is_enabled() ) {
			return new WP_Error( 'niftysvs_service_not_available', __( 'Service not available.', 'nifty-sms-verification-system' ), array( 'status' => 400 ) );
		}

		try {
			$verification = $this->get_service()
				->verifications->create(
					$phone_number,
					'sms' // Channel.
				);
		} catch ( Exception $e ) {
			return new WP_Error( 'niftysvs_twilio_send_otp_error', $e->getMessage(), array( 'status' => 400 ) );
		}

		return $verification->sid;
	}

	/**
	 * Verifies the OTP for the given phone number.
	 *
	 * @param string $phone_number The phone number to verify.
	 * @param string $code         The OTP code to verify.
	 *
	 * @return bool|WP_Error True if verification is successful, False on failure.
	 */
	public function verify( string $phone_number, string $code ) {

		if ( ! $this->is_enabled() ) {
			return new WP_Error( 'niftysvs_service_not_available', __( 'Service not available.', 'nifty-sms-verification-system' ), array( 'status' => 400 ) );
		}

		try {
			$verification_check = $this->get_service()->verificationChecks->create(
				array(
					'to'   => $phone_number,
					'code' => $code,
				)
			);
		} catch ( Exception $e ) {
			return new WP_Error( 'niftysvs_twilio_verify_otp_error', $e->getMessage(), array( 'status' => 400 ) );
		}

		return 'approved' === $verification_check->status;
	}

	/**
	 * Returns the Twilio Verification service instance.
	 *
	 * @return ServiceContext The Twilio Verify service instance.
	 */
	public function get_service() {
		return $this->get_client()->verify->v2->services( $this->service_sid );
	}

	/**
	 * Gets the Twilio client instance.
	 *
	 * @return Client The Twilio client instance.
	 *
	 * @throws Exception When the Twilio credentials are invalid.
	 */
	public function get_client(): Client {
		return new Client( $this->sid, $this->token );
	}
}
