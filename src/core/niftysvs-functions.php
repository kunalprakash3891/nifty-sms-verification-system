<?php
/**
 * Verification System Functions
 *
 * @package    Nifty_SMS_Verification_System
 * @subpackage Core
 * @copyright  Copyright (c) 2024, Brajesh Singh
 * @license    https://www.gnu.org/licenses/gpl.html GNU Public License
 * @author     Brajesh Singh
 * @since      1.0.0
 */

use Nifty_SMS_Verification_System\Models\Blacklist;
use Nifty_SMS_Verification_System\Models\Pending_Verification;
use Nifty_SMS_Verification_System\Models\User_Phone_Number;

// Do not allow direct access over web.
defined( 'ABSPATH' ) || exit;

/**
 * Retrieves an option
 *
 * @param string $option_name Option name.
 * @param mixed  $default     Default value.
 *
 * @return mixed
 */
function niftysvs_get_option( string $option_name, $default = null ) {
	$options = niftysvs_get_options();

	if ( isset( $options[ $option_name ] ) ) {
		return $options[ $option_name ];
	}

	return $default;
}

/**
 * Retrieves all options
 *
 * @return array
 */
function niftysvs_get_options(): array {
	return (array) get_option( 'niftysvs_settings', array() );
}

/**
 * Checks if api credentials were defined?
 */
function niftysvs_is_credential_defined(): bool {
	return defined( 'NIFTYSVS_TWILIO_TOKEN' ) && defined( 'NIFTYSVS_TWILIO_TOKEN' );
}

/**
 * Retrieves Twilio SID
 *
 * @return string
 */
function niftysvs_get_twilio_sid(): string {
	return defined( 'NIFTYSVS_TWILIO_SID' ) ? NIFTYSVS_TWILIO_SID : niftysvs_get_option( 'twilio_sid', '' );
}

/**
 * Retrieves Twilio Token
 *
 * @return string
 */
function niftysvs_get_twilio_token(): string {
	return defined( 'NIFTYSVS_TWILIO_TOKEN' ) ? NIFTYSVS_TWILIO_TOKEN : niftysvs_get_option( 'twilio_token', '' );
}

/**
 * Retrieves Twilio Token
 *
 * @return string
 */
function niftysvs_get_twilio_service_sid(): string {
	return defined( 'NIFTYSVS_TWILIO_SERVICE_SID' ) ? NIFTYSVS_TWILIO_SERVICE_SID : niftysvs_get_option( 'twilio_service_sid', '' );
}

/**
 * Checks if the Twilio service is enabled.
 *
 * @return bool
 */
function niftsvs_is_verification_enabled(): bool {
	return (bool) niftysvs_get_option( 'enable_verification', false );
}

/**
 * Checks if the Verification is enforced.
 *
 * @return bool
 */
function niftysvs_is_verification_enforced(): bool {
	return (bool) niftysvs_get_option( 'enforce_verification', false );
}

/**
 * Retrieves user's phone number from user meta
 *
 * @param int $user_id User ID.
 *
 * @return string
 */
function niftysvs_get_user_phone_number( int $user_id ): string {

	if ( ! $user_id ) {
		return '';
	}

	return (string) get_user_meta( $user_id, '_niftysvs_phone_number', true );
}

/**
 * Retrieves phone verification status from user meta
 *
 * @param int $user_id User ID.
 *
 * @return string
 */
function niftysvs_get_phone_verified_status( int $user_id ): string {
	$status = get_user_meta( $user_id, '_niftysvs_phone_verified_status', true );

	return $status ? $status : 'none';
}

/**
 * Caches phone verification status and phone number in user meta
 *
 * @param int               $user_id User ID.
 * @param User_Phone_Number $entry   Phone number entry.
 */
function niftysvs_cache_phone_details( int $user_id, User_Phone_Number $entry ) {
	// delete existing status
	global $wpdb;

	if ( ! $entry->phone_number ) {
		return;
	}

	$old_user_ids = $wpdb->get_col( $wpdb->prepare( "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value = %s", '_niftysvs_phone_number', $entry->phone_number ) );
	// this is to ensure that we don't have multiple entries for the same phone number.
	foreach ( $old_user_ids as $old_user_id ) {
		delete_user_meta( $old_user_id, '_niftysvs_phone_verified_status' );
		delete_user_meta( $old_user_id, '_niftysvs_phone_number' );
	}

	update_user_meta( $user_id, '_niftysvs_phone_verified_status', $entry->status );
	update_user_meta( $user_id, '_niftysvs_phone_number', $entry->phone_number );
}

/**
 * Delete cached phone verification status and phone number from user meta
 *
 * @param int $user_id User ID.
 */
function niftysvs_delete_cached_phone_details( int $user_id ) {
	delete_user_meta( $user_id, '_niftysvs_phone_verified_status' );
	delete_user_meta( $user_id, '_niftysvs_phone_number' );
}

// User phone number functions.

/**
 * Adds a new phone number entry for the user
 *
 * @param int   $user_id      User ID.
 * @param array $data Array containing user_id, phone_number, device_id and status.
 *
 * @return bool True if added successfully, false otherwise.
 */
function niftysvs_update_user_phone_details( int $user_id, array $data ): bool {

	if ( empty( $user_id ) ) {
		return false;
	}

	niftysvs_delete_cached_phone_details( $user_id );
	// empty phone number, delete the entry.
	if ( isset( $data['phone_number'] ) && empty( $data['phone_number'] ) ) {
		return User_Phone_Number::destroy(
			array(
				'user_id' => $user_id,
			)
		);
	}

	// step 1. check if the phone number already exists in the table.
	// drop the entry if it exists.
	if ( ! empty( $data['phone_number'] ) ) {
		$entry = User_Phone_Number::first( array( 'phone_number' => $data['phone_number'] ) );
	} else {
		$entry = User_Phone_Number::first( array( 'user_id' => $user_id ) );
	}

	// drop record if it exists.
	if ( ! $entry ) {

		// drop for user too.
		User_Phone_Number::destroy(
			array(
				'user_id' => $user_id,
			)
		);

		$entry = User_Phone_Number::create(
			array(
				'status'       => 'unverified',
				'phone_number' => '',
				'device_id'    => '',
				'user_id'      => 0,
			)
		);
	} else {
		niftysvs_delete_cached_phone_details( $entry->user_id );
		// if entry user id and the new does not match, delete device id.
		if ( (int) $entry->user_id !== (int) $user_id ) {
			$entry->device_id = '';
		}
	}

	if ( ! empty( $data['phone_number'] ) ) {
		// drop the entry if it exists.
		User_Phone_Number::destroy(
			array(
				'phone_number' => $data['phone_number'],
			)
		);

		$entry->phone_number = $data['phone_number'];

		if ( $entry->user_id ) {
			niftysvs_delete_cached_phone_details( $entry->user_id );
		}
	}

	if ( ! empty( $data['device_id'] ) ) {
		$entry->device_id = $data['device_id'];
	}

	if ( ! empty( $data['status'] ) ) {
		$entry->status = $data['status'];
	}

	$entry->user_id = $user_id;
	// update verification time.
	$entry->created_at = current_time( 'mysql', true );

	$saved = $entry->save();

	if ( false !== $saved ) {
		// cache the phone number and status.
		niftysvs_cache_phone_details( $user_id, $entry );
	}

	// Delete pending verification entry.
	if ( ! empty( $data['device_id'] ) ) {
		Pending_Verification::destroy( array( 'device_id' => $data['device_id'] ) );
	}

	if ( ! empty( $data['phone_number'] ) ) {
		Pending_Verification::destroy( array( 'phone_number' => $data['phone_number'] ) );
	}

	return $saved;
}

/**
 * Transitions a Pending request to user phone number entry.
 *
 * @param int                  $user_id User id.
 * @param Pending_Verification $pending Pending request object.
 */
function niftysvs_transition_user_verification_request( int $user_id, $pending ) {

	if ( ! $pending ) {
		return;
	}

	niftysvs_update_user_phone_details(
		$user_id,
		array(
			'phone_number' => $pending->phone_number,
			'device_id'    => $pending->device_id,
			'status'       => $pending->is_verified ? 'verified' : 'unverified',
		)
	);
}

/**
 * Deletes user phone number entry.
 *
 * @param int $user_id User ID.
 *
 * @return bool True if deleted successfully, false otherwise
 */
function niftysvs_delete_user_phone_number( int $user_id ): bool {
	if ( ! $user_id ) {
		return false;
	}

	$entry = User_Phone_Number::first( array( 'user_id' => $user_id ) );

	if ( ! $entry ) {
		return false;
	}

	niftysvs_delete_cached_phone_details( $user_id );

	return $entry->delete();
}

// blacklist functions.

/**
 * Checks if the phone number is blacklisted
 *
 * @param string $phone_number Phone number.
 *
 * @return bool
 */
function niftysvs_is_phone_number_blacklisted( string $phone_number ): bool {
	$blacklist = Blacklist::first( array( 'phone_number' => $phone_number ) );

	if ( empty( $blacklist ) ) {
		return false;
	}

	return true;
}

/**
 * Blacklists a phone number
 *
 * @param string $phone_number Phone number to blacklist.
 * @param string $reason       Optional reason for blacklisting.
 *
 * @return bool True if blacklisted successfully, false otherwise.
 */
function niftysvs_blacklist_phone_number( string $phone_number, string $reason = '' ): bool {
	if ( empty( $phone_number ) ) {
		return false;
	}

	$blacklist = Blacklist::create(
		array(
			'phone_number' => $phone_number,
			'notes'        => $reason,
			'moderator_id' => get_current_user_id(),
			'created_at'   => current_time( 'mysql', true ),
		)
	);

	return $blacklist->save();
}

/**
 * Removes a phone number from blacklist
 *
 * @param string $phone_number Phone number to remove from blacklist.
 *
 * @return bool True if removed successfully, false otherwise
 */
function niftysvs_remove_phone_number_from_blacklist( string $phone_number ): bool {
	if ( empty( $phone_number ) ) {
		return false;
	}

	$blacklist = Blacklist::first( array( 'phone_number' => $phone_number ) );

	if ( ! $blacklist ) {
		return false;
	}

	return $blacklist->delete();
}

// pending request thing.

/**
 * Adds a new pending verification request
 *
 * @param array $data Array containing device_id and phone_number.
 *
 * @return bool True if added successfully, false otherwise.
 */
function niftysvs_add_pending_verification( array $data ): bool {
	if ( empty( $data['device_id'] ) || empty( $data['phone_number'] ) ) {
		return false;
	}

	// drop the entry if it exists.
	Pending_Verification::destroy( array( 'device_id' => $data['device_id'] ) );

	$pending = Pending_Verification::create(
		array(
			'device_id'    => $data['device_id'],
			'phone_number' => $data['phone_number'],
			'created_at'   => current_time( 'mysql', true ),
		)
	);

	return $pending->save();
}

/**
 * Checks if the phone number is verified.
 *
 * @param string $phone_number Phone number.
 *
 * @return bool
 */
function niftysvs_is_phone_number_verified( string $phone_number ): bool {
	$entry = User_Phone_Number::first(
		array(
			'phone_number' => $phone_number,
			'status'       => 'verified',
		)
	);

	if ( $entry ) {
		return true;
	}

	return false;
}

/**
 * Retrieves date time format string based on wp preference
 *
 * @param string $format date format.
 *
 * @return string
 */
function niftysvs_get_dateformat_string( $format = '' ) {
	if ( ! empty( $format ) ) {
		return $format;
	}

	$date_format = get_option( 'date_format' );
	$time_format = get_option( 'time_format' );

	if ( ! empty( $date_format ) || ! empty( $time_format ) ) {
		return $time_format . ', ' . $date_format;
	}

	return 'g:i:s A, F j, Y';
}
