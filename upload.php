<?php
require_once 'config.php';

// Function to generate short code from name
function generateShortCode($conn, $name) {
    $base_code = strtolower(str_replace(' ', '', $name));
    $short_code = $base_code;
    $counter = 1;
    
    // Check if short code exists in products
    $check_query = "SELECT COUNT(*) as count FROM products WHERE short_code = '$short_code' 
                    UNION 
                    SELECT COUNT(*) as count FROM services WHERE short_code = '$short_code'";
    $result = mysqli_query($conn, $check_query);
    $total_count = 0;
    while ($row = mysqli_fetch_assoc($result)) {
        $total_count += $row['count'];
    }
    
    // If exists, add numbers until we find a unique one
    while ($total_count > 0) {
        $short_code = $base_code . $counter;
        $counter++;
        
        $check_query = "SELECT COUNT(*) as count FROM products WHERE short_code = '$short_code' 
                        UNION 
                        SELECT COUNT(*) as count FROM services WHERE short_code = '$short_code'";
        $result = mysqli_query($conn, $check_query);
        $total_count = 0;
        while ($row = mysqli_fetch_assoc($result)) {
            $total_count += $row['count'];
        }
    }
    
    return $short_code;
}

// Handle product upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_product'])) {
    // Generate short code
    $short_code = generateShortCode($conn, $_POST['seller_name']);
    
    // Insert product data
    $name = mysqli_real_escape_string($conn, $_POST['product_name']);
    $description = mysqli_real_escape_string($conn, $_POST['product_description']);
    $price = mysqli_real_escape_string($conn, $_POST['price']);
    $price_type = isset($_POST['price_type']) ? mysqli_real_escape_string($conn, $_POST['price_type']) : '';
    $category_id = mysqli_real_escape_string($conn, $_POST['category']);
    $subcategory_id = mysqli_real_escape_string($conn, $_POST['subcategory']);
    $seller_name = mysqli_real_escape_string($conn, $_POST['seller_name']);
    $whatsapp_number = mysqli_real_escape_string($conn, $_POST['whatsapp_number']);
    $whatsapp_group = isset($_POST['whatsapp_group']) ? mysqli_real_escape_string($conn, $_POST['whatsapp_group']) : '';
    
    $sql = "INSERT INTO products (short_code, name, description, price, price_type, 
            category_id, subcategory_id, seller_name, whatsapp_number, whatsapp_group_link) 
            VALUES ('$short_code', '$name', '$description', '$price', ";
    
    if ($price_type) {
        $sql .= "'$price_type', ";
    } else {
        $sql .= "NULL, ";
    }
    
    $sql .= "'$category_id', '$subcategory_id', '$seller_name', '$whatsapp_number', ";
    
    if ($whatsapp_group) {
        $sql .= "'$whatsapp_group')";
    } else {
        $sql .= "NULL)";
    }
    
    if (mysqli_query($conn, $sql)) {
        $product_id = mysqli_insert_id($conn);
        $upload_success = "Product uploaded successfully! Your short code is: <strong>$short_code</strong>";
        
        // Handle multiple image uploads
        if (!empty($_FILES['product_images']['name'][0])) {
            $upload_dir = 'uploads/products/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            foreach ($_FILES['product_images']['tmp_name'] as $key => $tmp_name) {
                $file_name = time() . '_' . basename($_FILES['product_images']['name'][$key]);
                $target_file = $upload_dir . $file_name;
                
                if (move_uploaded_file($tmp_name, $target_file)) {
                    $image_path = mysqli_real_escape_string($conn, $target_file);
                    mysqli_query($conn, "INSERT INTO product_images (product_id, image_path) VALUES ('$product_id', '$image_path')");
                }
            }
        }
    } else {
        $upload_error = "Error uploading product: " . mysqli_error($conn);
    }
}

// Handle service upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_service'])) {
    // Generate short code
    $short_code = generateShortCode($conn, $_POST['freelancer_name']);
    
    // Insert service data
    $freelancer_name = mysqli_real_escape_string($conn, $_POST['freelancer_name']);
    $profession = mysqli_real_escape_string($conn, $_POST['profession']);
    $description = mysqli_real_escape_string($conn, $_POST['service_description']);
    $whatsapp_number = mysqli_real_escape_string($conn, $_POST['service_whatsapp']);
    
    $sql = "INSERT INTO services (short_code, freelancer_name, profession, description, whatsapp_number) 
            VALUES ('$short_code', '$freelancer_name', '$profession', '$description', '$whatsapp_number')";
    
    if (mysqli_query($conn, $sql)) {
        $service_id = mysqli_insert_id($conn);
        $upload_success = "Service uploaded successfully! Your short code is: <strong>$short_code</strong>";
        
        // Handle multiple portfolio image uploads
        if (!empty($_FILES['portfolio_images']['name'][0])) {
            $upload_dir = 'uploads/services/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            foreach ($_FILES['portfolio_images']['tmp_name'] as $key => $tmp_name) {
                $file_name = time() . '_' . basename($_FILES['portfolio_images']['name'][$key]);
                $target_file = $upload_dir . $file_name;
                
                if (move_uploaded_file($tmp_name, $target_file)) {
                    $image_path = mysqli_real_escape_string($conn, $target_file);
                    mysqli_query($conn, "INSERT INTO service_images (service_id, image_path) VALUES ('$service_id', '$image_path')");
                }
            }
        }
    } else {
        $upload_error = "Error uploading service: " . mysqli_error($conn);
    }
}

// Handle product management actions
if (isset($_POST['manage_action'])) {
    $short_code = mysqli_real_escape_string($conn, $_POST['short_code']);
    
    if ($_POST['manage_action'] === 'delete') {
        $product_id = mysqli_real_escape_string($conn, $_POST['product_id']);
        $sql = "DELETE FROM products WHERE id = '$product_id'";
        mysqli_query($conn, $sql);
        $manage_message = "Product deleted successfully!";
    } elseif ($_POST['manage_action'] === 'update_status') {
        $product_id = mysqli_real_escape_string($conn, $_POST['product_id']);
        $status = mysqli_real_escape_string($conn, $_POST['status']);
        $sql = "UPDATE products SET status = '$status' WHERE id = '$product_id'";
        mysqli_query($conn, $sql);
        $manage_message = "Product status updated successfully!";
    }
}

// Handle service management actions
if (isset($_POST['service_action'])) {
    $short_code = mysqli_real_escape_string($conn, $_POST['service_short_code']);
    
    if ($_POST['service_action'] === 'delete_service') {
        $service_id = mysqli_real_escape_string($conn, $_POST['service_id']);
        $sql = "DELETE FROM services WHERE id = '$service_id'";
        mysqli_query($conn, $sql);
        $manage_message = "Service deleted successfully!";
    } elseif ($_POST['service_action'] === 'update_service') {
        $service_id = mysqli_real_escape_string($conn, $_POST['service_id']);
        $freelancer_name = mysqli_real_escape_string($conn, $_POST['edit_freelancer_name']);
        $profession = mysqli_real_escape_string($conn, $_POST['edit_profession']);
        $description = mysqli_real_escape_string($conn, $_POST['edit_service_description']);
        $whatsapp_number = mysqli_real_escape_string($conn, $_POST['edit_service_whatsapp']);
        
        $sql = "UPDATE services SET freelancer_name = '$freelancer_name', profession = '$profession', 
                description = '$description', whatsapp_number = '$whatsapp_number' WHERE id = '$service_id'";
        mysqli_query($conn, $sql);
        $manage_message = "Service updated successfully!";
    }
}

// Handle additional service images
if (isset($_POST['add_service_images'])) {
    $service_id = mysqli_real_escape_string($conn, $_POST['service_id']);
    
    if (!empty($_FILES['additional_images']['name'][0])) {
        $upload_dir = 'uploads/services/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        foreach ($_FILES['additional_images']['tmp_name'] as $key => $tmp_name) {
            $file_name = time() . '_' . basename($_FILES['additional_images']['name'][$key]);
            $target_file = $upload_dir . $file_name;
            
            if (move_uploaded_file($tmp_name, $target_file)) {
                $image_path = mysqli_real_escape_string($conn, $target_file);
                mysqli_query($conn, "INSERT INTO service_images (service_id, image_path) VALUES ('$service_id', '$image_path')");
            }
        }
        $manage_message = "Additional images added successfully!";
    }
}

// Fetch categories for dropdown
$categories_result = mysqli_query($conn, "SELECT * FROM categories ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Products & Services - Zetumarket</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #FF6D00; /* Jumia Orange */
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
            background: #E65100;
            color: var(--text-color);
            line-height: 1.6;
            min-height: 100vh;
            padding: 20px;
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
        
        /* Upload Container */
        .upload-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        /* Tabs */
        .upload-tabs {
            display: flex;
            margin-bottom: 30px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.3);
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 10px 10px 0 0;
            overflow: hidden;
        }
        
        .tab-btn {
            padding: 15px 30px;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            font-size: 16px;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.8);
            cursor: pointer;
            transition: all 0.3s;
            flex: 1;
            text-align: center;
        }
        
        .tab-btn.active {
            color: var(--white);
            border-bottom: 3px solid var(--white);
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .tab-btn:hover {
            color: var(--white);
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
            animation: fadeIn 0.5s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Form Styles */
        .form-section {
            background: var(--white);
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 30px;
        }
        
        .form-section h3 {
            color: var(--secondary-color);
            margin-bottom: 20px;
            font-size: 22px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border-color);
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
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 2px rgba(255, 109, 0, 0.2);
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background-color: var(--primary-color);
            color: var(--white);
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
            text-align: center;
            text-decoration: none;
        }
        
        .btn:hover {
            background-color: var(--primary-dark);
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 14px;
            width: auto;
        }
        
        .btn-delete {
            background-color: var(--error-color);
        }
        
        .btn-delete:hover {
            background-color: #D32F2F;
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
        
        .form-group .hint {
            font-size: 12px;
            color: var(--text-light);
            margin-top: 5px;
        }
        
        .optional {
            color: var(--text-light);
            font-weight: normal;
            font-size: 0.9em;
        }
        
        /* File Upload */
        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
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
            transition: all 0.3s;
        }
        
        .file-btn:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        .preview-images {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
        }
        
        .image-preview {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 5px;
            border: 1px solid var(--border-color);
        }
        
        /* Manage Section */
        .manage-section {
            background: var(--white);
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-top: 20px;
        }
        
        .short-code-input {
            max-width: 300px;
            display: flex;
            gap: 10px;
        }
        
        .short-code-input input {
            flex: 1;
        }
        
        /* Currency Label */
        .currency-label {
            position: relative;
        }
        
        .currency-label::before {
            content: 'KSh ';
            position: absolute;
            left: 12px;
            top: 40px;
            color: var(--text-color);
            font-weight: 600;
            z-index: 1;
        }
        
        .currency-label input {
            padding-left: 55px;
        }
        
        /* Modals */
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
            max-width: 800px;
            width: 100%;
            max-height: 80vh;
            overflow-y: auto;
            animation: modalFadeIn 0.3s;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        
        @keyframes modalFadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
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
        
        /* Product Grid */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
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
            height: 200px;
            object-fit: cover;
        }
        
        .product-info {
            padding: 15px;
        }
        
        .product-name {
            font-weight: 600;
            margin-bottom: 10px;
            font-size: 16px;
        }
        
        .product-price {
            color: var(--primary-color);
            font-weight: 700;
            font-size: 18px;
            margin-bottom: 10px;
        }
        
        .product-price-type {
            color: var(--text-light);
            font-size: 14px;
            font-style: italic;
            margin-top: -5px;
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
        
        .status-select {
            width: 100%;
            padding: 8px;
            border-radius: 5px;
            border: 1px solid var(--border-color);
            margin-bottom: 10px;
        }
        
        .image-gallery {
            display: flex;
            gap: 10px;
            overflow-x: auto;
            padding: 10px 0;
            margin-bottom: 15px;
        }
        
        .gallery-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 5px;
            border: 1px solid var(--border-color);
        }
        
        .portfolio-container {
            margin-bottom: 20px;
        }
        
        .current-images {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        
        .portfolio-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 5px;
            border: 1px solid var(--border-color);
        }
        
        textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .upload-tabs {
                flex-direction: column;
            }
            
            .tab-btn {
                padding: 12px;
                border-bottom: none;
                border-right: 3px solid transparent;
            }
            
            .tab-btn.active {
                border-bottom: none;
                border-right: 3px solid var(--white);
            }
            
            .form-row {
                flex-direction: column;
                gap: 15px;
            }
            
            .logo h1 {
                font-size: 28px;
            }
            
            .short-code-input {
                flex-direction: column;
            }
        }
                /* Responsive Header */
        @media (max-width: 768px) {
            .logo-container {
                justify-content: space-between;
                padding: 0 10px;
            }
            
            .logo-container .logo h1 {
                font-size: 24px;
            }
            
            .logo-container .logo p {
                font-size: 11px;
            }
            
            .logo-container a[href="index.php"] {
                font-size: 13px;
                padding: 6px 12px;
            }
        }
        
        @media (max-width: 480px) {
            .logo-container .logo h1 {
                font-size: 20px;
            }
            
            .logo-container .logo p {
                display: none; /* Hide subtitle on very small screens */
            }
            
            .logo-container a[href="index.php"] {
                font-size: 12px;
                padding: 5px 10px;
            }
        }
    </style>
</head>
<body>
     <!-- Logo with Home link -->
    <div class="logo-container" style="display: flex; justify-content: center; align-items: center; gap: 30px; margin-bottom: 30px; flex-wrap: wrap;">
        <a href="admin.php" class="logo">
            <h1><i class="fas fa-store"></i> Zetumarket</h1>
            <p>Upload Products & Services</p>
        </a>
        
        <!-- Home link next to logo -->
        <a href="index.php" style="color: white; text-decoration: none; font-size: 16px; padding: 10px 20px; transition: all 0.3s;">
            <i class="fas fa-arrow-left"></i> Back to Home
        </a>
    </div>
    <div class="upload-container">
        <?php if (isset($upload_success)): ?>
        <div class="alert alert-success">
            <?php echo $upload_success; ?>
        </div>
        <?php endif; ?>
        
        <?php if (isset($upload_error)): ?>
        <div class="alert alert-error">
            <?php echo $upload_error; ?>
        </div>
        <?php endif; ?>
        
        <?php if (isset($manage_message)): ?>
        <div class="alert alert-success">
            <?php echo $manage_message; ?>
        </div>
        <?php endif; ?>
        
 <div class="upload-tabs">
    <button class="tab-btn active" onclick="openTab('product')">Upload Product</button>
    <button class="tab-btn" onclick="openTab('service')">Upload Service</button>

    <!-- Redirect to manage.php -->
    <button class="tab-btn" onclick="window.location.href='manage.php'">
        Manage Products/Services
    </button>
</div>

        
        <!-- Product Upload Tab -->
        <div id="product-tab" class="tab-content active">
            <div class="form-section">
                <h3><i class="fas fa-box"></i> Product Information</h3>
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="product_name">Product Name *</label>
                            <input type="text" id="product_name" name="product_name" class="form-control" required>
                        </div>
                        <div class="form-group currency-label">
                            <label for="price">Price *</label>
                            <input type="number" id="price" name="price" class="form-control" step="0.01" min="0" required placeholder="0.00">
                            <div class="hint">Price in Kenyan Shillings (KSh)</div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="price_type">Price Type <span class="optional">(Optional)</span></label>
                            <select id="price_type" name="price_type" class="form-control">
                                <option value="">Select if applicable</option>
                                <option value="new">New</option>
                                <option value="second_hand">Second Hand</option>
                            </select>
                            <div class="hint">Select if your product is new or second hand</div>
                        </div>
                        <div class="form-group">
                            <label for="category">Category *</label>
                            <select id="category" name="category" class="form-control" required onchange="loadSubcategories(this.value)">
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
                        <label for="subcategory">Subcategory *</label>
                        <select id="subcategory" name="subcategory" class="form-control" required>
                            <option value="">Select a category first</option>
                        </select>
                        <div class="hint">Select a subcategory for your product</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="product_description">Description *</label>
                        <textarea id="product_description" name="product_description" class="form-control" rows="4" required placeholder="Describe your product in detail..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Product Images *</label>
                        <div class="file-input-wrapper">
                            <button type="button" class="file-btn">
                                <i class="fas fa-cloud-upload-alt"></i> Choose Images
                            </button>
                            <input type="file" id="product_images" name="product_images[]" multiple accept="image/*" onchange="previewImages(this, 'product-preview')" required>
                        </div>
                        <div class="hint">You can select multiple images. Maximum 10 images. Supported formats: JPG, PNG, GIF</div>
                        <div id="product-preview" class="preview-images"></div>
                    </div>
                    
                    <h3 style="margin-top: 30px;"><i class="fas fa-user"></i> Seller Information</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="seller_name">Seller Name *</label>
                            <input type="text" id="seller_name" name="seller_name" class="form-control" required>
                            <div class="hint">This will be used to generate your short code</div>
                        </div>
                        <div class="form-group">
                            <label for="whatsapp_number">WhatsApp Number *</label>
                            <input type="tel" id="whatsapp_number" name="whatsapp_number" class="form-control" required placeholder="+254712345678">
                            <div class="hint">Include country code (e.g., +254712345678)</div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="whatsapp_group">WhatsApp Group Link <span class="optional">(Optional)</span></label>
                        <input type="url" id="whatsapp_group" name="whatsapp_group" class="form-control" placeholder="https://chat.whatsapp.com/...">
                        <div class="hint">If you have a WhatsApp group for your business, add the link here</div>
                    </div>
                    
                    <button type="submit" name="upload_product" class="btn">
                        <i class="fas fa-upload"></i> Upload Product
                    </button>
                    
                    <div class="hint" style="margin-top: 15px;">
                        * Required fields. After uploading, you'll receive a short code to manage your product.
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Service Upload Tab -->
        <div id="service-tab" class="tab-content">
            <div class="form-section">
                <h3><i class="fas fa-briefcase"></i> Service Information</h3>
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="freelancer_name">Freelancer Name *</label>
                            <input type="text" id="freelancer_name" name="freelancer_name" class="form-control" required>
                            <div class="hint">This will be used to generate your short code</div>
                        </div>
                        <div class="form-group">
                            <label for="profession">Profession *</label>
                            <input type="text" id="profession" name="profession" class="form-control" required placeholder="e.g., Graphic Designer, Web Developer, Plumber">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="service_description">Description of Services *</label>
                        <textarea id="service_description" name="service_description" class="form-control" rows="4" required placeholder="Describe what you do, your experience, skills, services offered, etc."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="service_whatsapp">WhatsApp Number *</label>
                        <input type="tel" id="service_whatsapp" name="service_whatsapp" class="form-control" required placeholder="+254712345678">
                        <div class="hint">Include country code (e.g., +254712345678)</div>
                    </div>
                    
                    <div class="form-group">
                        <label>Portfolio Images <span class="optional">(Optional but recommended)</span></label>
                        <div class="file-input-wrapper">
                            <button type="button" class="file-btn">
                                <i class="fas fa-cloud-upload-alt"></i> Choose Portfolio Images
                            </button>
                            <input type="file" id="portfolio_images" name="portfolio_images[]" multiple accept="image/*" onchange="previewImages(this, 'portfolio-preview')">
                        </div>
                        <div class="hint">Upload images of your previous work. Maximum 15 images. Supported formats: JPG, PNG, GIF</div>
                        <div id="portfolio-preview" class="preview-images"></div>
                    </div>
                    
                    <button type="submit" name="upload_service" class="btn">
                        <i class="fas fa-upload"></i> Upload Service
                    </button>
                    
                    <div class="hint" style="margin-top: 15px;">
                        * Required fields. After uploading, you'll receive a short code to manage your service.
                    </div>
                </form>
            </div>
        </div>
        
        
    </div>
    
    <!-- Product Management Modal -->
    <div id="productModal" class="modal-overlay">
        <div class="modal">
            <div class="modal-header">
                <h3>Manage Product</h3>
                <button class="close-modal" onclick="closeModal('productModal')">&times;</button>
            </div>
            <div class="modal-body" id="productModalBody">
                <!-- Product details loaded here -->
            </div>
        </div>
    </div>
    
    <!-- Service Management Modal -->
    <div id="serviceModal" class="modal-overlay">
        <div class="modal">
            <div class="modal-header">
                <h3>Manage Service</h3>
                <button class="close-modal" onclick="closeModal('serviceModal')">&times;</button>
            </div>
            <div class="modal-body" id="serviceModalBody">
                <!-- Service details loaded here -->
            </div>
        </div>
    </div>
    
            <script>
    // Tab switching
    function openTab(tabName) {
        // Hide all tabs
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.remove('active');
        });
        
        // Remove active class from all buttons
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        
        // Show selected tab
        document.getElementById(tabName + '-tab').classList.add('active');
        
        // Activate clicked button
        event.currentTarget.classList.add('active');
    }
    
    // Load subcategories based on selected category
    function loadSubcategories(categoryId) {
        if (!categoryId) {
            document.getElementById('subcategory').innerHTML = '<option value="">Select a category first</option>';
            return;
        }
        
        fetch('get_subcategories.php?category_id=' + categoryId)
            .then(response => response.json())
            .then(data => {
                const select = document.getElementById('subcategory');
                
                if (Array.isArray(data)) {
                    select.innerHTML = '<option value="">Select Subcategory *</option>';
                    
                    if (data.length > 0) {
                        data.forEach(subcategory => {
                            const option = document.createElement('option');
                            option.value = subcategory.id;
                            option.textContent = subcategory.name;
                            select.appendChild(option);
                        });
                    } else {
                        select.innerHTML = '<option value="">No subcategories available</option>';
                    }
                } else if (data.error) {
                    console.error('Error:', data.error);
                    select.innerHTML = '<option value="">Error loading subcategories</option>';
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                document.getElementById('subcategory').innerHTML = '<option value="">Error loading subcategories</option>';
            });
    }
    
    // Image preview
    function previewImages(input, previewId) {
        const preview = document.getElementById(previewId);
        preview.innerHTML = '';
        
        if (input.files) {
            for (let i = 0; i < Math.min(input.files.length, 10); i++) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'image-preview';
                    preview.appendChild(img);
                }
                reader.readAsDataURL(input.files[i]);
            }
        }
    }
    
    // Format price with KSh symbol
    function formatPrice(price) {
        return 'KSh ' + parseFloat(price).toLocaleString('en-KE', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }
    
    // Load products by short code
    function loadProducts() {
        const shortCode = document.getElementById('manage_code').value.trim();
        if (!shortCode) {
            alert('Please enter your short code');
            return;
        }
        
        // Show loading
        const resultsDiv = document.getElementById('manage-results');
        resultsDiv.innerHTML = '<div class="alert">Loading...</div>';
        
        fetch('get_user_data.php?short_code=' + encodeURIComponent(shortCode))
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    resultsDiv.innerHTML = `<div class="alert alert-error">${data.error}</div>`;
                    return;
                }
                
                let html = `
                    <div style="margin-bottom: 30px;">
                        <h4>Short Code: <strong>${shortCode}</strong></h4>
                        <p>Found ${data.products ? data.products.length : 0} product(s) and ${data.services ? data.services.length : 0} service(s)</p>
                    </div>
                `;
                
                if (data.products && data.products.length > 0) {
                    html += `
                        <div style="margin-bottom: 30px;">
                            <h4><i class="fas fa-box"></i> Your Products</h4>
                            <div class="product-grid">
                    `;
                    
                    data.products.forEach(product => {
                        const priceDisplay = formatPrice(product.price);
                        const priceTypeDisplay = product.price_type 
                            ? `<div class="product-price-type">(${product.price_type.replace('_', ' ')})</div>` 
                            : '';
                        const mainImage = product.images && product.images.length > 0 ? product.images[0] : 'https://via.placeholder.com/250x200?text=No+Image';
                        
                        html += `
                            <div class="product-card">
                                <img src="${mainImage}" 
                                     alt="${product.name}" class="product-image">
                                <div class="product-info">
                                    <div class="product-name">${product.name}</div>
                                    <div class="product-price">${priceDisplay}</div>
                                    ${priceTypeDisplay}
                                    <div class="product-status status-${product.status}">${product.status}</div>
                                    <button class="btn btn-sm" onclick="openProductModal('${shortCode}', ${product.id})">
                                        <i class="fas fa-edit"></i> Manage
                                    </button>
                                </div>
                            </div>
                        `;
                    });
                    
                    html += `
                            </div>
                        </div>
                    `;
                }
                
                if (data.services && data.services.length > 0) {
                    html += `
                        <div>
                            <h4><i class="fas fa-briefcase"></i> Your Services</h4>
                            <div class="product-grid">
                    `;
                    
                    data.services.forEach(service => {
                        html += `
                            <div class="product-card">
                                <div style="background: #f5f5f5; height: 200px; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-briefcase" style="font-size: 60px; color: var(--primary-color);"></i>
                                </div>
                                <div class="product-info">
                                    <div class="product-name">${service.freelancer_name}</div>
                                    <div class="product-price">${service.profession}</div>
                                    <button class="btn btn-sm" onclick="openServiceModal('${shortCode}', ${service.id})">
                                        <i class="fas fa-edit"></i> Manage
                                    </button>
                                </div>
                            </div>
                        `;
                    });
                    
                    html += `
                            </div>
                        </div>
                    `;
                }
                
                if ((!data.products || data.products.length === 0) && (!data.services || data.services.length === 0)) {
                    html = `<div class="alert alert-error">No products or services found for this short code.</div>`;
                }
                
                resultsDiv.innerHTML = html;
            })
            .catch(error => {
                console.error('Error:', error);
                resultsDiv.innerHTML = `<div class="alert alert-error">Error loading data. Please try again.</div>`;
            });
    }
    
    // Open product management modal
    function openProductModal(shortCode, productId) {
        fetch('get_product_details.php?short_code=' + encodeURIComponent(shortCode) + '&product_id=' + productId)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    alert('Error: ' + data.error);
                    return;
                }
                
                let imagesHtml = '';
                if (data.images && data.images.length > 0) {
                    data.images.forEach(image => {
                        imagesHtml += `<img src="${image}" class="gallery-image" alt="Product image">`;
                    });
                }
                
                const priceDisplay = formatPrice(data.price);
                const priceTypeDisplay = data.price_type ? `(${data.price_type.replace('_', ' ')})` : '';
                const createdDate = data.created_at ? new Date(data.created_at).toLocaleDateString() : 'Unknown';
                
                const modalBody = document.getElementById('productModalBody');
                modalBody.innerHTML = `
                    <div style="margin-bottom: 20px;">
                        <h4>${data.name}</h4>
                        <p>${data.description || 'No description'}</p>
                        <div class="product-price">Price: ${priceDisplay} ${priceTypeDisplay}</div>
                        <div><strong>Seller:</strong> ${data.seller_name}</div>
                        <div><strong>WhatsApp:</strong> ${data.whatsapp_number}</div>
                        ${data.whatsapp_group_link ? `<div><strong>Group:</strong> <a href="${data.whatsapp_group_link}" target="_blank">Join Group</a></div>` : ''}
                        <div><strong>Uploaded:</strong> ${createdDate}</div>
                    </div>
                    
                    ${imagesHtml ? `<h5>Product Images:</h5><div class="image-gallery">${imagesHtml}</div>` : '<p>No images uploaded</p>'}
                    
                    <form method="POST" action="upload.php" onsubmit="return confirmAction()">
                        <input type="hidden" name="short_code" value="${shortCode}">
                        <input type="hidden" name="product_id" value="${productId}">
                        
                        <div class="form-group">
                            <label>Update Status</label>
                            <select name="status" class="status-select">
                                <option value="available" ${data.status === 'available' ? 'selected' : ''}>Available</option>
                                <option value="sold" ${data.status === 'sold' ? 'selected' : ''}>Sold</option>
                                <option value="unavailable" ${data.status === 'unavailable' ? 'selected' : ''}>Unavailable</option>
                            </select>
                        </div>
                        
                        <div style="display: flex; gap: 10px;">
                            <button type="submit" name="manage_action" value="update_status" class="btn">
                                <i class="fas fa-save"></i> Update Status
                            </button>
                            <button type="submit" name="manage_action" value="delete" class="btn btn-delete">
                                <i class="fas fa-trash"></i> Delete Product
                            </button>
                        </div>
                    </form>
                `;
                
                document.getElementById('productModal').style.display = 'flex';
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error loading product details. Please check console for details.');
            });
    }
    
    // Open service management modal
    function openServiceModal(shortCode, serviceId) {
        fetch('get_service_details.php?short_code=' + encodeURIComponent(shortCode) + '&service_id=' + serviceId)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    alert('Error: ' + data.error);
                    return;
                }
                
                let imagesHtml = '';
                if (data.images && data.images.length > 0) {
                    imagesHtml = '<div class="current-images">';
                    data.images.forEach(image => {
                        imagesHtml += `<img src="${image}" class="portfolio-image" alt="Portfolio image">`;
                    });
                    imagesHtml += '</div>';
                }
                
                const modalBody = document.getElementById('serviceModalBody');
                modalBody.innerHTML = `
                    <form method="POST" action="upload.php" enctype="multipart/form-data" onsubmit="return confirm('Save changes?')">
                        <input type="hidden" name="service_short_code" value="${shortCode}">
                        <input type="hidden" name="service_id" value="${serviceId}">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Freelancer Name *</label>
                                <input type="text" name="edit_freelancer_name" value="${data.freelancer_name || ''}" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Profession *</label>
                                <input type="text" name="edit_profession" value="${data.profession || ''}" class="form-control" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Description *</label>
                            <textarea name="edit_service_description" class="form-control" rows="4" required>${data.description || ''}</textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>WhatsApp Number *</label>
                            <input type="tel" name="edit_service_whatsapp" value="${data.whatsapp_number || ''}" class="form-control" required>
                        </div>
                        
                        <div class="portfolio-container">
                            <label>Current Portfolio Images</label>
                            ${imagesHtml || '<p>No images uploaded yet.</p>'}
                        </div>
                        
                        <div class="form-group">
                            <label>Add More Portfolio Images</label>
                            <div class="file-input-wrapper">
                                <button type="button" class="file-btn">
                                    <i class="fas fa-plus"></i> Add Images
                                </button>
                                <input type="file" name="additional_images[]" multiple accept="image/*">
                            </div>
                            <div class="hint">You can add more images to your portfolio</div>
                        </div>
                        
                        <div style="display: flex; gap: 10px; margin-top: 20px;">
                            <button type="submit" name="service_action" value="update_service" class="btn">
                                <i class="fas fa-save"></i> Update Service
                            </button>
                            <button type="submit" name="service_action" value="delete_service" class="btn btn-delete">
                                <i class="fas fa-trash"></i> Delete Service
                            </button>
                        </div>
                        
                        <div style="margin-top: 20px;">
                            <button type="submit" name="add_service_images" class="btn" style="background-color: var(--secondary-color);">
                                <i class="fas fa-images"></i> Add Images Only
                            </button>
                        </div>
                    </form>
                `;
                
                document.getElementById('serviceModal').style.display = 'flex';
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error loading service details. Please check console for details.');
            });
    }
    
    // Confirm action
    function confirmAction() {
        return confirm('Are you sure you want to perform this action?');
    }
    
    // Close modal
    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }
    
    // Close modals when clicking outside
    window.onclick = function(event) {
        if (event.target.classList.contains('modal-overlay')) {
            event.target.style.display = 'none';
        }
    }
    
    // Auto-format phone number
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
    });
    </script>
</body>
</html>