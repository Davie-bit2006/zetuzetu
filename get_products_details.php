<?php
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$response = [];

try {
    if (!isset($_GET['short_code']) || !isset($_GET['product_id'])) {
        throw new Exception('Missing parameters');
    }
    
    $short_code = mysqli_real_escape_string($conn, $_GET['short_code']);
    $product_id = mysqli_real_escape_string($conn, $_GET['product_id']);
    
    $sql = "SELECT * FROM products WHERE short_code = '$short_code' AND id = '$product_id'";
    $result = mysqli_query($conn, $sql);
    
    if (!$result) {
        throw new Exception('Database query failed: ' . mysqli_error($conn));
    }
    
    if (mysqli_num_rows($result) == 0) {
        throw new Exception('Product not found');
    }
    
    $product = mysqli_fetch_assoc($result);
    
    // Get images
    $images_sql = "SELECT image_path FROM product_images WHERE product_id = '$product_id'";
    $images_result = mysqli_query($conn, $images_sql);
    
    $images = [];
    if ($images_result) {
        while ($image = mysqli_fetch_assoc($images_result)) {
            $images[] = $image['image_path'];
        }
    }
    
    $product['images'] = $images;
    $response = $product;
    
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response);
?>