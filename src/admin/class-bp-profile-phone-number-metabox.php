<?php
/**
 * Profile edit admin metabox
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
use WP_User;

defined( 'ABSPATH' ) || exit;

/**
 * BuddyPress Edit Profile Phone Number Metabox(in admin)
 */
class BP_Profile_Phone_Number_Metabox {

	/**
	 * Message to show after update
	 *
	 * @var string
	 */
	private string $message = '';

	/**
	 * Message type
	 *
	 * @var string
	 */
	private string $message_type = '';

	/**
	 * Sets ups the metabox and handler.
	 */
	public function setup() {
		add_action( 'bp_members_admin_user_metaboxes', array( $this, 'add_metabox' ) );
		add_action( 'bp_members_admin_load', array( $this, 'update' ) );
		add_action( 'all_admin_notices', array( $this, 'show_message' ) );
	}

	/**
	 * Show the admin notice.
	 */
	public function show_message() {

		if ( ! did_action( 'bp_members_admin_load' ) ) {
			return;
		}

		$feedback = get_user_meta( $this->get_user_id(), '_nifty_phone_details_feedback', true );

		if ( ! $feedback ) {
			return;
		}

		$message      = $feedback['message'];
		$message_type = $feedback['message_type'];

		delete_user_meta( $this->get_user_id(), '_nifty_phone_details_feedback' );

		if ( $message ) {
			wp_admin_notice( $message, array(
				'type'        => $message_type,
				'dismissible' => true
			) );
		}
	}

	/**
	 * Adds the phone number metabox
	 */
	public function add_metabox() {
		add_meta_box(
			'niftysvs_phone_number_metabox',
			__( 'Mobile Phone Number', 'nifty-sms-verification-system' ),
			array( $this, 'render_phone_metabox' ),
			get_current_screen()->id,
			'side',
			'default'
		);
	}

	/**
	 * Render the metabox content
	 *
	 * @param WP_User $user Current user object
	 */
	public function render_phone_metabox( $user = null ) {
		$user_id      = $user->ID;
		$phone_number = niftysvs_get_user_phone_number( $user_id );
		$phone_number = ! empty( $phone_number ) ? $phone_number : '';
		if ( ! $phone_number && isset( $_POST['niftysvs_user_phone_number'] ) ) {
			$phone_number = trim( wp_unslash( $_POST['niftysvs_user_phone_number'] ) );
		}

		$status       = niftysvs_get_phone_verified_status( $user_id );
		?>
		<?php wp_nonce_field( 'niftysvs_user_phone_number_metabox', 'niftysvs_user_phone_number_nonce' ); ?>

        <div class="bp-profile-phone-number">
            <p>
                <label for="niftysvs_user_phone_number"><?php esc_html_e( 'Phone Number', 'nifty-sms-verification-system' ); ?></label>
                <input type="text" name="niftysvs_user_phone_number" id="niftysvs_user_phone_number"
                       value="<?php echo esc_attr( $phone_number ); ?>"/>

            </p>
        </div>

		<?php if ( current_user_can( 'manage_options' ) ) : ?>
            <p>
                <label for="niftysvs_phone_status"><?php esc_html_e( 'Phone Status', 'nifty-sms-verification-system' ); ?></label>
                <select name="niftysvs_phone_status" id="niftysvs_phone_status">
                    <option value="unverified" <?php selected( $status, 'unverified' ); ?>>
						<?php esc_html_e( 'Unverified', 'nifty-sms-verification-system' ); ?>
                    </option>
                    <option value="verified" <?php selected( $status, 'verified' ); ?>>
						<?php esc_html_e( 'Verified', 'nifty-sms-verification-system' ); ?>
                    </option>
                </select>

            </p>
		<?php else : ?>
            <p>
                <label for="niftysvs_phone_status"><?php esc_html_e( 'Phone Status', 'nifty-sms-verification-system' ); ?></label>
                <span><strong><?php echo $status; ?></strong></span>
            </p>
		<?php endif; ?>
        <?php // submission button.
            submit_button( __( 'Update', 'nifty-sms-verification-system' ), 'secondary', 'submit' );
        ?>
		<?php
	}

	/**
	 * Save the phone number
	 */
	public function update() {
		// Security checks
		if ( ! isset( $_POST['niftysvs_user_phone_number_nonce'] ) ||
		     ! wp_verify_nonce( $_POST['niftysvs_user_phone_number_nonce'], 'niftysvs_user_phone_number_metabox' ) ) {
			return;
		}

		$user_id        = $this->get_user_id();
		$logged_user_id = get_current_user_id();
		if ( ! $user_id || ! $logged_user_id ) {
			return;
		}

		$is_admin_user = current_user_can( 'manage_options' );

		$update_args = array();

		// make sure it is the same user or admin.
		if ( $user_id !== $logged_user_id && ! $is_admin_user ) {
			return;
		}

		// Save the phone number
		// should we validate the phone number here?
		if ( isset( $_POST['niftysvs_user_phone_number'] ) ) {
			$phone_number = sanitize_text_field( wp_unslash( $_POST['niftysvs_user_phone_number'] ) );
		} else {
			$phone_number = '';
		}

		$needs_update = false;

		if ( niftysvs_get_user_phone_number( $user_id ) !== $phone_number ) {
			// check if the numbe is verified and attached to someone else.
			if ( $phone_number && niftysvs_is_phone_number_verified( $phone_number ) ) {
				$message      = sprintf( __( 'The phone number: %s is already verified and attached to another user.', 'nifty-sms-verification-system' ), $phone_number );
				$message_type = 'error';
				update_user_meta( $user_id, '_nifty_phone_details_feedback', array(
					'message'      => $message,
					'message_type' => $message_type
				) );

				return;
			} elseif ( $phone_number && niftysvs_is_phone_number_blacklisted( $phone_number ) ) {
				$message      = sprintf( __( 'The phone number: %s is blacklisted.', 'nifty-sms-verification-system' ), $phone_number );
				$message_type = 'error';
				update_user_meta( $user_id, '_nifty_phone_details_feedback', array(
					'message'      => $message,
					'message_type' => $message_type
				) );

				return;
			}

			$needs_update                = true;
			$update_args['phone_number'] = $phone_number;
			$update_args['status']       = 'unverified';
		}

		if ( $is_admin_user && ! empty( $_POST['niftysvs_phone_status'] ) ) {
			$status = sanitize_text_field( wp_unslash( $_POST['niftysvs_phone_status'] ) );
			if ( niftysvs_get_phone_verified_status( $user_id ) !== $status ) {
				$update_args['status'] = $status;
				$needs_update          = true;
			}
		}

		if ( ! $needs_update ) {
			return;
		}

		if ( false === niftysvs_update_user_phone_details( $user_id, $update_args ) ) {
			$message      = __( 'Failed to update the phone details.', 'nifty-sms-verification-system' );
			$message_type = 'error';
		} else {
			$message      = __( 'Phone details updated.', 'nifty-sms-verification-system' );
			$message_type = 'success';
		}

		update_user_meta( $user_id, '_nifty_phone_details_feedback', array(
			'message'      => $message,
			'message_type' => $message_type
		) );
	}

	/**
	 * Get the currently editing user id.
	 *
	 * @return int
	 */
	private function get_user_id() {

		$user_id = get_current_user_id();

		// We'll need a user ID when not on the user admin.
		if ( ! empty( $_GET['user_id'] ) ) {
			$user_id = wp_unslash( $_GET['user_id'] );
		}

		$user_id = absint( $user_id );

		return $user_id;
	}

}
