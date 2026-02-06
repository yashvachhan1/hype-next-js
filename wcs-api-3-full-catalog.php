<?php
/**
 * SNIPPET 3: FULL CATALOG (Brands, Products, Search)
 * Description: Heavy data endpoints loaded in background.
 * Endpoints: /wcs/v1/app-data-secondary, /products, /brands, /search
 */

add_action( 'rest_api_init', function() {
    register_rest_route( 'wcs/v1', '/app-data-secondary', array(
        'methods'  => 'GET',
        'callback' => 'wcs_get_secondary_data_safe',
        'permission_callback' => '__return_true'
    ));
    register_rest_route( 'wcs/v1', '/products', array(
        'methods'  => 'GET',
        'callback' => 'wcs_get_products_safe',
        'permission_callback' => '__return_true',
    ));
    register_rest_route( 'wcs/v1', '/brands', array(
        'methods'  => 'GET',
        'callback' => 'wcs_get_brands_safe',
        'permission_callback' => '__return_true',
    ));
    register_rest_route( 'wcs/v1', '/search', array(
        'methods'  => 'GET',
        'callback' => 'wcs_turbo_search',
        'permission_callback' => '__return_true'
    ));
});

if ( ! function_exists( 'wcs_get_secondary_data_safe' ) ) {
    function wcs_get_secondary_data_safe() {
        $cached_data = get_transient( 'wcs_secondary_app_data_v1' );
        if ( false !== $cached_data ) return new WP_REST_Response( $cached_data, 200 );
        
        global $wpdb;

        // Fetch Helper
        $fetch_products_v7 = function($slug) use ($wpdb) {
             $sql = "SELECT p.ID, p.post_title, p.post_name, 
                    pm_price.meta_value as price, pm_reg.meta_value as regular_price, 
                    pm_img.meta_value as image_file
                FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->term_relationships} tr ON (p.ID = tr.object_id)
                INNER JOIN {$wpdb->term_taxonomy} tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id)
                INNER JOIN {$wpdb->terms} t ON (tt.term_id = t.term_id)
                LEFT JOIN {$wpdb->postmeta} pm_price ON (p.ID = pm_price.post_id AND pm_price.meta_key = '_price')
                LEFT JOIN {$wpdb->postmeta} pm_reg ON (p.ID = pm_reg.post_id AND pm_reg.meta_key = '_regular_price')
                LEFT JOIN {$wpdb->postmeta} pm_thumb ON (p.ID = pm_thumb.post_id AND pm_thumb.meta_key = '_thumbnail_id')
                LEFT JOIN {$wpdb->postmeta} pm_img ON (pm_thumb.meta_value = pm_img.post_id AND pm_img.meta_key = '_wp_attached_file')
                WHERE p.post_type = 'product' AND p.post_status = 'publish' AND tt.taxonomy = 'product_cat' AND t.slug = %s
                ORDER BY p.post_date DESC LIMIT 4";
            
            $results = $wpdb->get_results( $wpdb->prepare($sql, $slug) );
            $upload_base = wp_upload_dir()['baseurl'];
            $data = array();
            foreach($results as $r) {
                $img = ($r->image_file) ? ((strpos($r->image_file, 'http') === 0) ? $r->image_file : $upload_base . '/' . $r->image_file) : '';
                $data[] = array( 'id' => (int)$r->ID, 'name' => $r->post_title, 'slug' => $r->post_name, 'image' => $img, 'price_html' => '<span class="amount">$' . ($r->price ?: $r->regular_price) . '</span>' );
            }
            return $data;
        };

        $new_arrivals = $fetch_products_v7('new-arrivals');
        $best_sellers = $fetch_products_v7('best_sellers');

        // BRANDS SQL
        $brands_sql = "SELECT t.term_id, t.name, t.slug, tm.meta_value as image_id FROM {$wpdb->terms} t
            INNER JOIN {$wpdb->term_taxonomy} tt ON (t.term_id = tt.term_id)
            LEFT JOIN {$wpdb->termmeta} tm ON (t.term_id = tm.term_id AND tm.meta_key = 'pwb_brand_image')
            WHERE tt.taxonomy = 'pwb-brand' AND tt.count > 0 ORDER BY tt.count DESC LIMIT 20";
        $results = $wpdb->get_results($brands_sql);
        $brands = array();
        foreach($results as $r) {
             $img = $r->image_id ? wp_get_attachment_image_url($r->image_id, 'medium') : '';
             if($img) $brands[] = array('id'=>(int)$r->term_id, 'name'=>$r->name, 'slug'=>$r->slug, 'image'=>$img);
             if(count($brands)>=10) break;
        }

        $final_data = array( 'brands' => $brands, 'new_arrivals' => $new_arrivals, 'best_sellers' => $best_sellers );
        set_transient( 'wcs_secondary_app_data_v1', $final_data, HOUR_IN_SECONDS );
        return new WP_REST_Response( $final_data, 200 );
    }
}

if ( ! function_exists( 'wcs_get_products_safe' ) ) {
    function wcs_get_products_safe( $request ) {
        global $wpdb;
        $params = $request->get_params(); // Handle both GET and POST params
        $cat_slug = isset($params['category']) ? $params['category'] : '';
        $brand_slug = isset($params['brand']) ? $params['brand'] : '';
        $search = isset($params['search']) ? $params['search'] : '';
        $slug = isset($params['slug']) ? $params['slug'] : '';
        $page = isset($params['page']) ? intval($params['page']) : 1;
        $limit = 12; // User requested 12 per page
        $offset = ($page - 1) * $limit;

        // SINGLE PRODUCT LOGIC
        if ( $slug ) {
            $args = array( 'name' => $slug, 'post_type' => 'product', 'post_status' => 'publish', 'numberposts' => 1 );
            $posts = get_posts($args);
            if (empty($posts)) return new WP_REST_Response( array('success' => false, 'message' => 'Product not found'), 404 );
            
            $p = wc_get_product($posts[0]->ID);
            $pid = $p->get_id();

            // 1. Raw Meta for Bypass
            $price = get_post_meta($pid, '_price', true);
            $reg_price = get_post_meta($pid, '_regular_price', true);
            $wholesale_price = get_post_meta($pid, 'wholesale_customer_wholesale_price', true);
            $stock_status = get_post_meta($pid, '_stock_status', true);
            
            // 2. Image Logic (Robust Fallback)
            $img_id = get_post_meta($pid, '_thumbnail_id', true);
            $img_url = $img_id ? wp_get_attachment_image_url($img_id, 'full') : '';
            
            // Gallery
            $gallery = $p->get_gallery_image_ids();
            $gallery_urls = [];
            foreach($gallery as $gid) $gallery_urls[] = wp_get_attachment_image_url($gid, 'full');

            if (empty($img_url) && !empty($gallery_urls)) {
                $img_url = $gallery_urls[0]; // Fallback to first gallery image
            }

            // 3. Brand
            $brand_name = 'Hype Distribution';
            $terms = get_the_terms($pid, 'pwb-brand');
            if ($terms && !is_wp_error($terms)) $brand_name = $terms[0]->name;

            // 4. Variations (Raw Fetch to avoid filtering)
            $variations_data = [];
            $product_type = $p->get_type();
            
            if ($product_type === 'variable' || $product_type === 'variation') {
                // Fetch children manually to ensure we get them even if "hidden"
                $args_vars = array('post_parent' => $pid, 'post_type' => 'product_variation', 'numberposts' => -1, 'post_status' => 'publish');
                $children_posts = get_posts($args_vars);
                
                foreach($children_posts as $child) {
                    $vid = $child->ID;
                    $v_price = get_post_meta($vid, '_price', true);
                    $v_reg = get_post_meta($vid, '_regular_price', true);
                    $v_stock = get_post_meta($vid, '_stock_status', true);
                    $v_img_id = get_post_meta($vid, '_thumbnail_id', true);
                    $v_img = $v_img_id ? wp_get_attachment_image_url($v_img_id, 'full') : $img_url; // Fallback to parent
                    
                    // Attributes
                    $v_obj = wc_get_product($vid);
                    $v_atts = $v_obj ? $v_obj->get_attributes() : [];
                    $v_name = $v_obj ? wc_get_formatted_variation( $v_obj, true ) : $child->post_title;

                    $variations_data[] = array(
                        'id' => $vid,
                        'attributes' => $v_atts,
                        'price' => $v_price,
                        'regular_price' => $v_reg,
                        'price_html' => $v_obj ? $v_obj->get_price_html() : '$'.$v_price,
                        'image' => $v_img,
                        'display_name' => $v_name,
                        'stock_status' => $v_stock,
                        'max_qty' => $v_obj ? $v_obj->get_max_purchase_quantity() : -1
                    );
                }
                if (!empty($variations_data)) $product_type = 'variable'; // Force type
            }

            // Fallback Image from Variation if Parent has none
            if (empty($img_url) && !empty($variations_data)) {
                $img_url = $variations_data[0]['image'];
            }

            $data = array(
                'id' => $pid,
                'name' => $p->get_name(),
                'slug' => $p->get_slug(),
                'type' => $product_type, // Important for Frontend
                'price_html' => $p->get_price_html(),
                'regular_price' => $reg_price,
                'price' => $price,
                'raw_price' => $price,
                'wholesale_price' => $wholesale_price,
                'brand' => $brand_name,
                'description' => $p->get_description(),
                'short_description' => $p->get_short_description(),
                'stock_status' => $stock_status,
                'images' => empty($img_url) ? [] : array_merge([$img_url], $gallery_urls),
                'image' => $img_url, // Main Image
                'gallery' => $gallery_urls,
                'attributes' => array(),
                'variations' => $variations_data,
                'related_products' => []
            );
            
            // Attributes (Parent)
            foreach($p->get_attributes() as $attr) {
                $data['attributes'][] = array(
                    'name' => wc_attribute_label($attr->get_name()),
                    'options' => $attr->get_options()
                );
            }
            
            // Related
            $r_ids = wc_get_related_products($pid, 4);
            foreach($r_ids as $rid) {
                $rp = wc_get_product($rid);
                if($rp) $data['related_products'][] = array(
                    'id'=>$rp->get_id(), 
                    'name'=>$rp->get_name(), 
                    'slug'=>$rp->get_slug(), 
                    'image'=>wp_get_attachment_image_url($rp->get_image_id(), 'medium'), 
                    'price_html'=>$rp->get_price_html()
                );
            }

            return new WP_REST_Response( array('products' => array($data)), 200 );
        }

        // --- MAIN LIST QUERY ---
        $sql_select = "SELECT DISTINCT p.ID, p.post_title, p.post_name, 
                pm_price.meta_value as price, 
                pm_reg.meta_value as regular_price,
                pm_whole.meta_value as wholesale_price,
                pm_img.meta_value as image_id ";
        
        $sql_from = "FROM {$wpdb->posts} p ";
        $sql_joins = "";
        
        $sql_joins .= " LEFT JOIN {$wpdb->postmeta} pm_price ON (p.ID = pm_price.post_id AND pm_price.meta_key = '_price') ";
        $sql_joins .= " LEFT JOIN {$wpdb->postmeta} pm_reg ON (p.ID = pm_reg.post_id AND pm_reg.meta_key = '_regular_price') ";
        $sql_joins .= " LEFT JOIN {$wpdb->postmeta} pm_whole ON (p.ID = pm_whole.post_id AND pm_whole.meta_key = 'wholesale_customer_wholesale_price') ";
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

        // RECURSIVE CATEGORY FIX
        if ( $cat_slug && $cat_slug !== 'all' ) {
            $term_row = $wpdb->get_row( $wpdb->prepare( 
                "SELECT t.term_id FROM {$wpdb->terms} t INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id WHERE t.slug = %s AND tt.taxonomy = 'product_cat' LIMIT 1", 
                $cat_slug 
            ));

            if ( $term_row ) {
                $target_id = $term_row->term_id;
                
                // PERFORMANCE FIX: Cache the family tree for 1 hour
                $cache_key = 'wcs_cat_family_' . $target_id;
                $ids_str = get_transient( $cache_key );

                if ( false === $ids_str ) {
                    // Get all children
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
        
        // Final SQL Construction
        $query_sql = $sql_select . $sql_from . $sql_joins . $where_sql . " GROUP BY p.ID ORDER BY p.post_date DESC LIMIT $limit OFFSET $offset";
        
        $results = $wpdb->get_results($query_sql);

        // Count Query (Slightly simplified to avoid perf hit, but accurate enough)
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
                'raw_price' => $r->price,
                'wholesale_price' => $r->wholesale_price,
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
}

if ( ! function_exists( 'wcs_get_brands_safe' ) ) {
    function wcs_get_brands_safe( $request ) {
        global $wpdb;
        $params = $request->get_params();
        $cat_slug = isset($params['category']) ? $params['category'] : '';

        if ( $cat_slug && $cat_slug !== 'all' ) {
            // 1. Recursive Category Lookup
            $term_row = $wpdb->get_row( $wpdb->prepare( 
                "SELECT t.term_id FROM {$wpdb->terms} t INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id WHERE t.slug = %s AND tt.taxonomy = 'product_cat' LIMIT 1", 
                $cat_slug 
            ));

            if (!$term_row) return new WP_REST_Response([], 200);

            $target_id = $term_row->term_id;
            
            // PERFORMANCE FIX: Cache the family tree for 1 hour
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

            // 2. Query: Brands that share objects with ANY of these Categories
            $sql = "SELECT DISTINCT t.term_id, t.name, t.slug, COUNT(p.ID) as count 
                    FROM {$wpdb->terms} t
                    INNER JOIN {$wpdb->term_taxonomy} tt ON (t.term_id = tt.term_id)
                    INNER JOIN {$wpdb->term_relationships} tr_brand ON (tt.term_taxonomy_id = tr_brand.term_taxonomy_id)
                    INNER JOIN {$wpdb->posts} p ON (tr_brand.object_id = p.ID)
                    INNER JOIN {$wpdb->term_relationships} tr_cat ON (p.ID = tr_cat.object_id)
                    INNER JOIN {$wpdb->term_taxonomy} tt_cat ON (tr_cat.term_taxonomy_id = tt_cat.term_taxonomy_id)
                    WHERE tt.taxonomy = 'pwb-brand' 
                    AND p.post_status = 'publish' 
                    AND tt_cat.term_id IN ($ids_str)
                    GROUP BY t.term_id 
                    ORDER BY count DESC LIMIT 50";
            
            $results = $wpdb->get_results( $sql );
            
            $data = [];
            foreach($results as $r) {
                 $img_id = get_term_meta( $r->term_id, 'pwb_brand_image', true );
                 $img = $img_id ? wp_get_attachment_image_url($img_id, 'medium') : '';
                 $data[] = array( 'id' => (int)$r->term_id, 'name' => $r->name, 'slug' => $r->slug, 'image' => $img, 'count' => (int)$r->count );
            }
            return new WP_REST_Response( array('brands' => $data), 200 );
        }

        // Default: All Brands
        $terms = get_terms( array( 'taxonomy' => 'pwb-brand', 'hide_empty' => true, 'number' => 50 ) );
        if ( is_wp_error( $terms ) ) return new WP_REST_Response( array('brands' => []), 200 );
        $data = [];
        foreach($terms as $t) {
             $img_id = get_term_meta( $t->term_id, 'pwb_brand_image', true );
             $img = $img_id ? wp_get_attachment_image_url($img_id, 'medium') : '';
             $data[] = array( 'id' => $t->term_id, 'name' => $t->name, 'slug' => $t->slug, 'image' => $img, 'count' => $t->count );
        }
        return new WP_REST_Response( array('brands' => $data), 200 );
    }
}

if ( ! function_exists( 'wcs_turbo_search' ) ) {
    function wcs_turbo_search( $request ) {
        global $wpdb;
        $q = $request->get_param('q');
        if ( strlen($q) < 3 ) return new WP_REST_Response( [], 200 );
        
        // Search Title OR SKU
        $sql = "SELECT DISTINCT p.ID, p.post_title, p.post_name 
                FROM {$wpdb->posts} p 
                LEFT JOIN {$wpdb->postmeta} pm ON (p.ID = pm.post_id AND pm.meta_key = '_sku')
                WHERE p.post_type = 'product' 
                AND p.post_status = 'publish' 
                AND (p.post_title LIKE %s OR pm.meta_value LIKE %s) 
                LIMIT 10";
        
        $like = '%' . $wpdb->esc_like($q) . '%';
        $results = $wpdb->get_results( $wpdb->prepare($sql, $like, $like) );
        
        $data = [];
        foreach($results as $r) {
             $p = wc_get_product($r->ID);
             $img = $p ? wp_get_attachment_image_url($p->get_image_id(), 'thumbnail') : '';
             $data[] = array( 'id' => $r->ID, 'name' => $r->post_title, 'slug' => $r->post_name, 'image' => $img, 'price_html' => $p->get_price_html() );
        }
        return new WP_REST_Response( $data, 200 );
    }
}
