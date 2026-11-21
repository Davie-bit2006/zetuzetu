<?php
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$response = [];

try {
    if (!isset($_GET['short_code']) || !isset($_GET['service_id'])) {
        throw new Exception('Missing parameters');
    }
    
    $short_code = mysqli_real_escape_string($conn, $_GET['short_code']);
    $service_id = mysqli_real_escape_string($conn, $_GET['service_id']);
    
    $sql = "SELECT * FROM services WHERE short_code = '$short_code' AND id = '$service_id'";
    $result = mysqli_query($conn, $sql);
    
    if (!$result) {
        throw new Exception('Database query failed: ' . mysqli_error($conn));
    }
    
    if (mysqli_num_rows($result) == 0) {
        throw new Exception('Service not found');
    }
    
    $service = mysqli_fetch_assoc($result);
    
    // Get images
    $images_sql = "SELECT image_path FROM service_images WHERE service_id = '$service_id'";
    $images_result = mysqli_query($conn, $images_sql);
    
    $images = [];
    if ($images_result) {
        while ($image = mysqli_fetch_assoc($images_result)) {
            $images[] = $image['image_path'];
        }
    }
    
    $service['images'] = $images;
    $response = $service;
    
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response);
?>