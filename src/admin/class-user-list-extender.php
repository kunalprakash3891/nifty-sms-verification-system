<?php
/**
 * Blacklist table list
 *
 * @package    Nifty_SMS_Verification_System
 * @subpackage Admin
 * @copyright  Copyright (c) 2024, Brajesh Singh
 * @license    https://www.gnu.org/licenses/gpl.html GNU Public License
 * @author     Brajesh Singh
 * @since      1.0.0
 */

namespace Nifty_SMS_Verification_System\Admin;

// Do not allow direct access over web.
use Nifty_SMS_Verification_System\Models\User_Phone_Number;

defined( 'ABSPATH' ) || exit;

/**
 * Admin users list extender.
 */
class User_List_Extender {

	/**
	 * Sets up hooks.
	 */
	public function setup() {
		add_filter( 'manage_users_columns', array( $this, 'add_user_columns' ) );
		add_action( 'manage_users_custom_column', array( $this, 'show_user_column_content' ), 10, 3 );
        //sortable columns
		add_filter('manage_users_sortable_columns', array($this, 'make_verification_status_sortable' ));
		add_filter( 'users_list_table_query_args', array( $this, 'filter_user_query_args' ) );

		add_filter('users_pre_query', array($this, 'filter_user_search'), 10, 2);
	}
    /**
     * Filter user search to include phone numbers
     *
     * @param mixed $null The return value.
     * @param \WP_User_Query $query The WP_User_Query instance
     */
	public function filter_user_search( $null, $query ) {
		global $wpdb;

		if ( ! is_admin() || ! $query->get( 'search' ) ) {
			return $null;
		}

		$table = User_Phone_Number::table();

		$search = trim( $query->get( 'search' ), '*' );
		// Infuture, might want to check for the intention(leading or trailing or both) and adjust the query accordingly.
		$where = $query->query_where;
		$where .= $wpdb->prepare(
			" OR ID IN (SELECT user_id FROM {$table} WHERE phone_number LIKE %s)",
			'%' . $wpdb->esc_like( $search ) . '%'
		);

		$query->query_where = $where;

		return $null;
	}

    /**
     * Make phone number column sortable
     *
     * @param array $sortable_columns Array of sortable columns
     *
     * @return array Modified array of sortable columns
     */
    public function make_verification_status_sortable($sortable_columns) {
        $sortable_columns['niftysvs_verification_status'] = 'smsv_status';
        return $sortable_columns;
    }

	/**
	 * Filters user query args on wp-admin/users.php
	 *
	 * @param array $args args.
	 *
	 * @return array
	 */
	public function filter_user_query_args( $args ) {

		if ( isset( $_GET['orderby'] ) && 'smsv_status' === $_GET['orderby'] ) {
			$args['orderby']  = 'meta_value';
			$args['meta_key'] = '_nifsms_verification_status';
		}

		return $args;
	}
	/**
	 * Add new columns to the users list
	 *
	 * @param array $columns Existing columns
	 *
	 * @return array Modified columns
	 */
	public function add_user_columns( $columns ) {
		$columns['niftysvs_phone_number']        = __( 'Phone Number', 'nifty-sms-verification-system' );
		$columns['niftysvs_verification_status'] = __( 'Mobile Status', 'nifty-sms-verification-system' );

		return $columns;
	}

	/**
	 * Display content for custom columns
	 *
	 * @param string $output Custom column output
	 * @param string $column_name Column name
	 * @param int $user_id User ID
	 *
	 * @return string Column content
	 */
	public function show_user_column_content( $output, $column_name, $user_id ) {
		switch ( $column_name ) {
			case 'niftysvs_phone_number':
				$phone = niftysvs_get_user_phone_number( $user_id );

				return $phone ? esc_html( $phone ) : '-';

			case 'niftysvs_verification_status':
				$status       =niftysvs_get_phone_verified_status( $user_id );
				$status_text  = 'verified' === $status ? __( 'Verified', 'nifty-sms-verification-system' ) : __( 'Unverified', 'nifty-sms-verification-system' );
				$status_class = $status ? 'verified' : 'not-verified';

				return sprintf( '<span class="verification-status %s">%s</span>', esc_attr( $status_class ), esc_html( $status_text ) );
		}

		return $output;
	}
}
