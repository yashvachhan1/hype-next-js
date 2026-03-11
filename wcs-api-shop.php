<?php
/**
 * SNIPPET: MODULAR SHOP API
 * Description: Main Catalog, Search, and Filtering.
 * Endpoint: /wcs/v1/shop/products
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action( 'rest_api_init', function() {
    register_rest_route( 'wcs/v1', '/products', array(
        'methods'  => 'GET',
        'callback' => 'wcs_get_shop_products',
        'permission_callback' => '__return_true'
    ));
});

function wcs_get_shop_products( $request ) {
    global $wpdb;
    $params = $request->get_params();
    
    $cat_slug = isset($params['category']) ? $params['category'] : '';
    $brand_slug = isset($params['brand']) ? $params['brand'] : '';
    $search = isset($params['search']) ? $params['search'] : '';
    $page = isset($params['page']) ? intval($params['page']) : 1;
    $limit = 12; // Fixed Limit
    $offset = ($page - 1) * $limit;

    // --- MAIN LIST QUERY ---
    $sql_select = "SELECT DISTINCT p.ID, p.post_title, p.post_name, 
            pm_price.meta_value as price, 
            pm_reg.meta_value as regular_price,
            pm_img.meta_value as image_id ";
    
    $sql_from = "FROM {$wpdb->posts} p ";
    $sql_joins = "";
    
    // Joins for Meta
    $sql_joins .= " LEFT JOIN {$wpdb->postmeta} pm_price ON (p.ID = pm_price.post_id AND pm_price.meta_key = '_price') ";
    $sql_joins .= " LEFT JOIN {$wpdb->postmeta} pm_reg ON (p.ID = pm_reg.post_id AND pm_reg.meta_key = '_regular_price') ";
    $sql_joins .= " LEFT JOIN {$wpdb->postmeta} pm_thumb ON (p.ID = pm_thumb.post_id AND pm_thumb.meta_key = '_thumbnail_id') ";
    $sql_joins .= " LEFT JOIN {$wpdb->postmeta} pm_img ON (pm_thumb.meta_value = pm_img.post_id AND pm_img.meta_key = '_wp_attached_file') ";
    
    // SKU Join for Search
    $sql_joins .= " LEFT JOIN {$wpdb->postmeta} pm_sku ON (p.ID = pm_sku.post_id AND pm_sku.meta_key = '_sku') ";

    $where_clauses = ["p.post_type = 'product'", "p.post_status = 'publish'"];

    if ( $search ) {
        $s_esc = $wpdb->esc_like($search);
        // Search Title OR SKU
        $where_clauses[] = "(p.post_title LIKE '%$s_esc%' OR pm_sku.meta_value LIKE '%$s_esc%')";
    }

    // RECURSIVE CATEGORY LOGIC (Optimized with Cache)
    if ( $cat_slug && $cat_slug !== 'all' ) {
        $term_row = $wpdb->get_row( $wpdb->prepare( 
            "SELECT t.term_id FROM {$wpdb->terms} t INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id WHERE t.slug = %s AND tt.taxonomy = 'product_cat' LIMIT 1", 
            $cat_slug 
        ));

        if ( $term_row ) {
            $target_id = $term_row->term_id;
            
            // Cache Family Tree
            $cache_key = 'wcs_cat_family_' . $target_id;
            $ids_str = get_transient( $cache_key );

            if ( false === $ids_str ) {
                $all_cats = $wpdb->get_results( "SELECT term_id, parent FROM {$wpdb->term_taxonomy} WHERE taxonomy = 'product_cat'" );
                $children_map = [];
                foreach ( $all_cats as $cat ) $children_map[ $cat->parent ][] = $cat->term_id;

                $include_ids = [ $target_id ];
                $stack = [ $target_id ];
                while ( !empty($stack) ) {
                    $curr = array_pop($stack);
                    if ( isset($children_map[$curr]) ) {
                        foreach ( $children_map[$curr] as $child ) {
                                $include_ids[] = $child;
                                $stack[] = $child;
                        }
                    }
                }
                $ids_str = implode(',', array_map('intval', array_unique($include_ids)));
                set_transient( $cache_key, $ids_str, HOUR_IN_SECONDS );
            }
            
            $sql_joins .= " INNER JOIN {$wpdb->term_relationships} tr ON (p.ID = tr.object_id) ";
            $sql_joins .= " INNER JOIN {$wpdb->term_taxonomy} tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id) ";
            $where_clauses[] = "tt.term_id IN ($ids_str)";
        } else {
            $where_clauses[] = "0=1"; // Cat not found
        }
    }

    if ( $brand_slug ) {
            $sql_joins .= " INNER JOIN {$wpdb->term_relationships} tr_b ON (p.ID = tr_b.object_id) ";
            $sql_joins .= " INNER JOIN {$wpdb->term_taxonomy} tt_b ON (tr_b.term_taxonomy_id = tt_b.term_taxonomy_id) ";
            $sql_joins .= " INNER JOIN {$wpdb->terms} t_b ON (tt_b.term_id = t_b.term_id) ";
            $where_clauses[] = "tt_b.taxonomy = 'pwb-brand'";
            $where_clauses[] = $wpdb->prepare("t_b.slug = %s", $brand_slug);
    }

    $where_sql = " WHERE " . implode( " AND ", $where_clauses );
    
    // Final SQL
    $query_sql = $sql_select . $sql_from . $sql_joins . $where_sql . " GROUP BY p.ID ORDER BY p.post_date DESC LIMIT $limit OFFSET $offset";
    
    $results = $wpdb->get_results($query_sql);

    // Count Query
    $count_sql = "SELECT COUNT(DISTINCT p.ID) " . $sql_from . $sql_joins . $where_sql;
    $total_products = $wpdb->get_var($count_sql);

    $products = array();
    $upload_dir = wp_upload_dir();
    $base_url = $upload_dir['baseurl'];

    foreach($results as $r) {
            $img_url = '';
            if ( $r->image_id ) {
                $img_url = (strpos($r->image_id, 'http') === 0) ? $r->image_id : $base_url . '/' . $r->image_id;
            }
            $products[] = array(
            'id' => (int)$r->ID,
            'name' => $r->post_title,
            'slug' => $r->post_name,
            'price_html' => '<span class="amount">$' . ($r->price ?: $r->regular_price) . '</span>',
            'image' => $img_url,
            'type' => 'simple'
            );
    }

    return new WP_REST_Response( array( 
        'products' => $products, 
        'pagination' => array( 
            'total' => (int)$total_products, 
            'per_page' => $limit, 
            'current_page' => $page, 
            'total_pages' => ceil($total_products / $limit) 
        ) 
    ), 200 );
}
