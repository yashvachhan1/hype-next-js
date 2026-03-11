<?php
/**
 * SNIPPET: MODULAR ACCOUNT API
 * Description: User Dashboard Data (Orders, Profile).
 * Endpoint: /wcs/v1/account
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action( 'rest_api_init', function() {
    register_rest_route( 'wcs/v1', '/account', array(
        'methods'  => 'GET',
        'callback' => 'wcs_get_account_data',
        'permission_callback' => function() {
            return is_user_logged_in();
        }
    ));
});

function wcs_get_account_data( $request ) {
    $user_id = get_current_user_id();
    
    // Get Orders
    $orders = wc_get_orders( array(
        'customer' => $user_id,
        'limit' => 5,
        'orderby' => 'date',
        'order' => 'DESC'
    ));

    $orders_data = [];
    foreach($orders as $order) {
        $orders_data[] = array(
            'id' => $order->get_id(),
            'status' => $order->get_status(),
            'total' => $order->get_formatted_order_total(),
            'date' => $order->get_date_created()->date('Y-m-d')
        );
    }

    $user = wp_get_current_user();
    
    return new WP_REST_Response( array( 
        'profile' => array(
            'email' => $user->user_email,
            'name' => $user->display_name
        ),
        'orders' => $orders_data 
    ), 200 );
}
