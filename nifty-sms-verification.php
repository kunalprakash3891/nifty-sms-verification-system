<?php
/**
 * Plugin Name:  Nifty SMS Verification System
 * Version:      1.1.2
 * Description:  It allows verification of user mobile numbers using twilio api.
 * Author:       BuddyDev
 * Author URI:   https://buddydev.com/
 * Requires PHP: 7.4
 * License:      GPL2
 * License URI:  https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:  nifty-sms-verification-system
 * Domain Path:  /languages
 *
 * @package nifty-sms-verification
 **/

use Nifty_SMS_Verification_System\Bootstrap\Autoloader;
use Nifty_SMS_Verification_System\Bootstrap\Bootstrapper;
use Nifty_SMS_Verification_System\Bootstrap\Installer;
use Nifty_SMS_Verification_System\Schema\Schema;

// Do not allow direct access over web.
defined( 'ABSPATH' ) || exit;

/**
 * Nifty SMS Verification System
 *
 * @property-read string $path     Absolute path to the plugin directory.
 * @property-read string $url      Absolute url to the plugin directory.
 * @property-read string $basename Plugin base name.
 * @property-read string $version  Plugin version.
 */
class Nifty_SMS_Verification_System {

	/**
	 * Plugin Version.
	 *
	 * @var string
	 */
	private string $version = '1.1.2';

	/**
	 * Singleton instance
	 *
	 * @var self
	 */
	private static $instance = null;

	/**
	 * Plugin directory absolute path
	 *
	 * @var string
	 */
	private string $path;

	/**
	 * Plugin directory url
	 *
	 * @var string
	 */
	private string $url;

	/**
	 * Plugin Basename.
	 *
	 * @var string
	 */
	private string $basename;

	/**
	 * Protected properties. These properties are inaccessible via magic method.
	 *
	 * @var array
	 */
	private array $guarded = array( 'guarded', 'instance' );

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->bootstrap();
	}

	/**
	 * Retrieves Singleton Instance
	 *
	 * @return self
	 */
	public static function get_instance(): self {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Bootstraps the core.
	 */
	private function bootstrap() {
		$this->path     = plugin_dir_path( __FILE__ );
		$this->url      = plugin_dir_url( __FILE__ );
		$this->basename = plugin_basename( __FILE__ );

		require 'vendor/autoload.php';

		// Load autoloader.
		require_once $this->path . 'src/bootstrap/class-autoloader.php';

		$autoloader = new Autoloader( 'Nifty_SMS_Verification_System\\', __DIR__ . '/src/' );

		spl_autoload_register( $autoloader );

		//register_activation_hook( __FILE__, array( $this, 'on_activation' ) );
		//register_deactivation_hook( __FILE__, array( $this, 'on_deactivation' ) );

		// Drop tables on uninstall.
		// register_uninstall_hook( __FILE__, array( 'Schema', 'drop' ) );.
		( new Installer() )->setup( __FILE__ );
		Bootstrapper::boot();
	}

	/**
	 * On activation, creates tables
	 */
	public function on_activation() {
		Schema::create();
	}


	/**
	 * On deactivation. Does cleanup if needed.
	 */
	public function on_deactivation() {
		// do cleanup.
	}

	/**
	 * Magic method for checking if property is set.
	 *
	 * @param string $name property name.
	 *
	 * @return bool
	 */
	public function __isset( string $name ) {
		return property_exists( $this, $name ) && ! in_array( $name, $this->guarded, true );
	}

	/**
	 * Magic method for accessing property as readonly(It's a lie, references can be updated).
	 *
	 * @param string $name property name.
	 *
	 * @return mixed|null
	 */
	public function __get( string $name ) {

		if ( property_exists( $this, $name ) && ! in_array( $name, $this->guarded, true ) ) {
			return $this->{$name};
		}

		return null;
	}
}

/**
 * Helper to access singleton instance
 *
 * @return Nifty_SMS_Verification_System
 */
function niftysvs_verification(): Nifty_SMS_Verification_System {
	return Nifty_SMS_Verification_System::get_instance();
}

// start.
niftysvs_verification();
