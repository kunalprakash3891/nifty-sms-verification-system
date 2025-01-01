<?php
/**
 * DB model for Numbers which have been delayed/blocked for sms
 *
 * @package    Nifty_SMS_Verification_System
 * @subpackage Models
 * @copyright  Copyright (c) 2024, Brajesh Singh
 * @license    https://www.gnu.org/licenses/gpl.html GNU Public License
 * @author     Brajesh Singh
 * @since      1.0.0
 */

namespace Nifty_SMS_Verification_System\Models;

use Nifty_SMS_Verification_System\Schema\Schema;

// Do not allow direct access over web.
defined( 'ABSPATH' ) || exit;
/**
 * Delayed SMS Number model
 *
 * @property int    $id Row id.
 * @property string $phone_number Phone number.
 * @property string $created_at Delay started at.
 */
#[\AllowDynamicProperties]
class SMS_Delayed_Number extends Model {

	/**
	 * Field name for Datetime field.
	 *
	 * @var string
	 */
	const FIELD_CREATED_AT = 'created_at';

	/**
	 * Retrieves the table name.
	 *
	 * @return string
	 */
	public static function table() {
		return Schema::table( 'sms_delayed_numbers' );
	}

	/**
	 * Retrieves table schema.
	 *
	 * @return array
	 */
	public static function schema() {
		return array(
			'id'           => 'integer',
			'phone_number' => 'string',
			'created_at'   => 'datetime',
		);
	}

	/**
	 * Checks if the number is delayed.
	 *
	 * @param string $mobile_number Mobile number.
	 *
	 * @return bool
	 */
	public static function is_delayed( string $mobile_number ): bool {
		$delayed = self::first( array( 'phone_number' => $mobile_number ) );

		if ( ! $delayed ) {
			return false;
		}

		$current_time_stamp      = strtotime( current_time( 'mysql', true ) );
		$delay_request_timestamp = strtotime( $delayed->created_at );
		$rate_limit              = niftysvs_get_option( 'verification_rate_limit', array(
			'duration' => 5, // 5 mins
			'delay'    => 5, // 5 mins
			'attempts' => 5, // 5 requests
		) );

		$delay = absint( $rate_limit['delay'] ) * 60;// convert mins to sec.

		if ( $current_time_stamp > ( $delay_request_timestamp + $delay ) ) {
			$delayed->delete();

			return false;
		}

		return true;
	}
}
