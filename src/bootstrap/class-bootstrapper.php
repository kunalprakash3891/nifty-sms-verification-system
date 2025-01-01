<?php
/**
 * Bootstrapper. Initializes the plugin.
 *
 * @package    Nifty_SMS_Verification_System
 * @subpackage Bootstrap
 * @copyright  Copyright (c) 2024, Brajesh Singh
 * @license    https://www.gnu.org/licenses/gpl.html GNU Public License
 * @author     Brajesh Singh
 * @since      1.0.0
 */

namespace Nifty_SMS_Verification_System\Bootstrap;

use Nifty_SMS_Verification_System\Admin\Admin_Settings;
use Nifty_SMS_Verification_System\Admin\Black_List_Quick_Edit_Helper;
use Nifty_SMS_Verification_System\Admin\Blacklist_Admin_Helper;
use Nifty_SMS_Verification_System\Admin\BP_Profile_Phone_Number_Metabox;
use Nifty_SMS_Verification_System\Admin\User_List_Extender;
use Nifty_SMS_Verification_System\Handlers\Signup_Helper;
use Nifty_SMS_Verification_System\Handlers\Verification_Cleaner;
use Nifty_SMS_Verification_System\Handlers\User_Deletion_Listener;
use Nifty_SMS_Verification_System\Handlers\Verification_Code_Validation_Controller;
use Nifty_SMS_Verification_System\Handlers\API_Availability_Controller;
use Nifty_SMS_Verification_System\Handlers\BP_Members_Endpoint_Extender;
use Nifty_SMS_Verification_System\Handlers\Phone_Number_REST_Controller;
use Nifty_SMS_Verification_System\Handlers\Verification_Request_REST_Controller;

// Do not allow direct access over web.
defined( 'ABSPATH' ) || exit;

/**
 * Bootstrapper.
 */
class Bootstrapper {

	/**
	 * Singleton instance.
	 *
	 * @var self
	 */
	private static $instance = null;

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->setup();
	}

	/**
	 * Initializes and retrieve class singleton instance.
	 *
	 * @return self
	 */
	private static function get_instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Returns singleton instance.
	 *
	 * @return self
	 */
	public static function boot() {
		return self::get_instance();
	}

	/**
	 * Binds hooks
	 */
	private function setup() {

		add_action( 'plugins_loaded', array( $this, 'load_admin' ), 9996 ); // pt settings 1.0.4.

		add_action( 'bp_loaded', array( $this, 'load' ) );
		// On high priority available for other plugins.
		add_action( 'bp_init', array( $this, 'load_translations' ), 0 );

		add_action( 'bp_rest_api_init', array( $this, 'register_routes' ), 15 );
	}

	/**
	 * Loads core functions/template tags.
	 *
	 * These are non auto loadable constructs.
	 */
	public function load() {
		$path = niftysvs_verification()->path;

		$files = array(
			'src/core/niftysvs-functions.php',
		);

		foreach ( $files as $file ) {
			require_once $path . $file;
		}

		if ( niftsvs_is_verification_enabled() ) {
			( new Signup_Helper() )->setup();
			( new User_Deletion_Listener() )->setup();
		}

		( new Verification_Cleaner() )->setup();
	}

	/**
	 * Loads Admin
	 */
	public function load_admin() {

		if ( ! function_exists( 'buddypress' ) ) {
			return;
		}

		if ( ! is_admin() ){
			return;
		}

		( new Black_List_Quick_Edit_Helper() )->setup();

		if ( defined( 'DOING_AJAX' ) ) {
			return;
		}

		if ( niftsvs_is_verification_enabled() ) {
			( new BP_Profile_Phone_Number_Metabox() )->setup();
			( new User_List_Extender() )->setup();
			(new Blacklist_Admin_Helper())->setup();
		}

		( new Admin_Settings() )->setup();
	}

	/**
	 * Loads translations.
	 */
	public function load_translations() {
		load_plugin_textdomain(
			'nifty-sms-verification-system',
			false,
			basename( niftysvs_verification()->path ) . '/languages'
		);
	}

	/**
	 * Registers routes
	 */
	public function register_routes() {
		( new API_Availability_Controller() )->register_routes();

		if ( niftsvs_is_verification_enabled() ) {
			( new Phone_Number_REST_Controller() )->register_routes();
			( new Verification_Request_REST_Controller() )->register_routes();
			( new Verification_Code_Validation_Controller() )->register_routes();
			( new BP_Members_Endpoint_Extender() )->register_fields();
		}
	}
}
