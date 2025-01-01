<?php
/**
 * User deletion listener
 *
 * Cleans up the user data from our tables on account deletion.
 */

namespace Nifty_SMS_Verification_System\Handlers;

use Nifty_SMS_Verification_System\Models\Blacklist;
use Nifty_SMS_Verification_System\Models\Pending_Verification;
use Nifty_SMS_Verification_System\Models\User_Phone_Number;

// exit if file is called directly.
defined( 'ABSPATH' ) || exit;

class User_Deletion_Listener {

	/**
	 * Sets up user deletion listener.
	 */
	public function setup() {
		add_action( 'delete_user', array( $this, 'handle_user_deletion' ) );
	}

	/**
	 * Cleans up entries on user deletion.
	 *
	 * @param int $user_id User id.
	 */
	public function handle_user_deletion( $user_id ) {
		// clear cache.
		// Drop User phone numbers.
		User_Phone_Number::destroy( array( 'user_id' => $user_id ) );
		// Drop from pending
		Pending_Verification::destroy( array( 'user_id' => $user_id ) );

		// Drop from blacklist
		Blacklist::destroy( array( 'moderator_id' => $user_id ) );
	}
}
