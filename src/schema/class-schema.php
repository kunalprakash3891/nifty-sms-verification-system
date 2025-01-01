<?php
/**
 * Database Schema helper
 *
 * @package    Nifty_SMS_Verification_System
 * @subpackage Schema
 * @copyright  Copyright (c) 2024, Brajesh Singh
 * @license    https://www.gnu.org/licenses/gpl.html GNU Public License
 * @author     Brajesh Singh
 * @since      1.0.0
 */

namespace Nifty_SMS_Verification_System\Schema;

// Do not allow direct access over web.
defined( 'ABSPATH' ) || exit;

/**
 * Schema Manager.
 *
 * For actual models, Please see models directory.
 */
class Schema {

	/**
	 * Retrieves full table name from identifier.
	 *
	 * @param string $name Table identifier.
	 *
	 * @return null|string Full table name or null.
	 */
	public static function table( string $name ) {
		$tables = array(
			'phone_numbers'       => 'niftysvs_user_phone_numbers',
			'pending'             => 'niftysvs_pending_verifications',
			'blacklist'           => 'niftysvs_blacklist',
			'sms_log'             => 'niftysvs_sms_log',
			'sms_delayed_numbers' => 'niftysvs_sms_delayed_numbers',
		);

		global $wpdb;

		return isset( $tables[ $name ] ) ? $wpdb->prefix . $tables[ $name ] : null;
	}

	/**
	 * Creates Tables.
	 */
	public static function create() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate     = $wpdb->get_charset_collate();
		$table_phone_numbers = self::table( 'phone_numbers' );
		$sql                 = array();

		if ( ! self::exists( $table_phone_numbers ) ) {
			$sql[] = "CREATE TABLE `{$table_phone_numbers}` (
				  `id` bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				  `user_id` bigint NOT NULL,
				  `phone_number` varchar(20) NOT NULL,
				  `device_id` varchar(64) NOT NULL,
				  `status` varchar(20) NOT NULL,
				  `created_at` datetime NOT NULL,
				  CONSTRAINT `user_phone_number` UNIQUE (`user_id`,`phone_number`)
				){$charset_collate};";
		}

		$pending_table = self::table( 'pending' );

		if ( ! self::exists( $pending_table ) ) {
			$sql[] = "CREATE TABLE `{$pending_table}` (
			  `id` bigint NOT NULL AUTO_INCREMENT PRIMARY KEY,
			  `device_id` varchar(64) NOT NULL,
			  `phone_number` varchar(20) NOT NULL,
			  `request_count` int NOT NULL,
			  `is_verified` tinyint(1) NOT NULL DEFAULT '0',
			  `requested_at` datetime NOT NULL,
			  UNIQUE KEY `device_id` (`device_id`)
			){$charset_collate};";
		}

		$table_blacklist = self::table( 'blacklist' );

		if ( ! self::exists( $table_blacklist ) ) {

			$sql[] = "CREATE TABLE `{$table_blacklist}`(
  				`id` bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  				`moderator_id` bigint(20) NOT NULL,
  				`phone_number` varchar(20) NOT NULL,
  				`notes` varchar(255) NOT NULL DEFAULT '',
  				`created_at` datetime NOT NULL,
  				UNIQUE KEY `phone_number` (`phone_number`)
			){$charset_collate};";
		}

		$sms_log_table = self::table( 'sms_log' );
		if ( ! self::exists( $sms_log_table ) ) {
			$sql[] = "CREATE TABLE `{$sms_log_table}` (
			  `id` bigint NOT NULL AUTO_INCREMENT PRIMARY KEY,
			  `phone_number` varchar(20) NOT NULL,
			  `requested_at` datetime NOT NULL,
			  KEY `phone_number` (`phone_number`)
			){$charset_collate};";
		}

		$sms_delayed_numbers = self::table( 'sms_delayed_numbers' );
		if ( ! self::exists( $sms_delayed_numbers ) ) {
			$sql[] = "CREATE TABLE `{$sms_delayed_numbers}` (
			  `id` bigint NOT NULL AUTO_INCREMENT PRIMARY KEY,
			  `phone_number` varchar(20) NOT NULL,
			  `created_at` datetime NOT NULL,
			  KEY `phone_number` (`phone_number`)
			){$charset_collate};";
		}

		if ( ! $sql ) {
			return;
		}

		dbDelta( $sql );
	}

	/**
	 * Checks if table exists.
	 *
	 * @param string $table_name table name.
	 *
	 * @return bool
	 */
	public static function exists( string $table_name ): bool {
		global $wpdb;

		return $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) === $table_name;
	}
}
