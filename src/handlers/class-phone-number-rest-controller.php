<?php
/**
 * Phone number REST controller
 *
 * @package    Nifty_SMS_Verification_System
 * @subpackage Handlers
 * @copyright  Copyright (c) 2024, Brajesh Singh
 * @license    https://www.gnu.org/licenses/gpl.html GNU Public License
 * @author     Brajesh Singh
 * @since      1.0.0
 */

namespace Nifty_SMS_Verification_System\Handlers;

use Nifty_SMS_Verification_System\Models\User_Phone_Number;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Server;
use WP_REST_Response;
use WP_Error;

/**
 * Phone number rest controller
 */
class Phone_Number_REST_Controller extends WP_REST_Controller {

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->namespace = 'nifty-sms-verification-system/v1';
		$this->rest_base = 'phonenumber';
	}

	/**
	 * Registers routes
	 */
	public function register_routes() {

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<user_id>[\d]+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'delete_item_permissions_check' ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Checks if a given request has access to get phone number for the user.
	 *
	 * @param WP_REST_Request $request Request object.
	 */
	public function get_item_permissions_check( $request ) {
		if ( ! current_user_can( 'edit_user', $request['user_id'] ) ) {
			return new WP_Error(
				'niftysvs_rest_access_permission_denied',
				__( 'Sorry, you are not allowed to access the phone number.', 'nifty-sms-verification-system' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Retrieves the phone number for the user.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_item( $request ) {
		$user_id = (int) $request['user_id'];
		$entry   = User_Phone_Number::first( array( 'user_id' => $user_id ) );

		if ( empty( $entry ) ) {
			return new WP_Error( 'niftysvs_rest_no_phone', __( 'No phone number found.', 'nifty-sms-verification-system' ), array( 'status' => 404 ) );
		}

		$response = $this->prepare_item_for_response( $entry, $request );
		$response->set_status( 200 );

		return $response;
	}

	/**
	 * Checks if the phone number can be deleted.
	 *
	 * @param WP_REST_Request $request Request object.
	 */
	public function delete_item_permissions_check( $request ) {
		if ( ! current_user_can( 'edit_user', $request['user_id'] ) ) {
			return new WP_Error(
				'niftysvs_rest_delete_permission_denied',
				__( 'Sorry, you are not allowed to delete.', 'nifty-sms-verification-system' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Deletes the phone number for the user.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_item( $request ) {
		$user_id = (int) $request['user_id'];
		$entry   = User_Phone_Number::first( array( 'user_id'=> $user_id ) );

		if ( empty( $entry ) ) {
			return new WP_Error( 'niftysvs_rest_no_phone', __( 'No phone number found.', 'nifty-sms-verification-system' ), array( 'status' => 404 ) );
		}

		niftysvs_delete_cached_phone_details( $user_id );

		$deleted = $entry->delete();

		$previous = $this->prepare_item_for_response( $entry, $request );

		return new WP_REST_Response(
			array(
				'deleted'  => (bool) $deleted,
				'previous' => $previous->get_data(),
			),
			200
		);
	}

	/**
	 * Prepares the phone number record for response.
	 *
	 * @param User_Phone_Number $item Phone number row.
	 * @param WP_REST_Request   $request Request object.
	 *
	 * @return WP_REST_Response Response object.
	 */
	public function prepare_item_for_response( $item, $request ) {
		$fields = $this->get_fields_for_response( $request );
		$data   = array();

		if ( rest_is_field_included( 'phone_number', $fields ) ) {
			$data['phone_number'] = $item->phone_number;
		}
		if ( rest_is_field_included( 'device_id', $fields ) ) {
			$data['device_id'] = $item->device_id;
		}
		if ( rest_is_field_included( 'user_id', $fields ) ) {
			$data['user_id'] = (int) $item->user_id;
		}
		if ( rest_is_field_included( 'status', $fields ) ) {
			$data['status'] = $item->status;
		}

		if ( rest_is_field_included( 'created_at', $fields ) ) {
			$data['created_at'] = mysql_to_rfc3339( $item->created_at );
		}

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';

		$data = $this->add_additional_fields_to_object( $data, $request );
		$data = $this->filter_response_by_context( $data, $context );

		return rest_ensure_response( $data );
	}

	/**
	 * Retrieves the schema for the phone number REST endpoint.
	 *
	 * @return array Schema data.
	 */
	public function get_item_schema() {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'niftysvs_phone_details',
			'type'       => 'object',
			'properties' => array(
				'phone_number' => array(
					'description' => __( 'The phone number.', 'nifty-sms-verification-system' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
				),
				'device_id'    => array(
					'description' => __( 'Device identifier.', 'nifty-sms-verification-system' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
				),
				'user_id'      => array(
					'description' => __( 'The user ID.', 'nifty-sms-verification-system' ),
					'type'        => 'integer',
					'context'     => array( 'view' ),
				),
				'status'       => array(
					'description' => __( 'Phone number verification status.', 'nifty-sms-verification-system' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
				),
				'created_at'   => array(
					'description' => __( 'Last verification timestamp.', 'nifty-sms-verification-system' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'view' ),
				),
			),
		);
	}
}
