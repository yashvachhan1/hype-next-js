<?php
/**
 * SNIPPET 2: INITIAL DATA (Menu, Hero, Site Info)
 * Description: Extremely fast endpoint for above-the-fold content. 
 * Endpoint: /wcs/v1/app-data-initial
 */

add_action( 'rest_api_init', function() {
    register_rest_route( 'wcs/v1', '/app-data-initial', array(
        'methods'  => 'GET',
        'callback' => 'wcs_get_initial_data_safe',
        'permission_callback' => '__return_true'
    ));
    // Keep legacy alias for safety
    register_rest_route( 'wcs/v1', '/app-data', array(
        'methods'  => 'GET',
        'callback' => 'wcs_get_initial_data_safe',
        'permission_callback' => '__return_true'
    ));
});

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

        // 3. HERO TRENDING PRODUCTS
        $fetch_products_sql_v7 = function($slug) use ($wpdb) {
            $sql = "SELECT p.ID, p.post_title, p.post_name, pm_img_file.meta_value as image_file
                FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->term_relationships} tr ON (p.ID = tr.object_id)
                INNER JOIN {$wpdb->term_taxonomy} tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id)
                INNER JOIN {$wpdb->terms} t ON (tt.term_id = t.term_id)
                LEFT JOIN {$wpdb->postmeta} pm_thumb ON (p.ID = pm_thumb.post_id AND pm_thumb.meta_key = '_thumbnail_id')
                LEFT JOIN {$wpdb->postmeta} pm_img_file ON (pm_thumb.meta_value = pm_img_file.post_id AND pm_img_file.meta_key = '_wp_attached_file')
                WHERE p.post_type = 'product' AND p.post_status = 'publish' AND tt.taxonomy = 'product_cat' AND t.slug = %s
                ORDER BY p.post_date DESC LIMIT 4";
            
            $results = $wpdb->get_results( $wpdb->prepare($sql, $slug) );
            $upload_base = wp_upload_dir()['baseurl'];
            $data = array();
            foreach($results as $r) {
                $img = '';
                if ( $r->image_file ) {
                    $img = (strpos($r->image_file, 'http') === 0) ? $r->image_file : $upload_base . '/' . $r->image_file;
                }
                $data[] = array( 'id' => (int)$r->ID, 'name' => $r->post_title, 'slug' => $r->post_name, 'image' => $img );
            }
            return $data;
        };

        $trending_data = $fetch_products_sql_v7('trending-products');

         // 4. WHOLESALE RULES (Crucial for Frontend)
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
            'trending'  => $trending_data, 
        );

        set_transient( 'wcs_initial_app_data_v1', $final_data, HOUR_IN_SECONDS );
        return new WP_REST_Response( $final_data, 200 );
    }
}
