<?php
/**
 * BULLETPROOF API FOR REACT APP (SNIPPET SAFE VERSION V4)
 * 1. Removed <?php tag (for Snippets)
 * 2. Renamed ALL functions to avoid "Already Exists" errors
 * 3. Added ROLE to login response
 * 4. Added WHOLESALE PRICE fetch
 * 5. Added RAW PRICE to ignore "Please Login" plugin filters
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

    // MAP NEW FUNCTION NAMES HERE
    $routes = array(
        '/app-data' => 'wcs_get_initial_data_safe', // Default to Initial (Light)
        '/app-data-initial' => 'wcs_get_initial_data_safe',
        '/app-data-secondary' => 'wcs_get_secondary_data_safe',
        '/login' => 'wcs_login_user_safe',
        '/brands' => 'wcs_get_brands_safe',
        '/products' => 'wcs_get_products_safe',
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

// --- RENAMED FUNCTIONS BELOW ---

if ( ! function_exists( 'wcs_update_user_details_safe' ) ) {
    function wcs_update_user_details_safe( $request ) {
        $params = $request->get_json_params();
        $user_id = isset($params['user_id']) ? intval($params['user_id']) : 0;
        
        if ( !$user_id ) return new WP_REST_Response( array('success' => false, 'message' => 'User ID missing'), 400 );

        if ( !empty($params['billing']) ) {
            foreach($params['billing'] as $key => $value) {
                update_user_meta( $user_id, 'billing_' . $key, sanitize_text_field($value) );
            }
        }
        if ( !empty($params['shipping']) ) {
            foreach($params['shipping'] as $key => $value) {
                update_user_meta( $user_id, 'shipping_' . $key, sanitize_text_field($value) );
            }
        }
        return new WP_REST_Response( array( 'success' => true ), 200 );
    }
}

if ( ! function_exists( 'wcs_get_user_orders_safe' ) ) {
    function wcs_get_user_orders_safe( $request ) {
        try {
            $user_id = intval( $request->get_param( 'user_id' ) );
            if ( !$user_id ) return new WP_REST_Response( array('orders' => []), 200 );

            if ( ! function_exists( 'wc_get_orders' ) ) {
                return new WP_REST_Response( array('error' => 'WooCommerce Not Active'), 500 );
            }

            $orders = wc_get_orders( array(
                'customer' => $user_id,
                'limit' => 20,
                'orderby' => 'date',
                'order' => 'DESC'
            ));

            $data = array();
            foreach($orders as $order) {
                $items = array();
                foreach ( $order->get_items() as $item_id => $item ) {
                    $product = $item->get_product();
                    $img = ( $product && method_exists($product, 'get_image_id') ) ? wp_get_attachment_image_url($product->get_image_id(), 'thumbnail') : '';
                    $items[] = array(
                        'id' => $item_id,
                        'name' => $item->get_name(),
                        'quantity' => $item->get_quantity(),
                        'total' => wc_price($item->get_total()),
                        'image' => $img
                    );
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
        } catch ( \Exception $e ) {
            return new WP_REST_Response( array( 'error' => $e->getMessage() ), 500 );
        }
    }
}

if ( ! function_exists( 'wcs_get_user_refunds_safe' ) ) {
    function wcs_get_user_refunds_safe( $request ) {
        try {
            $user_id = intval( $request->get_param( 'user_id' ) );
            if ( !$user_id ) return new WP_REST_Response( array('refunds' => []), 200 );

            if ( ! function_exists( 'wc_get_orders' ) ) return new WP_REST_Response( array('refunds' => []), 200 );

            $customer_orders = wc_get_orders( array( 'customer' => $user_id, 'return' => 'ids', 'limit' => -1 ) );
            if ( empty($customer_orders) ) return new WP_REST_Response( array('refunds' => []), 200 );

            $args = array(
                'post_type'      => 'shop_order_refund',
                'post_parent__in' => $customer_orders,
                'post_status'    => 'any',
                'numberposts'    => -1,
            );
            
            $refunds = get_posts( $args );
            $data = array();
            foreach ( $refunds as $refund_post ) {
                $refund = wc_get_order( $refund_post->ID );
                $parent_order = wc_get_order( $refund_post->post_parent );
                if ( ! $refund || ! $parent_order ) continue;

                $data[] = array(
                    'id' => '#' . $refund->get_id(),
                    'date' => $refund->get_date_created() ? $refund->get_date_created()->date('M j, Y') : '',
                    'amount' => wc_price( $refund->get_amount() ),
                    'reason' => $refund->get_reason() ?: 'Refund Request',
                    'order_id' => '#' . $parent_order->get_id(),
                    'status' => 'Processed'
                );
            }
            return new WP_REST_Response( array( 'refunds' => $data ), 200 );
        } catch ( \Exception $e ) {
            return new WP_REST_Response( array( 'refunds' => [] ), 200 );
        }
    }
}

if ( ! function_exists( 'wcs_get_user_details_safe' ) ) {
    function wcs_get_user_details_safe( $request ) {
        try {
            $user_id = intval( $request->get_param( 'user_id' ) );
            if ( !$user_id ) return new WP_REST_Response( [], 400 );

            $u = get_userdata($user_id);
            if ( !$u ) return new WP_REST_Response( [], 404 );

            $data = array(
                'billing' => array(
                    'first_name' => get_user_meta($user_id, 'billing_first_name', true) ?: $u->first_name ?: $u->display_name,
                    'last_name' => get_user_meta($user_id, 'billing_last_name', true) ?: $u->last_name,
                    'company' => get_user_meta($user_id, 'billing_company', true),
                    'address_1' => get_user_meta($user_id, 'billing_address_1', true),
                    'city' => get_user_meta($user_id, 'billing_city', true),
                    'state' => get_user_meta($user_id, 'billing_state', true),
                    'postcode' => get_user_meta($user_id, 'billing_postcode', true),
                    'country' => get_user_meta($user_id, 'billing_country', true) ?: 'US',
                    'email' => $u->user_email,
                    'phone' => get_user_meta($user_id, 'billing_phone', true),
                ),
                'role' => reset( $u->roles ), // ADDED ROLE
                'dashboard_stats' => array(
                    'total_orders' => function_exists('wc_get_customer_order_count') ? wc_get_customer_order_count($user_id) : 0,
                    'total_spent' => function_exists('wc_get_customer_total_spent') ? wc_get_customer_total_spent($user_id) : 0
                )
            );
            return new WP_REST_Response( $data, 200 );
        } catch ( \Exception $e ) {
            return new WP_REST_Response( array('error' => $e->getMessage()), 500 );
        }
    }
}

if ( ! function_exists( 'wcs_get_brands_safe' ) ) {
    function wcs_get_brands_safe( $request ) {
        $cat_slug = $request->get_param( 'category' );
        @set_time_limit( 600 );
        $args = array(
            'taxonomy' => 'pwb-brand',
            'hide_empty' => true,
            'number' => 100,
            'orderby' => 'count',
            'order' => 'DESC'
        );
        $terms = get_terms( $args );
        $brands = array();
        if ( !is_wp_error($terms) ) {
            foreach($terms as $t) {
                $brands[] = array( 'id' => $t->term_id, 'name' => $t->name, 'slug' => $t->slug, 'count' => $t->count );
            }
        }
        return new WP_REST_Response( array( 'brands' => $brands ), 200 );
    }
}

if ( ! function_exists( 'wcs_get_products_safe' ) ) {
    function wcs_get_products_safe( $request ) {
        @ini_set('memory_limit', '2048M'); 
        @ini_set('max_execution_time', 1200);
        @set_time_limit(1200);

        try {
            $cat_slug = $request->get_param( 'category' );
            $brand_slug = $request->get_param( 'brand' );
            $min_price = $request->get_param( 'min_price' ); 
            $max_price = $request->get_param( 'max_price' );
            $search = $request->get_param( 'search' );
            $slug = $request->get_param( 'slug' );
            $page = $request->get_param( 'page' ) ? intval($request->get_param( 'page' )) : 1;

            if ( $slug ) {
                $decoded_slug = urldecode($slug);
                $args_q = array( 'post_type' => 'product', 'name' => $decoded_slug, 'posts_per_page' => 1, 'post_status' => array('publish', 'private'), 'fields' => 'ids' );
                $q = new WP_Query($args_q);
                $product_id = !empty($q->posts) ? $q->posts[0] : 0;
                
                 if ( !$product_id && function_exists('get_page_by_path') ) {
                     $post_obj = get_page_by_path( $decoded_slug, OBJECT, 'product' );
                     if ( $post_obj ) $product_id = $post_obj->ID;
                 }

                if ( !$product_id ) return new WP_REST_Response( array( 'products' => [], 'total' => 0 ), 200 );

                $p = wc_get_product($product_id);
                if (!$p) return new WP_REST_Response( array( 'products' => [], 'total' => 0 ), 200 );

                $img_url = wp_get_attachment_image_url( $p->get_image_id(), 'full' );
                $gallery_ids = $p->get_gallery_image_ids();
                $gallery = array();
                if ($img_url) $gallery[] = $img_url;
                foreach($gallery_ids as $id) $gallery[] = wp_get_attachment_image_url( $id, 'full' );

                $variations_data = array();
                if ( $p->is_type('variable') ) {
                    $available_variations = $p->get_available_variations();
                    foreach ( $available_variations as $variation ) {
                        $v_id = $variation['variation_id'];
                        $v_obj = wc_get_product($v_id);
                        if(!$v_obj) continue;
                        
                        $attr_strings = array();
                        foreach ( $v_obj->get_attributes() as $name => $val ) {
                            $val_name = $val;
                             if ( taxonomy_exists( $name ) ) {
                                 $term = get_term_by( 'slug', $val, $name );
                                 if ( $term ) $val_name = $term->name;
                             }
                             $attr_strings[] = $val_name; 
                        }
                        
                        $v_price = $v_obj->get_price() ?: $v_obj->get_regular_price() ?: 0;
                        $variations_data[] = array(
                            'id' => $v_id,
                            'name' => implode(', ', $attr_strings), 
                            'display_name' => !empty($attr_strings) ? implode(' - ', $attr_strings) : 'Variation #'.$v_id,
                            'price' => $v_price,
                            'regular_price' => $v_obj->get_regular_price(),
                            'image' => wp_get_attachment_image_url( $v_obj->get_image_id(), 'full' ),
                            'sku' => $v_obj->get_sku(),
                            'stock_status' => $v_obj->get_stock_status()
                        );
                    }
                }

                 $related_products = array();
                 $r_ids = wc_get_related_products($p->get_id(), 4);
                 foreach($r_ids as $r_id) {
                    $r_price = get_post_meta($r_id, '_price', true);
                    $r_reg_price = get_post_meta($r_id, '_regular_price', true);
                    $r_img_id = get_post_thumbnail_id($r_id);
                    $r_img = wp_get_attachment_image_url($r_img_id, 'medium');
                    
                    $related_products[] = array(
                        'id' => $r_id,
                        'name' => get_the_title($r_id),
                        'slug' => get_post_field('post_name', $r_id),
                        'price' => $r_price,
                        'raw_price' => $r_price,
                        'regular_price' => $r_reg_price,
                        'image' => $r_img ?: '',
                        'badge' => ''
                    );
                 }

                $price_val = $p->get_price();
                if ( ( ! $price_val ) && $p->is_type('variable') ) $price_val = $p->get_variation_price( 'min', true );
                if ( ! $price_val ) $price_val = $p->get_regular_price();

                // WHOLESALE GET
                $wholesale_price = get_post_meta($p->get_id(), 'wholesale_customer_wholesale_price', true); // TRYING COMMON META KEY

                $final_product = array(
                    'id' => $p->get_id(),
                    'name' => $p->get_name(),
                    'type' => $p->get_type(),
                    'price' => strip_tags($p->get_price_html()), 
                    'price_html' => $p->get_price_html(),
                    'raw_price' => $price_val,
                    'regular_price' => $p->get_regular_price(),
                    'sale_price' => $p->get_sale_price(),
                    'wholesale_price' => $wholesale_price, //ADDED
                    'image' => $img_url ?: '',
                    'gallery' => $gallery,
                    'slug' => $p->get_slug(),
                    'description' => $p->get_description(),
                    'short_description' => $p->get_short_description(),
                    'sku' => $p->get_sku(),
                    'stock_status' => $p->get_stock_status(),
                    'variations' => $variations_data,
                    'related_products' => $related_products
                );

                return new WP_REST_Response( array( 'products' => array($final_product), 'total' => 1 ), 200 );
            }

            global $wpdb;
            
            $sql = "SELECT DISTINCT p.ID, p.post_title, p.post_name, 
                    pm_price.meta_value as price, 
                    pm_reg.meta_value as regular_price,
                    pm_whole.meta_value as wholesale_price,
                    pm_img.meta_value as image_id
                    FROM {$wpdb->posts} p ";
            
            $sql .= " LEFT JOIN {$wpdb->postmeta} pm_price ON (p.ID = pm_price.post_id AND pm_price.meta_key = '_price') ";
            $sql .= " LEFT JOIN {$wpdb->postmeta} pm_reg ON (p.ID = pm_reg.post_id AND pm_reg.meta_key = '_regular_price') ";
            $sql .= " LEFT JOIN {$wpdb->postmeta} pm_whole ON (p.ID = pm_whole.post_id AND pm_whole.meta_key = 'wholesale_customer_wholesale_price') "; // JOIN WHOLESALE
            $sql .= " LEFT JOIN {$wpdb->postmeta} pm_thumb ON (p.ID = pm_thumb.post_id AND pm_thumb.meta_key = '_thumbnail_id') ";
            $sql .= " LEFT JOIN {$wpdb->postmeta} pm_img ON (pm_thumb.meta_value = pm_img.post_id AND pm_img.meta_key = '_wp_attached_file') ";

            $where_clauses = array();
            $where_clauses[] = "p.post_type = 'product'";
            $where_clauses[] = "p.post_status IN ('publish', 'private')"; 

            if ( $search ) {
                $s_esc = $wpdb->esc_like($search);
                $where_clauses[] = "(p.post_title LIKE '%$s_esc%')";
            }
            
            $debug_ids = 'none';

            if ( $cat_slug && $cat_slug !== 'all' ) {
                $term_row = $wpdb->get_row( $wpdb->prepare( 
                    "SELECT t.term_id, tt.term_taxonomy_id FROM {$wpdb->terms} t 
                     INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id 
                     WHERE t.slug = %s AND tt.taxonomy = 'product_cat' LIMIT 1", 
                    $cat_slug 
                ));

                if ( $term_row ) {
                    $target_term_id = $term_row->term_id;
                    $all_cats = $wpdb->get_results( "SELECT term_id, parent FROM {$wpdb->term_taxonomy} WHERE taxonomy = 'product_cat'" );
                    $children_map = array();
                    foreach ( $all_cats as $cat ) {
                        $children_map[ $cat->parent ][] = $cat->term_id;
                    }

                    $include_ids = array( $target_term_id );
                    $stack = array( $target_term_id );
                    
                    while ( !empty($stack) ) {
                        $current_id = array_pop($stack);
                        if ( isset($children_map[$current_id]) ) {
                            foreach ( $children_map[$current_id] as $child_id ) {
                                 $include_ids[] = $child_id;
                                 $stack[] = $child_id;
                            }
                        }
                    }
                    
                    $include_ids = array_unique($include_ids);
                    $ids_str = implode(',', array_map('intval', $include_ids));
                    
                     $sql .= " INNER JOIN {$wpdb->term_relationships} tr ON (p.ID = tr.object_id) ";
                     $sql .= " INNER JOIN {$wpdb->term_taxonomy} tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id) ";
                     $where_clauses[] = "tt.term_id IN ($ids_str)";
                } else {
                     $where_clauses[] = "0=1"; 
                }
            }
            
            if ( $brand_slug ) {
                 $sql .= " INNER JOIN {$wpdb->term_relationships} tr_b ON (p.ID = tr_b.object_id) ";
                 $sql .= " INNER JOIN {$wpdb->term_taxonomy} tt_b ON (tr_b.term_taxonomy_id = tt_b.term_taxonomy_id) ";
                 $sql .= " INNER JOIN {$wpdb->terms} t_b ON (tt_b.term_id = t_b.term_id) ";
                 
                 $where_clauses[] = "tt_b.taxonomy = 'pwb-brand'";
                 $where_clauses[] = $wpdb->prepare("t_b.slug = %s", $brand_slug);
            }

            if ( (isset($min_price) || isset($max_price)) && !$slug ) {
                $min = isset($min_price) ? floatval($min_price) : 0;
                $max = isset($max_price) && $max_price > 0 ? floatval($max_price) : 99999;
                 if ( $min > 0 || $max < 5000 ) {
                     $where_clauses[] = $wpdb->prepare("CAST(pm_price.meta_value AS SIGNED) BETWEEN %d AND %d", $min, $max);
                 }
            }

            $where_sql = " WHERE " . implode( " AND ", $where_clauses );
            $limit = 12;
            $offset = ($page - 1) * $limit;
            
            $sql .= $where_sql;
            $sql .= " GROUP BY p.ID ORDER BY p.post_date DESC LIMIT $limit OFFSET $offset ";

            $raw_products = $wpdb->get_results($sql);
            
            $count_sql = "SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p ";
            if ( ($cat_slug && $cat_slug !== 'all') ) { 
                 $count_sql .= " INNER JOIN {$wpdb->term_relationships} tr ON (p.ID = tr.object_id) ";
                 $count_sql .= " INNER JOIN {$wpdb->term_taxonomy} tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id) ";
            }
            if ( $brand_slug ) {
                 $count_sql .= " INNER JOIN {$wpdb->term_relationships} tr_b ON (p.ID = tr_b.object_id) ";
                 $count_sql .= " INNER JOIN {$wpdb->term_taxonomy} tt_b ON (tr_b.term_taxonomy_id = tt_b.term_taxonomy_id) ";
                 $count_sql .= " INNER JOIN {$wpdb->terms} t_b ON (tt_b.term_id = t_b.term_id) ";
            }
            if (isset($min) && ($min > 0 || $max < 5000)) {
                 $count_sql .= " LEFT JOIN {$wpdb->postmeta} pm_price ON (p.ID = pm_price.post_id AND pm_price.meta_key = '_price') ";
            }

            $count_sql .= $where_sql;
            $total_posts = $wpdb->get_var($count_sql);
            $total_pages = ceil($total_posts / $limit);

            $products = array();
            $upload_dir = wp_upload_dir();
            $base_url = $upload_dir['baseurl'];

            if ( !empty($raw_products) ) {
                foreach ( $raw_products as $rp ) {
                    $price_html = $rp->price ? '<span class="amount">$' . $rp->price . '</span>' : '';
                    
                    $img_url = '';
                    if ( $rp->image_id ) {
                        if (strpos($rp->image_id, 'http') === 0) {
                            $img_url = $rp->image_id;
                        } else {
                            $img_url = $base_url . '/' . $rp->image_id;
                        }
                    }

                    $products[] = array(
                        'id' => (int)$rp->ID,
                        'name' => $rp->post_title,
                        'slug' => $rp->post_name,
                        'price' => $rp->price,
                        'price_html' => $price_html,
                        'raw_price' => $rp->price, 
                        'regular_price' => $rp->regular_price,
                        'wholesale_price' => $rp->wholesale_price, // MAP IT
                        'sale_price' => '',
                        'image' => $img_url,
                        'type' => 'simple',
                        'badge' => ''
                    );
                }
            }
            
            return new WP_REST_Response( array( 
                'products' => $products, 
                'pages' => $total_pages,
                'total' => (int)$total_posts,
                'debug_ids' => $debug_ids,
                'debug_sql' => $sql
            ), 200 );

        } catch ( Throwable $e ) {
            return new WP_REST_Response( array( 'code' => 'server_error', 'message' => $e->getMessage(), 'data' => array( 'status' => 500 ) ), 500 );
        }
    }
}

if ( ! function_exists( 'wcs_register_user_safe' ) ) {
    function wcs_register_user_safe( $request ) {
        $params = $request->get_json_params();

        if ( empty($params['email']) || empty($params['password']) ) {
            return new WP_REST_Response( array( 'success' => false, 'message' => 'Email and Password are required.' ), 400 );
        }

        if ( email_exists( $params['email'] ) ) {
            return new WP_REST_Response( array( 'success' => false, 'message' => 'Email already registered.' ), 400 );
        }

        $user_id = wp_create_user( $params['email'], $params['password'], $params['email'] );

        if ( is_wp_error( $user_id ) ) {
             return new WP_REST_Response( array( 'success' => false, 'message' => $user_id->get_error_message() ), 500 );
        }

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

        wp_mail( get_option('admin_email'), 'New Wholesale Registration', "A new user {$params['email']} has registered and is awaiting approval.\nCompany: " . ($params['company_name'] ?? 'N/A') );

        if ( class_exists( 'Forminator_API' ) ) {
            $form_id = 2954; 
            $entry_data = array(
                array( 'name' => 'name-1', 'value' => $params['first_name'] . ' ' . $params['last_name'] ),
                array( 'name' => 'email-1', 'value' => $params['email'] ),
                array( 'name' => 'text-1', 'value' => $params['email'] ),
                array( 'name' => 'text-2', 'value' => $params['company_name'] ),
                array( 'name' => 'text-3', 'value' => $params['tax_id'] ),
                array( 'name' => 'phone-1', 'value' => $params['phone'] ),
                array( 'name' => 'address-1', 'value' => array(
                    'street_address' => $params['address_1'],
                    'city' => $params['city'],
                    'state' => $params['state'],
                    'zip' => $params['postcode'],
                    'country' => 'US'
                ))
            );
            Forminator_API::add_form_entry( $form_id, $entry_data );
        }

        return new WP_REST_Response( array( 
            'success' => true, 
            'message' => 'Registration successful! Your account is pending approval. You will receive an email once verified.' 
        ), 200 );
    }
}

if ( ! function_exists( 'wcs_login_user_safe' ) ) {
    function wcs_login_user_safe( $request ) {
        $creds = array(
            'user_login'    => $request->get_param( 'username' ),
            'user_password' => $request->get_param( 'password' ),
            'remember'      => true
        );

        $user = wp_signon( $creds, false );

        if ( is_wp_error( $user ) ) {
            return new WP_REST_Response( array( 'success' => false, 'message' => $user->get_error_message() ), 401 );
        }

        return new WP_REST_Response( array(
            'success' => true,
            'user' => array(
                'id' => $user->ID,
                'name' => $user->display_name,
                'email' => $user->user_email,
                'role' => reset( $user->roles ) // ADDED ROLE
            )
        ), 200 );
    }
    // --- SEARCH ENDPOINT (TURBO) ---
    add_action('rest_api_init', function() {
        // 1. LOGIN
        register_rest_route( 'wcs/v1', '/login', array(
            'methods'  => 'POST',
            'callback' => 'wcs_login_user_safe',
            'permission_callback' => '__return_true'
        ));

        // 2. APP DATA (Headless)
        register_rest_route( 'wcs/v1', '/app-data', array(
            'methods'  => 'GET',
            'callback' => 'wcs_get_initial_data_safe',
            'permission_callback' => '__return_true'
        ));

        // 3. SEARCH (Turbo)
        register_rest_route( 'wcs/v1', '/search', array(
            'methods'  => 'GET',
            'callback' => 'wcs_turbo_search',
            'permission_callback' => '__return_true'
        ));
    });
}

function wcs_turbo_search( $request ) {
    global $wpdb;
    $q = sanitize_text_field( $request->get_param( 'q' ) );
    
    if ( empty( $q ) || strlen( $q ) < 2 ) {
        return new WP_REST_Response( array(), 200 );
    }

    // SEARCH SQL: Joins for Price, Image, SKU, Brand
    // Searches: Title OR SKU OR Brand Name
    $sql = "
        SELECT DISTINCT p.ID, p.post_title, p.post_name,
        pm_price.meta_value as price,
        pm_reg.meta_value as regular_price,
        pm_img_file.meta_value as image_file
        FROM {$wpdb->posts} p
        LEFT JOIN {$wpdb->postmeta} pm_sku ON (p.ID = pm_sku.post_id AND pm_sku.meta_key = '_sku')
        LEFT JOIN {$wpdb->postmeta} pm_price ON (p.ID = pm_price.post_id AND pm_price.meta_key = '_price')
        LEFT JOIN {$wpdb->postmeta} pm_reg ON (p.ID = pm_reg.post_id AND pm_reg.meta_key = '_regular_price')
        -- Image Join
        LEFT JOIN {$wpdb->postmeta} pm_thumb ON (p.ID = pm_thumb.post_id AND pm_thumb.meta_key = '_thumbnail_id')
        LEFT JOIN {$wpdb->postmeta} pm_img_file ON (pm_thumb.meta_value = pm_img_file.post_id AND pm_img_file.meta_key = '_wp_attached_file')
        -- Brand Join (Optional)
        LEFT JOIN {$wpdb->term_relationships} tr ON (p.ID = tr.object_id)
        LEFT JOIN {$wpdb->term_taxonomy} tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id AND tt.taxonomy = 'pwb-brand')
        LEFT JOIN {$wpdb->terms} t ON (tt.term_id = t.term_id)
        
        WHERE p.post_type = 'product' 
        AND p.post_status = 'publish'
        AND (
            p.post_title LIKE %s 
            OR pm_sku.meta_value LIKE %s
            OR t.name LIKE %s
        )
        ORDER BY p.post_date DESC
        LIMIT 6
    ";

    $like = '%' . $wpdb->esc_like( $q ) . '%';
    $results = $wpdb->get_results( $wpdb->prepare( $sql, $like, $like, $like ) );
    
    $data = array();
    $upload_base = wp_upload_dir()['baseurl'];

    foreach($results as $r) {
        $img = '';
        if ( $r->image_file ) {
            if ( strpos($r->image_file, 'http') === 0 ) $img = $r->image_file;
            else $img = $upload_base . '/' . $r->image_file;
        }

        $data[] = array(
            'id' => $r->ID,
            'name' => $r->post_title,
            'slug' => $r->post_name,
            'image' => $img,
            'price' => $r->price ?: $r->regular_price,
            'regular_price' => $r->regular_price
        );
    }

    return new WP_REST_Response( $data, 200 );
}

if ( ! function_exists( 'wcs_get_initial_data_safe' ) ) {
    function wcs_get_initial_data_safe() {
        // 1. CACHE (Initial Data - 1 Hour)
        $cached_data = get_transient( 'wcs_initial_app_data_v1' );
        if ( false !== $cached_data ) {
             return new WP_REST_Response( $cached_data, 200 );
        }

        global $wpdb;

        // 2. MENU (Optimized)
        $order = array('DEVICES', 'E-JUICES', 'COILS / PODS', 'DISPOSABLES', 'HEMP', 'NICOTINE POUCHES', 'SMOKESHOP', 'VAPE DEALS', 'KRATOM/ MASHROOM', 'BRANDS');
        $menu = array();

        foreach ( $order as $cat_name ) {
            if ( $cat_name === 'BRANDS' || $cat_name === 'VAPE DEALS' ) {
                $menu[] = array( 'id' => rand(9000,9999), 'name' => $cat_name, 'type' => 'link', 'children' => array() );
                continue;
            }

            $term = get_term_by( 'name', $cat_name, 'product_cat' );
            if ( ! $term ) continue;

            // Only fetch level 2 children here (Fast)
            $children_terms = get_terms( array( 'taxonomy' => 'product_cat', 'parent' => $term->term_id, 'hide_empty' => true ) );
            $level2 = array();

            if ( !is_wp_error($children_terms) ) {
                foreach ( $children_terms as $child ) {
                    $level2[] = array( 'id' => $child->term_id, 'name' => $child->name, 'slug' => $child->slug, 'children' => [] );
                }
            }

            if ( $cat_name === 'HEMP' ) {
                $level2[] = array( 'id' => 8888, 'name' => 'COA', 'slug' => 'coa', 'type' => 'custom', 'children' => array() );
            }

            $menu[] = array( 'id' => $term->term_id, 'name' => $term->name, 'slug' => $term->slug, 'type' => 'category', 'children' => $level2 );
        }

        // 3. fetch_products_sql_v7 Helper
        $fetch_products_sql_v7 = function($slug) use ($wpdb) {
            $sql = "
                SELECT 
                    p.ID, p.post_title, p.post_name, p.post_date,
                    pm_price.meta_value as price,
                    pm_reg.meta_value as regular_price,
                    pm_whole.meta_value as wholesale_price,
                    pm_img_file.meta_value as image_file
                FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->term_relationships} tr ON (p.ID = tr.object_id)
                INNER JOIN {$wpdb->term_taxonomy} tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id)
                INNER JOIN {$wpdb->terms} t ON (tt.term_id = t.term_id)
                LEFT JOIN {$wpdb->postmeta} pm_price ON (p.ID = pm_price.post_id AND pm_price.meta_key = '_price')
                LEFT JOIN {$wpdb->postmeta} pm_reg ON (p.ID = pm_reg.post_id AND pm_reg.meta_key = '_regular_price')
                LEFT JOIN {$wpdb->postmeta} pm_whole ON (p.ID = pm_whole.post_id AND pm_whole.meta_key = 'wholesale_customer_wholesale_price')
                LEFT JOIN {$wpdb->postmeta} pm_thumb ON (p.ID = pm_thumb.post_id AND pm_thumb.meta_key = '_thumbnail_id')
                LEFT JOIN {$wpdb->postmeta} pm_img_file ON (pm_thumb.meta_value = pm_img_file.post_id AND pm_img_file.meta_key = '_wp_attached_file')
                WHERE p.post_type = 'product' 
                AND p.post_status = 'publish' 
                AND tt.taxonomy = 'product_cat'
                AND t.slug = %s
                ORDER BY p.post_date DESC
                LIMIT 4
            ";
            
            $results = $wpdb->get_results( $wpdb->prepare($sql, $slug) );
            $upload_base = wp_upload_dir()['baseurl'];
            
            $data = array();
            foreach($results as $r) {
                $img = '';
                if ( $r->image_file ) {
                    if ( strpos($r->image_file, 'http') === 0 ) {
                         $img = $r->image_file;
                    } else {
                         $img = $upload_base . '/' . $r->image_file;
                    }
                }
                $data[] = array(
                    'id' => (int)$r->ID,
                    'name' => $r->post_title,
                    'slug' => $r->post_name,
                    'image' => $img,
                    'price_html' => '<span class="amount">$' . ($r->price ?: $r->regular_price) . '</span>',
                    'raw_price' => $r->price,
                    'regular_price' => $r->regular_price,
                    'wholesale_price' => $r->wholesale_price,
                );
            }
            return $data;
        };

        // ONLY FETCH TRENDING (Hero) here
        $trending_data = $fetch_products_sql_v7('trending-products');

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

        $final_data = array(
            'site_name' => get_bloginfo( 'name' ),
            'logo'      => 'https://hotpink-camel-152562.hostingersite.com/wp-content/uploads/2026/01/Group-2104-1-2.png',
            'free_shipping_threshold' => (float) get_option( 'wcs_free_shipping_threshold', 0 ),
            'wholesale_rules' => $wholesale_rules,
            'menu'      => $menu,
            'trending'  => $trending_data, // Only Hero Data
        );

        set_transient( 'wcs_initial_app_data_v1', $final_data, HOUR_IN_SECONDS );
        return new WP_REST_Response( $final_data, 200 );
    }
}

if ( ! function_exists( 'wcs_get_secondary_data_safe' ) ) {
    function wcs_get_secondary_data_safe() {
        // CACHE (Secondary Data - 1 Hour)
        $cached_data = get_transient( 'wcs_secondary_app_data_v1' );
        if ( false !== $cached_data ) {
             return new WP_REST_Response( $cached_data, 200 );
        }
        
        global $wpdb;

        // Fetch Helper (Same as above, duplicated for isolation/transient safety)
        $fetch_products_sql_v7 = function($slug) use ($wpdb) {
             $sql = "
                SELECT 
                    p.ID, p.post_title, p.post_name, p.post_date,
                    pm_price.meta_value as price,
                    pm_reg.meta_value as regular_price,
                    pm_whole.meta_value as wholesale_price,
                    pm_img_file.meta_value as image_file
                FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->term_relationships} tr ON (p.ID = tr.object_id)
                INNER JOIN {$wpdb->term_taxonomy} tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id)
                INNER JOIN {$wpdb->terms} t ON (tt.term_id = t.term_id)
                LEFT JOIN {$wpdb->postmeta} pm_price ON (p.ID = pm_price.post_id AND pm_price.meta_key = '_price')
                LEFT JOIN {$wpdb->postmeta} pm_reg ON (p.ID = pm_reg.post_id AND pm_reg.meta_key = '_regular_price')
                LEFT JOIN {$wpdb->postmeta} pm_whole ON (p.ID = pm_whole.post_id AND pm_whole.meta_key = 'wholesale_customer_wholesale_price')
                LEFT JOIN {$wpdb->postmeta} pm_thumb ON (p.ID = pm_thumb.post_id AND pm_thumb.meta_key = '_thumbnail_id')
                LEFT JOIN {$wpdb->postmeta} pm_img_file ON (pm_thumb.meta_value = pm_img_file.post_id AND pm_img_file.meta_key = '_wp_attached_file')
                WHERE p.post_type = 'product' 
                AND p.post_status = 'publish' 
                AND tt.taxonomy = 'product_cat'
                AND t.slug = %s
                ORDER BY p.post_date DESC
                LIMIT 4
            ";
            $results = $wpdb->get_results( $wpdb->prepare($sql, $slug) );
            $upload_base = wp_upload_dir()['baseurl'];
            $data = array();
            foreach($results as $r) {
                $img = '';
                if ( $r->image_file ) {
                    if ( strpos($r->image_file, 'http') === 0 ) $img = $r->image_file;
                    else $img = $upload_base . '/' . $r->image_file;
                }
                $data[] = array(
                    'id' => (int)$r->ID,
                    'name' => $r->post_title,
                    'slug' => $r->post_name,
                    'image' => $img,
                    'price_html' => '<span class="amount">$' . ($r->price ?: $r->regular_price) . '</span>',
                    'raw_price' => $r->price,
                    'regular_price' => $r->regular_price,
                    'wholesale_price' => $r->wholesale_price,
                );
            }
            return $data;
        };

        $new_arrivals = $fetch_products_sql_v7('new-arrivals');
        $best_sellers = $fetch_products_sql_v7('best-sellers');

        // BRANDS SQL
        $brands_sql = "
            SELECT t.term_id, t.name, t.slug, tm.meta_value as image_id
            FROM {$wpdb->terms} t
            INNER JOIN {$wpdb->term_taxonomy} tt ON (t.term_id = tt.term_id)
            LEFT JOIN {$wpdb->termmeta} tm ON (t.term_id = tm.term_id AND tm.meta_key = 'pwb_brand_image')
            WHERE tt.taxonomy = 'pwb-brand' 
            AND tt.count > 0
            ORDER BY tt.count DESC
            LIMIT 20
        ";
        $brand_results = $wpdb->get_results( $brands_sql );
        $brand_data = array();
        
        foreach ( $brand_results as $b ) {
            $img = '';
            if ( $b->image_id ) {
                 $img = wp_get_attachment_image_url( $b->image_id, 'medium' );
            }
            if ($img) {
                 $brand_data[] = array( 'id' => (int)$b->term_id, 'name' => $b->name, 'slug' => $b->slug, 'image' => $img );
            }
            if(count($brand_data) >= 10) break;
        }

        $final_data = array(
            'brands'    => $brand_data,
            'new_arrivals' => $new_arrivals,
            'best_sellers' => $best_sellers
        );

        set_transient( 'wcs_secondary_app_data_v1', $final_data, HOUR_IN_SECONDS );
        return new WP_REST_Response( $final_data, 200 );
    }
}

    // Helper to format products for lists (DRY)
    if ( !function_exists('wcs_format_product_light') ) {
        function wcs_format_product_light($p_id) {
            $product = wc_get_product($p_id);
            if (!$product) return null;

            $img_id = $product->get_image_id();
            $img_url = $img_id ? wp_get_attachment_image_url($img_id, 'full') : '';
            
            $brand_name = '';
            $brand_terms = get_the_terms($p_id, 'pwb-brand');
            if ($brand_terms && !is_wp_error($brand_terms)) {
                $brand_name = $brand_terms[0]->name;
            }

            // Get Prices
            $price = $product->get_price();
            $reg_price = $product->get_regular_price();
            $wholesale_price = get_post_meta($p_id, 'wholesale_customer_wholesale_price', true);

            // Gallery (for hover)
            $gallery_ids = $product->get_gallery_image_ids();
            $gallery = array();
            if(!empty($gallery_ids)) {
                 $gallery[] = wp_get_attachment_image_url($gallery_ids[0], 'full');
            }

            return array(
                'id' => $p_id,
                'name' => $product->get_name(),
                'price_html' => $product->get_price_html(), 
                'raw_price' => $price,
                'regular_price' => $reg_price,
                'wholesale_price' => $wholesale_price,
                'image' => $img_url,
                'gallery' => $gallery,
                'brand' => $brand_name,
                'slug' => $product->get_slug()
            );
        }
    }
}

if ( ! function_exists( 'wcs_create_order_safe' ) ) {
    function wcs_create_order_safe( $request ) {
        error_reporting(0);
        @ini_set( 'display_errors', 0 );

        $params = $request->get_json_params();
        
        if ( !function_exists('wc_create_order') ) {
            return new WP_REST_Response( array( 'success' => false, 'message' => 'WooCommerce not active' ), 500 );
        }

        if ( empty($params['line_items']) ) {
            return new WP_REST_Response( array( 'success' => false, 'message' => 'No items in cart' ), 400 );
        }

        $required_fields = array(
            'first_name' => 'First name',
            'last_name'  => 'Last name',
            'email'      => 'Email address',
            'phone'      => 'Phone number',
            'address_1'  => 'Street address',
            'city'       => 'Town / City',
            'state'      => 'State',
            'postcode'   => 'ZIP Code',
            'country'    => 'Country'
        );

        foreach ( $required_fields as $key => $label ) {
            if ( empty($params['billing'][$key]) ) {
                 return new WP_REST_Response( array( 'success' => false, 'message' => "<strong>$label</strong> is a required field." ), 400 );
            }
        }
        
        if ( !is_email( $params['billing']['email'] ) ) {
            return new WP_REST_Response( array( 'success' => false, 'message' => "Invalid email address." ), 400 );
        }

        try {
            $order = wc_create_order();
            
            if ( is_wp_error($order) || !$order ) {
                 throw new Exception( 'Failed to create order object' );
            }

            foreach ( $params['line_items'] as $item ) {
                $product_id = intval($item['product_id']);
                $quantity = intval($item['quantity'] ?? 1);
                $variation_id = !empty($item['variation_id']) ? intval($item['variation_id']) : 0;
                
                $args = array();
                if ( $variation_id ) {
                    $args['variation_id'] = $variation_id;
                }
                
                $product = wc_get_product($product_id);
                if ( $product ) {
                    $order->add_product( $product, $quantity, $args );
                }
            }

            $address = array(
                'first_name' => $params['billing']['first_name'] ?? '',
                'last_name'  => $params['billing']['last_name'] ?? '',
                'email'      => $params['billing']['email'] ?? '',
                'phone'      => $params['billing']['phone'] ?? '',
                'address_1'  => $params['billing']['address_1'] ?? '',
                'city'       => $params['billing']['city'] ?? '',
                'state'      => $params['billing']['state'] ?? '',
                'postcode'   => $params['billing']['postcode'] ?? '',
                'country'    => $params['billing']['country'] ?? 'US',
            );
            $order->set_address( $address, 'billing' );
            $order->set_address( $address, 'shipping' ); 
            
            $payment_method = $params['payment_method'] ?? 'cod';
            $order->set_payment_method( $payment_method );
            $order->set_payment_method_title( $payment_method === 'cod' ? 'Cash on Delivery' : 'Credit Card' );

            $order->calculate_totals();
            $order->update_status( 'processing', 'Order created via React App' );

            return new WP_REST_Response( array(
                'success' => true,
                'order_id' => $order->get_id(),
                'order_key' => $order->get_order_key(),
                'total' => $order->get_total()
            ), 200 );

        } catch ( \Throwable $e ) {
            return new WP_REST_Response( array( 'success' => false, 'message' => $e->getMessage() ), 500 );
        }
    }
}

if ( ! function_exists( 'wcs_get_form_data_safe' ) ) {
    function wcs_get_form_data_safe( $request ) {
        $id = intval( $request->get_param( 'id' ) );
        if ( !$id ) return new WP_REST_Response( array('success' => false, 'message' => 'Form ID missing'), 400 );

        if ( ! class_exists( 'Forminator' ) || ! class_exists( 'Forminator_Base_Form_Model' ) ) {
            return new WP_REST_Response( array('success' => false, 'message' => 'Forminator Not Active'), 500 );
        }

        $model = Forminator_Base_Form_Model::get_model( $id );
        if ( !$model ) {
            return new WP_REST_Response( array('success' => false, 'message' => 'Form not found'), 404 );
        }

        return new WP_REST_Response( array(
            'success' => true,
            'id' => $id,
            'title' => $model->name,
            'fields' => $model->get_fields(),
            'settings' => $model->settings
        ), 200 );
    }
}

if ( ! function_exists( 'wcs_handle_form_submit_safe' ) ) {
    function wcs_handle_form_submit_safe( $request ) {
        $params = $request->get_params(); 
        $files = $request->get_file_params(); 
        
        $form_id = intval( $params['form_id'] ?? 0 );
        
        if ( isset($params['data']) && is_string($params['data']) ) {
            $decoded = json_decode($params['data'], true);
            if (is_array($decoded)) {
                $params = array_merge($params, $decoded);
            }
        }

        if ( !$form_id ) {
            return new WP_REST_Response( array('success' => false, 'message' => 'Form ID missing'), 400 );
        }

        if ( ! class_exists( 'Forminator_API' ) ) {
            return new WP_REST_Response( array('success' => false, 'message' => 'Forminator API not found'), 500 );
        }

        $entry_data = array();

        foreach ( $params as $key => $val ) {
            if ( in_array($key, ['form_id', '_wpnonce', '_locale']) ) continue;
            $entry_data[] = array( 'name' => $key, 'value' => $val );
        }

        if ( !empty($files) ) {
            require_once( ABSPATH . 'wp-admin/includes/image.php' );
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
            require_once( ABSPATH . 'wp-admin/includes/media.php' );

            foreach ( $files as $key => $file ) {
                $attachment_id = media_handle_upload( $key, 0 );
                if ( is_wp_error( $attachment_id ) ) {
                    return new WP_REST_Response( array( 'success' => false, 'message' => 'Upload Error: ' . $attachment_id->get_error_message() ), 400 );
                }
                $img_url = wp_get_attachment_url($attachment_id);
                $entry_data[] = array( 'name' => $key, 'value' => $img_url );
            }
        }

        $entry = Forminator_API::add_form_entry( $form_id, $entry_data );

        if ( is_wp_error( $entry ) ) {
            return new WP_REST_Response( array( 'success' => false, 'message' => $entry->get_error_message() ), 400 );
        }

        return new WP_REST_Response( array( 'success' => true, 'entry_id' => $entry ), 200 );
    }
}
