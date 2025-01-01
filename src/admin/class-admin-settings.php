<?php
/**
 * Admin Settings Page
 *
 * @package    niftysvs_Verification_System
 * @subpackage Admin
 * @copyright  Copyright (c) 2024, Brajesh Singh
 * @license    https://www.gnu.org/licenses/gpl.html GNU Public License
 * @author     Brajesh Singh
 * @since      1.0.0
 */

namespace Nifty_SMS_Verification_System\Admin;

// Prevent direct access.
defined( 'ABSPATH' ) || exit;

/**
 * Admin Settings Page helper
 */
class Admin_Settings {
	/**
	 * The option name used for storing plugin settings
	 *
	 * @var string
	 */
	private string $option_name = 'niftysvs_settings';

	/**
	 * Sets up hooks.
	 */
	public function setup() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Adds the SMS verification settings page to the WordPress admin menu.
	 */
	public function add_settings_page() {
		add_options_page(
			__( 'Nifty SMS Verification', 'nifty-sms-verification-system' ),
			__( 'Nifty SMS Verification', 'nifty-sms-verification-system' ),
			'manage_options',
			'nifty-smsv-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Registers the settings with the WordPress settings API.
	 *
	 * Adds the settings section and fields to the settings page.
	 */
	public function register_settings() {
		register_setting(
			'niftysvs_settings_group',
			$this->option_name,
			array( $this, 'sanitize_settings' )
		);

		add_settings_section(
			'niftysvs_main_section',
			__( 'Twilio API Settings', 'nifty-sms-verification-system' ),
			array( $this, 'section_callback' ),
			'nifty-smsv-settings'
		);

		$is_credentials_defined = niftysvs_is_credential_defined();

		add_settings_field(
			'twilio_sid',
			__( 'Twilio Account SID', 'nifty-sms-verification-system' ),
			array( $this, 'render_text_field' ),
			'nifty-smsv-settings',
			'niftysvs_main_section',
			array(
				'field'       => 'twilio_sid',
				'disabled'    => $is_credentials_defined,
				'description' => defined( 'NIFTYSVS_TWILIO_SID' ) ? __( 'You are using the constant NIFTYSVS_TWILIO_SID to override this value.', 'nifty-sms-verification-system' ) : __( 'You may use the constant NIFTYSVS_TWILIO_SID instead adding value here.', 'nifty-sms-verification-system' )
			)
		);

		add_settings_field(
			'twilio_token',
			__( 'Twilio Auth Token', 'nifty-sms-verification-system' ),
			array( $this, 'render_text_field' ),
			'nifty-smsv-settings',
			'niftysvs_main_section',
			array(
				'field'       => 'twilio_token',
				'disabled'    => $is_credentials_defined,
				'description' => defined( 'NIFTYSVS_TWILIO_TOKEN' ) ? __( 'You are using the constant NIFTYSVS_TWILIO_TOKEN to override this value.', 'nifty-sms-verification-system' ) : __( 'You may use the constant NIFTYSVS_TWILIO_TOKEN instead of adding value here.', 'nifty-sms-verification-system' )
			)
		);

		add_settings_field(
			'twilio_service_sid',
			__( 'Twilio Service SID', 'nifty-sms-verification-system' ),
			array( $this, 'render_text_field' ),
			'nifty-smsv-settings',
			'niftysvs_main_section',
			array(
				'field'       => 'twilio_service_sid',
				'disabled'    => defined( 'NIFTYSVS_TWILIO_SERVICE_SID' ),
				'description' => defined( 'NIFTYSVS_TWILIO_SERVICE_SID' ) ? __( 'You are using the constant NIFTYSVS_TWILIO_SERVICE_SID to override this value.', 'nifty-sms-verification-system' ) : __( 'You may use the constant NIFTYSVS_TWILIO_SERVICE_SID instead of adding value here.', 'nifty-sms-verification-system' )
			)
		);

		add_settings_field(
			'enable_verification',
			__( 'Enable SMS Verification', 'nifty-sms-verification-system' ),
			array( $this, 'render_radio_field' ),
			'nifty-smsv-settings',
			'niftysvs_main_section',
			array(
				'field'   => 'enable_verification',
				'options' => array(
					0 => __( 'No', 'nifty-sms-verification-system' ),
					1 => __( 'Yes', 'nifty-sms-verification-system'
					)
				)
			)
		);

		add_settings_field(
			'enforce_verification',
			__( 'Enforce SMS Verification', 'nifty-sms-verification-system' ),
			array( $this, 'render_radio_field' ),
			'nifty-smsv-settings',
			'niftysvs_main_section',
			array(
				'field'   => 'enforce_verification',
				'options' => array(
					0 => __( 'No', 'nifty-sms-verification-system' ),
					1 => __( 'Yes', 'nifty-sms-verification-system'
					)
				)
			)
		);

		add_settings_field(
			'verification_rate_limit',
			__( 'Rate Limit', 'nifty-sms-verification-system' ),
			array( $this, 'render_rate_limit_field' ),
			'nifty-smsv-settings',
			'niftysvs_main_section',
			array( 'field' => 'verification_rate_limit' )
		);
	}

	/**
	 * Sanitizes the settings before saving them to the database.
	 *
	 * @param array $input The settings to sanitize.
	 *
	 * @return array The sanitized settings.
	 */
	public function sanitize_settings( $input ) {
		$sanitized = array();

		$credentials_available = ! niftysvs_is_credential_defined();

		if ( $credentials_available && isset( $input['twilio_sid'] ) ) {
			$sanitized['twilio_sid'] = sanitize_text_field( $input['twilio_sid'] );
		}

		if ( $credentials_available && isset( $input['twilio_token'] ) ) {
			$sanitized['twilio_token'] = sanitize_text_field( $input['twilio_token'] );
		}

		if ( ! defined( 'NIFTYSVS_TWILIO_SERVICE_SID' ) && isset( $input['twilio_service_sid'] ) ) {
			$sanitized['twilio_service_sid'] = sanitize_text_field( $input['twilio_service_sid'] );
		}

		$sanitized['enable_verification']  = isset( $input['enable_verification'] ) ? absint( $input['enable_verification'] ) : 0;
		$sanitized['enforce_verification'] = isset( $input['enforce_verification'] ) ? absint( $input['enforce_verification'] ) : 0;

		$rate_limit = isset( $input['verification_rate_limit'] ) ? $input['verification_rate_limit'] : array();

		if ( $rate_limit ) {
			$rate_limit['attempts'] = isset( $rate_limit['attempts'] ) ? absint( $rate_limit['attempts'] ) : 0;
			$rate_limit['duration'] = isset( $rate_limit['duration'] ) ? absint( $rate_limit['duration'] ) : 0;
			$rate_limit['delay']    = isset( $rate_limit['delay'] ) ? absint( $rate_limit['delay'] ) : 0;
		}

		$sanitized['verification_rate_limit'] = $rate_limit;

		return $sanitized;
	}

	/**
	 * Renders the main section of the settings page.
	 *
	 * Provides a brief description of the settings page and the Twilio API settings.
	 */
	public function section_callback() {
		echo '<p>' . __( 'Configure your Twilio API credentials and SMS verification settings.', 'nifty-sms-verification-system' ) . '</p>';
	}

	/**
	 * Renders a text field for settings page.
	 *
	 * Displays an input field for the Twilio Account SID and Auth Token settings.
	 *
	 * @param array $args The field arguments.
	 */
	public function render_text_field( $args ) {

		$default     = isset( $args['default'] ) ? $args['default'] : '';
		$disabled    = empty( $args['disabled'] ) ? '' : 'disabled';
		$description = empty( $args['description'] ) ? '' : '<p class="description">' . esc_html( $args['description'] ) . '</p>';
		$value       = $disabled ? '****' : niftysvs_get_option( $args['field'], $default );
		printf(
			'<input type="text" class="regular-text" name="%s[%s]" value="%s" %s/> %s',
			esc_attr( $this->option_name ),
			esc_attr( $args['field'] ),
			esc_attr( $value ),
			$disabled,
			$description
		);
	}

	/**
	 * Renders a checkbox field for the settings page.
	 *
	 * Displays a checkbox field for enabling or disabling SMS verification.
	 *
	 * @param array $args The field arguments.
	 */
	public function render_checkbox_field( $args ) {
		$options = get_option( $this->option_name );
		$value   = isset( $options[ $args['field'] ] ) ? $options[ $args['field'] ] : '0';
		printf(
			'<input type="checkbox" name="%s[%s]" value="1" %s />',
			esc_attr( $this->option_name ),
			esc_attr( $args['field'] ),
			checked( '1', $value, false )
		);
	}

	/**
	 * Renders a radio field for the settings page.
	 *
	 * @param array $args The field arguments containing options and field name.
	 */
	public function render_radio_field( $args ) {
		$field = isset( $args['field'] ) ? $args['field'] : '';
		$value = niftysvs_get_option( $field, 0 );
		if ( isset( $args['options'] ) && is_array( $args['options'] ) ) {
			foreach ( $args['options'] as $key => $label ) {
				printf(
					'<label><input type="radio" name="%s[%s]" value="%s" %s /> %s</label> &nbsp;',
					esc_attr( $this->option_name ),
					esc_attr( $args['field'] ),
					esc_attr( $key ),
					checked( $key, $value, false ),
					esc_html( $label )
				);
			}
		}
	}

	/**
	 * Renders the rate limit field for the settings page.
	 *
	 * @param array $args The field arguments.
	 */
	public function render_rate_limit_field( $args ) {
		$options = niftysvs_get_option( $args['field'], array() );
		$fields  = array(
			'attempts' => __( 'Max Attempts', 'nifty-sms-verification-system' ),
			'duration' => __( 'During(min)', 'nifty-sms-verification-system' ),
			'delay'    => __( 'Delay(min)', 'nifty-sms-verification-system' )
		);

		$field_name = $args['field'];
		foreach ( $fields as $key => $label ) {
			$value    = isset( $options[ $key ] ) ? $options[ $key ] : '';
			$field_id = $field_name . '_' . $key;
			echo '<label for="' . esc_attr( $field_id ) . '">' . esc_html( $label ) . '</label><br>';
			printf(
				'<input type="number" min="0" id="%s" name="%s[%s][%s]" value="%s" class="small-text" /><br><br>',
				esc_attr( $field_id ),
				esc_attr( $this->option_name ),
				esc_attr( $field_name ),
				esc_attr( $key ),
				esc_attr( $value )
			);
		}
	}

	/**
	 * Renders the Nifty SMS settings page.
	 *
	 * Displays the Nifty SMS settings page in the WordPress admin area.
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <form action="options.php" method="post">
				<?php
				settings_fields( 'niftysvs_settings_group' );
				do_settings_sections( 'nifty-smsv-settings' );
				submit_button();
				?>
            </form>
        </div>
		<?php
	}
}
