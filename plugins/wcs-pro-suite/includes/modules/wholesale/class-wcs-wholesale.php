<?php

class WCS_Wholesale {

	private $plugin_name;
	private $version;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

    /**
     * Apply Wholesale Price at the Product Object Level
     * (Works for REST API, Shop Grid, Cart, and Checkout)
     */
	public function apply_wholesale_price( $price, $product ) {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return $price;
        
        // Validation
        if ( ! $product ) return $price;
        $user = wp_get_current_user();
        if ( ! $user || $user->ID === 0 ) return $price;

        $roles = $user->roles;
        $role_a_name = get_option('swp_role_a_name', 'wholesale_customer_1');
        $role_b_name = get_option('swp_role_b_name', 'wholesale_customer_2');
        
        $discount_percent = 0;
        
        // Check Roles
        if ( in_array( $role_a_name, $roles ) ) {
            $discount_percent = get_option('swp_role_a_percent', 0);
        } elseif ( in_array( $role_b_name, $roles ) ) {
            $discount_percent = get_option('swp_role_b_percent', 0);
        }

        if ( $discount_percent <= 0 ) return $price;

        // Calculate New Price
        // Use regular price as base to avoid compounding if hook runs multiple times?
        // Actually get_price returns the current price. 
        // We should calculate from Regular Price to be consistent.
        $regular_price = $product->get_regular_price();
        if ( ! $regular_price ) return $price;

        $new_price = $regular_price - ( $regular_price * ( $discount_percent / 100 ) );
        
        // Ensure we don't return negative (though math won't allow if percent <= 100)
        return max( 0, $new_price );
    }

    // Hook registration in core loader needs to change?
    // This file defines methods, but where are they hooked? 
    // They are hooked in class-wcs-core.php! 
    // I MUST rename the methods to match what class-wcs-core.php expects? 
    // OR I must update class-wcs-core.php.
    // It's safer to Keep the Method Names but change implementation?
    // No, existing names (apply_wholesale_discounts) are descriptive of FEES.
    // I should create new method `apply_wholesale_price` and UPDATE core hooks.

