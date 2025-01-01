<?php
/**
 * Admin Menu Helper
 *
 * @package    Nifty_SMS_Verification_System
 * @subpackage Admin
 * @copyright  Copyright (c) 2024, Brajesh Singh
 * @license    https://www.gnu.org/licenses/gpl.html GNU Public License
 * @author     Brajesh Singh, Ravi Sharma
 * @since      1.0.0
 */

namespace Nifty_SMS_Verification_System\Admin;

// Exit if accessed directly.
use Nifty_SMS_Verification_System\Models\Blacklist;

defined( 'ABSPATH' ) || exit;

/**
 * Black list ajax edit helper.
 */
class Black_List_Quick_Edit_Helper {

	/**
	 * Sets up hooks.
	 */
	public function setup() {
		add_action( 'wp_ajax_niftysvs_blacklist_quick_edit', array( $this, 'handle' ) );
	}

	/**
	 * Handle quick edit form submission via AJAX.
	 */
	public function handle() {
		check_ajax_referer( 'niftysvs_blacklist_quick_edit_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'You do not have permission to perform this action.', 'nifty-sms-verification-system' ) );
		}

		$id           = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		$phone_number = isset( $_POST['phone_number'] ) ? sanitize_text_field( wp_unslash( $_POST['phone_number'] ) ) : '';
		$notes        = isset( $_POST['notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['notes'] ) ) : '';

		if ( empty( $id ) || empty( $phone_number ) ) {
			wp_send_json_error( __( 'Invalid data provided.', 'nifty-sms-verification-system' ) );
		}

		$blacklist = Blacklist::find( $id );
		if ( ! $blacklist ) {
			wp_send_json_error( __( 'Blacklist entry not found.', 'nifty-sms-verification-system' ) );
		}
		// check if the current phone number is already blacklisted.
		$existing_blacklist = Blacklist::first( array( 'phone_number'=>  $phone_number ) );
		if ( $existing_blacklist && $existing_blacklist->id !== $blacklist->id ) {
			wp_send_json_error( __( 'Phone number is already blacklisted.', 'nifty-sms-verification-system' ) );
		}

		$blacklist->phone_number = $phone_number;
		$blacklist->notes        = $notes;
		$blacklist->save();

		wp_send_json_success( __( 'Blacklist entry updated successfully.', 'nifty-sms-verification-system' ) );
	}
}
