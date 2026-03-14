<?php
/**
 * Plugin Name: Hype Modular APIs
 * Description: Loads the modern, modular API suite for the Next.js Frontend.
 * Version: 2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define Base Path
$wcs_api_dir = plugin_dir_path(__FILE__);
if ( ! $wcs_api_dir ) $wcs_api_dir = __DIR__ . '/';

// 1. Core & Settings
require_once $wcs_api_dir . 'wcs-api-core.php';       // System Settings & Headers
require_once $wcs_api_dir . 'wcs-api-settings.php';   // Global App Data (Logo, Rules)

// 2. Menu & Navigation
require_once $wcs_api_dir . 'wcs-api-menu.php';

// 3. Home Page Sections
require_once $wcs_api_dir . 'wcs-api-home-trending.php';
require_once $wcs_api_dir . 'wcs-api-home-new-arrivals.php';
require_once $wcs_api_dir . 'wcs-api-home-bestsellers.php';

// 4. Shop & Product
require_once $wcs_api_dir . 'wcs-api-shop.php';       // Catalog, Search, Filters
require_once $wcs_api_dir . 'wcs-api-product.php';    // Single Product

// 5. Authentication
require_once $wcs_api_dir . 'wcs-api-login.php';
require_once $wcs_api_dir . 'wcs-api-register.php';

// 6. User Area
require_once $wcs_api_dir . 'wcs-api-account.php';    // Dashboard, Orders

// 7. Commerce (Cart, Checkout)
require_once $wcs_api_dir . 'wcs-api-cart.php';
require_once $wcs_api_dir . 'wcs-api-checkout.php';
require_once $wcs_api_dir . 'wcs-api-wishlist.php';

// 8. Utilities
require_once $wcs_api_dir . 'wcs-api-contact.php';    // Forms

/**
 * Note: If you deleted 'wholesale-pricing-snippet.php', 
 * ensure your Wholesale PRICING LOGIC is handled.
 * Currently, 'wcs-api-settings.php' provides the DATA (20%/30%),
 * but the Backend Calculation Logic might need to be restored if missing.
 */
