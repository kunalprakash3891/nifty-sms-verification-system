<?php
/**
 * User Phone numbers model
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
 * User Phone numbers model
 *
 * @property int    $id              Row id.
 * @property int    $user_id         User id.
 * @property string $phone_number Phone number.
 * @property string $device_id    Device id.
 * @property string $status       Verification status(verified|unverified).
 * @property string $created_at   Created at/Verified at.
 */
#[\AllowDynamicProperties]
class User_Phone_Number extends Model {

	/**
	 * Retrieves the table name.
	 *
	 * @return string
	 */
	public static function table() {
		return Schema::table( 'phone_numbers' );
	}

	/**
	 * Retrieves table schema.
	 *
	 * @return array
	 */
	public static function schema() {
		return array(
			'id'           => 'integer',
			'user_id'      => 'integer',
			'phone_number' => 'string',
			'device_id'    => 'string',
			'status'       => 'string',
			'created_at'  => 'datetime',
		);
	}

	/**
	 * Retrieves count for each request status.
	 *
	 * @return array
	 */
	public static function count_by_status(): array {
		global $wpdb;
		$table = static::table();
		$query = "SELECT status, COUNT(*) as count FROM {$table} GROUP BY status ORDER BY count DESC";

		$results = $wpdb->get_results( $query, OBJECT_K );
		if ( false === $results ) {
			return array();
		}

		$counts = array(
			'all'        => 0,
			'verified'   => 0,
			'unverified' => 0,
		);

		foreach ( $results as $key => $row ) {
			$counts[ $key ] = (int) $row->count;
			$counts['all']  += $row->count;
		}

		return $counts;
	}
}
