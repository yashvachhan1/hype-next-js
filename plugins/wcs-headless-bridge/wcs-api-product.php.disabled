<?php
/**
 * SNIPPET: MODULAR PRODUCT API
 * Description: Single Product Details (Images, Variations, Stock).
 * Endpoint: /wcs/v1/product/detail
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action( 'rest_api_init', function() {
    register_rest_route( 'wcs/v1', '/product/detail', array(
        'methods'  => 'GET',
        'callback' => 'wcs_get_single_product_detail',
        'permission_callback' => '__return_true'
    ));
});

function wcs_get_single_product_detail( $request ) {
    $slug = $request->get_param('slug');
    if ( ! $slug ) return new WP_REST_Response( array('error' => 'Slug required'), 400 );

    $args = array( 'name' => $slug, 'post_type' => 'product', 'post_status' => 'publish', 'numberposts' => 1 );
    $posts = get_posts($args);
    if (empty($posts)) return new WP_REST_Response( array('success' => false, 'message' => 'Product not found'), 404 );
    
    $p = wc_get_product($posts[0]->ID);
    $pid = $p->get_id();

    // 1. Raw Meta 
    $price = get_post_meta($pid, '_price', true);
    $reg_price = get_post_meta($pid, '_regular_price', true);
    $stock_status = get_post_meta($pid, '_stock_status', true);
    
    // 2. Image Logic
    $img_id = get_post_meta($pid, '_thumbnail_id', true);
    $img_url = $img_id ? wp_get_attachment_image_url($img_id, 'full') : '';
    
    // Gallery
    $gallery = $p->get_gallery_image_ids();
    $gallery_urls = [];
    foreach($gallery as $gid) $gallery_urls[] = wp_get_attachment_image_url($gid, 'full');

    if (empty($img_url) && !empty($gallery_urls)) {
        $img_url = $gallery_urls[0];
    }

    // 3. Brand
    $brand_name = 'Hype Distribution';
    $terms = get_the_terms($pid, 'pwb-brand');
    if ($terms && !is_wp_error($terms)) $brand_name = $terms[0]->name;

    // 4. Variations
    $variations_data = [];
    $product_type = $p->get_type();
    
    if ($product_type === 'variable' || $product_type === 'variation') {
        $args_vars = array('post_parent' => $pid, 'post_type' => 'product_variation', 'numberposts' => -1, 'post_status' => 'publish');
        $children_posts = get_posts($args_vars);
        
        foreach($children_posts as $child) {
            $vid = $child->ID;
            $v_price = get_post_meta($vid, '_price', true);
            $v_reg = get_post_meta($vid, '_regular_price', true);
            $v_stock = get_post_meta($vid, '_stock_status', true);
            $v_img_id = get_post_meta($vid, '_thumbnail_id', true);
            $v_img = $v_img_id ? wp_get_attachment_image_url($v_img_id, 'full') : $img_url; 
            
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
        if (!empty($variations_data)) $product_type = 'variable';
    }

    // Fallback Image
    if (empty($img_url) && !empty($variations_data)) {
        $img_url = $variations_data[0]['image'];
    }

    $data = array(
        'id' => $pid,
        'name' => $p->get_name(),
        'slug' => $p->get_slug(),
        'type' => $product_type,
        'price_html' => $p->get_price_html(),
        'regular_price' => $reg_price,
        'price' => $price,
        'brand' => $brand_name,
        'description' => $p->get_description(),
        'short_description' => $p->get_short_description(),
        'stock_status' => $stock_status,
        'images' => empty($img_url) ? [] : array_merge([$img_url], $gallery_urls),
        'image' => $img_url,
        'gallery' => $gallery_urls,
        'attributes' => array(),
        'variations' => $variations_data,
        'related_products' => []
    );
    
    // Attributes
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

    // Wrap in "products" array to match frontend expectations
    return new WP_REST_Response( array('products' => array($data)), 200 );
}
