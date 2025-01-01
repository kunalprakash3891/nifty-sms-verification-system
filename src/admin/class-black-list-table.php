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

use Nifty_SMS_Verification_System\Models\Blacklist;
use WP_List_Table;

// Prevent direct access.
defined( 'ABSPATH' ) || exit;

/**
 * Class Black_List_Table
 */
class Black_List_Table extends WP_List_Table {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'blacklist',
				'plural'   => 'blacklists',
				'ajax'     => false,
				'screen'   => 'users_page_niftysvs-blacklist',
			)
		);
	}

	/**
	 * Checks user permissions
	 *
	 * @return bool
	 */
	public function ajax_user_can() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Prepare items
	 */
	public function prepare_items() {
		$per_page = $this->get_items_per_page( 'blacklist_per_page', 20 );;
		$current_page = $this->get_pagenum();
		// Handle sorting
		$orderby = isset( $_REQUEST['orderby'] ) ? sanitize_text_field( $_REQUEST['orderby'] ) : 'created_at';
		$order   = isset( $_REQUEST['order'] ) ? sanitize_text_field( $_REQUEST['order'] ) : 'DESC';

		$args = array(
			'orderby'  => $orderby,
			'order'    => $order,
			'page'     => $current_page,
			'per_page' => $per_page,
		);

		$search = isset( $_REQUEST['s'] ) ? trim( wp_unslash( $_REQUEST['s'] ) ) : '';

		if ( $search ) {
			$args['phone_number'] = array(
				'op'    => 'LIKE',
				'value' => $search,
			);
		}

		// Get items and ensure it's an array
		$items       = Blacklist::get( $args );
		$this->items = is_array( $items ) ? $items : array();

		// Get total items count for pagination
		$total_items = (int) Blacklist::count( $args );

		// Set pagination arguments
		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total_items / $per_page ),
			)
		);
	}

	/**
	 * Message to be displayed when there are no items
	 */
	public function no_items() {
		_e( 'No blacklisted numbers found.', 'nifty-sms-verification-system' );
	}

	/**
	 * Get columns
	 *
	 * @return array
	 */
	public function get_columns() {
		return array(
			'cb'           => '<input type="checkbox" />',
			'phone_number' => __( 'Phone Number', 'nifty-sms-verification-system' ),
			'moderator_id' => __( 'Added By', 'nifty-sms-verification-system' ),
			'created_at'   => __( 'Date', 'nifty-sms-verification-system' ),
			'notes'        => __( 'Notes', 'nifty-sms-verification-system' ),
		);
	}

	/**
	 * Get sortable columns
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		return array(
			'phone_number' => array( 'phone_number', true ),
			'created_at'   => array( 'created_at', true ),
		);
	}

	/**
	 * Column default
	 *
	 * @param object $item
	 * @param string $column_name
	 *
	 * @return string
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'moderator_id':
				$user = get_userdata( $item->moderator_id );
				return $user ? esc_html( $user->display_name ) : '—';
			case 'created_at':
				$datetime = \DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $item->created_at, new \DateTimeZone( 'UTC' ) );

				return wp_date( niftysvs_get_dateformat_string(), $datetime->getTimestamp(), wp_timezone() );
			default:
				return isset( $item->$column_name ) ? esc_html( $item->$column_name ) : '';
		}
	}

	/**
	 * Column cb
	 *
	 * @param object $item
	 *
	 * @return string
	 */
	public function column_cb( $item ) {
		return sprintf(
			'<input type="checkbox" name="bulk-delete[]" value="%s" />',
			$item->id
		);
	}

	/**
	 * Column phone number
	 *
	 * @param object $item
	 *
	 * @return string
	 */
	public function column_phone_number( $item ) {
		$actions = array(
			'edit' => sprintf(
				'<a href="#" class="editinline" data-id="%s">%s</a>',
				$item->id,
				__( 'Quick Edit', 'nifty-sms-verification-system' )
			),
			'delete' => sprintf(
				'<a href="%s" class="delete">%s</a>',
				wp_nonce_url(
					add_query_arg(
						array(
							'action' => 'niftysvs-delete-blacklisted',
							'id'     => $item->id,
						)
					),
					'niftysvs_delete_blacklist_' . $item->id
				),
				__( 'Delete', 'nifty-sms-verification-system' )
			),
		);

		return sprintf(
			'<span class="phone-number-text">%1$s</span> %2$s <div id="inline-%3$s" class="hidden"><div class="phone_number">%1$s</div><div class="notes">%4$s</div></div>',
			esc_html( $item->phone_number ),
			$this->row_actions( $actions ),
			$item->id,
			esc_html( $item->notes )
		);
	}

	/**
	 * Get bulk actions
	 *
	 * @return array
	 */
	public function get_bulk_actions() {
		return array(
			'delete' => __( 'Delete', 'nifty-sms-verification-system' ),
		);
	}

	/**
	 * Retrieves views
	 *
	 * @return array
	 */
	protected function get_views() {

		$count = Blacklist::count();

		$views = array(
			'all' => sprintf(
				'<a href="%s" class="current">%s</a>',
				admin_url( 'admin.php?page=niftysvs-blacklist' ),
				__( 'All', 'nifty-sms-verification-system' ) . sprintf( '<span class="count"> (%s)</span>', $count )
			),
		);

		return $views;
	}

}
