<?php
/**
 * Test script for Optica Vision API
 */

require_once('../../../wp-load.php');
require_once('includes/class-optica-vision-api.php');

// Start output buffering
ob_start();

// Set headers for JSON response
header('Content-Type: application/json');

$api = new Optica_Vision_API();

// Test API connection with limited data
$result = $api->test_connection();

if (is_wp_error($result)) {
    $output = [
        'status' => 'error',
        'message' => $result->get_error_message(),
        'data' => null
    ];
} else {
    $output = [
        'status' => 'success',
        'message' => 'API connection successful',
        'sample_data' => array_slice($result, 0, 5), // Show first 5 items
        'total_items' => count($result),
        'first_item_keys' => !empty($result[0]) ? array_keys($result[0]) : []
    ];
}

// Clean any previous output
ob_end_clean();

echo json_encode($output, JSON_PRETTY_PRINT);
?>
