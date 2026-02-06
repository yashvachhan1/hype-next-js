<?php
/**
 * SNIPPET 1: CORE & USER API (Authentication, Orders, User Profile)
 * Description: Handles Login, Register, Checkout, and Core API Setup.
 */

// 1. GLOBAL SHUTDOWN HANDLER
if ( ! function_exists( 'wcs_shutdown_handler_safe' ) ) {
    function wcs_shutdown_handler_safe() {
        $error = error_get_last();
        if ( $error && in_array( $error['type'], array( E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR ) ) ) {
            if ( ob_get_length() ) ob_clean();
            header( 'Content-Type: application/json; charset=UTF-8' );
            echo json_encode( array( 
                'code' => 'fatal_server_error', 
                'message' => 'Server timeout or crash', 
                'debug' => $error['message'] . ' on line ' . $error['line']
            ));
            exit;
        }
    }
}
register_shutdown_function( 'wcs_shutdown_handler_safe' );

add_action( 'rest_api_init', function() {
    error_reporting(0);
    @ini_set( 'display_errors', 0 );
    @ini_set( 'memory_limit', '2048M' ); 
    @ini_set( 'max_execution_time', 1200 ); 
    @set_time_limit( 1200 );

    // CORE ROUTES
    $routes = array(
        '/login' => 'wcs_login_user_safe',
        '/checkout' => 'wcs_create_order_safe',
        '/register' => 'wcs_register_user_safe',
        '/orders' => 'wcs_get_user_orders_safe',
        '/user-details' => 'wcs_get_user_details_safe',
        '/update-user' => 'wcs_update_user_details_safe',
        '/refunds' => 'wcs_get_user_refunds_safe',
        '/form' => 'wcs_get_form_data_safe',
        '/form/submit' => 'wcs_handle_form_submit_safe'
    );

    foreach ( $routes as $route => $callback ) {
        $method = 'GET';
        if ( in_array( $route, ['/login', '/checkout', '/register', '/update-user', '/form/submit'] ) ) {
            $method = 'POST';
        }
        
        register_rest_route( 'wcs/v1', $route, array(
            'methods'  => $method,
            'callback' => $callback,
            'permission_callback' => '__return_true',
        ));
    }
});

// --- CORE FUNCTIONS ---

if ( ! function_exists( 'wcs_login_user_safe' ) ) {
    function wcs_login_user_safe( $request ) {
        $creds = array(
            'user_login'    => $request->get_param( 'username' ),
            'user_password' => $request->get_param( 'password' ),
            'remember'      => true
        );
        $user = wp_signon( $creds, false );
        if ( is_wp_error( $user ) ) return new WP_REST_Response( array( 'success' => false, 'message' => $user->get_error_message() ), 401 );
        return new WP_REST_Response( array( 'success' => true, 'user' => array( 'id' => $user->ID, 'name' => $user->display_name, 'email' => $user->user_email, 'role' => reset( $user->roles ) ) ), 200 );
    }
}

if ( ! function_exists( 'wcs_register_user_safe' ) ) {
    function wcs_register_user_safe( $request ) {
        $params = $request->get_json_params();
        if ( empty($params['email']) || empty($params['password']) ) return new WP_REST_Response( array( 'success' => false, 'message' => 'Email and Password required.' ), 400 );
        if ( email_exists( $params['email'] ) ) return new WP_REST_Response( array( 'success' => false, 'message' => 'Email already registered.' ), 400 );
        $user_id = wp_create_user( $params['email'], $params['password'], $params['email'] );
        if ( is_wp_error( $user_id ) ) return new WP_REST_Response( array( 'success' => false, 'message' => $user_id->get_error_message() ), 500 );
        
        $fields = ['first_name', 'last_name', 'company_name', 'phone', 'tax_id', 'address_1', 'city', 'state', 'postcode'];
        foreach($fields as $f) {
            if (!empty($params[$f])) {
                $meta_key = $f;
                if ($f === 'company_name') $meta_key = 'billing_company';
                if ($f === 'phone') $meta_key = 'billing_phone';
                if ($f === 'tax_id') $meta_key = 'billing_ein'; 
                if (in_array($f, ['address_1', 'city', 'state', 'postcode'])) $meta_key = 'billing_' . $f;
                update_user_meta( $user_id, $meta_key, sanitize_text_field($params[$f]) );
            }
        }
        update_user_meta( $user_id, 'pw_user_status', 'pending' ); 
        update_user_meta( $user_id, 'account_status', 'awaiting_admin_review' );
        wp_mail( get_option('admin_email'), 'New Wholesale Registration', "New user {$params['email']} registered." );
        return new WP_REST_Response( array( 'success' => true, 'message' => 'Registration successful! Pending approval.' ), 200 );
    }
}

if ( ! function_exists( 'wcs_get_user_details_safe' ) ) {
    function wcs_get_user_details_safe( $request ) {
        $user_id = intval( $request->get_param( 'user_id' ) );
        if ( !$user_id ) return new WP_REST_Response( [], 400 );
        $u = get_userdata($user_id);
        if ( !$u ) return new WP_REST_Response( [], 404 );
        $data = array(
            'billing' => array(
                'first_name' => get_user_meta($user_id, 'billing_first_name', true),
                'last_name' => get_user_meta($user_id, 'billing_last_name', true),
                'email' => $u->user_email,
                'phone' => get_user_meta($user_id, 'billing_phone', true),
                'company' => get_user_meta($user_id, 'billing_company', true),
                'address_1' => get_user_meta($user_id, 'billing_address_1', true),
                'city' => get_user_meta($user_id, 'billing_city', true),
                'state' => get_user_meta($user_id, 'billing_state', true),
                'postcode' => get_user_meta($user_id, 'billing_postcode', true),
                'country' => get_user_meta($user_id, 'billing_country', true) ?: 'US',
            ),
            'role' => reset( $u->roles ),
             'dashboard_stats' => array(
                'total_orders' => function_exists('wc_get_customer_order_count') ? wc_get_customer_order_count($user_id) : 0,
                'total_spent' => function_exists('wc_get_customer_total_spent') ? wc_get_customer_total_spent($user_id) : 0
            )
        );
        return new WP_REST_Response( $data, 200 );
    }
}

if ( ! function_exists( 'wcs_update_user_details_safe' ) ) {
    function wcs_update_user_details_safe( $request ) {
        $params = $request->get_json_params();
        $user_id = isset($params['user_id']) ? intval($params['user_id']) : 0;
        if ( !$user_id ) return new WP_REST_Response( array('success' => false, 'message' => 'User ID missing'), 400 );
        if ( !empty($params['billing']) ) {
            foreach($params['billing'] as $key => $value) update_user_meta( $user_id, 'billing_' . $key, sanitize_text_field($value) );
        }
        if ( !empty($params['shipping']) ) {
            foreach($params['shipping'] as $key => $value) update_user_meta( $user_id, 'shipping_' . $key, sanitize_text_field($value) );
        }
        return new WP_REST_Response( array( 'success' => true ), 200 );
    }
}

if ( ! function_exists( 'wcs_get_user_orders_safe' ) ) {
    function wcs_get_user_orders_safe( $request ) {
        $user_id = intval( $request->get_param( 'user_id' ) );
        if ( !$user_id || !function_exists( 'wc_get_orders' ) ) return new WP_REST_Response( array('orders' => []), 200 );
        $orders = wc_get_orders( array( 'customer' => $user_id, 'limit' => 20, 'orderby' => 'date', 'order' => 'DESC' ));
        $data = array();
        foreach($orders as $order) {
            $items = array();
            foreach ( $order->get_items() as $item_id => $item ) {
                $product = $item->get_product();
                $img = ( $product && method_exists($product, 'get_image_id') ) ? wp_get_attachment_image_url($product->get_image_id(), 'thumbnail') : '';
                $items[] = array( 'id' => $item_id, 'name' => $item->get_name(), 'quantity' => $item->get_quantity(), 'total' => wc_price($item->get_total()), 'image' => $img );
            }
            $data[] = array(
                'id' => '#' . $order->get_id(),
                'order_number' => $order->get_id(),
                'date' => $order->get_date_created() ? $order->get_date_created()->date('M j, Y') : '',
                'status' => ucfirst($order->get_status()),
                'total' => $order->get_formatted_order_total(),
                'items_count' => $order->get_item_count(),
                'line_items' => $items,
                'shipping_address' => $order->get_formatted_shipping_address()
            );
        }
        return new WP_REST_Response( array( 'orders' => $data ), 200 );
    }
}

if ( ! function_exists( 'wcs_create_order_safe' ) ) {
    function wcs_create_order_safe( $request ) {
        $params = $request->get_json_params();
        if ( !function_exists('wc_create_order') ) return new WP_REST_Response( array( 'success' => false, 'message' => 'WooCommerce not active' ), 500 );
        if ( empty($params['line_items']) ) return new WP_REST_Response( array( 'success' => false, 'message' => 'No items in cart' ), 400 );
        
        try {
            $order = wc_create_order();
            if ( is_wp_error($order) || !$order ) throw new Exception( 'Failed to create order' );
            
            foreach ( $params['line_items'] as $item ) {
                $product = wc_get_product($item['product_id']);
                if ( $product ) $order->add_product( $product, $item['quantity'] ?? 1 );
            }
            
            $address = $params['billing'] ?? [];
            $order->set_address( $address, 'billing' );
            $order->set_address( $address, 'shipping' ); 
            $order->calculate_totals();
            $order->update_status( 'processing', 'Order created via React App' );
            
            return new WP_REST_Response( array( 'success' => true, 'order_id' => $order->get_id(), 'total' => $order->get_total() ), 200 );
        } catch ( \Throwable $e ) {
            return new WP_REST_Response( array( 'success' => false, 'message' => $e->getMessage() ), 500 );
        }
    }
}

if ( ! function_exists( 'wcs_get_user_refunds_safe' ) ) {
     function wcs_get_user_refunds_safe( $request ) {
         return new WP_REST_Response( array( 'refunds' => [] ), 200 );
     }
}

if ( ! function_exists( 'wcs_get_form_data_safe' ) ) {
    function wcs_get_form_data_safe( $request ) {
         return new WP_REST_Response( array('success' => false, 'message' => 'Form not found'), 404 );
    }
}

if ( ! function_exists( 'wcs_handle_form_submit_safe' ) ) {
    function wcs_handle_form_submit_safe( $request ) {
        return new WP_REST_Response( array( 'success' => true ), 200 );
    }
}
