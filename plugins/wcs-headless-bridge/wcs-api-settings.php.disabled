<?php
/**
 * SNIPPET: MODULAR SETTINGS API
 * Description: Global App Settings (Wholesale Rules, Logo, Shipping Limits).
 * Endpoint: /wcs/v1/app/settings
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action( 'rest_api_init', function() {
    register_rest_route( 'wcs/v1', '/app/settings', array(
        'methods'  => 'GET',
        'callback' => 'wcs_get_app_settings_modular',
        'permission_callback' => '__return_true'
    ));
});

function wcs_get_app_settings_modular() {
    // Cache for 1 hour
    $cache_key = 'wcs_app_settings_v1';
    $cached = get_transient($cache_key);
    if ($cached !== false) return new WP_REST_Response($cached, 200);

    // Wholesale Rules (Critical for Frontend Pricing)
    $wholesale_rules = array(
        'tier_1' => array(
            'role' => get_option('swp_role_a_name', 'customer_category_1'),
            'discount' => (int) get_option('swp_role_a_percent', 20),
        ),
        'tier_2' => array(
            'role' => get_option('swp_role_b_name', 'customer_category_2'),
            'discount' => (int) get_option('swp_role_b_percent', 30),
        )
    );

    $data = array(
        'site_name' => get_bloginfo( 'name' ),
        'logo'      => 'https://hotpink-camel-152562.hostingersite.com/wp-content/uploads/2026/01/Group-2104-1-2.png', // Hardcoded as per previous ID
        'free_shipping_threshold' => (float) get_option( 'wcs_free_shipping_threshold', 0 ),
        'wholesale_rules' => $wholesale_rules,
        'contact_email' => get_option('admin_email'),
        'phone' => '1 (866) 818-9598' // Standard Support Phone
    );

    set_transient($cache_key, $data, HOUR_IN_SECONDS);
    return new WP_REST_Response($data, 200);
}
