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
 * Admin Menu Helper
 */
class Blacklist_Admin_Helper {

	private $table;

	/**
	 * Registers Admin Menu
	 */
	public function setup() {
		add_action( 'admin_menu', array( $this, 'register_menu' ), 10 );
		add_action( 'admin_notices', array( $this, 'add_admin_notice' ) );
		// add_action( 'load-toplevel_page_niftysvs-blacklist', array( $this, 'process_single_view_request_form' ) );
		// for add new blacklist number.
		add_action( 'current_screen', array( $this, 'init_table' ) );
		add_action( 'admin_post_niftysvs_add_blacklist_number', array( $this, 'handle_add_blacklist' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'admin_init', array( $this, 'handle_delete_blacklist' ) );
		add_action( 'admin_init', array( $this, 'handle_bulk_action' ) );
        // for pagination value.
		add_filter( 'set_screen_option_blacklist_per_page', function ( $screen_option, $option, $value ) {
			return absint( $value );
		}, 10, 3 );

	}

	/**
	 * Registers menu
	 */
	public function register_menu() {
		add_users_page(
			_x( 'Phone Blacklist', 'admin-page-title', 'nifty-sms-verification-system' ),
			_x( 'Blacklist', 'admin-page-title', 'nifty-sms-verification-system' ),
			'manage_options',
			'niftysvs-blacklist',
			array( $this, 'render_requests' ),
			200
		);
	}

	/**
	 * Adds notice on requests list page.
	 */
	public function add_admin_notice() {
		if ( ! isset( $_GET['page'] ) || 'niftysvs-blacklist' !== $_GET['page'] ) {
			return;
		}

		if ( ! empty( $_GET['deleted'] ) || ! empty( $_GET['bulk_delete'] ) ) {
			wp_admin_notice(
				__( 'Deleted from blacklist successfully.', 'nifty-sms-verification-system' ),
				array(
					'type'        => 'success',
					'dismissible' => true,
				)
			);
		} elseif ( ! empty( $_GET['added'] ) ) {
			wp_admin_notice(
				__( 'Added to the blacklist successfully.', 'nifty-sms-verification-system' ),
				array(
					'type'        => 'success',
					'dismissible' => true,
				)
			);
		}
	}

	/**
	 * Initilize table
	 **/
	public function init_table() {
		if ( ! isset( $_GET['page'] ) || 'niftysvs-blacklist' !== $_GET['page'] ) {
			return;
		}

		$this->table = new Black_List_Table();

        add_screen_option( 'per_page', array(
			'default' => 20,
			'option'  => 'blacklist_per_page',
		) );
	}

	/**
	 * Enqueue scripts for admin page.
	 *
	 * @param string $hook Hook suffix for the current admin page.
	 */
	public function enqueue_scripts( $hook ) {
		if ( 'users_page_niftysvs-blacklist' !== $hook ) {
			return;
		}
		wp_enqueue_style( 'niftysvs-admin', plugin_dir_url( __FILE__ ) . 'css/blacklist.css', array(), '1.0.0' );
		wp_enqueue_script( 'niftysvs-admin', plugin_dir_url( __FILE__ ) . 'js/admin.js', array( 'jquery' ), '1.0.0', true );
		wp_enqueue_script( 'niftysvs-blacklist-quick-edit', plugin_dir_url( __FILE__ ) . 'js/quick-edit.js', array( 'jquery', 'wp-util' ), '1.0.0', true );
		wp_localize_script( 'niftysvs-blacklist-quick-edit', 'NiftySVSBlacklistQuickEdit', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'niftysvs_blacklist_quick_edit_nonce' ),
		) );
	}

	/**
	 * Renders page content.
	 */
	public function render_requests() {

		$table = $this->table;
		$table->prepare_items();
		?>
        <div class="wrap">
            <h2 class="wp-heading-inline">
				<?php _e( 'Blacklisted Numbers', 'nifty-sms-verification-system' ); ?>
                <a href="#" class="page-title-action" id="add-blacklist-toggle"><?php _e( 'Add New', 'nifty-sms-verification-system' ); ?></a>
            </h2>


            <div id="add-blacklist-form"
                 style="display: none; margin: 20px 0; padding: 20px; background: #fff; border: 1px solid #ccd0d4;">
                <h3><?php _e( 'Add Number to Blacklist', 'nifty-sms-verification-system' ); ?></h3>
                <form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">
                    <input type="hidden" name="action" value="niftysvs_add_blacklist_number">
					<?php wp_nonce_field( 'niftysvs_add_blacklist_number', 'blacklist_nonce' ); ?>

                    <table class="form-table">
                        <tr>
                            <th>
                                <label for="phone_number"><?php _e( 'Phone Number', 'nifty-sms-verification-system' ); ?></label>
                            </th>
                            <td><input type="text" name="phone_number" id="phone_number" class="regular-text" required>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="reason"><?php _e( 'Reason', 'nifty-sms-verification-system' ); ?></label>
                            </th>
                            <td><textarea name="reason" id="reason" rows="3" class="regular-text"></textarea></td>
                        </tr>
                    </table>

                    <p class="submit">
                        <input type="submit" class="button button-primary" value="<?php _e( 'Add to Blacklist', 'nifty-sms-verification-system' ); ?>">
                    </p>
                </form>
            </div>

			<?php $table->views(); ?>
            <form method="post" action="">
				<?php
				$table->search_box( __( 'Search phone', 'nifty-sms-verification-system' ), 'blacklisted_number' );
				$table->display();
				?>
            </form>
        </div>

        <script type="text/template" id="tmpl-quick-edit-template">
            <tr id="quick-edit-row"><td colspan="5"><div class="quick-edit-form">
                <label><?php _e( 'Phone Number', 'nifty-sms-verification-system' ); ?>: <input type="text" id="quick-edit-phone-number"></label>
                <label><?php _e( 'Notes', 'nifty-sms-verification-system' ); ?>: <textarea id="quick-edit-notes"></textarea></label>
                <div class="action-btns">
                    <button class="button button-primary" id="quick-edit-save"><?php _e( 'Save', 'nifty-sms-verification-system' ); ?></button>
                    <button class="button" id="quick-edit-cancel"><?php _e( 'Cancel', 'nifty-sms-verification-system' ); ?></button>
                </div>
            </div></td></tr>
        </script>
		<?php
	}

	/**
	 * Handle form submission for adding a new blacklist number.
	 */
	public function handle_add_blacklist() {

		check_admin_referer( 'niftysvs_add_blacklist_number', 'blacklist_nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have permission to perform this action.', 'nifty-sms-verification-system' ) );
		}

		$phone_number = isset( $_POST['phone_number'] ) ? sanitize_text_field( wp_unslash( $_POST['phone_number'] ) ) : '';
		$reason       = isset( $_POST['reason'] ) ? sanitize_textarea_field( wp_unslash( $_POST['reason'] ) ) : '';

		if ( empty( $phone_number ) ) {
			wp_redirect( add_query_arg( 'error', 'empty_phone', wp_get_referer() ) );
			exit;
		}

		$blacklist               = new Blacklist();
		$blacklist->phone_number = $phone_number;
		$blacklist->notes        = $reason;
		$blacklist->moderator_id = get_current_user_id();
		$blacklist->save();
		if ( false === $blacklist->save() ) {
			wp_redirect( add_query_arg( 'error', 'failed', wp_get_referer() ) );
			exit;
		}

		wp_redirect( add_query_arg( 'added', '1', wp_get_referer() ) );
		exit;
	}

	/**
	 * Handle deletion of blacklist entries
	 */
	public function handle_delete_blacklist() {
		if ( ! isset( $_GET['action'] ) || 'niftysvs-delete-blacklisted' !== $_GET['action'] || ! isset( $_GET['id'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have permission to perform this action.', 'nifty-sms-verification-system' ) );
		}

		$id = (int) $_GET['id'];

		check_admin_referer( 'niftysvs_delete_blacklist_' . $id );

		$blacklist = Blacklist::find( $id );
		if ( $blacklist && $blacklist->delete() ) {
			wp_redirect( add_query_arg( 'deleted', '1', remove_query_arg( array(
				'action',
				'id',
				'_wpnonce'
			), wp_get_referer() ) ) );
			exit;
		}

		wp_redirect( add_query_arg( 'delete-error', '1', wp_get_referer() ) );
		exit;
	}

	public function handle_bulk_action() {

		if ( ! isset( $_GET['page'] ) || 'niftysvs-blacklist' !== $_GET['page'] ) {
			return;
		}

		if ( ! isset( $_POST['action'] ) || 'delete' !== $_POST['action'] ) {
			return;
		}

		if ( ! isset( $_POST['bulk-delete'] ) || ! is_array( $_POST['bulk-delete'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have permission to perform this action.', 'nifty-sms-verification-system' ) );
		}

		$ids = array_map( 'intval', wp_unslash( $_POST['bulk-delete'] ) );

		foreach ( $ids as $id ) {
			$blacklist = Blacklist::find( $id );
			if ( $blacklist ) {
				$blacklist->delete();
			}
		}

		wp_redirect( add_query_arg( 'deleted', '1', remove_query_arg( array(
			'action',
			'id',
			'_wpnonce'
		), wp_get_referer() ) ) );
		exit;
	}
}
