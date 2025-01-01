<?php
/**
 * DB model for Pending Verifications
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
 * Pending Verification model
 *
 * @property int    $id Row id.
 * @property string $phone_number Phone number.
 * @property string $device_id Device id.
 * @property int    $request_count Total request count in last 5 mins.
 * @property int    $is_verified Is verified
 * @property string $requested_at Requested at.
 */
#[\AllowDynamicProperties]
class Pending_Verification extends Model {

	/**
	 * Field name for Datetime field.
	 *
	 * @var string
	 */
	const FIELD_CREATED_AT = 'requested_at';

	/**
	 * Retrieves the table name.
	 *
	 * @return string
	 */
	public static function table() {
		return Schema::table( 'pending' );
	}

	/**
	 * Retrieves table schema.
	 *
	 * @return array
	 */
	public static function schema() {
		return array(
			'id'            => 'integer',
			'phone_number'  => 'string',
			'device_id'     => 'string',
			'request_count' => 'integer',
			'is_verified'   => 'integer',
			'requested_at'  => 'datetime',
		);
	}
}
