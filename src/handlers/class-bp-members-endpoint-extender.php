<?php
/**
 * BuddyPress Members endpoint extender
 *
 * @package    Nifty_SMS_Verification_System
 * @subpackage Handlers
 * @copyright  Copyright (c) 2024, Brajesh Singh
 * @license    https://www.gnu.org/licenses/gpl.html GNU Public License
 * @author     Brajesh Singh
 * @since      1.0.0
 */

namespace Nifty_SMS_Verification_System\Handlers;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * It adds phone_status field to members list and single member BuddyPress end points.
 */
class BP_Members_Endpoint_Extender {

	/**
	 * Registers the routes.
	 */
	public function register_fields() {

		bp_rest_register_field(
			'members',
			'phone_status',
			array(
				'get_callback' => function ( $member ) {
					return niftysvs_get_phone_verified_status( $member['id'] );
				},
				'schema'       => array(
					'description' => __( "The user's phone verification status.", 'nifty-sms-verification-system' ),
					'type'        => 'string',
				),
			)
		);
	}
}
