<?php
/**
 * Twilio client implementation(Not used currently)
 *
 * @package    Nifty_SMS_Verification_System
 * @subpackage Handlers
 * @copyright  Copyright (c) 2024, Brajesh Singh
 * @license    https://www.gnu.org/licenses/gpl.html GNU Public License
 * @author     Brajesh Singh
 * @since      1.0.0
 */

namespace Nifty_SMS_Verification_System\Core;

class Twilio_Client_OLD {


	private string $sid;
	private string $token;

	public function __construct( $sid, $token ) {
		$this->sid   = $sid;
		$this->token = $token;
	}

	public function is_enabled(): bool {
		return $this->sid && $this->token && niftsvs_is_verification_enabled();
	}

	/**
	 * Checks for the valid phone number.
	 *
	 * @param $phone_number
	 *
	 * @return array|WP_Error
	 */
	public function lookup_phone_number( $phone_number ) {

		$args = array(
			'headers' => $this->get_auth_headers(),
			'timeout' => 30,
		);

		$response = wp_remote_get(
			'https://lookups.twilio.com/v2/PhoneNumbers/' . $phone_number,
			$args
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		} else {
			$body = wp_remote_retrieve_body( $response );
			$data = (array) json_decode( $body );
			if ( empty( $data['valid'] ) ) {
				return new WP_Error( 'niftysvs_twilio_validation_invalid_phone_number', __( 'Invalid phone number.', 'nifty-sms-verification-system' ), array( 'status' => 400 ) );
			} elseif ( ! empty( $data['validation_errors'] ) ) {
				return new WP_Error( 'niftysvs_twilio_validation_error', join( ',', $data['validation_errors'] ), array( 'status' => 400 ) );
			}
			// Process response data
		}

		return $data;
	}

	private function get_auth_headers(): array {
		$auth = base64_encode( $this->sid . ':' . $this->token );

		return array(
			'Authorization' => 'Basic ' . $auth,
		);
	}
}
