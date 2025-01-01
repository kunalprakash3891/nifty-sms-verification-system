<?php
/**
 * Signup endpoint helper
 *
 * @package    BP_New_Signup_Endpoints
 * @copyright  Copyright (c) 2024, Brajesh Singh
 * @license    https://www.gnu.org/licenses/gpl.html GNU Public License
 * @author     Brajesh Singh, Ravi Sharma
 * @since      1.0.0
 */

namespace Nifty_SMS_Verification_System\Handlers;

use Nifty_SMS_Verification_System\Models\Pending_Verification;
use WP_REST_Request;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Signup endpoint helper.
 */
class Signup_Helper {

	/**
	 * Sets up the actions and filters.
	 */
	public function setup() {
		add_filter( 'bp_rest_signup_create_item_meta', array( $this, 'filter_item_meta' ), 10, 2 );
		add_filter( 'bp_rest_signup_create_item_query_arguments', array( $this, 'filter_args' ) );

		add_action( 'bp_core_activated_user', array( $this, 'handle_signup' ), 10, 3 );
	}

	/**
	 * Filters item meta in signup rest request and adds device id.
	 *
	 * @param array $meta Meta info.
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return array
	 */
	public function filter_item_meta( $meta, $request ) {

		$device_id = $request->get_param( 'device_id' );

		if ( empty( $device_id ) ) {
			return $meta;
		}

		$meta['niftysvs_device_id'] = $device_id;

		return $meta;
	}

	/**
	 * Filters signup schema and adds the schema for device id.
	 *
	 * @param array $args Schema info.
	 *
	 * @return array
	 */
	public function filter_args( $args ) {

		$args['device_id'] = array(
			'context'           => array( 'edit' ),
			'title'             => __( 'Device ID', 'nifty-sms-verification-system' ),
			'description'       => '',
			'type'              => 'string',
			'required'          => niftysvs_is_verification_enforced(),
			'sanitize_callback' => 'sanitize_text_field',
			'validate_callback' => array( $this, 'validate_device_id' ),
		);

		return $args;
	}

	/**
	 * Validates the device ID parameter in the signup request.
	 *
	 * @param string $device_id The device ID to validate.
	 * @param WP_REST_Request $request The request object.
	 * @param string $param The parameter name.
	 *
	 * @return bool|\WP_Error True if valid, WP_Error otherwise.
	 */
	public function validate_device_id( $device_id, $request, $param ) {
		if ( ! $device_id ) {
			return niftysvs_is_verification_enforced() ? new \WP_Error( 'nifty_sms_verification_device_id_required', __( 'Device ID is required.', 'nifty-sms-verification-system' ), array( 'status' => 400 ) ) : true;
		}

		$pending = Pending_Verification::first( array( 'device_id' => $device_id ) );

		if ( ! $pending ) {
			return new \WP_Error( 'nifty_sms_verification_device_id_not_found', __( 'Invalid device id supplied.', 'nifty-sms-verification-system' ), array( 'status' => 400 ) );
		}

		// we do not care if it is verified or not.
		return true;
	}

	/**
	 * Handles user signup completion by linking phone number details.
	 *
	 * Links the pending phone verification data to the newly created user account
	 * and removes the pending verification entry.
	 *
	 * @param int $user_id The ID of the newly created user.
	 * @param mixed $key The activation key.
	 * @param array $user The user signup data.
	 *
	 * @return void
	 */
	public function handle_signup( $user_id, $key, $user ) {
		if ( empty( $user ) ) {
			return;
		}

		if ( ! is_array( $user ) || empty( $user['meta'] ) || ! is_array( $user['meta'] ) ) {
			return;
		}

		$device_id = isset( $user['meta']['niftysvs_device_id'] ) ? $user['meta']['niftysvs_device_id'] : '';
		if ( ! $device_id ) {
			return;
		}

		// check the pending table.
		$pending = Pending_Verification::first( array( 'device_id' => $device_id ) );
		if ( ! $pending ) {
			return;
		}

		// the new user has not verified it and the number is already verified by someone else.
		if ( ! $pending->is_verified && niftysvs_is_phone_number_verified( $pending->phone_number ) ) {
			$pending->delete(); // delete pending entry.
			return;
		}

		niftysvs_transition_user_verification_request( $user_id, $pending );
	}
}
