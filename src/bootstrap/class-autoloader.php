<?php
/**
 * Nifty SMS Verification System Autoload Implementation.
 *
 * @package    Nifty_SMS_Verification_System
 * @subpackage Bootstrap
 * @copyright  Copyright (c) 2024, Brajesh Singh.
 * @license    https://www.gnu.org/licenses/gpl.html GNU Public License
 * @author     Brajesh Singh
 * @since      1.0.0
 */

namespace Nifty_SMS_Verification_System\Bootstrap;

// Do not allow direct access over web.
defined( 'ABSPATH' ) || exit;

/**
 * Auto Loader for Nifty_SMS_Verification_System.
 *
 * After registering this autoload function with SPL, the following line
 * would cause the function to attempt to load the Nifty_SMS_Verification_System\Bar\Baz\Qux class
 * from /path/to/wp-content/plugins/nifty-sms-verification/src/bar/baz/class-qux.php:
 *
 *      new Nifty_SMS_Verification_System\Baz_Xyz\Qux;
 *  maps to /path/to/wp-content/plugins/nifty-sms-verification/src/baz-xyz/class-qux.php
 * The path/directory name should be all lowercase and the class file is named as 'class-$classname.php'
 *
 * @version 1.0.0
 */
class Autoloader {

	/**
	 * Namespace root. e.g. Nifty_SMS_Verification_System\\(escaped slashes);
	 *
	 * @var string
	 */
	private $root_namespace = null;

	/**
	 * Absolute path to the source directory mapped to our name space root.
	 *
	 * @var string
	 */
	private $root_path = null;

	/**
	 * Autoloader constructor.
	 *
	 * @param string $root_namespace namespace root/prefix.
	 * @param string $root_path Absolute source path mapped to the root.
	 */
	public function __construct( $root_namespace, $root_path ) {
		$this->root_namespace = $root_namespace;
		$this->root_path      = $root_path;
	}

	/**
	 * Class Loader for PressThemes Settings package.
	 *
	 * After registering this autoload function with SPL, the following line
	 * would cause the function to attempt to load the \Press_Themes\PT_Settings\Bar\Baz\Qux class
	 * from /path/to/project/pt-settings/src/bar/baz/class-qux.php:
	 *
	 *      new \Press_Themes\PT_Settings\Baz\Qux;
	 *  maps to /path/to/pt-settings/src/baz/class-qux.php
	 * The path/directory name should be all lowercase and the class file is named as 'class-$classname.php'
	 * Since we wills tick to wp standards, we will be using interface-$classname.php for loading interface(and similar for traits)
	 * It sacrifices a bit of speed for readability( if we do not prefix with class-, interface-,trait-, it wil be better).
	 *
	 * @param string $class The fully-qualified class name.
	 *
	 * @return void
	 */
	public function __invoke( $class ) {
		// Project-specific namespace prefix.
		$prefix = $this->root_namespace;

		// Base directory for the namespace prefix.
		$base_dir = $this->root_path;

		// does the class use the namespace prefix?
		$len = strlen( $prefix );

		if ( strncmp( $prefix, $class, $len ) !== 0 ) {
			// no, move to the next registered autoloader.
			return;
		}

		// get the relative class name.
		// also make it lower case as we will use it as file name with wp standards.
		$relative_class = strtolower( substr( $class, $len ) );

		// replace the namespace prefix with the base directory, replace namespace
		// separators with directory separators in the relative class name, append
		// with .php. Also, replace the underscore(_) with hyphen(-).
		$file = $base_dir . str_replace( array( '_', '\\' ), array( '-', '/' ), $relative_class );

		$file      = explode( '/', $file );
		$file_name = array_pop( $file );
		$base_dir  = join( '/', $file );

		// Since we have a naming convention for file like 'class-', 'interface-', 'trait-',
		// we need to test for each of them.
		$class_file     = $base_dir . "/class-{$file_name}.php";
		$interface_file = $base_dir . "/interface-{$file_name}.php";
		$trait_file     = $base_dir . "/trait-{$file_name}.php";

		// Replace the last component
		// If the file exists, require it.
		if ( file_exists( $class_file ) ) {
			require_once $class_file;
		} elseif ( file_exists( $interface_file ) ) {
			require_once $interface_file;
		} elseif ( file_exists( $trait_file ) ) {
			require_once $trait_file;
		}
	}
}
