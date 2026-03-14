<?php
/**
 * SNIPPET: MODULAR HOME API - TRENDING
 * Description: Fetches Trending Products for Home Page.
 * Endpoint: /wcs/v1/home/trending
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action( 'rest_api_init', function() {
    register_rest_route( 'wcs/v1', '/home/trending', array(
        'methods'  => 'GET',
        'callback' => 'wcs_get_home_trending',
        'permission_callback' => '__return_true'
    ));
});

function wcs_get_home_trending() {
    $cache_key = 'wcs_home_trending_v2';
    $cached = get_transient($cache_key);
    if ($cached !== false) return new WP_REST_Response($cached, 200);

    global $wpdb;

    // Fetch 8 Trending Products
    // Using GROUP BY to ensure uniqueness even if joins multiply rows
    $sql = "SELECT p.ID, p.post_title, p.post_name, pm_price.meta_value as price, pm_reg.meta_value as regular_price, 
            pm_img.meta_value as image_file
        FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->term_relationships} tr ON (p.ID = tr.object_id)
        INNER JOIN {$wpdb->term_taxonomy} tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id)
        INNER JOIN {$wpdb->terms} t ON (tt.term_id = t.term_id)
        LEFT JOIN {$wpdb->postmeta} pm_price ON (p.ID = pm_price.post_id AND pm_price.meta_key = '_price')
        LEFT JOIN {$wpdb->postmeta} pm_reg ON (p.ID = pm_reg.post_id AND pm_reg.meta_key = '_regular_price')
        LEFT JOIN {$wpdb->postmeta} pm_thumb ON (p.ID = pm_thumb.post_id AND pm_thumb.meta_key = '_thumbnail_id')
        LEFT JOIN {$wpdb->postmeta} pm_img ON (pm_thumb.meta_value = pm_img.post_id AND pm_img.meta_key = '_wp_attached_file')
        WHERE p.post_type = 'product' AND p.post_status = 'publish' AND tt.taxonomy = 'product_cat' AND t.slug = 'trending-products'
        GROUP BY p.ID
        ORDER BY p.post_date DESC LIMIT 8";

    $results = $wpdb->get_results( $sql );
    $upload_base = wp_upload_dir()['baseurl'];
    
    $data = array();
    foreach($results as $r) {
        $img = '';
        if ( $r->image_file ) {
            $img = (strpos($r->image_file, 'http') === 0) ? $r->image_file : $upload_base . '/' . $r->image_file;
        }
        $data[] = array( 
            'id' => (int)$r->ID, 
            'name' => $r->post_title, 
            'slug' => $r->post_name, 
            'image' => $img,
            'price' => $r->price,
            'regular_price' => $r->regular_price ?: $r->price
        );
    }

    set_transient($cache_key, $data, HOUR_IN_SECONDS);
    return new WP_REST_Response($data, 200);
}
