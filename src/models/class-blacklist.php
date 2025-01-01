<?php
/**
 * DB model for User phone number blacklist
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
 * Blacklist
 *
 * @property int    $id Row id.
 * @property int    $moderator_id Moderator id.
 * @property string $phone_number Phone number.
 * @property string $notes Admin notes.
 * @property string $created_at Added tp the list at.
 */
#[\AllowDynamicProperties]
class Blacklist extends Model {

	/**
	 * Retrieves the blacklist table name.
	 *
	 * @return string
	 */
	public static function table() {
		return Schema::table( 'blacklist' );
	}

	/**
	 * Retrieves table schema.
	 *
	 * @return array
	 */
	public static function schema() {
		return array(
			'id'           => 'integer',
			'moderator_id' => 'integer',
			'phone_number' => 'string',
			'notes'        => 'string',
			'created_at'   => 'datetime',
		);
	}
}
