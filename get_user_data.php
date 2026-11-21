<?php
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$response = [];

try {
    if (!isset($_GET['short_code']) || empty($_GET['short_code'])) {
        throw new Exception('No short code provided');
    }
    
    $short_code = mysqli_real_escape_string($conn, $_GET['short_code']);
    
    // Get products
    $products_sql = "SELECT p.*, c.name as category_name 
                    FROM products p 
                    LEFT JOIN categories c ON p.category_id = c.id 
                    WHERE p.short_code = '$short_code' 
                    ORDER BY p.created_at DESC";
    $products_result = mysqli_query($conn, $products_sql);
    
    $products = [];
    if ($products_result && mysqli_num_rows($products_result) > 0) {
        while ($product = mysqli_fetch_assoc($products_result)) {
            // Get product images
            $product_id = $product['id'];
            $images_sql = "SELECT image_path FROM product_images WHERE product_id = '$product_id'";
            $images_result = mysqli_query($conn, $images_sql);
            
            $images = [];
            if ($images_result) {
                while ($image = mysqli_fetch_assoc($images_result)) {
                    $images[] = $image['image_path'];
                }
            }
            
            $product['images'] = $images;
            $products[] = $product;
        }
    }
    
    // Get services
    $services_sql = "SELECT * FROM services WHERE short_code = '$short_code' ORDER BY created_at DESC";
    $services_result = mysqli_query($conn, $services_sql);
    
    $services = [];
    if ($services_result && mysqli_num_rows($services_result) > 0) {
        while ($service = mysqli_fetch_assoc($services_result)) {
            // Get service images
            $service_id = $service['id'];
            $images_sql = "SELECT image_path FROM service_images WHERE service_id = '$service_id'";
            $images_result = mysqli_query($conn, $images_sql);
            
            $images = [];
            if ($images_result) {
                while ($image = mysqli_fetch_assoc($images_result)) {
                    $images[] = $image['image_path'];
                }
            }
            
            $service['images'] = $images;
            $services[] = $service;
        }
    }
    
    if (empty($products) && empty($services)) {
        $response['error'] = 'No products or services found for this short code.';
    } else {
        $response['success'] = true;
        $response['products'] = $products;
        $response['services'] = $services;
    }
    
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response);
?>