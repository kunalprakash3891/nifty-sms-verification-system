<?php
/**
 * Pending verification cleaner
 *
 * @package    BP_New_Signup_Endpoints
 * @copyright  Copyright (c) 2024, Brajesh Singh
 * @license    https://www.gnu.org/licenses/gpl.html GNU Public License
 * @author     Brajesh Singh, Ravi Sharma
 * @since      1.0.0
 */

namespace Nifty_SMS_Verification_System\Handlers;

use Nifty_SMS_Verification_System\Models\Pending_Verification;
use Nifty_SMS_Verification_System\Models\SMS_Log;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Signup endpoint helper.
 */
class Verification_Cleaner {

	/**
	 * Sets up the hook.
	 */
	public function setup() {
		add_action( 'niftysvs_verification_cleanup', array( $this, 'cleanup' ) );
	}

	/**
	 * Cleans up abandoned verification requests and old sms log.
	 */
	public function cleanup() {
		$table = Pending_Verification::table();
		global $wpdb;
		$current_time_gmt = current_time( 'mysql', true );
		// all pending verification older than 6 hour.
		$wpdb->query( $wpdb->prepare( "DELETE FROM $table WHERE requested_at < DATE_SUB( %s - INTERVAL 1 DAY", $current_time_gmt  ) );

		// sms log table.
		$log_table = SMS_Log::table();
		$wpdb->query( $wpdb->prepare( "DELETE FROM $log_table WHERE requested_at < DATE_SUB( %s - INTERVAL 1 DAY", $current_time_gmt  ) );
	}
}