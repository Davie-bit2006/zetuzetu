<?php
require_once 'config.php';

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin.php");
    exit();
}

// Handle login
$login_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    
    // Default credentials
    if ($username === 'admin' && $password === 'admin123') {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = 'admin';
        header("Location: admin.php");
        exit();
    } else {
        $login_error = 'Invalid username or password';
    }
}

// Handle category and subcategory operations
if (isLoggedIn()) {
    // Add Category
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
        $name = mysqli_real_escape_string($conn, $_POST['category_name']);
        $description = mysqli_real_escape_string($conn, $_POST['category_description']);
        
        $sql = "INSERT INTO categories (name, description) VALUES ('$name', '$description')";
        if (mysqli_query($conn, $sql)) {
            $success_message = 'Category added successfully!';
        } else {
            $error_message = 'Error adding category: ' . mysqli_error($conn);
        }
    }
    
    // Add Subcategory
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_subcategory'])) {
        $category_id = mysqli_real_escape_string($conn, $_POST['category_id']);
        $name = mysqli_real_escape_string($conn, $_POST['subcategory_name']);
        $description = mysqli_real_escape_string($conn, $_POST['subcategory_description']);
        
        $sql = "INSERT INTO subcategories (category_id, name, description) 
                VALUES ('$category_id', '$name', '$description')";
        if (mysqli_query($conn, $sql)) {
            $success_message = 'Subcategory added successfully!';
        } else {
            $error_message = 'Error adding subcategory: ' . mysqli_error($conn);
        }
    }
    
    // Delete Category
    if (isset($_GET['delete_category'])) {
        $id = mysqli_real_escape_string($conn, $_GET['delete_category']);
        $sql = "DELETE FROM categories WHERE id = '$id'";
        if (mysqli_query($conn, $sql)) {
            $success_message = 'Category deleted successfully!';
        } else {
            $error_message = 'Error deleting category: ' . mysqli_error($conn);
        }
    }
    
    // Delete Subcategory
    if (isset($_GET['delete_subcategory'])) {
        $id = mysqli_real_escape_string($conn, $_GET['delete_subcategory']);
        $sql = "DELETE FROM subcategories WHERE id = '$id'";
        if (mysqli_query($conn, $sql)) {
            $success_message = 'Subcategory deleted successfully!';
        } else {
            $error_message = 'Error deleting subcategory: ' . mysqli_error($conn);
        }
    }
    
    // Delete Product
    if (isset($_GET['delete_product'])) {
        $id = mysqli_real_escape_string($conn, $_GET['delete_product']);
        $sql = "DELETE FROM products WHERE id = '$id'";
        if (mysqli_query($conn, $sql)) {
            $success_message = 'Product deleted successfully!';
        } else {
            $error_message = 'Error deleting product: ' . mysqli_error($conn);
        }
    }
    
    // Delete Service
    if (isset($_GET['delete_service'])) {
        $id = mysqli_real_escape_string($conn, $_GET['delete_service']);
        $sql = "DELETE FROM services WHERE id = '$id'";
        if (mysqli_query($conn, $sql)) {
            $success_message = 'Service deleted successfully!';
        } else {
            $error_message = 'Error deleting service: ' . mysqli_error($conn);
        }
    }
    
    // Handle Order Status Update
    if (isset($_GET['update_order_status'])) {
        $order_id = mysqli_real_escape_string($conn, $_GET['update_order_status']);
        $status = mysqli_real_escape_string($conn, $_GET['status']);
        
        $sql = "UPDATE orders SET status = '$status' WHERE id = '$order_id'";
        if (mysqli_query($conn, $sql)) {
            $success_message = 'Order status updated successfully!';
        } else {
            $error_message = 'Error updating order status: ' . mysqli_error($conn);
        }
    }
    
    // Delete Order
    if (isset($_GET['delete_order'])) {
        $id = mysqli_real_escape_string($conn, $_GET['delete_order']);
        $sql = "DELETE FROM orders WHERE id = '$id'";
        if (mysqli_query($conn, $sql)) {
            $success_message = 'Order deleted successfully!';
        } else {
            $error_message = 'Error deleting order: ' . mysqli_error($conn);
        }
    }
    
    // Fetch all categories
    $categories_result = mysqli_query($conn, "SELECT * FROM categories ORDER BY created_at DESC");
    
    // Fetch all subcategories with category names
    $subcategories_result = mysqli_query($conn, "
        SELECT s.*, c.name as category_name 
        FROM subcategories s 
        JOIN categories c ON s.category_id = c.id 
        ORDER BY s.created_at DESC
    ");
    
    // Fetch all products with details
    $products_result = mysqli_query($conn, "
        SELECT p.*, c.name as category_name, sc.name as subcategory_name 
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN subcategories sc ON p.subcategory_id = sc.id
        ORDER BY p.created_at DESC
    ");
    
    // Fetch all services with details
    $services_result = mysqli_query($conn, "
        SELECT s.* 
        FROM services s
        ORDER BY s.created_at DESC
    ");
    
    // Fetch all orders with product details
    $orders_result = mysqli_query($conn, "
        SELECT o.*, p.name as product_name, p.price as product_price, 
               c.name as category_name, p.seller_name, p.whatsapp_number as seller_whatsapp
        FROM orders o
        JOIN products p ON o.product_id = p.id
        LEFT JOIN categories c ON p.category_id = c.id
        ORDER BY o.created_at DESC
    ");
    
    // Get order counts for stats
    $total_orders_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM orders");
    $total_orders = mysqli_fetch_assoc($total_orders_query)['count'];
    
    $pending_orders_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM orders WHERE status = 'pending'");
    $pending_orders = mysqli_fetch_assoc($pending_orders_query)['count'];
    
    $confirmed_orders_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM orders WHERE status = 'confirmed'");
    $confirmed_orders = mysqli_fetch_assoc($confirmed_orders_query)['count'];
    
    $completed_orders_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM orders WHERE status = 'completed'");
    $completed_orders = mysqli_fetch_assoc($completed_orders_query)['count'];
    
    $delivered_orders_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM orders WHERE status = 'delivered'");
    $delivered_orders = mysqli_fetch_assoc($delivered_orders_query)['count'];
    
    $cancelled_orders_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM orders WHERE status = 'cancelled'");
    $cancelled_orders = mysqli_fetch_assoc($cancelled_orders_query)['count'];
    
    // Get today's orders count
    $today = date('Y-m-d');
    $today_orders_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM orders WHERE DATE(created_at) = '$today'");
    $today_orders = mysqli_fetch_assoc($today_orders_query)['count'];
    
    // Get this month's orders count
    $month_start = date('Y-m-01');
    $month_orders_query = mysqli_query($conn, "SELECT COUNT(*) as count FROM orders WHERE DATE(created_at) >= '$month_start'");
    $month_orders = mysqli_fetch_assoc($month_orders_query)['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zetumarket Admin</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

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
            --info-color: #2196F3;
            --warning-color: #FF9800;
        }

        body {
            background-color: var(--light-bg);
            color: var(--text-color);
            line-height: 1.6;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Login Page Styles */
        .login-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, var(--primary-light) 0%, var(--primary-color) 100%);
        }

        .login-box {
            background: var(--white);
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }

        .login-logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-logo h1 {
            color: var(--primary-color);
            font-size: 32px;
            margin-bottom: 10px;
        }

        .login-logo p {
            color: var(--text-light);
            font-size: 14px;
        }

        .back-home {
            margin-top: 15px;
            text-align: center;
        }

        .back-home a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }

        .back-home a:hover {
            text-decoration: underline;
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
            width: 100%;
        }

        .btn:hover {
            background-color: var(--primary-dark);
        }

        .btn-secondary {
            background-color: var(--secondary-color);
        }

        .btn-secondary:hover {
            background-color: #222;
        }

        .btn-info {
            background-color: var(--info-color);
        }

        .btn-info:hover {
            background-color: #0b7dda;
        }

        .alert {
            padding: 12px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .alert-error {
            background-color: rgba(244, 67, 54, 0.1);
            border: 1px solid var(--error-color);
            color: var(--error-color);
        }

        .alert-success {
            background-color: rgba(76, 175, 80, 0.1);
            border: 1px solid var(--success-color);
            color: var(--success-color);
        }

        /* Admin Dashboard Styles */
        .header {
            background-color: var(--white);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
        }

        .logo h2 {
            color: var(--primary-color);
            font-size: 24px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logout-btn {
            background-color: var(--error-color);
            padding: 8px 15px;
            border-radius: 5px;
            color: white;
            text-decoration: none;
            font-size: 14px;
        }

        .logout-btn:hover {
            background-color: #D32F2F;
        }

        .main-content {
            padding: 30px 0;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .section-header h3 {
            color: var(--secondary-color);
            font-size: 24px;
        }

        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--white);
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            text-align: center;
        }

        .stat-card h4 {
            color: var(--text-light);
            font-size: 14px;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .stat-card .number {
            font-size: 36px;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .card {
            background-color: var(--white);
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 25px;
            margin-bottom: 30px;
        }

        .card-header {
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 15px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header h4 {
            color: var(--secondary-color);
            font-size: 20px;
        }

        .nav-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid var(--border-color);
            flex-wrap: wrap;
        }

        .tab-btn {
            padding: 10px 20px;
            background: none;
            border: none;
            border-bottom: 2px solid transparent;
            cursor: pointer;
            font-weight: 500;
            color: var(--text-light);
            transition: all 0.3s;
        }

        .tab-btn.active {
            color: var(--primary-color);
            border-bottom-color: var(--primary-color);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
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

        .table-responsive {
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th {
            background-color: var(--light-bg);
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: var(--text-color);
            border-bottom: 2px solid var(--border-color);
        }

        .data-table td {
            padding: 15px;
            border-bottom: 1px solid var(--border-color);
            vertical-align: top;
        }

        .data-table tr:hover {
            background-color: rgba(255, 109, 0, 0.05);
        }

        .actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 14px;
            width: auto;
        }

        .btn-edit {
            background-color: var(--info-color);
        }

        .btn-edit:hover {
            background-color: #0b7dda;
        }

        .btn-delete {
            background-color: var(--error-color);
        }

        .btn-delete:hover {
            background-color: #D32F2F;
        }

        .btn-success {
            background-color: var(--success-color);
        }

        .btn-success:hover {
            background-color: #388E3C;
        }

        .btn-warning {
            background-color: var(--warning-color);
        }

        .btn-warning:hover {
            background-color: #F57C00;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: var(--text-light);
        }

        .empty-state p {
            font-size: 18px;
        }

        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-success {
            background-color: rgba(76, 175, 80, 0.1);
            color: var(--success-color);
            border: 1px solid var(--success-color);
        }

        .badge-info {
            background-color: rgba(33, 150, 243, 0.1);
            color: var(--info-color);
            border: 1px solid var(--info-color);
        }

        .badge-warning {
            background-color: rgba(255, 152, 0, 0.1);
            color: var(--warning-color);
            border: 1px solid var(--warning-color);
        }

        .badge-primary {
            background-color: rgba(255, 109, 0, 0.1);
            color: var(--primary-color);
            border: 1px solid var(--primary-color);
        }

        .badge-error {
            background-color: rgba(244, 67, 54, 0.1);
            color: var(--error-color);
            border: 1px solid var(--error-color);
        }

        .badge-pending {
            background-color: rgba(255, 152, 0, 0.1);
            color: var(--warning-color);
            border: 1px solid var(--warning-color);
        }
        
        .badge-confirmed {
            background-color: rgba(33, 150, 243, 0.1);
            color: var(--info-color);
            border: 1px solid var(--info-color);
        }
        
        .badge-completed {
            background-color: rgba(139, 195, 74, 0.1);
            color: #8BC34A;
            border: 1px solid #8BC34A;
        }
        
        .badge-delivered {
            background-color: rgba(76, 175, 80, 0.1);
            color: var(--success-color);
            border: 1px solid var(--success-color);
        }
        
        .badge-cancelled {
            background-color: rgba(244, 67, 54, 0.1);
            color: var(--error-color);
            border: 1px solid var(--error-color);
        }

        .back-home-admin {
            margin-top: 20px;
            text-align: center;
        }

        .back-home-admin a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            padding: 10px 20px;
            background: var(--white);
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .back-home-admin a:hover {
            background: var(--primary-light);
            color: var(--white);
        }

        .order-details {
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 15px;
            margin-top: 10px;
            border-left: 4px solid var(--primary-color);
        }

        .order-details p {
            margin-bottom: 8px;
            line-height: 1.5;
        }

        .status-dropdown {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        .status-select {
            padding: 6px 12px;
            border-radius: 5px;
            border: 1px solid var(--border-color);
            background-color: white;
            font-size: 14px;
            min-width: 150px;
        }

        .order-id {
            font-family: monospace;
            font-weight: bold;
            color: var(--primary-color);
        }

        .order-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .summary-card {
            background: var(--white);
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .summary-card h5 {
            color: var(--text-light);
            font-size: 14px;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .summary-card .count {
            font-size: 32px;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 5px;
        }

        .whatsapp-link {
            color: #25D366;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .whatsapp-link:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
                gap: 15px;
            }
            
            .nav-tabs {
                overflow-x: auto;
                white-space: nowrap;
                padding-bottom: 5px;
            }
            
            .actions {
                flex-direction: column;
                gap: 5px;
            }
            
            .btn-sm {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php if (!isLoggedIn()): ?>
    <!-- Login Page -->
    <div class="login-container">
        <div class="login-box">
            <div class="login-logo">
                <h1><i class="fas fa-store"></i> Zetumarket</h1>
                <p>Admin Dashboard</p>
            </div>
            
            <?php if ($login_error): ?>
            <div class="alert alert-error">
                <?php echo $login_error; ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" class="form-control" placeholder="Enter username" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Enter password" required>
                </div>
                
                <button type="submit" name="login" class="btn">Login</button>
                
                <div class="back-home">
                    <a href="upload.php"><i class="fas fa-home"></i> Back to Home</a>
                </div>
            </form>
        </div>
    </div>
    
    <?php else: ?>
    <!-- Admin Dashboard -->
    <div class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <h2><i class="fas fa-store"></i> Zetumarket Admin</h2>
                </div>
                <div class="user-info">
                    <span>Welcome, <?php echo $_SESSION['admin_username']; ?></span>
                    <a href="?logout=true" class="logout-btn"></i> Logout</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="main-content">
        <div class="container">
            <!-- Messages -->
            <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <?php echo $success_message; ?>
            </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
            <div class="alert alert-error">
                <?php echo $error_message; ?>
            </div>
            <?php endif; ?>
            
            <!-- Stats Cards -->
            <div class="stats-cards">
                <?php
                // Count total products
                $total_products = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM products"));
                // Count total services
                $total_services = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM services"));
                ?>
                
                <div class="stat-card">
                    <h4>Total Products</h4>
                    <div class="number"><?php echo $total_products; ?></div>
                    <p>Items listed for sale</p>
                </div>
                
                <div class="stat-card">
                    <h4>Total Services</h4>
                    <div class="number"><?php echo $total_services; ?></div>
                    <p>Services offered</p>
                </div>
                
                <div class="stat-card">
                    <h4>Total Orders</h4>
                    <div class="number"><?php echo $total_orders; ?></div>
                    <p>All orders received</p>
                </div>
                
                <div class="stat-card">
                    <h4>Completed Orders</h4>
                    <div class="number"><?php echo $completed_orders; ?></div>
                    <p>Successfully completed</p>
                </div>
            </div>
            
            <!-- Orders Summary -->
            <div class="order-summary">
                <div class="summary-card">
                    <h5>Today's Orders</h5>
                    <div class="count"><?php echo $today_orders; ?></div>
                    <p>Orders placed today</p>
                </div>
                
                <div class="summary-card">
                    <h5>This Month</h5>
                    <div class="count"><?php echo $month_orders; ?></div>
                    <p>Orders this month</p>
                </div>
            </div>
            
            <!-- Navigation Tabs -->
            <div class="card">
                <div class="nav-tabs">
                    <button class="tab-btn active" data-tab="orders">Orders</button>
                    <button class="tab-btn" data-tab="products">All Products</button>
                    <button class="tab-btn" data-tab="services">All Services</button>
                    <button class="tab-btn" data-tab="categories">Categories</button>
                </div>
                
                <!-- Orders Tab -->
                <div class="tab-content active" id="orders">
                    <div class="card-header">
                        <h4>All Orders</h4>
                    </div>
                    <div class="table-responsive">
                        <?php if (mysqli_num_rows($orders_result) > 0): ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Product</th>
                                    <th>Buyer Details</th>
                                    <th>Requirements</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($order = mysqli_fetch_assoc($orders_result)): 
                                    $status_badge = [
                                        'pending' => 'badge-pending',
                                        'confirmed' => 'badge-confirmed',
                                        'completed' => 'badge-completed',
                                        'delivered' => 'badge-delivered',
                                        'cancelled' => 'badge-cancelled'
                                    ];
                                ?>
                                <tr>
                                    <td class="order-id">#ORD<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($order['product_name']); ?></strong><br>
                                        <small>Category: <?php echo htmlspecialchars($order['category_name']); ?></small>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($order['buyer_name']); ?></strong><br>
                                        <a href="tel:<?php echo $order['buyer_phone']; ?>" class="whatsapp-link">
                                            <i class="fas fa-phone"></i> <?php echo $order['buyer_phone']; ?>
                                        </a><br>
                                        <?php if ($order['delivery_place']): ?>
                                        <small>📍 <?php echo htmlspecialchars($order['delivery_place']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo nl2br(htmlspecialchars($order['requirements'])); ?></td>
                                    <td><strong>KSh <?php echo number_format($order['product_price'], 2); ?></strong></td>
                                    <td>
                                        <span class="badge <?php echo $status_badge[$order['status']] ?? 'badge-info'; ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?></td>
                                    <td class="actions">
                                        <div class="status-dropdown">
                                            <select class="status-select" onchange="updateOrderStatus(<?php echo $order['id']; ?>, this.value)">
                                                <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="confirmed" <?php echo $order['status'] == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                                <option value="completed" <?php echo $order['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                <option value="delivered" <?php echo $order['status'] == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                                <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                            </select>
                                            <a href="?delete_order=<?php echo $order['id']; ?>" 
                                               class="btn btn-sm btn-delete" 
                                               onclick="return confirm('Delete order #ORD<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?>?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                        <div class="empty-state">
                            <p>No orders found.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- All Products Tab -->
                <div class="tab-content" id="products">
                    <div class="card-header">
                        <h4>All Products</h4>
                    </div>
                    <div class="table-responsive">
                        <?php if (mysqli_num_rows($products_result) > 0): ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Name</th>
                                    <th>Price</th>
                                    <th>Category</th>
                                    <th>Seller</th>
                                    <th>WhatsApp</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                mysqli_data_seek($products_result, 0);
                                while ($product = mysqli_fetch_assoc($products_result)): ?>
                                <tr>
                                    <td><code><?php echo $product['short_code']; ?></code></td>
                                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                                    <td>KSh <?php echo number_format($product['price'], 2); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($product['category_name']); ?>
                                        <?php if ($product['subcategory_name']): ?>
                                        <br><small><?php echo htmlspecialchars($product['subcategory_name']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($product['seller_name']); ?></td>
                                    <td><?php echo $product['whatsapp_number']; ?></td>
                                    <td>
                                        <?php 
                                        $status_badge = [
                                            'available' => 'badge-success',
                                            'sold' => 'badge-warning',
                                            'unavailable' => 'badge-info'
                                        ];
                                        ?>
                                        <span class="badge <?php echo $status_badge[$product['status']] ?? 'badge-info'; ?>">
                                            <?php echo ucfirst($product['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($product['created_at'])); ?></td>
                                    <td class="actions">
                                        <a href="?delete_product=<?php echo $product['id']; ?>" 
                                           class="btn btn-sm btn-delete" 
                                           onclick="return confirm('Delete product: <?php echo addslashes($product['name']); ?>?')">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                        <div class="empty-state">
                            <p>No products found.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- All Services Tab -->
                <div class="tab-content" id="services">
                    <div class="card-header">
                        <h4>All Services</h4>
                    </div>
                    <div class="table-responsive">
                        <?php if (mysqli_num_rows($services_result) > 0): ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Freelancer</th>
                                    <th>Profession</th>
                                    <th>Description</th>
                                    <th>WhatsApp</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                mysqli_data_seek($services_result, 0);
                                while ($service = mysqli_fetch_assoc($services_result)): ?>
                                <tr>
                                    <td><code><?php echo $service['short_code']; ?></code></td>
                                    <td><?php echo htmlspecialchars($service['freelancer_name']); ?></td>
                                    <td><?php echo htmlspecialchars($service['profession']); ?></td>
                                    <td><?php echo substr(htmlspecialchars($service['description']), 0, 100); ?>...</td>
                                    <td><?php echo $service['whatsapp_number']; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($service['created_at'])); ?></td>
                                    <td class="actions">
                                        <a href="?delete_service=<?php echo $service['id']; ?>" 
                                           class="btn btn-sm btn-delete" 
                                           onclick="return confirm('Delete service by <?php echo addslashes($service['freelancer_name']); ?>?')">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                        <div class="empty-state">
                            <p>No services found.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Categories Tab -->
                <div class="tab-content" id="categories">
                    <!-- Add Category Section -->
                    <div class="card">
                        <div class="card-header">
                            <h4>Add New Category</h4>
                        </div>
                        <form method="POST" action="">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="category_name">Category Name</label>
                                    <input type="text" id="category_name" name="category_name" class="form-control" placeholder="Enter category name" required>
                                </div>
                                <div class="form-group">
                                    <label for="category_description">Description (Optional)</label>
                                    <input type="text" id="category_description" name="category_description" class="form-control" placeholder="Enter category description">
                                </div>
                            </div>
                            <button type="submit" name="add_category" class="btn">Add Category</button>
                        </form>
                    </div>
                    
                    <!-- Add Subcategory Section -->
                    <div class="card">
                        <div class="card-header">
                            <h4>Add New Subcategory</h4>
                        </div>
                        <form method="POST" action="">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="category_id">Parent Category</label>
                                    <select id="category_id" name="category_id" class="form-control" required>
                                        <option value="">Select a category</option>
                                        <?php 
                                        mysqli_data_seek($categories_result, 0);
                                        while ($category = mysqli_fetch_assoc($categories_result)): 
                                        ?>
                                        <option value="<?php echo $category['id']; ?>"><?php echo $category['name']; ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="subcategory_name">Subcategory Name</label>
                                    <input type="text" id="subcategory_name" name="subcategory_name" class="form-control" placeholder="Enter subcategory name" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="subcategory_description">Description (Optional)</label>
                                <input type="text" id="subcategory_description" name="subcategory_description" class="form-control" placeholder="Enter subcategory description">
                            </div>
                            <button type="submit" name="add_subcategory" class="btn">Add Subcategory</button>
                        </form>
                    </div>
                    
                    <!-- Categories List -->
                    <div class="card">
                        <div class="card-header">
                            <h4>Existing Categories</h4>
                        </div>
                        <div class="table-responsive">
                            <?php 
                            mysqli_data_seek($categories_result, 0);
                            if (mysqli_num_rows($categories_result) > 0): 
                            ?>
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Description</th>
                                        <th>Created At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($category = mysqli_fetch_assoc($categories_result)): ?>
                                    <tr>
                                        <td><?php echo $category['id']; ?></td>
                                        <td><?php echo $category['name']; ?></td>
                                        <td><?php echo $category['description'] ?: '-'; ?></td>
                                        <td><?php echo date('Y-m-d', strtotime($category['created_at'])); ?></td>
                                        <td class="actions">
                                            <a href="?delete_category=<?php echo $category['id']; ?>" 
                                               class="btn btn-sm btn-delete" 
                                               onclick="return confirm('Are you sure you want to delete this category? All subcategories will also be deleted.')">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                            <?php else: ?>
                            <div class="empty-state">
                                <p>No categories found. Add your first category above.</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Subcategories List -->
                    <div class="card">
                        <div class="card-header">
                            <h4>Existing Subcategories</h4>
                        </div>
                        <div class="table-responsive">
                            <?php 
                            mysqli_data_seek($subcategories_result, 0);
                            if (mysqli_num_rows($subcategories_result) > 0): ?>
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Parent Category</th>
                                        <th>Description</th>
                                        <th>Created At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($subcategory = mysqli_fetch_assoc($subcategories_result)): ?>
                                    <tr>
                                        <td><?php echo $subcategory['id']; ?></td>
                                        <td><?php echo $subcategory['name']; ?></td>
                                        <td><?php echo $subcategory['category_name']; ?></td>
                                        <td><?php echo $subcategory['description'] ?: '-'; ?></td>
                                        <td><?php echo date('Y-m-d', strtotime($subcategory['created_at'])); ?></td>
                                        <td class="actions">
                                            <a href="?delete_subcategory=<?php echo $subcategory['id']; ?>" 
                                               class="btn btn-sm btn-delete" 
                                               onclick="return confirm('Are you sure you want to delete this subcategory?')">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                            <?php else: ?>
                            <div class="empty-state">
                                <p>No subcategories found. Add your first subcategory above.</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Back to Home Button -->
            <div class="back-home-admin">
                <a href="upload.php"><i class="fas fa-arrow-left"></i> Back to Home Page</a>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <script>
    // Tab functionality
    document.addEventListener('DOMContentLoaded', function() {
        // Tab switching
        const tabBtns = document.querySelectorAll('.tab-btn');
        const tabContents = document.querySelectorAll('.tab-content');
        
        tabBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const tabId = this.getAttribute('data-tab');
                
                // Remove active class from all buttons and contents
                tabBtns.forEach(b => b.classList.remove('active'));
                tabContents.forEach(c => c.classList.remove('active'));
                
                // Add active class to current button and content
                this.classList.add('active');
                document.getElementById(tabId).classList.add('active');
            });
        });
        
        // Simple confirmation for deletes
        const deleteButtons = document.querySelectorAll('.btn-delete');
        deleteButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to delete this item?')) {
                    e.preventDefault();
                }
            });
        });
        
        // Clear success/error messages after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
    });
    
    // Update order status
    function updateOrderStatus(orderId, status) {
        if (confirm('Change order status to ' + status + '?')) {
            window.location.href = `?update_order_status=${orderId}&status=${status}`;
        } else {
            // Reset the dropdown to original value
            window.location.reload();
        }
    }
    </script>
</body>
</html>