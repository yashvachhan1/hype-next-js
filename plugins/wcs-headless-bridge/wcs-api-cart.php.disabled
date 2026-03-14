<?php
/**
 * SNIPPET: MODULAR CART API
 * Description: Handles Cart Operations (Get, Add, Remove).
 * Endpoint: /wcs/v1/cart
 * Note: Requires session handling on frontend to persist cart.
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action( 'rest_api_init', function() {
    register_rest_route( 'wcs/v1', '/cart', array(
        'methods'  => ['GET', 'POST', 'DELETE'],
        'callback' => 'wcs_cart_handler',
        'permission_callback' => '__return_true'
    ));
});

function wcs_cart_handler( $request ) {
    // Ensure WooCommerce is loaded
    if ( ! function_exists( 'WC' ) ) {
        return new WP_REST_Response( array( 'error' => 'WooCommerce inactive' ), 500 );
    }

    // Initialize Session if missing (Critical for REST API)
    if ( is_null( WC()->session ) ) {
        $session_class = apply_filters( 'woocommerce_session_handler', 'WC_Session_Handler' );
        WC()->session = new $session_class();
        WC()->session->init();
    }

    // Initialize Cart if missing
    if ( is_null( WC()->cart ) ) {
        WC()->cart = new WC_Cart();
    }
    
    // Safety check: if still failed, return empty structure (don't crash frontend)
    if ( ! WC()->cart ) {
         return new WP_REST_Response( array( 'items' => [], 'total' => 0, 'count' => 0 ), 200 );
    }

    $method = $request->get_method();

    // GET: Retrieve Cart
    if ( $method === 'GET' ) {
        $items = [];
        foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
            $product = $cart_item['data'];
            $items[] = array(
                'key' => $cart_item_key,
                'id' => $product->get_id(),
                'name' => $product->get_name(),
                'qty' => $cart_item['quantity'],
                'price' => $product->get_price(),
                'image' => wp_get_attachment_image_url($product->get_image_id(), 'thumbnail')
            );
        }
        return new WP_REST_Response( array( 
            'items' => $items, 
            'total' => WC()->cart->get_total(),
            'count' => WC()->cart->get_cart_contents_count()
        ), 200 );
    }

    // POST: Add to Cart
    if ( $method === 'POST' ) {
        $product_id = (int) $request->get_param('product_id');
        $quantity = (int) $request->get_param('quantity') ?: 1;
        $variation_id = (int) $request->get_param('variation_id') ?: 0;

        $cart_item_key = WC()->cart->add_to_cart( $product_id, $quantity, $variation_id );
        
        if ( ! $cart_item_key ) {
            return new WP_REST_Response( array( 'success' => false, 'message' => 'Could not add to cart.' ), 400 );
        }
        return new WP_REST_Response( array( 'success' => true, 'key' => $cart_item_key ), 200 );
    }
    
    // DELETE: Remove Item
    if ( $method === 'DELETE' ) {
        $key = $request->get_param('key');
        if($key) {
            WC()->cart->remove_cart_item($key);
        }
        return new WP_REST_Response( array( 'success' => true ), 200 );
    }
}
