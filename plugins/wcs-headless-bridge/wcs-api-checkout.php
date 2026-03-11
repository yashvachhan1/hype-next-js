<?php
/**
 * SNIPPET: MODULAR CHECKOUT API
 * Description: Process Payments & Create Orders.
 * Endpoint: /wcs/v1/checkout
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action( 'rest_api_init', function() {
    register_rest_route( 'wcs/v1', '/checkout', array(
        'methods'  => 'POST',
        'callback' => 'wcs_process_checkout',
        'permission_callback' => '__return_true'
    ));
});

function wcs_process_checkout( $request ) {
    if ( WC()->cart->is_empty() ) {
        return new WP_REST_Response( array( 'success' => false, 'message' => 'Cart is empty.' ), 400 );
    }

    $address = $request->get_param('address');
    
    // Create Order
    $checkout = WC()->checkout();
    $order_id = $checkout->create_order( array(
        'billing_first_name' => $request->get_param('first_name'),
        'billing_last_name'  => $request->get_param('last_name'),
        'billing_email'      => $request->get_param('email'),
        'billing_address_1'  => $address['line1'],
        'payment_method'     => 'cod' // Simplified for example
    ));

    if ( is_wp_error( $order_id ) ) {
        return new WP_REST_Response( array( 'success' => false, 'message' => $order_id->get_error_message() ), 500 );
    }

    return new WP_REST_Response( array( 
        'success' => true, 
        'order_id' => $order_id, 
        'redirect' => $checkout->get_order_received_url( $order_id ) 
    ), 200 );
}
