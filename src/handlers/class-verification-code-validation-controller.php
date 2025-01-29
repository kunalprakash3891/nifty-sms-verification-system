<?php
/**
 * REST API Handler for verification code validation
 *
 * @package    Nifty_SMS_Verification_System
 * @subpackage Handlers
 * @copyright  Copyright (c) 2024, Brajesh Singh
 * @license    https://www.gnu.org/licenses/gpl.html GNU Public License
 * @author     Brajesh Singh
 * @since      1.0.0
 */

namespace Nifty_SMS_Verification_System\Handlers;

use Tmeister\Firebase\JWT\JWT;
use Nifty_SMS_Verification_System\Core\Twilio_Client;
use Nifty_SMS_Verification_System\Models\Pending_Verification;
use Nifty_SMS_Verification_System\Models\User_Phone_Number;
use WP_REST_Controller;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * REST API Controller for SMS verification code validation.
 */
class Verification_Code_Validation_Controller extends WP_REST_Controller {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->namespace = 'nifty-sms-verification-system/v1';
		$this->rest_base = 'verifysmscode';
	}

	/**
	 * Register the REST API routes for SMS verification.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'verify_code' ),
					'permission_callback' => array( $this, 'verify_code_permissions_check' ),
					'args'                => $this->get_endpoint_args(),
					'schema'              => array( $this, 'get_public_item_schema' ),
				),
			)
		);
	}

	/**
	 * Check if the request has permission to verify SMS code.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error True if the request has permission, WP_Error object otherwise.
	 */
	public function verify_code_permissions_check( $request ) {
		return true; // Anyone can verify code.
	}

	/**
	 * Verify the SMS code against stored data.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function verify_code( $request ) {
		$device_id         = $request->get_param( 'device_id' );
		$phone_number      = $request->get_param( 'phone_number' );
		$request_id        = $request->get_param( 'request_id' );
		$verification_code = $request->get_param( 'verification_code' );
		$generate_auth      = $request->get_param( 'generate_auth' );

		// should we opt for it?
		$request = Pending_Verification::first(
			array(
				'phone_number' => $phone_number,
				'device_id'    => $device_id,
			)
		);

		if ( ! $request ) {
			return new WP_Error(
				'niftysvs_rest_verification_invalid_request',
				__( 'Invalid action.', 'nifty-sms-verification-system' ),
				array( 'status' => 404 )
			);
		}

		// request exists.
		$client = new Twilio_Client();
		$status = $client->verify( $phone_number, $verification_code );
		if ( is_wp_error( $status ) ) {
			return $status;
		}

		if ( $status ) {
			// mark verified.
			$request->is_verified = 1;
			$request->save();
			if ( is_user_logged_in() ) {
				niftysvs_transition_user_verification_request( get_current_user_id(), $request );
			} else { // for non logged user.

				$record = User_Phone_Number::first(
					array(
						'phone_number' => $phone_number,
					)
				);

				if ( $record ) {
					// there exists an associated user.
					// delete all pending requests for this number.
					$request->delete();
					$response = array(
						'verified'  => (bool) $request->is_verified,
						'user_id'   => absint( $record->user_id ),
						'device_id' => $device_id,
					);

					if ( $generate_auth && $request->is_verified ) {
						$token = $this->generate_token( (int) $record->user_id );
						if ( is_wp_error( $token ) ) {
							return $token;
						}
						$response['auth'] = $token;
					}

					return rest_ensure_response( $response );
				}
			}
		}

		$response = array(
			'verified'  => (bool) $request->is_verified,
			'user_id'   => get_current_user_id(),
			'device_id' => $device_id,
		);

		if ( $generate_auth && $request->is_verified ) {
			$token = $this->generate_token( get_current_user_id() );
			if ( is_wp_error( $token ) ) {
				return $token;
			}
			$response['auth'] = $token;
		}

		return rest_ensure_response( $response );
	}

	/**
	 * Get the arguments for the verify code endpoint.
	 *
	 * @return array Array of arguments for the endpoint.
	 * @since 1.0.0
	 */
	protected function get_endpoint_args() {
		return array(
			'device_id'         => array(
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'phone_number'      => array(
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'request_id'        => array(
				'required'          => true,
				'type'              => 'integer',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'verification_code' => array(
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => 'rest_validate_request_arg',
			),
			'generate_auth' => array(
				'required'          => false,
				'default'           => false,
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
				'validate_callback' => 'rest_validate_request_arg',
			),
		);
	}

	/**
	 * Get the schema for the verification response.
	 *
	 * @return array Schema data array.
	 * @since 1.0.0
	 */
	public function get_item_schema() {
		if ( $this->schema ) {
			return $this->schema;
		}

		$this->schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'verification',
			'type'       => 'object',
			'properties' => array(
				'verified'  => array(
					'description' => __( 'Whether the verification was successful or not.', 'nifty-sms-verification-system' ),
					'type'        => 'boolean',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'user_id'   => array(
					'description' => __( 'The user ID, if verification was successful.', 'nifty-sms-verification-system' ),
					'type'        => 'integer',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'device_id' => array(
					'description' => __( 'The device ID that was verified.', 'nifty-sms-verification-system' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'auth' => array(
					'description' => __( 'Auth token if desired.', 'nifty-sms-verification-system' ),
					'type'        => 'object',
					'context'     => array( 'view' ),
					'readonly'    => true,
					'properties' => array(
						'token'             => array(
							'description' => __( 'The JWT token.', 'nifty-sms-verification-system' ),
							'type'        => 'string',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'user_email'        => array(
							'description' => __( 'The user email.', 'nifty-sms-verification-system' ),
							'type'        => 'string',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'user_nicename'     => array(
							'description' => __( 'The user nicename.', 'nifty-sms-verification-system' ),
							'type'        => 'string',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
						'user_display_name' => array(
							'description' => __( 'The user display name.', 'nifty-sms-verification-system' ),
							'type'        => 'string',
							'context'     => array( 'view' ),
							'readonly'    => true,
						),
					)
				),
			),
		);

		return $this->schema;
	}

	/**
	 * This is a custom copy of `Jwt_Auth_Public::generate_token` to generate jwt token based on sms verification status.
	 *
	 * @see Jwt_Auth_Public::generate_token()
	 * @param $user_id
	 *
	 * @return mixed|string|WP_Error|null
	 */
	private function generate_token( $user_id ) {

		if ( ! $user_id || ! class_exists( JWT::class ) ) {
			return null;
		}

		if ( function_exists( 'bpmts_is_user_suspended' ) && bpmts_is_user_suspended( $user_id ) ) {
			return null;
		}

		$secret_key = defined( 'JWT_AUTH_SECRET_KEY' ) ? JWT_AUTH_SECRET_KEY : false;


		if ( ! $secret_key ) {
			return null;
			/*return new WP_Error(
				'jwt_auth_bad_config',
				__( 'JWT is not configured properly, please contact the admin', 'nifty-sms-verification-system' ),
				array(
					'status' => 403,
				)
			);*/
		}

		// verify that user exists.
		$user = get_user_by( 'id', $user_id );

		if ( ! $user ) {
			return null;
		}

		/** Generate */
		$issuedAt  = time();
		$notBefore = apply_filters( 'jwt_auth_not_before', $issuedAt, $issuedAt );
		$expire    = apply_filters( 'jwt_auth_expire', $issuedAt + ( DAY_IN_SECONDS * 7 ), $issuedAt );

		$token = array(
			'iss'  => get_bloginfo( 'url' ),
			'iat'  => $issuedAt,
			'nbf'  => $notBefore,
			'exp'  => $expire,
			'data' => array(
				'user' => array(
					'id' => $user->data->ID,
				),
			),
		);

		/** Let the user modify the token data before the sign. */
		$algorithm = $this->get_algorithm();

		if ( $algorithm === false ) {
			return new WP_Error(
				'jwt_auth_unsupported_algorithm',
				__( 'Algorithm not supported, see https://www.rfc-editor.org/rfc/rfc7518#section-3',
					'wp-api-jwt-auth' ),
				array(
					'status' => 403,
				)
			);
		}

		$token = JWT::encode(
			apply_filters( 'jwt_auth_token_before_sign', $token, $user ),
			$secret_key,
			$algorithm
		);

		/** The token is signed, now create the object with no sensible user data to the client*/
		$data = array(
			'token'             => $token,
			'user_email'        => $user->data->user_email,
			'user_nicename'     => $user->data->user_nicename,
			'user_display_name' => $user->data->display_name,
		);

		/** Let the user modify the data before send it back */
		return apply_filters( 'jwt_auth_token_before_dispatch', $data, $user );
	}

	/**
	 * Copy of the method `Jwt_Auth_Public::get_algorithm`.
	 *
	 * @see Jwt_Auth_Public::get_algorithm() for more details.
	 *
	 * Get the algorithm used to sign the token via the filter jwt_auth_algorithm.
	 * and validate that the algorithm is in the supported list.
	 *
	 * @return false|mixed|null
	 */
	private function get_algorithm() {
		$algorithm = apply_filters( 'jwt_auth_algorithm', 'HS256' );

		if ( ! in_array( $algorithm, array(
			'HS256',
			'HS384',
			'HS512',
			'RS256',
			'RS384',
			'RS512',
			'ES256',
			'ES384',
			'ES512',
			'PS256',
			'PS384',
			'PS512'
		) ) ) {
			return false;
		}

		return $algorithm;
	}
}
