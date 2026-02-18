<?php
/**
 * Image Processor Class
 * 
 * Handles processing of images and matching them to products
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class OVIM_Image_Processor {
    
    /**
     * Process existing images in the media library
     */
    public static function process_existing_images() {
        // Get all image attachments
        $args = array(
            'post_type'      => 'attachment',
            'post_mime_type' => 'image',
            'post_status'    => 'inherit',
            'posts_per_page' => -1,
        );
        
        $images = get_posts($args);
        
        $total = count($images);
        $matched = 0;
        $already_assigned = 0;
        
        foreach ($images as $image) {
            // Get filename without extension
            $filename = pathinfo(basename(get_attached_file($image->ID)), PATHINFO_FILENAME);
            
            // Clean the filename (remove any non-alphanumeric characters)
            $sku = preg_replace('/[^a-zA-Z0-9]/', '', $filename);
            
            // Find product by SKU
            $product_id = self::get_product_id_by_sku($sku);
            
            if ($product_id) {
                // Check if this image is already assigned to this product
                $current_image_id = get_post_thumbnail_id($product_id);
                
                if ($current_image_id == $image->ID) {
                    $already_assigned++;
                    continue;
                }
                
                // Set product image
                self::set_product_image($product_id, $image->ID);
                $matched++;
            }
        }
        
        return array(
            'total' => $total,
            'matched' => $matched,
            'already_assigned' => $already_assigned,
        );
    }
    
    /**
     * Get product ID by SKU
     */
    public static function get_product_id_by_sku($sku) {
        global $wpdb;
        
        $product_id = $wpdb->get_var($wpdb->prepare("
            SELECT post_id FROM {$wpdb->postmeta}
            WHERE meta_key = '_sku'
            AND meta_value = %s
            LIMIT 1
        ", $sku));
        
        return $product_id;
    }
    
    /**
     * Set product image
     */
    public static function set_product_image($product_id, $attachment_id) {
        // Check if the product already has a featured image
        $current_image_id = get_post_thumbnail_id($product_id);
        
        // Set the product image
        set_post_thumbnail($product_id, $attachment_id);
        
        // Add product gallery if there was already a featured image
        if ($current_image_id) {
            // Get current gallery
            $gallery = get_post_meta($product_id, '_product_image_gallery', true);
            $gallery_array = $gallery ? explode(',', $gallery) : array();
            
            // Add the old featured image to the gallery if it's not already there
            if (!in_array($current_image_id, $gallery_array)) {
                $gallery_array[] = $current_image_id;
            }
            
            // Update gallery
            update_post_meta($product_id, '_product_image_gallery', implode(',', $gallery_array));
        }
        
        return true;
    }
}
