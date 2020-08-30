<?php

/**
 * WPSFsettings
 */
class WPSFsettings {
	/**
	 * @var string
	 */
	private $plugin_path;

	/**
	 * @var WordPressSettingsFramework
	 */
	private $wpsf;

	/**
	 * WPSFsettings constructor.
	 */
	function __construct() {
		$this->plugin_path = plugin_dir_path( __FILE__ );

		// Include and create a new WordPressSettingsFramework
		require_once( $this->plugin_path . 'settings/settings-framework.php' );
		$this->wpsf = new WordPressSettingsFramework( $this->plugin_path . 'settings/settings-general.php', 'wpoa_settings_general' );

		// Add admin menu
		add_action( 'admin_menu', array( $this, 'add_settings_page' ), 99 );
		
		// Add an optional settings validation filter (recommended)
		add_filter( $this->wpsf->get_option_group() . '_settings_validate', array( &$this, 'validate_settings' ) );
	}

	/**
	 * Add settings page.
	 */
	function add_settings_page() {
		$this->wpsf->add_settings_page( array(
			'parent_slug' => 'woocommerce',
			'page_title'  => __( 'WooCommerce Pending Orders', 'woo-pending-orders-alerts' ),
			'menu_title'  => __( 'Pending Orders', 'woo-pending-orders-alerts' ),
			'capability'  => 'activate_plugins',
		) );
	}

	/**
	 * Validate settings.
	 * 
	 * @param $input
	 *
	 * @return mixed
	 */
	function validate_settings( $input ) {
		// Do your settings validation here
		// Same as $sanitize_callback from http://codex.wordpress.org/Function_Reference/register_setting

		// remove previous schedule and set a new with the new time
		$timestamp = wp_next_scheduled( 'wpcorders_woo_pending_orders' );
		wp_unschedule_event( $timestamp, 'wpcorders_woo_pending_orders' );
		return $input;
	}

	// ...
}

$wpoa_settings = new WPSFsettings();
