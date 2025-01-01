<?php
/**
 * REST API Handler for verification request
 *
 * @package    Nifty_SMS_Verification_System
 * @subpackage Handlers
 * @copyright  Copyright (c) 2024, Brajesh Singh
 * @license    https://www.gnu.org/licenses/gpl.html GNU Public License
 * @author     Brajesh Singh
 * @since      1.0.0
 */

namespace Nifty_SMS_Verification_System\Handlers;

use Nifty_SMS_Verification_System\Core\Twilio_Client;
use Nifty_SMS_Verification_System\Models\Pending_Verification;
use Nifty_SMS_Verification_System\Models\SMS_Delayed_Number;
use Nifty_SMS_Verification_System\Models\SMS_Log;
use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

// Do not allow direct access over web.
defined( 'ABSPATH' ) || exit;

/**
 * Verification request handler
 */
class Verification_Request_REST_Controller extends WP_REST_Controller {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->namespace = 'nifty-sms-verification-system/v1';
		$this->rest_base = 'requestsmscode';
	}

	/**
	 * Registers the routes for handling verification requests.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'handle_request' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'device_id'    => array(
							'required' => true,
							'type'     => 'string',
						),
						'phone_number' => array(
							'required' => true,
							'type'     => 'string',
						),
					),
				),
			)
		);
	}

	/**
	 * Handles the verification request.
	 *
	 * @param WP_REST_Request $request The request object.
	 *
	 * @return WP_Error|WP_REST_Response|mixed The response data.
	 */
	public function handle_request( $request ) {
		$device_id    = sanitize_text_field( $request['device_id'] );
		$phone_number = sanitize_text_field( $request['phone_number'] );

		$response = array(
			'verified'   => false,
			'requested'  => false,
			'request_id' => 0,
		);

		// check for blacklist if we are here
		if ( niftysvs_is_phone_number_blacklisted( $phone_number ) ) {
			return new WP_Error( 'niftysvs_phone_number_blacklisted', __( 'The phone number is blacklisted.', 'nifty-sms-verification-system' ), array( 'status' => 403 ) );
		}

		// check for existing pending request,
		$request = Pending_Verification::first( array( 'phone_number' => $phone_number ) );

		// if request is verified for the number from same device.
		if ( $request && $request->is_verified && $request->device_id === $device_id ) {
			if ( ! is_user_logged_in() ) {
				return array(
					'verified'   => true,
					'requested'  => false,
					'request_id' => absint( $request->id ),
				);
			}
		}

		// check if already in delayed list.
		// we should probably do it only if we have a request.
		if ( SMS_Delayed_Number::is_delayed( $phone_number ) ) {
			return new WP_Error( 'niftysvs_rate_limit_exceeded', __( 'Sorry, you have requested too many SMS codes. Please try again later.', 'nifty-sms-verification-system' ), array( 'status' => 429 ) );
		}

		if ( $request ) {
			$current_time = current_time( 'mysql', true );
			$rate_limit   = niftysvs_get_option( 'verification_rate_limit', array(
				'duration' => 5, // 5 mins
				'delay'    => 5, // 5 mins
				'attempts' => 5, // 5 requests
			) );

			$interval = absint( $rate_limit['duration'] );
			$attempts = absint( $rate_limit['attempts'] );
			// check sms log table for the rate limit.
			global $wpdb;
			$sms_log_table = SMS_Log::table();
			$count         = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM $sms_log_table WHERE phone_number = %s AND requested_at > DATE_SUB(%s, INTERVAL %d MINUTE)",
					$phone_number,
					$current_time,
					$interval
				)
			);

			$count = absint( $count );
			if ( $count >= $attempts ) {
				// add to delayed list.
				$delayed = SMS_Delayed_Number::create( array(
					'phone_number' => $phone_number,
					'created_at'   => $request->requested_at,
				) );

				$delayed->save();

				return new WP_Error( 'niftysvs_rate_limit_exceeded', __( 'Sorry, you have requested too many SMS codes. Please try again later.', 'nifty-sms-verification-system' ), array( 'status' => 429 ) );
			}
		}

		if ( $request ) {
			$old_request = clone( $request );
		} else {
			$old_request = null;
		}

		// The phone number is already in the pending list.
		if ( $request ) {
			$request->is_verified = 0;// always reset the verification status on new request.
			// what if device id changed, update with the new device id.
			if ( $request->device_id !== $device_id ) {
				$request->device_id = $device_id;
				// drop any record with the current device id.
				Pending_Verification::destroy( array( 'device_id' => $device_id ) );
				//$request->save();
			}
		} else {
			// delete any record with the current device id.
			Pending_Verification::destroy( array( 'device_id' => $device_id ) );
			// first request.
			$request = Pending_Verification::create(
				array(
					'device_id'     => $device_id,
					'phone_number'  => $phone_number,
					'requested_at'  => current_time( 'mysql', true ),
					'request_count' => 1,
				)
			);
		}

		// mark current time as request time.
		$request->requested_at = current_time( 'mysql', true );
		// if we are here, we can send the code.
		// save to db
		$client = new Twilio_Client();
		$code   = $client->send_otp( $phone_number );
		if ( is_wp_error( $code ) ) {
			return $code;
		}

		// if we are here, save to the database.
		if ( $old_request != $request && false === $request->save() ) {
			return new WP_Error( 'niftysvs_request_save_error', __( 'Failed to save the request.', 'nifty-sms-verification-system' ), array( 'status' => 500 ) );
		}

		$log = SMS_Log::create( array(
			'phone_number' => $phone_number,
			'requested_at' => $request->requested_at,
		) );
		$log->save();

		return array(
			'verified'   => false,
			'requested'  => true,
			'request_id' => absint( $request->id ),
		);
	}

	/**
	 * Retrieves the schema for the verification request.
	 *
	 * @return array The schema array.
	 */
	public function get_item_schema() {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'verification-request',
			'type'       => 'object',
			'properties' => array(
				'verified'   => array(
					'description' => __( 'Verification status.', 'nifty-sms-verification-system' ),
					'type'        => 'boolean',
					'readonly'    => true,
				),
				'requested'  => array(
					'description' => __( 'Request status.', 'nifty-sms-verification-system' ),
					'type'        => 'boolean',
					'readonly'    => true,
				),
				'request_id' => array(
					'description' => __( 'Unique request identifier.', 'nifty-sms-verification-system' ),
					'type'        => 'integer',
					'readonly'    => true,
				),
			),
		);
	}
}
