<?php
/**
 * SNIPPET: MODULAR WISHLIST API
 * Description: Wishlist Management (Requires Custom Meta).
 * Endpoint: /wcs/v1/wishlist
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action( 'rest_api_init', function() {
    register_rest_route( 'wcs/v1', '/wishlist', array(
        'methods'  => ['GET', 'POST'],
        'callback' => 'wcs_wishlist_handler',
        'permission_callback' => '__return_true'
    ));
});

function wcs_wishlist_handler( $request ) {
    $user_id = get_current_user_id();
    if ( ! $user_id ) return new WP_REST_Response( [], 200 ); // Guest

    if ( $request->get_method() === 'GET' ) {
        $ids = get_user_meta( $user_id, '_wcs_wishlist', true );
        if ( ! is_array($ids) ) $ids = [];
        return new WP_REST_Response( array( 'ids' => $ids ), 200 );
    }

    if ( $request->get_method() === 'POST' ) {
        $pid = (int) $request->get_param('product_id');
        $current = get_user_meta( $user_id, '_wcs_wishlist', true );
        if ( ! is_array($current) ) $current = [];

        if ( in_array($pid, $current) ) {
            $current = array_diff($current, [$pid]); // Remove
        } else {
            $current[] = $pid; // Add
        }
        
        update_user_meta( $user_id, '_wcs_wishlist', $current );
        return new WP_REST_Response( array( 'ids' => $current ), 200 );
    }
}
