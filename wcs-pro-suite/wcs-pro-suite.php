/**
 * Plugin Name: WCS Pro Suite
 * Description: The All-in-One Growth Suite for WooCommerce. Wholesale Pricing + Premium Shop Filters in one free plugin.
 * Version: 1.0.2
 * Author: Your Name
 * Text Domain: wcs-pro-suite
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Current Plugin Version.
 */
define( 'WCS_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 */
function activate_woo_custom_suite() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wcs-activator.php';
	WCS_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_woo_custom_suite() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wcs-deactivator.php';
	WCS_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_woo_custom_suite' );
register_deactivation_hook( __FILE__, 'deactivate_woo_custom_suite' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-wcs-core.php';
require plugin_dir_path( __FILE__ ) . 'standalone-wishlist.php';

/**
 * Begins execution of the plugin.
 */
function run_woo_custom_suite() {
	$plugin = new WCS_Core();
	$plugin->run();
}
run_woo_custom_suite();
