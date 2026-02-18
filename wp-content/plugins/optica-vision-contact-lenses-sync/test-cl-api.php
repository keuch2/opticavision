<?php
/**
 * Test script for Optica Vision Contact Lenses API
 */

require_once(__DIR__ . '/../../../wp-load.php');
require_once('includes/class-optica-vision-cl-api.php');

// Start output buffering
ob_start();

// Set headers for JSON response
header('Content-Type: application/json');

$api = new Optica_Vision_CL_API();

// Test API connection with limited data
$result = $api->test_connection();

if (is_wp_error($result)) {
    $output = [
        'status' => 'error',
        'message' => $result->get_error_message(),
        'data' => null
    ];
} else {
    // Analyze grouping
    $all_products = $api->get_contact_lenses();
    $grouping_analysis = [];
    
    if (!is_wp_error($all_products)) {
        $groups = [];
        $brands = [];
        
        foreach ($all_products as $product) {
            $brand = $product['marca'];
            $brands[$brand] = ($brands[$brand] ?? 0) + 1;
            
            // Simulate grouping logic
            $base_desc = preg_replace('/Simple visión Lentes de Co[^A-Z]*/', '', $product['descripcion']);
            $base_desc = preg_replace('/ME \d+\.\d+ \d+\.\d+/', '', $base_desc);
            $base_desc = preg_replace('/\s+(INCOLORO|AZUL|VERDE|GRIS)$/', '', $base_desc);
            $base_desc = trim($base_desc);
            
            $group_key = $brand . '_' . $base_desc;
            $groups[$group_key] = ($groups[$group_key] ?? 0) + 1;
        }
        
        $grouping_analysis = [
            'total_products' => count($all_products),
            'estimated_variable_products' => count($groups),
            'brands' => $brands,
            'average_variations_per_product' => count($all_products) / max(1, count($groups)),
            'sample_groups' => array_slice(array_keys($groups), 0, 5)
        ];
    }
    
    $output = [
        'status' => 'success',
        'message' => 'Contact lenses API connection successful',
        'sample_data' => array_slice($result, 0, 5), // Show first 5 items
        'total_items' => is_wp_error($all_products) ? 'Error getting total' : count($all_products),
        'first_item_structure' => !empty($result[0]) ? array_keys($result[0]) : [],
        'grouping_analysis' => $grouping_analysis
    ];
}

// Clean any previous output
ob_end_clean();

echo json_encode($output, JSON_PRETTY_PRINT);
?> 