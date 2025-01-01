<?php

namespace Nifty_SMS_Verification_System\Bootstrap;

use Nifty_SMS_Verification_System\Schema\Schema;

/**
 * Plugin installer class
 */
class Installer {

	/**
	 * Sets up installer.
	 */
	public function setup( $file ) {
		register_activation_hook( $file, array( $this, 'activate' ) );
		register_deactivation_hook( $file, array( $this, 'deactivate' ) );
	}

	/**
	 * Runs on plugin activation.
	 */
	public function activate() {
		// Add activation tasks here
		Schema::create();
		$this->setup_default_options();
		$this->schedule_cleanup();
	}

	/**
	 * Runs on plugin deactivation.
	 */
	public function deactivate() {
		wp_clear_scheduled_hook( 'niftysvs_verification_cleanup' );
		// Disable options auoload on deactivation.
		update_option( 'niftysvs_settings', get_option( 'niftysvs_settings', true ), false );
	}

	/**
	 * Sets up default options
	 */
	private function setup_default_options() {

		if ( ! get_option( 'niftysvs_settings' ) ) {
			$options = array(
				'twilio_sid'              => '',
				'twilio_token'            => '',
				'twilio_service_sid'      => '',
				'enable_verification'     => 0,
				'enforce_verification'    => 0,
				'verification_rate_limit' => array( 'attempts' => 5, 'duration' => 5, 'delay' => 30 ),
			);

			update_option( 'niftysvs_settings', $options, true );
		} else {
			// enable options autoload on activation.
			update_option( 'niftysvs_settings', get_option( 'niftysvs_settings', true ), true );
		}
	}

	/**
	 * Schedules the cron job for cleaning up verification requests.
	 */
	private function schedule_cleanup() {
		if ( ! wp_next_scheduled( 'niftysvs_verification_cleanup' ) ) {
			wp_schedule_event( time(), 'daily', 'niftysvs_verification_cleanup' );
		}
	}

}
