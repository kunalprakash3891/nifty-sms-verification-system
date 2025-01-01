<?php
/**
 * Verification API Availability REST Controller
 *
 * @package    Nifty_SMS_Verification_System
 * @subpackage Handlers
 * @copyright  Copyright (c) 2024, Brajesh Singh
 * @license    https://www.gnu.org/licenses/gpl.html GNU Public License
 * @author     Brajesh Singh
 * @since      1.0.0
 */

namespace Nifty_SMS_Verification_System\Handlers;

use WP_REST_Controller;
use WP_REST_Response;
use WP_REST_Server;

// Do not allow direct access over web.
defined( 'ABSPATH' ) || exit;

/**
 * API Availability Controller class
 */
class API_Availability_Controller extends WP_REST_Controller {

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->namespace = 'nifty-sms-verification-system/v1';
		$this->rest_base = 'available';
	}

	/**
	 * Registers routes
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_availability' ),
					'permission_callback' => '__return_true',
					'schema'              => array( $this, 'get_public_item_schema' ),
				),
			)
		);
	}

	/**
	 * Get availability status
	 *
	 * @return WP_REST_Response
	 */
	public function get_availability() {
		return rest_ensure_response(
			array(
				'available'            => niftsvs_is_verification_enabled(),
				'enforce_verification' => niftysvs_is_verification_enforced(),
			)
		);
	}

	/**
	 * Retrieves item schema
	 *
	 * @return array
	 */
	public function get_item_schema() {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'niftysvs_availability',
			'type'       => 'object',
			'properties' => array(
				'available'            => array(
					'description' => __( 'Whether the API is available.', 'nifty-sms-verification-system' ),
					'type'        => 'boolean',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'enforce_verification' => array(
					'description' => __( 'Whether verification is enforced.', 'nifty-sms-verification-system' ),
					'type'        => 'boolean',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
			),
		);
	}

}
