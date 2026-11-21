<?php
require_once 'config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

$message = '';
$error = '';
$short_code = '';
$user_data = ['products' => [], 'services' => []];
$user_type = ''; // 'product_seller' or 'freelancer' or 'mixed'
$code_exists = false;

// Handle all POST actions (update, delete, add, etc.)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['short_code'])) {
        $short_code = mysqli_real_escape_string($conn, $_POST['short_code']);
        
        // UPDATE PRODUCT
        if (isset($_POST['update_product'])) {
            $product_id = mysqli_real_escape_string($conn, $_POST['product_id']);
            $name = mysqli_real_escape_string($conn, $_POST['product_name']);
            $description = mysqli_real_escape_string($conn, $_POST['product_description']);
            $price = mysqli_real_escape_string($conn, $_POST['price']);
            $price_type = mysqli_real_escape_string($conn, $_POST['price_type']);
            $status = mysqli_real_escape_string($conn, $_POST['status']);
            $category_id = mysqli_real_escape_string($conn, $_POST['category']);
            $subcategory_id = mysqli_real_escape_string($conn, $_POST['subcategory']);
            
            $sql = "UPDATE products SET 
                    name = '$name',
                    description = '$description',
                    price = '$price',
                    price_type = '$price_type',
                    status = '$status',
                    category_id = '$category_id',
                    subcategory_id = '$subcategory_id'
                    WHERE id = '$product_id' AND short_code = '$short_code'";
            
            if (mysqli_query($conn, $sql)) {
                $message = "Product updated successfully!";
                
                // Handle new images
                if (!empty($_FILES['new_product_images']['name'][0])) {
                    $upload_dir = 'uploads/products/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    foreach ($_FILES['new_product_images']['tmp_name'] as $key => $tmp_name) {
                        $file_name = time() . '_' . basename($_FILES['new_product_images']['name'][$key]);
                        $target_file = $upload_dir . $file_name;
                        
                        if (move_uploaded_file($tmp_name, $target_file)) {
                            $image_path = mysqli_real_escape_string($conn, $target_file);
                            mysqli_query($conn, "INSERT INTO product_images (product_id, image_path) VALUES ('$product_id', '$image_path')");
                        }
                    }
                }
                
                // Auto-refresh to show updated data
                header("Location: manage.php?short_code=" . urlencode($short_code) . "&updated=product");
                exit();
            } else {
                $error = "Error updating product: " . mysqli_error($conn);
            }
        }
        
        // ADD NEW PRODUCT
        elseif (isset($_POST['add_new_product'])) {
            $name = mysqli_real_escape_string($conn, $_POST['new_product_name']);
            $description = mysqli_real_escape_string($conn, $_POST['new_product_description']);
            $price = mysqli_real_escape_string($conn, $_POST['new_price']);
            $price_type = isset($_POST['new_price_type']) ? mysqli_real_escape_string($conn, $_POST['new_price_type']) : '';
            $category_id = mysqli_real_escape_string($conn, $_POST['new_category']);
            $subcategory_id = mysqli_real_escape_string($conn, $_POST['new_subcategory']);
            
            // Get seller info from existing product (if any)
            $seller_info = mysqli_query($conn, "SELECT seller_name, whatsapp_number FROM products WHERE short_code = '$short_code' LIMIT 1");
            if ($seller_info && mysqli_num_rows($seller_info) > 0) {
                $seller = mysqli_fetch_assoc($seller_info);
                $seller_name = $seller['seller_name'];
                $whatsapp_number = $seller['whatsapp_number'];
            } else {
                // If no existing product, get from form
                $seller_name = mysqli_real_escape_string($conn, $_POST['new_seller_name']);
                $whatsapp_number = mysqli_real_escape_string($conn, $_POST['new_whatsapp_number']);
            }
            
            $sql = "INSERT INTO products (short_code, name, description, price, price_type, 
                    category_id, subcategory_id, seller_name, whatsapp_number) 
                    VALUES ('$short_code', '$name', '$description', '$price', ";
            
            if ($price_type) {
                $sql .= "'$price_type', ";
            } else {
                $sql .= "NULL, ";
            }
            
            $sql .= "'$category_id', '$subcategory_id', '$seller_name', '$whatsapp_number')";
            
            if (mysqli_query($conn, $sql)) {
                $product_id = mysqli_insert_id($conn);
                $message = "New product added successfully!";
                
                // Handle image uploads
                if (!empty($_FILES['new_product_images_add']['name'][0])) {
                    $upload_dir = 'uploads/products/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    foreach ($_FILES['new_product_images_add']['tmp_name'] as $key => $tmp_name) {
                        $file_name = time() . '_' . basename($_FILES['new_product_images_add']['name'][$key]);
                        $target_file = $upload_dir . $file_name;
                        
                        if (move_uploaded_file($tmp_name, $target_file)) {
                            $image_path = mysqli_real_escape_string($conn, $target_file);
                            mysqli_query($conn, "INSERT INTO product_images (product_id, image_path) VALUES ('$product_id', '$image_path')");
                        }
                    }
                }
                
                // Auto-refresh to show the new product
                header("Location: manage.php?short_code=" . urlencode($short_code) . "&added=product");
                exit();
            } else {
                $error = "Error adding new product: " . mysqli_error($conn);
            }
        }
        
        // ADD NEW SERVICE
        elseif (isset($_POST['add_new_service'])) {
            $freelancer_name = mysqli_real_escape_string($conn, $_POST['new_freelancer_name']);
            $profession = mysqli_real_escape_string($conn, $_POST['new_profession']);
            $description = mysqli_real_escape_string($conn, $_POST['new_service_description']);
            $whatsapp_number = mysqli_real_escape_string($conn, $_POST['new_service_whatsapp']);
            
            $sql = "INSERT INTO services (short_code, freelancer_name, profession, description, whatsapp_number) 
                    VALUES ('$short_code', '$freelancer_name', '$profession', '$description', '$whatsapp_number')";
            
            if (mysqli_query($conn, $sql)) {
                $service_id = mysqli_insert_id($conn);
                $message = "New service added successfully!";
                
                // Handle portfolio image uploads
                if (!empty($_FILES['new_portfolio_images_add']['name'][0])) {
                    $upload_dir = 'uploads/services/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    foreach ($_FILES['new_portfolio_images_add']['tmp_name'] as $key => $tmp_name) {
                        $file_name = time() . '_' . basename($_FILES['new_portfolio_images_add']['name'][$key]);
                        $target_file = $upload_dir . $file_name;
                        
                        if (move_uploaded_file($tmp_name, $target_file)) {
                            $image_path = mysqli_real_escape_string($conn, $target_file);
                            mysqli_query($conn, "INSERT INTO service_images (service_id, image_path) VALUES ('$service_id', '$image_path')");
                        }
                    }
                }
                
                // Auto-refresh to show the new service
                header("Location: manage.php?short_code=" . urlencode($short_code) . "&added=service");
                exit();
            } else {
                $error = "Error adding new service: " . mysqli_error($conn);
            }
        }
        
        // DELETE PRODUCT
        elseif (isset($_POST['delete_product'])) {
            $product_id = mysqli_real_escape_string($conn, $_POST['product_id']);
            
            // First delete images
            mysqli_query($conn, "DELETE FROM product_images WHERE product_id = '$product_id'");
            // Then delete product
            $sql = "DELETE FROM products WHERE id = '$product_id' AND short_code = '$short_code'";
            
            if (mysqli_query($conn, $sql)) {
                $message = "Product deleted successfully!";
                // Auto-refresh
                header("Location: manage.php?short_code=" . urlencode($short_code) . "&deleted=product");
                exit();
            } else {
                $error = "Error deleting product: " . mysqli_error($conn);
            }
        }
        
        // UPDATE PRODUCT STATUS
        elseif (isset($_POST['update_status'])) {
            $product_id = mysqli_real_escape_string($conn, $_POST['product_id']);
            $status = mysqli_real_escape_string($conn, $_POST['status']);
            
            $sql = "UPDATE products SET status = '$status' WHERE id = '$product_id' AND short_code = '$short_code'";
            
            if (mysqli_query($conn, $sql)) {
                $message = "Product status updated to " . ucfirst($status) . "!";
                // Auto-refresh
                header("Location: manage.php?short_code=" . urlencode($short_code) . "&updated=status");
                exit();
            } else {
                $error = "Error updating status: " . mysqli_error($conn);
            }
        }
        
        // UPDATE SERVICE
        elseif (isset($_POST['update_service'])) {
            $service_id = mysqli_real_escape_string($conn, $_POST['service_id']);
            $freelancer_name = mysqli_real_escape_string($conn, $_POST['freelancer_name']);
            $profession = mysqli_real_escape_string($conn, $_POST['profession']);
            $description = mysqli_real_escape_string($conn, $_POST['service_description']);
            $whatsapp_number = mysqli_real_escape_string($conn, $_POST['service_whatsapp']);
            
            $sql = "UPDATE services SET 
                    freelancer_name = '$freelancer_name',
                    profession = '$profession',
                    description = '$description',
                    whatsapp_number = '$whatsapp_number'
                    WHERE id = '$service_id' AND short_code = '$short_code'";
            
            if (mysqli_query($conn, $sql)) {
                $message = "Service updated successfully!";
                
                // Handle new portfolio images
                if (!empty($_FILES['new_portfolio_images']['name'][0])) {
                    $upload_dir = 'uploads/services/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    foreach ($_FILES['new_portfolio_images']['tmp_name'] as $key => $tmp_name) {
                        $file_name = time() . '_' . basename($_FILES['new_portfolio_images']['name'][$key]);
                        $target_file = $upload_dir . $file_name;
                        
                        if (move_uploaded_file($tmp_name, $target_file)) {
                            $image_path = mysqli_real_escape_string($conn, $target_file);
                            mysqli_query($conn, "INSERT INTO service_images (service_id, image_path) VALUES ('$service_id', '$image_path')");
                        }
                    }
                }
                
                // Auto-refresh
                header("Location: manage.php?short_code=" . urlencode($short_code) . "&updated=service");
                exit();
            } else {
                $error = "Error updating service: " . mysqli_error($conn);
            }
        }
        
        // DELETE SERVICE
        elseif (isset($_POST['delete_service'])) {
            $service_id = mysqli_real_escape_string($conn, $_POST['service_id']);
            
            // First delete images
            mysqli_query($conn, "DELETE FROM service_images WHERE service_id = '$service_id'");
            // Then delete service
            $sql = "DELETE FROM services WHERE id = '$service_id' AND short_code = '$short_code'";
            
            if (mysqli_query($conn, $sql)) {
                $message = "Service deleted successfully!";
                // Auto-refresh
                header("Location: manage.php?short_code=" . urlencode($short_code) . "&deleted=service");
                exit();
            } else {
                $error = "Error deleting service: " . mysqli_error($conn);
            }
        }
        
        // RECOVER CODE
        elseif (isset($_POST['recover_code'])) {
            $phone = mysqli_real_escape_string($conn, $_POST['phone_number']);
            
            // Format phone number for search
            $formatted_phone = $phone;
            if (strpos($phone, '+254') === 0) {
                $formatted_phone = $phone;
            } elseif (strpos($phone, '254') === 0) {
                $formatted_phone = '+' . $phone;
            } elseif (strpos($phone, '0') === 0) {
                $formatted_phone = '+254' . substr($phone, 1);
            }
            
            // Search in products table
            $product_codes = mysqli_query($conn, "SELECT DISTINCT short_code FROM products WHERE whatsapp_number LIKE '%$formatted_phone%' OR whatsapp_number LIKE '%$phone%'");
            
            // Search in services table
            $service_codes = mysqli_query($conn, "SELECT DISTINCT short_code FROM services WHERE whatsapp_number LIKE '%$formatted_phone%' OR whatsapp_number LIKE '%$phone%'");
            
            $codes = [];
            
            if ($product_codes) {
                while ($row = mysqli_fetch_assoc($product_codes)) {
                    $codes[] = $row['short_code'];
                }
            }
            
            if ($service_codes) {
                while ($row = mysqli_fetch_assoc($service_codes)) {
                    if (!in_array($row['short_code'], $codes)) {
                        $codes[] = $row['short_code'];
                    }
                }
            }
            
            if (!empty($codes)) {
                $_SESSION['recovered_codes'] = $codes;
                $_SESSION['recovery_phone'] = $phone;
                header("Location: manage.php?code_recovered=1");
                exit();
            } else {
                $error = "No short codes found for the provided phone number.";
            }
        }
    }
}

// Handle GET request (load data)
if (isset($_GET['short_code']) && !empty($_GET['short_code'])) {
    $short_code = mysqli_real_escape_string($conn, $_GET['short_code']);
    
    // Check if code exists in either products or services
    $check_code_sql = "SELECT COUNT(*) as count FROM (
                        SELECT short_code FROM products WHERE short_code = '$short_code'
                        UNION
                        SELECT short_code FROM services WHERE short_code = '$short_code'
                    ) as temp";
    $check_result = mysqli_query($conn, $check_code_sql);
    if ($check_result) {
        $row = mysqli_fetch_assoc($check_result);
        $code_exists = $row['count'] > 0;
    }
    
    if ($code_exists) {
        // Get products
        $products_sql = "SELECT p.*, c.name as category_name 
                        FROM products p 
                        LEFT JOIN categories c ON p.category_id = c.id 
                        WHERE p.short_code = '$short_code' 
                        ORDER BY p.created_at DESC";
        $products_result = mysqli_query($conn, $products_sql);
        
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
                $user_data['products'][] = $product;
            }
        }
        
        // Get services
        $services_sql = "SELECT * FROM services WHERE short_code = '$short_code' ORDER BY created_at DESC";
        $services_result = mysqli_query($conn, $services_sql);
        
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
                $user_data['services'][] = $service;
            }
        }
        
        // Determine user type
        $has_products = !empty($user_data['products']);
        $has_services = !empty($user_data['services']);
        
        if ($has_products && !$has_services) {
            $user_type = 'product_seller';
        } elseif (!$has_products && $has_services) {
            $user_type = 'freelancer';
        } elseif ($has_products && $has_services) {
            $user_type = 'mixed';
        }
    }
}

// Get categories for dropdowns
$categories_result = mysqli_query($conn, "SELECT * FROM categories ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products & Services - Zetumarket</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #FF6D00;
            --primary-dark: #E65100;
            --primary-light: #FF9E40;
            --secondary-color: #333333;
            --light-bg: #F5F5F5;
            --white: #FFFFFF;
            --border-color: #E0E0E0;
            --text-color: #333;
            --text-light: #666;
            --success-color: #4CAF50;
            --error-color: #F44336;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, var(--primary-light) 0%, var(--primary-color) 100%);
            color: var(--text-color);
            line-height: 1.6;
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        /* Logo */
        .logo-container {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo {
            display: inline-block;
            text-decoration: none;
        }
        
        .logo h1 {
            color: var(--white);
            font-size: 36px;
            margin-bottom: 5px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }
        
        .logo p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 14px;
        }
        
        /* Search Box */
        .search-box {
            background: var(--white);
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 30px;
            text-align: center;
        }
        
        .search-box h2 {
            color: var(--secondary-color);
            margin-bottom: 20px;
        }
        
        .search-form {
            display: flex;
            gap: 15px;
            max-width: 400px;
            margin: 0 auto;
        }
        
        .search-form input {
            flex: 1;
            padding: 15px;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            font-size: 16px;
        }
        
        .search-form button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 5px;
            padding: 15px 30px;
            cursor: pointer;
            font-weight: 600;
            transition: background-color 0.3s;
            min-width: 100px;
        }
        
        .search-form button:hover {
            background-color: var(--primary-dark);
        }
        
        /* Card */
        .card {
            background: var(--white);
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 30px;
        }
        
        /* Alert Messages */
        .alert {
            padding: 12px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert-error {
            background-color: rgba(255, 255, 255, 0.9);
            border: 1px solid var(--error-color);
            color: var(--error-color);
        }
        
        .alert-success {
            background-color: rgba(255, 255, 255, 0.9);
            border: 1px solid var(--success-color);
            color: var(--success-color);
        }
        
        /* Product Grid - 2 columns on mobile */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        @media (max-width: 768px) {
            .product-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
            }
        }
        
        @media (max-width: 480px) {
            .product-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .product-card {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.3s;
            background: var(--white);
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .product-image {
            width: 100%;
            height: 180px;
            object-fit: cover;
        }
        
        .product-info {
            padding: 15px;
        }
        
        .product-name {
            font-weight: 600;
            margin-bottom: 10px;
            font-size: 16px;
            height: 40px;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        
        .product-price {
            color: var(--primary-color);
            font-weight: 700;
            font-size: 18px;
            margin-bottom: 10px;
        }
        
        .product-status {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .status-available {
            background-color: rgba(76, 175, 80, 0.1);
            color: var(--success-color);
        }
        
        .status-sold {
            background-color: rgba(33, 150, 243, 0.1);
            color: #2196F3;
        }
        
        .status-unavailable {
            background-color: rgba(158, 158, 158, 0.1);
            color: #9E9E9E;
        }
        
        .product-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn {
            display: inline-block;
            padding: 8px 15px;
            border-radius: 5px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            border: none;
            transition: background-color 0.3s;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
        }
        
        .btn-secondary {
            background-color: var(--secondary-color);
            color: white;
        }
        
        .btn-secondary:hover {
            background-color: #222;
        }
        
        .btn-danger {
            background-color: var(--error-color);
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #D32F2F;
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }
        
        /* Navigation Tabs */
        .nav-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid var(--border-color);
            flex-wrap: wrap;
        }
        
        .nav-tab-btn {
            padding: 12px 25px;
            background: none;
            border: none;
            border-bottom: 2px solid transparent;
            cursor: pointer;
            font-weight: 500;
            color: var(--text-light);
            transition: all 0.3s;
            font-size: 16px;
        }
        
        .nav-tab-btn:hover {
            color: var(--primary-color);
        }
        
        .nav-tab-btn.active {
            color: var(--primary-color);
            border-bottom-color: var(--primary-color);
        }
        
        .nav-tab-content {
            display: none;
            animation: fadeIn 0.5s;
        }
        
        .nav-tab-content.active {
            display: block;
        }
        
        /* Add New Section */
        .add-new-section {
            margin-top: 30px;
            padding: 20px;
            background: var(--white);
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .add-new-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid var(--border-color);
        }
        
        .add-tab-btn {
            padding: 10px 20px;
            background: none;
            border: none;
            border-bottom: 2px solid transparent;
            cursor: pointer;
            font-weight: 500;
            color: var(--text-light);
            transition: all 0.3s;
        }
        
        .add-tab-btn.active {
            color: var(--primary-color);
            border-bottom-color: var(--primary-color);
        }
        
        .add-tab-content {
            display: none;
        }
        
        .add-tab-content.active {
            display: block;
            animation: fadeIn 0.5s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Modal */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .modal {
            background: var(--white);
            border-radius: 10px;
            max-width: 500px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        
        .modal-header {
            padding: 20px 30px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: var(--primary-color);
            color: white;
            border-radius: 10px 10px 0 0;
        }
        
        .modal-header h3 {
            margin: 0;
            color: white;
        }
        
        .close-modal {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: white;
        }
        
        .modal-body {
            padding: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-color);
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            font-size: 16px;
        }
        
        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
        }
        
        .form-row .form-group {
            flex: 1;
            margin-bottom: 0;
        }
        
        textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .image-gallery {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }
        
        .gallery-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 5px;
            border: 1px solid var(--border-color);
        }
        
        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            margin-bottom: 10px;
        }
        
        .file-input-wrapper input[type=file] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
        
        .file-btn {
            background-color: var(--primary-light);
            color: var(--primary-dark);
            padding: 10px 20px;
            border-radius: 5px;
            border: 1px dashed var(--primary-color);
            display: inline-block;
            cursor: pointer;
        }
        
        .hint {
            font-size: 12px;
            color: var(--text-light);
            margin-top: 5px;
        }
        
        .modal-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px;
            color: var(--text-light);
        }
        
        .empty-state p {
            font-size: 18px;
        }
        
        /* Section Header */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .section-header h3 {
            color: var(--secondary-color);
            font-size: 22px;
        }
        
        /* Stats Cards */
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        
        .stat-card {
            background: var(--light-bg);
            border-radius: 8px;
            padding: 15px;
            text-align: center;
        }
        
        .stat-card h4 {
            color: var(--text-light);
            font-size: 14px;
            margin-bottom: 5px;
        }
        
        .stat-card .number {
            font-size: 28px;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        /* User Type Badge */
        .user-type-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
            vertical-align: middle;
        }
        
        .badge-product {
            background-color: rgba(33, 150, 243, 0.1);
            color: #2196F3;
        }
        
        .badge-service {
            background-color: rgba(156, 39, 176, 0.1);
            color: #9C27B0;
        }
        
        .badge-mixed {
            background-color: rgba(76, 175, 80, 0.1);
            color: var(--success-color);
        }
        
        /* Restriction Message */
        .restriction-message {
            background-color: rgba(255, 193, 7, 0.1);
            border-left: 4px solid #FFC107;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        /* Preview Images Container */
        .preview-images {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        
        .preview-images img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 5px;
            border: 1px solid #ddd;
        }
        
        /* Image Preview in Modal */
        .modal-preview-images {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        
        .modal-preview-images img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 5px;
            border: 1px solid #ddd;
        }
        
        /* Quick Links */
        .quick-links {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .quick-link {
            background: var(--white);
            padding: 15px 25px;
            border-radius: 8px;
            text-decoration: none;
            color: var(--text-color);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
            border: 1px solid var(--border-color);
        }
        
        .quick-link:hover {
            background: var(--primary-light);
            color: var(--primary-dark);
            transform: translateY(-2px);
        }
        
        .quick-link i {
            font-size: 18px;
        }
        
        /* Recover Code Link */
        .recover-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            margin-top: 10px;
            display: inline-block;
        }
        
        .recover-link:hover {
            text-decoration: underline;
        }
        
        /* Recovered Codes List */
        .codes-list {
            background: var(--light-bg);
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .code-item {
            background: var(--white);
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 10px;
            border-left: 4px solid var(--primary-color);
        }
        
        .code-item strong {
            color: var(--primary-color);
            font-size: 18px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
            }
            
            .search-form {
                flex-direction: column;
                gap: 15px;
            }
            
            .search-form button {
                width: 100%;
                padding: 15px;
            }
            
            .modal {
                max-height: 95vh;
            }
            
            .stats-cards {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .user-type-badge {
                display: block;
                margin-left: 0;
                margin-top: 10px;
            }
            
            .section-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .nav-tab-btn {
                padding: 10px 15px;
                font-size: 14px;
            }
            
            .quick-link {
                padding: 12px 20px;
                font-size: 14px;
            }
        }
        
        @media (max-width: 480px) {
            .search-form input {
                padding: 15px;
            }
            
            .stats-cards {
                grid-template-columns: 1fr;
            }
            
            .product-actions {
                flex-direction: column;
            }
            
            .btn-sm {
                width: 100%;
            }
            
            .nav-tabs {
                flex-direction: column;
                gap: 5px;
            }
            
            .nav-tab-btn {
                width: 100%;
                text-align: left;
                border-bottom: none;
                border-left: 2px solid transparent;
            }
            
            .nav-tab-btn.active {
                border-left-color: var(--primary-color);
                border-bottom-color: transparent;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Logo -->
        <div class="logo-container">
            <a href="index.php" class="logo">
                <h1><i class="fas fa-store"></i> Zetumarket</h1>
                <p>Manage Your Products & Services</p>
            </a>
        </div>
        
        <!-- Messages -->
        <?php if (!empty($message)): ?>
        <div class="alert alert-success">
            <?php echo $message; ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
        <div class="alert alert-error">
            <?php echo $error; ?>
        </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['code_recovered']) && isset($_SESSION['recovered_codes'])): ?>
        <div class="card">
            <div class="section-header">
                <h3><i class="fas fa-key"></i> Recovered Short Codes</h3>
            </div>
            <p>Found <?php echo count($_SESSION['recovered_codes']); ?> short code(s) for phone number: <?php echo $_SESSION['recovery_phone']; ?></p>
            <div class="codes-list">
                <?php foreach ($_SESSION['recovered_codes'] as $code): ?>
                <div class="code-item">
                    <strong><?php echo htmlspecialchars($code); ?></strong>
                    <div style="margin-top: 10px;">
                        <a href="manage.php?short_code=<?php echo urlencode($code); ?>" class="btn btn-primary btn-sm">
                            <i class="fas fa-external-link-alt"></i> Load Dashboard
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php unset($_SESSION['recovered_codes']); ?>
            <?php unset($_SESSION['recovery_phone']); ?>
        </div>
        <?php endif; ?>
        
        <!-- Search Box -->
        <div class="search-box">
            <h2>Enter Your Short Code</h2>
            <p>Enter the short code you received when uploading your product or service</p>
            
            <form method="GET" action="manage.php" class="search-form">
                <input type="text" name="short_code" 
                       placeholder="Enter your short code" 
                       value="<?php echo htmlspecialchars($short_code); ?>"
                       required>
                <button type="submit">
                    <i class="fas fa-search"></i> Load
                </button>
            </form>
            
            <p class="hint" style="margin-top: 15px;">
                Don't have a short code? <a href="upload.php" style="color: var(--primary-color); font-weight: 600;">Upload a product or service first</a>
            </p>
            
            <a href="#" class="recover-link" onclick="openRecoverModal()">
                <i class="fas fa-question-circle"></i> Forgot your short code? Recover it here
            </a>
        </div>
        
        <?php if (!empty($short_code) && $code_exists): ?>
            <!-- User Type Indicator -->
            <?php if ($user_type): ?>
            <div class="restriction-message">
                <strong><i class="fas fa-info-circle"></i> Account Type: </strong>
                <span class="user-type-badge badge-<?php echo $user_type; ?>">
                    <?php 
                    switch($user_type) {
                        case 'product_seller': echo 'Product Seller'; break;
                        case 'freelancer': echo 'Freelancer'; break;
                        case 'mixed': echo 'Mixed (Products & Services)'; break;
                    }
                    ?>
                </span>
                
                <?php if ($user_type === 'product_seller'): ?>
                <p style="margin-top: 10px; margin-bottom: 0;">You are registered as a product seller. You can only add products.</p>
                <?php elseif ($user_type === 'freelancer'): ?>
                <p style="margin-top: 10px; margin-bottom: 0;">You are registered as a freelancer. You can only add services.</p>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <!-- Stats Cards -->
            <div class="stats-cards">
                <div class="stat-card">
                    <h4>Products</h4>
                    <div class="number"><?php echo count($user_data['products']); ?></div>
                </div>
                <div class="stat-card">
                    <h4>Services</h4>
                    <div class="number"><?php echo count($user_data['services']); ?></div>
                </div>
                <div class="stat-card">
                    <h4>Short Code</h4>
                    <div class="number" style="font-size: 20px;"><?php echo htmlspecialchars($short_code); ?></div>
                </div>
                <div class="stat-card">
                    <h4>Total Items</h4>
                    <div class="number"><?php echo count($user_data['products']) + count($user_data['services']); ?></div>
                </div>
            </div>
            
            <!-- Quick Navigation Links -->
            <div class="quick-links">
                <?php if (!empty($user_data['products'])): ?>
                <a href="#products-section" class="quick-link" onclick="openNavTab('products')">
                    <i class="fas fa-box"></i> My Products (<?php echo count($user_data['products']); ?>)
                </a>
                <?php endif; ?>
                
                <?php if (!empty($user_data['services'])): ?>
                <a href="#services-section" class="quick-link" onclick="openNavTab('services')">
                    <i class="fas fa-briefcase"></i> My Services (<?php echo count($user_data['services']); ?>)
                </a>
                <?php endif; ?>
                
                <?php if ($user_type !== 'freelancer' || $user_type === 'mixed'): ?>
                <a href="#add-product" class="quick-link" onclick="openNavTab('add-product')">
                    <i class="fas fa-plus-circle"></i> Add Product
                </a>
                <?php endif; ?>
                
                <?php if ($user_type !== 'product_seller' || $user_type === 'mixed'): ?>
                <a href="#add-service" class="quick-link" onclick="openNavTab('add-service')">
                    <i class="fas fa-plus-circle"></i> Add Service
                </a>
                <?php endif; ?>
            </div>
            
            <!-- Navigation Tabs -->
            <div class="nav-tabs">
                <?php if (!empty($user_data['products'])): ?>
                <button class="nav-tab-btn active" onclick="openNavTab('products')">
                    <i class="fas fa-box"></i> My Products
                </button>
                <?php endif; ?>
                
                <?php if (!empty($user_data['services'])): ?>
                <button class="nav-tab-btn <?php echo empty($user_data['products']) ? 'active' : ''; ?>" onclick="openNavTab('services')">
                    <i class="fas fa-briefcase"></i> My Services
                </button>
                <?php endif; ?>
                
                <?php if ($user_type !== 'freelancer' || $user_type === 'mixed'): ?>
                <button class="nav-tab-btn" onclick="openNavTab('add-product')">
                    <i class="fas fa-plus"></i> Add Product
                </button>
                <?php endif; ?>
                
                <?php if ($user_type !== 'product_seller' || $user_type === 'mixed'): ?>
                <button class="nav-tab-btn" onclick="openNavTab('add-service')">
                    <i class="fas fa-plus"></i> Add Service
                </button>
                <?php endif; ?>
            </div>
            
            <!-- Products Section -->
            <?php if (!empty($user_data['products'])): ?>
            <div id="products-tab" class="nav-tab-content active">
                <div class="card">
                    <div class="section-header">
                        <h3><i class="fas fa-box"></i> My Products (<?php echo count($user_data['products']); ?>)</h3>
                    </div>
                    
                    <div class="product-grid">
                        <?php foreach ($user_data['products'] as $product): ?>
                        <div class="product-card">
                            <img src="<?php echo !empty($product['images']) ? $product['images'][0] : 'https://via.placeholder.com/250x200?text=No+Image'; ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
                            <div class="product-info">
                                <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                                <div class="product-price">KSh <?php echo number_format($product['price'], 2); ?></div>
                                <?php if ($product['price_type']): ?>
                                <div style="color: var(--text-light); font-size: 14px; margin-bottom: 10px;">
                                    <?php echo ucfirst(str_replace('_', ' ', $product['price_type'])); ?>
                                </div>
                                <?php endif; ?>
                                <div class="product-status status-<?php echo $product['status']; ?>">
                                    <?php echo ucfirst($product['status']); ?>
                                </div>
                                <div class="product-actions">
                                    <button class="btn btn-primary btn-sm" onclick="openEditProductModal(<?php echo htmlspecialchars(json_encode($product), ENT_QUOTES); ?>)">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button class="btn btn-secondary btn-sm" onclick="openStatusModal('<?php echo $short_code; ?>', <?php echo $product['id']; ?>, '<?php echo $product['status']; ?>')">
                                        <i class="fas fa-tag"></i> Status
                                    </button>
                                    <button class="btn btn-danger btn-sm" onclick="deleteProduct('<?php echo $short_code; ?>', <?php echo $product['id']; ?>)">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Services Section -->
            <?php if (!empty($user_data['services'])): ?>
            <div id="services-tab" class="nav-tab-content <?php echo empty($user_data['products']) ? 'active' : ''; ?>">
                <div class="card">
                    <div class="section-header">
                        <h3><i class="fas fa-briefcase"></i> My Services (<?php echo count($user_data['services']); ?>)</h3>
                    </div>
                    
                    <div class="product-grid">
                        <?php foreach ($user_data['services'] as $service): ?>
                        <div class="product-card">
                            <div style="background: #f5f5f5; height: 180px; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-briefcase" style="font-size: 50px; color: var(--primary-color);"></i>
                            </div>
                            <div class="product-info">
                                <div class="product-name"><?php echo htmlspecialchars($service['freelancer_name']); ?></div>
                                <div style="color: var(--primary-color); font-weight: 600; margin-bottom: 10px; font-size: 14px;">
                                    <?php echo htmlspecialchars($service['profession']); ?>
                                </div>
                                <div class="product-actions">
                                    <button class="btn btn-primary btn-sm" onclick="openEditServiceModal(<?php echo htmlspecialchars(json_encode($service), ENT_QUOTES); ?>)">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button class="btn btn-danger btn-sm" onclick="deleteService('<?php echo $short_code; ?>', <?php echo $service['id']; ?>)">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Add Product Section -->
            <?php if ($user_type !== 'freelancer' || $user_type === 'mixed'): ?>
            <div id="add-product-tab" class="nav-tab-content">
                <div class="card">
                    <div class="section-header">
                        <h3><i class="fas fa-plus-circle"></i> Add New Product</h3>
                        <?php if ($user_type === 'product_seller'): ?>
                        <span class="user-type-badge badge-product">Product Seller</span>
                        <?php endif; ?>
                    </div>
                    
                    <form method="POST" action="manage.php" enctype="multipart/form-data" onsubmit="return confirm('Add new product?')">
                        <input type="hidden" name="short_code" value="<?php echo htmlspecialchars($short_code); ?>">
                        <input type="hidden" name="add_new_product" value="1">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Product Name *</label>
                                <input type="text" name="new_product_name" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Price (KSh) *</label>
                                <input type="number" name="new_price" class="form-control" step="0.01" min="0" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Price Type</label>
                                <select name="new_price_type" class="form-control">
                                    <option value="">Select if applicable</option>
                                    <option value="new">New</option>
                                    <option value="second_hand">Second Hand</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Category *</label>
                                <select name="new_category" class="form-control" required onchange="loadNewSubcategories(this.value, 'new_subcategory')">
                                    <option value="">Select Category</option>
                                    <?php 
                                    mysqli_data_seek($categories_result, 0);
                                    while ($category = mysqli_fetch_assoc($categories_result)): 
                                    ?>
                                    <option value="<?php echo $category['id']; ?>"><?php echo $category['name']; ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Subcategory *</label>
                            <select id="new_subcategory" name="new_subcategory" class="form-control" required>
                                <option value="">Select a category first</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Description *</label>
                            <textarea name="new_product_description" class="form-control" rows="3" required></textarea>
                        </div>
                        
                        <?php if (empty($user_data['products'])): ?>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Seller Name *</label>
                                <input type="text" name="new_seller_name" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>WhatsApp Number *</label>
                                <input type="tel" name="new_whatsapp_number" class="form-control" required placeholder="+254712345678">
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="hint">
                            <i class="fas fa-info-circle"></i> Seller information will be copied from your existing products.
                        </div>
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label>Product Images *</label>
                            <div class="file-input-wrapper">
                                <button type="button" class="file-btn">
                                    <i class="fas fa-cloud-upload-alt"></i> Choose Images
                                </button>
                                <input type="file" name="new_product_images_add[]" multiple accept="image/*" onchange="previewNewImages(this, 'new-product-preview')" required>
                            </div>
                            <div class="hint">You can select multiple images</div>
                            <div id="new-product-preview" class="preview-images"></div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add Product
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Add Service Section -->
            <?php if ($user_type !== 'product_seller' || $user_type === 'mixed'): ?>
            <div id="add-service-tab" class="nav-tab-content">
                <div class="card">
                    <div class="section-header">
                        <h3><i class="fas fa-plus-circle"></i> Add New Service</h3>
                        <?php if ($user_type === 'freelancer'): ?>
                        <span class="user-type-badge badge-service">Freelancer</span>
                        <?php endif; ?>
                    </div>
                    
                    <form method="POST" action="manage.php" enctype="multipart/form-data" onsubmit="return confirm('Add new service?')">
                        <input type="hidden" name="short_code" value="<?php echo htmlspecialchars($short_code); ?>">
                        <input type="hidden" name="add_new_service" value="1">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Freelancer Name *</label>
                                <input type="text" name="new_freelancer_name" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Profession *</label>
                                <input type="text" name="new_profession" class="form-control" required placeholder="e.g., Graphic Designer">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Description *</label>
                            <textarea name="new_service_description" class="form-control" rows="3" required placeholder="Describe your services..."></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>WhatsApp Number *</label>
                            <input type="tel" name="new_service_whatsapp" class="form-control" required placeholder="+254712345678">
                        </div>
                        
                        <div class="form-group">
                            <label>Portfolio Images (Optional)</label>
                            <div class="file-input-wrapper">
                                <button type="button" class="file-btn">
                                    <i class="fas fa-cloud-upload-alt"></i> Choose Images
                                </button>
                                <input type="file" name="new_portfolio_images_add[]" multiple accept="image/*" onchange="previewNewImages(this, 'new-service-preview')">
                            </div>
                            <div class="hint">Upload images of your previous work</div>
                            <div id="new-service-preview" class="preview-images"></div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add Service
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Empty State - If code exists but no items yet -->
            <?php if (empty($user_data['products']) && empty($user_data['services'])): ?>
            <div class="card">
                <div class="empty-state">
                    <p>No products or services found for short code: <strong><?php echo htmlspecialchars($short_code); ?></strong></p>
                    <p style="margin-top: 20px;">
                        <a href="upload.php" class="btn btn-primary" style="padding: 12px 24px;">
                            <i class="fas fa-upload"></i> Upload Now
                        </a>
                    </p>
                </div>
            </div>
            <?php endif; ?>
            
        <?php elseif (!empty($short_code) && !$code_exists): ?>
            <!-- Code doesn't exist in database -->
            <div class="card">
                <div class="empty-state">
                    <h3 style="color: var(--error-color); margin-bottom: 20px;">
                        <i class="fas fa-exclamation-triangle"></i> Code Not Found
                    </h3>
                    <p>The short code <strong>"<?php echo htmlspecialchars($short_code); ?>"</strong> doesn't exist in our database.</p>
                    <p style="margin-top: 10px;">Please check your code and try again, or upload a new product/service.</p>
                    <p style="margin-top: 20px;">
                        <a href="upload.php" class="btn btn-primary" style="padding: 12px 24px;">
                            <i class="fas fa-upload"></i> Upload New Product/Service
                        </a>
                    </p>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Edit Product Modal -->
    <div id="editProductModal" class="modal-overlay">
        <div class="modal">
            <div class="modal-header">
                <h3>Edit Product</h3>
                <button class="close-modal" onclick="closeModal('editProductModal')">&times;</button>
            </div>
            <div class="modal-body" id="editProductModalBody">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
    
    <!-- Update Status Modal -->
    <div id="statusModal" class="modal-overlay">
        <div class="modal">
            <div class="modal-header">
                <h3>Update Product Status</h3>
                <button class="close-modal" onclick="closeModal('statusModal')">&times;</button>
            </div>
            <div class="modal-body" id="statusModalBody">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
    
    <!-- Edit Service Modal -->
    <div id="editServiceModal" class="modal-overlay">
        <div class="modal">
            <div class="modal-header">
                <h3>Edit Service</h3>
                <button class="close-modal" onclick="closeModal('editServiceModal')">&times;</button>
            </div>
            <div class="modal-body" id="editServiceModalBody">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
    
    <!-- Recover Code Modal -->
    <div id="recoverModal" class="modal-overlay">
        <div class="modal">
            <div class="modal-header">
                <h3><i class="fas fa-key"></i> Recover Your Short Code</h3>
                <button class="close-modal" onclick="closeModal('recoverModal')">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" action="manage.php" id="recoverForm">
                    <input type="hidden" name="short_code" value="<?php echo htmlspecialchars($short_code); ?>">
                    <input type="hidden" name="recover_code" value="1">
                    
                    <div class="form-group">
                        <label>Enter Your WhatsApp Number *</label>
                        <input type="tel" name="phone_number" class="form-control" required placeholder="+254712345678">
                        <div class="hint">Enter the WhatsApp number you used when uploading your product/service</div>
                    </div>
                    
                    <div class="modal-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Recover Code
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="closeModal('recoverModal')">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
    // Open Edit Product Modal
    function openEditProductModal(product) {
        let imagesHtml = '';
        if (product.images && product.images.length > 0) {
            imagesHtml = '<div class="image-gallery">';
            product.images.forEach(image => {
                imagesHtml += `<img src="${image}" class="gallery-image" alt="Product image">`;
            });
            imagesHtml += '</div>';
        }
        
        const modalBody = document.getElementById('editProductModalBody');
        modalBody.innerHTML = `
            <form method="POST" action="manage.php" enctype="multipart/form-data" id="editProductForm" onsubmit="return confirm('Save changes?')">
                <input type="hidden" name="short_code" value="<?php echo $short_code; ?>">
                <input type="hidden" name="product_id" value="${product.id}">
                <input type="hidden" name="update_product" value="1">
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Product Name *</label>
                        <input type="text" name="product_name" class="form-control" value="${product.name || ''}" required>
                    </div>
                    <div class="form-group">
                        <label>Price (KSh) *</label>
                        <input type="number" name="price" class="form-control" step="0.01" min="0" value="${product.price || ''}" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Price Type</label>
                        <select name="price_type" class="form-control">
                            <option value="">Select if applicable</option>
                            <option value="new" ${product.price_type === 'new' ? 'selected' : ''}>New</option>
                            <option value="second_hand" ${product.price_type === 'second_hand' ? 'selected' : ''}>Second Hand</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="available" ${product.status === 'available' ? 'selected' : ''}>Available</option>
                            <option value="sold" ${product.status === 'sold' ? 'selected' : ''}>Sold</option>
                            <option value="unavailable" ${product.status === 'unavailable' ? 'selected' : ''}>Unavailable</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Category *</label>
                        <select name="category" class="form-control" required onchange="loadSubcategoriesForEdit(this.value, ${product.subcategory_id})">
                            <option value="">Select Category</option>
                            <?php 
                            mysqli_data_seek($categories_result, 0);
                            while ($category = mysqli_fetch_assoc($categories_result)): 
                            ?>
                            <option value="<?php echo $category['id']; ?>" ${product.category_id == <?php echo $category['id']; ?> ? 'selected' : ''}>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Subcategory *</label>
                        <select id="edit_subcategory" name="subcategory" class="form-control" required>
                            <option value="">Select Subcategory</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Description *</label>
                    <textarea name="product_description" class="form-control" rows="4" required>${product.description || ''}</textarea>
                </div>
                
                <div class="form-group">
                    <label>Current Images</label>
                    ${imagesHtml || '<p>No images uploaded</p>'}
                </div>
                
                <div class="form-group">
                    <label>Add More Images</label>
                    <div class="file-input-wrapper">
                        <button type="button" class="file-btn">
                            <i class="fas fa-plus"></i> Add Images
                        </button>
                        <input type="file" name="new_product_images[]" multiple accept="image/*" id="editProductImages" onchange="previewModalImages(this, 'edit-product-preview')">
                    </div>
                    <div class="hint">You can add more product images</div>
                    <div id="edit-product-preview" class="modal-preview-images"></div>
                </div>
                
                <div class="modal-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editProductModal')">
                        Cancel
                    </button>
                </div>
            </form>
        `;
        
        // Load subcategories for selected category
        loadSubcategoriesForEdit(product.category_id, product.subcategory_id);
        
        document.getElementById('editProductModal').style.display = 'flex';
    }
    
    // Load subcategories for product editing
    function loadSubcategoriesForEdit(categoryId, selectedSubcategoryId) {
        if (!categoryId) return;
        
        fetch('get_subcategories.php?category_id=' + categoryId)
            .then(response => response.json())
            .then(data => {
                const select = document.querySelector('#edit_subcategory');
                if (select && Array.isArray(data)) {
                    let options = '<option value="">Select Subcategory</option>';
                    data.forEach(subcategory => {
                        const selected = subcategory.id == selectedSubcategoryId ? 'selected' : '';
                        options += `<option value="${subcategory.id}" ${selected}>${subcategory.name}</option>`;
                    });
                    select.innerHTML = options;
                }
            });
    }
    
    // Load subcategories for new product
    function loadNewSubcategories(categoryId, selectId) {
        if (!categoryId) {
            document.getElementById(selectId).innerHTML = '<option value="">Select a category first</option>';
            return;
        }
        
        fetch('get_subcategories.php?category_id=' + categoryId)
            .then(response => response.json())
            .then(data => {
                const select = document.getElementById(selectId);
                if (select && Array.isArray(data)) {
                    let options = '<option value="">Select Subcategory *</option>';
                    data.forEach(subcategory => {
                        options += `<option value="${subcategory.id}">${subcategory.name}</option>`;
                    });
                    select.innerHTML = options;
                }
            });
    }
    
    // Open Status Modal
    function openStatusModal(shortCode, productId, currentStatus) {
        const modalBody = document.getElementById('statusModalBody');
        modalBody.innerHTML = `
            <form method="POST" action="manage.php" onsubmit="return confirm('Update status?')">
                <input type="hidden" name="short_code" value="${shortCode}">
                <input type="hidden" name="product_id" value="${productId}">
                <input type="hidden" name="update_status" value="1">
                
                <div class="form-group">
                    <label>Select New Status</label>
                    <select name="status" class="form-control">
                        <option value="available" ${currentStatus === 'available' ? 'selected' : ''}>Available</option>
                        <option value="sold" ${currentStatus === 'sold' ? 'selected' : ''}>Sold</option>
                        <option value="unavailable" ${currentStatus === 'unavailable' ? 'selected' : ''}>Unavailable</option>
                    </select>
                </div>
                
                <div class="modal-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Status
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('statusModal')">
                        Cancel
                    </button>
                </div>
            </form>
        `;
        
        document.getElementById('statusModal').style.display = 'flex';
    }
    
    // Open Edit Service Modal
    function openEditServiceModal(service) {
        let imagesHtml = '';
        if (service.images && service.images.length > 0) {
            imagesHtml = '<div class="image-gallery">';
            service.images.forEach(image => {
                imagesHtml += `<img src="${image}" class="gallery-image" alt="Portfolio image">`;
            });
            imagesHtml += '</div>';
        }
        
        const modalBody = document.getElementById('editServiceModalBody');
        modalBody.innerHTML = `
            <form method="POST" action="manage.php" enctype="multipart/form-data" id="editServiceForm" onsubmit="return confirm('Save changes?')">
                <input type="hidden" name="short_code" value="<?php echo $short_code; ?>">
                <input type="hidden" name="service_id" value="${service.id}">
                <input type="hidden" name="update_service" value="1">
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Freelancer Name *</label>
                        <input type="text" name="freelancer_name" class="form-control" value="${service.freelancer_name || ''}" required>
                    </div>
                    <div class="form-group">
                        <label>Profession *</label>
                        <input type="text" name="profession" class="form-control" value="${service.profession || ''}" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Description *</label>
                    <textarea name="service_description" class="form-control" rows="4" required>${service.description || ''}</textarea>
                </div>
                
                <div class="form-group">
                    <label>WhatsApp Number *</label>
                    <input type="tel" name="service_whatsapp" class="form-control" value="${service.whatsapp_number || ''}" required>
                </div>
                
                <div class="form-group">
                    <label>Current Portfolio Images</label>
                    ${imagesHtml || '<p>No images uploaded</p>'}
                </div>
                
                <div class="form-group">
                    <label>Add More Portfolio Images</label>
                    <div class="file-input-wrapper">
                        <button type="button" class="file-btn">
                            <i class="fas fa-plus"></i> Add Images
                        </button>
                        <input type="file" name="new_portfolio_images[]" multiple accept="image/*" id="editServiceImages" onchange="previewModalImages(this, 'edit-service-preview')">
                    </div>
                    <div class="hint">You can add more images to your portfolio</div>
                    <div id="edit-service-preview" class="modal-preview-images"></div>
                </div>
                
                <div class="modal-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editServiceModal')">
                        Cancel
                    </button>
                </div>
            </form>
        `;
        
        document.getElementById('editServiceModal').style.display = 'flex';
    }
    
    // Delete Product
    function deleteProduct(shortCode, productId) {
        if (confirm('Are you sure you want to delete this product? This action cannot be undone.')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'manage.php';
            
            const shortCodeInput = document.createElement('input');
            shortCodeInput.type = 'hidden';
            shortCodeInput.name = 'short_code';
            shortCodeInput.value = shortCode;
            
            const productIdInput = document.createElement('input');
            productIdInput.type = 'hidden';
            productIdInput.name = 'product_id';
            productIdInput.value = productId;
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'delete_product';
            actionInput.value = '1';
            
            form.appendChild(shortCodeInput);
            form.appendChild(productIdInput);
            form.appendChild(actionInput);
            
            document.body.appendChild(form);
            form.submit();
        }
    }
    
    // Delete Service
    function deleteService(shortCode, serviceId) {
        if (confirm('Are you sure you want to delete this service? This action cannot be undone.')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'manage.php';
            
            const shortCodeInput = document.createElement('input');
            shortCodeInput.type = 'hidden';
            shortCodeInput.name = 'short_code';
            shortCodeInput.value = shortCode;
            
            const serviceIdInput = document.createElement('input');
            serviceIdInput.type = 'hidden';
            serviceIdInput.name = 'service_id';
            serviceIdInput.value = serviceId;
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'delete_service';
            actionInput.value = '1';
            
            form.appendChild(shortCodeInput);
            form.appendChild(serviceIdInput);
            form.appendChild(actionInput);
            
            document.body.appendChild(form);
            form.submit();
        }
    }
    
    // Image preview for new items
    function previewNewImages(input, previewId) {
        const preview = document.getElementById(previewId);
        preview.innerHTML = '';
        
        if (input.files) {
            for (let i = 0; i < Math.min(input.files.length, 10); i++) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.alt = "Image preview";
                    preview.appendChild(img);
                }
                reader.readAsDataURL(input.files[i]);
            }
        }
    }
    
    // Image preview for modal (edit section)
    function previewModalImages(input, previewId) {
        const preview = document.getElementById(previewId);
        if (!preview) {
            // Create preview container if it doesn't exist
            const container = document.createElement('div');
            container.id = previewId;
            container.className = 'modal-preview-images';
            input.parentNode.parentNode.appendChild(container);
        }
        
        const previewContainer = document.getElementById(previewId);
        if (input.files) {
            for (let i = 0; i < Math.min(input.files.length, 10); i++) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.alt = "Image preview";
                    previewContainer.appendChild(img);
                }
                reader.readAsDataURL(input.files[i]);
            }
        }
    }
    
    // Open Navigation Tab
    function openNavTab(tabName) {
        // Hide all nav tabs
        document.querySelectorAll('.nav-tab-content').forEach(tab => {
            tab.classList.remove('active');
        });
        
        // Remove active class from all nav buttons
        document.querySelectorAll('.nav-tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        
        // Show selected tab
        const tabElement = document.getElementById(tabName + '-tab');
        if (tabElement) {
            tabElement.classList.add('active');
        }
        
        // Activate clicked button
        const buttons = document.querySelectorAll('.nav-tab-btn');
        buttons.forEach(btn => {
            if (btn.textContent.includes(tabName.replace('-', ' ')) || 
                btn.getAttribute('onclick')?.includes(tabName)) {
                btn.classList.add('active');
            }
        });
        
        // Scroll to the tab
        setTimeout(() => {
            if (tabElement) {
                tabElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }, 100);
    }
    
    // Open Recover Modal
    function openRecoverModal() {
        document.getElementById('recoverModal').style.display = 'flex';
    }
    
    // Close Modal
    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }
    
    // Close modals when clicking outside
    window.onclick = function(event) {
        if (event.target.classList.contains('modal-overlay')) {
            event.target.style.display = 'none';
        }
    }
    
    // Phone number formatting
    document.addEventListener('DOMContentLoaded', function() {
        const phoneInputs = document.querySelectorAll('input[type="tel"]');
        phoneInputs.forEach(input => {
            input.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.startsWith('254')) {
                    value = '+' + value;
                } else if (value.startsWith('0')) {
                    value = '+254' + value.substring(1);
                } else if (value.length > 0 && !value.startsWith('+')) {
                    value = '+254' + value;
                }
                e.target.value = value;
            });
        });
        
        // Auto-scroll to newly added item if URL has added parameter
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('added')) {
            const addedType = urlParams.get('added');
            setTimeout(() => {
                if (addedType === 'product') {
                    openNavTab('products');
                } else if (addedType === 'service') {
                    openNavTab('services');
                }
            }, 500);
        }
        
        // Auto-open specific tab based on URL hash
        const hash = window.location.hash;
        if (hash) {
            const tabName = hash.replace('#', '');
            if (tabName && document.getElementById(tabName + '-tab')) {
                setTimeout(() => {
                    openNavTab(tabName);
                }, 300);
            }
        }
    });
    </script>
</body>
</html>