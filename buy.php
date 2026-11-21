<?php
require_once 'config.php';

// Get product ID from URL
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($product_id <= 0) {
    header('Location: index.php');
    exit();
}

// Get product details
$product_query = "SELECT p.*, c.name as category_name 
                  FROM products p 
                  LEFT JOIN categories c ON p.category_id = c.id 
                  WHERE p.id = $product_id AND p.status = 'available'";
$product_result = mysqli_query($conn, $product_query);
$product = mysqli_fetch_assoc($product_result);

if (!$product) {
    header('Location: index.php');
    exit();
}

// Get product images
$images_query = "SELECT image_path FROM product_images WHERE product_id = $product_id";
$images_result = mysqli_query($conn, $images_query);
$images = [];
while ($image = mysqli_fetch_assoc($images_result)) {
    $images[] = $image['image_path'];
}
$main_image = !empty($images) ? $images[0] : 'https://via.placeholder.com/400x300?text=No+Image';

// Handle form submission
$errors = [];
$order_id = null;
$whatsapp_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = mysqli_real_escape_string($conn, trim($_POST['name'] ?? ''));
    $phone = mysqli_real_escape_string($conn, trim($_POST['phone'] ?? ''));
    $delivery_place = mysqli_real_escape_string($conn, trim($_POST['delivery_place'] ?? ''));
    $requirements = mysqli_real_escape_string($conn, trim($_POST['requirements'] ?? ''));
    
    // Validate inputs
    if (empty($name)) {
        $errors[] = "Please enter your name";
    }
    
    if (empty($phone)) {
        $errors[] = "Please enter your phone number";
    } elseif (!preg_match('/^[0-9]{10,15}$/', $phone)) {
        $errors[] = "Please enter a valid phone number";
    }
    
    if (empty($requirements)) {
        $errors[] = "Please enter your requirements (size, color, etc.)";
    }
    
    // If no errors, save order
    if (empty($errors)) {
        $order_query = "INSERT INTO orders (product_id, buyer_name, buyer_phone, delivery_place, requirements, status, created_at) 
                        VALUES ('$product_id', '$name', '$phone', '$delivery_place', '$requirements', 'pending', NOW())";
        
        if (mysqli_query($conn, $order_query)) {
            $order_id = mysqli_insert_id($conn);
            
            // Prepare WhatsApp message
            $whatsapp_message = urlencode("Hello! I have placed an order on Zetumarket:\n\n" .
                "📦 Product: " . $product['name'] . "\n" .
                "💰 Price: KSh " . number_format($product['price'], 2) . "\n" .
                "👤 Buyer: " . $name . "\n" .
                "📱 Phone: " . $phone . "\n" .
                "📍 Delivery: " . ($delivery_place ? $delivery_place : "To be arranged") . "\n" .
                "📝 Requirements: " . $requirements . "\n\n" .
                "🆔 Order ID: #ORD" . str_pad($order_id, 6, '0', STR_PAD_LEFT) . "\n" .
                "💳 I will pay via M-Pesa Till Number 6713329\n" .
                "✅ Please confirm my order.");
        } else {
            $errors[] = "Failed to place order. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buy <?php echo htmlspecialchars($product['name']); ?> - Zetumarket</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #FF6D00;
            --primary-dark: #E65100;
            --primary-light: #FF9E40;
            --secondary-color: #333333;
            --light-bg: #FFF8F2;
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
            background-color: var(--light-bg);
            color: var(--text-color);
            line-height: 1.6;
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--primary-light);
        }
        
        .header h1 {
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        
        .header p {
            color: var(--text-light);
            font-size: 18px;
        }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 20px;
            padding: 10px 20px;
            border: 2px solid var(--primary-color);
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .back-btn:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        .container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-bottom: 40px;
        }
        
        @media (max-width: 768px) {
            .container {
                grid-template-columns: 1fr;
            }
        }
        
        .product-details {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .product-image {
            width: 100%;
            height: 300px;
            object-fit: cover;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid var(--border-color);
        }
        
        .product-name {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 15px;
            color: var(--secondary-color);
        }
        
        .product-price {
            font-size: 28px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 20px;
        }
        
        .product-description {
            color: var(--text-light);
            line-height: 1.8;
            margin-bottom: 25px;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 8px;
            border-left: 4px solid var(--primary-color);
        }
        
        .product-category {
            display: inline-block;
            background-color: var(--primary-light);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 14px;
            margin-bottom: 20px;
        }
        
        .order-form {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .form-title {
            font-size: 22px;
            color: var(--secondary-color);
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--primary-light);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--secondary-color);
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(255, 109, 0, 0.1);
        }
        
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .submit-btn {
            width: 100%;
            padding: 15px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .submit-btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 109, 0, 0.3);
        }
        
        .mpesa-guide {
            background: #f0f7ff;
            border-radius: 10px;
            padding: 25px;
            margin-top: 30px;
            border-left: 4px solid #00A859;
        }
        
        .mpesa-title {
            color: #00A859;
            font-size: 20px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .mpesa-steps {
            list-style-type: none;
            padding-left: 0;
        }
        
        .mpesa-steps li {
            margin-bottom: 15px;
            padding-left: 30px;
            position: relative;
            color: var(--text-color);
        }
        
        .mpesa-steps li:before {
            content: counter(step);
            counter-increment: step;
            position: absolute;
            left: 0;
            top: 0;
            background: #00A859;
            color: white;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            text-align: center;
            line-height: 22px;
            font-size: 14px;
            font-weight: bold;
        }
        
        .mpesa-steps {
            counter-reset: step;
        }
        
        .till-number {
            background: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            margin: 20px 0;
            border: 2px dashed #00A859;
        }
        
        .till-number h3 {
            color: #00A859;
            margin-bottom: 10px;
        }
        
        .till-number .number {
            font-size: 32px;
            font-weight: 700;
            color: var(--text-color);
            letter-spacing: 2px;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-weight: 500;
        }
        
        .alert.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert.info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        /* WhatsApp Modal Styles - UPDATED WITH ORANGE COLORS */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .modal {
            background: white;
            border-radius: 15px;
            max-width: 500px;
            width: 100%;
            animation: modalFadeIn 0.3s;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        @keyframes modalFadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .modal-header {
            padding: 25px 30px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
            text-align: center;
        }
        
        .modal-header h3 {
            margin: 0;
            font-size: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .modal-body {
            padding: 30px;
            text-align: center;
        }
        
        .modal-icon {
            font-size: 60px;
            color: var(--primary-color);
            margin-bottom: 20px;
        }
        
        .modal-message {
            font-size: 18px;
            margin-bottom: 25px;
            color: var(--text-color);
            line-height: 1.6;
        }
        
        .order-id {
            background-color: #FFF8F2;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            font-size: 20px;
            font-weight: 700;
            color: var(--primary-color);
            border: 2px solid var(--primary-light);
        }
        
        .whatsapp-btn-modal {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 15px 30px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 18px;
            transition: all 0.3s;
            width: 100%;
            border: none;
            cursor: pointer;
        }
        
        .whatsapp-btn-modal:hover {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-color) 100%);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 109, 0, 0.3);
        }
        
        .modal-footer {
            padding: 20px 30px;
            background-color: #FFF8F2;
            text-align: center;
            border-top: 1px solid var(--primary-light);
            color: var(--text-light);
            font-size: 14px;
        }
        
        .close-modal-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s;
        }
        
        .close-modal-btn:hover {
            background-color: rgba(255, 255, 255, 0.3);
            transform: rotate(90deg);
        }
        
        .requirements-note {
            font-size: 14px;
            color: var(--text-light);
            margin-top: 5px;
            font-style: italic;
        }
    </style>
</head>
<body>
    <a href="index.php" class="back-btn">
        <i class="fas fa-arrow-left"></i> Back to Products
    </a>
    
    <div class="header">
        <h1>Complete Your Purchase</h1>
        <p>Fill in your details to buy <?php echo htmlspecialchars($product['name']); ?></p>
    </div>
    
    <?php if (!empty($errors)): ?>
        <div class="alert error">
            <strong>Please fix the following errors:</strong>
            <ul style="margin-top: 10px; margin-left: 20px;">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <div class="container">
        <!-- Product Details Section -->
        <div class="product-details">
            <img src="<?php echo htmlspecialchars($main_image); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-image">
            
            <div class="product-category">
                <?php echo htmlspecialchars($product['category_name']); ?>
            </div>
            
            <h2 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h2>
            
            <div class="product-price">
                KSh <?php echo number_format($product['price'], 2); ?>
            </div>
            
            <div class="product-description">
                <?php echo nl2br(htmlspecialchars($product['description'])); ?>
            </div>
            
            <?php if ($product['price_type']): ?>
                <div style="margin-top: 20px;">
                    <strong>Price Type:</strong> 
                    <span style="background-color: #e8f4ff; padding: 5px 15px; border-radius: 20px; font-size: 14px;">
                        <?php echo htmlspecialchars(str_replace('_', ' ', $product['price_type'])); ?>
                    </span>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Order Form Section -->
        <div class="order-form">
            <h2 class="form-title">Buyer Information</h2>
            
            <form method="POST" action="" id="orderForm">
                <div class="form-group">
                    <label for="name">Full Name *</label>
                    <input type="text" id="name" name="name" 
                           value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" 
                           placeholder="Enter your full name" required>
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone Number *</label>
                    <input type="tel" id="phone" name="phone" 
                           value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" 
                           placeholder="e.g., 0712345678" pattern="[0-9]{10,15}" required>
                    <small style="color: var(--text-light);">Format: 10-15 digits, no spaces or dashes</small>
                </div>
                
                <div class="form-group">
                    <label for="delivery_place">Place of Delivery (Optional)</label>
                    <input type="text" id="delivery_place" name="delivery_place" 
                           value="<?php echo htmlspecialchars($_POST['delivery_place'] ?? ''); ?>" 
                           placeholder="e.g., Roma, ODM, Nairobi CBD">
                    <small style="color: var(--text-light);">Leave blank if you'll pick up or arrange delivery later</small>
                </div>
                
                <div class="form-group">
                    <label for="requirements">Requirements *</label>
                    <textarea id="requirements" name="requirements" 
                              placeholder="Specify size, color, quantity, or any other requirements..." 
                              required><?php echo htmlspecialchars($_POST['requirements'] ?? ''); ?></textarea>
                    <div class="requirements-note">
                        Example: Size 42, Color Black, Quantity 1
                    </div>
                </div>
                
                <button type="submit" class="submit-btn">
                    <i class="fas fa-shopping-cart"></i> Place Order
                </button>
            </form>
            
            <!-- M-Pesa Payment Guide -->
            <div class="mpesa-guide">
                <h3 class="mpesa-title">
                    <i class="fas fa-mobile-alt"></i> M-Pesa Payment Instructions
                </h3>
                
                <div class="till-number">
                    <h3>M-Pesa Till Number</h3>
                    <div class="number">6713329</div>
                    <p>Amount: <strong>KSh <?php echo number_format($product['price'], 2); ?></strong></p>
                </div>
                
                <ol class="mpesa-steps">
                    <li>Go to <strong>SIM Toolkit</strong> on your phone</li>
                    <li>Select <strong>M-Pesa</strong></li>
                    <li>Choose <strong>Lipa na M-Pesa</strong></li>
                    <li>Select <strong>Buy Goods and Services</strong></li>
                    <li>Enter Till Number: <strong>6713329</strong></li>
                    <li>Enter Amount: <strong>KSh <?php echo number_format($product['price'], 2); ?></strong></li>
                    <li>Enter your M-Pesa PIN</li>
                    <li>Confirm payment</li>
                </ol>
                
                <div class="alert info">
                    <i class="fas fa-info-circle"></i> 
                    <strong>Important:</strong> Keep your M-Pesa confirmation message as proof of payment.
                </div>
            </div>
        </div>
    </div>
    
    <!-- WhatsApp Confirmation Modal -->
    <?php if ($order_id && !empty($whatsapp_message)): ?>
    <div id="whatsappModal" class="modal-overlay" style="display: flex;">
        <div class="modal">
            <button class="close-modal-btn" onclick="closeModal()">&times;</button>
            
            <div class="modal-header">
                <h3>
                    <i class="fas fa-shopping-cart"></i> Order Placed Successfully!
                </h3>
            </div>
            
            <div class="modal-body">
                <div class="modal-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                
                <div class="modal-message">
                    <p> <strong>Order Confirmed!</strong></p>
                    <p>Your order has been placed successfully!</p>
                    <p>Please confirm via WhatsApp and complete payment.</p>
                </div>
                
                <div class="order-id">
                    <i class="fas fa-receipt"></i> Order ID: #ORD<?php echo str_pad($order_id, 6, '0', STR_PAD_LEFT); ?>
                </div>
                
                <a href="https://wa.me/254750755500?text=<?php echo $whatsapp_message; ?>" 
                   class="whatsapp-btn-modal" target="_blank">
                    <i class="fab fa-whatsapp"></i> Confirm Order via WhatsApp
                </a>
                
                <div style="margin-top: 20px; font-size: 14px; color: var(--text-light);">
                    <i class="fas fa-info-circle" style="color: var(--primary-color);"></i>
                    After WhatsApp confirmation, pay via M-Pesa Till: <strong>6713329</strong>
                </div>
            </div>
            
            <div class="modal-footer">
                <p><i class="fas fa-lightbulb" style="color: var(--primary-color);"></i> 
                Click the button above to open WhatsApp and confirm your order</p>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <script>
    // Phone number formatting
    document.getElementById('phone').addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        e.target.value = value;
    });
    
    // Form validation
    const form = document.getElementById('orderForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            let valid = true;
            const phone = document.getElementById('phone').value;
            const name = document.getElementById('name').value;
            const requirements = document.getElementById('requirements').value;
            
            // Remove any existing error highlights
            document.querySelectorAll('.error-highlight').forEach(el => {
                el.classList.remove('error-highlight');
            });
            
            if (!name.trim()) {
                document.getElementById('name').classList.add('error-highlight');
                valid = false;
            }
            
            if (!phone.match(/^[0-9]{10,15}$/)) {
                document.getElementById('phone').classList.add('error-highlight');
                valid = false;
            }
            
            if (!requirements.trim()) {
                document.getElementById('requirements').classList.add('error-highlight');
                valid = false;
            }
            
            if (!valid) {
                e.preventDefault();
                alert('Please fill in all required fields correctly.');
            }
        });
    }
    
    // Close modal function
    function closeModal() {
        const modal = document.getElementById('whatsappModal');
        if (modal) {
            modal.style.display = 'none';
        }
    }
    
    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('whatsappModal');
        if (modal && event.target === modal) {
            closeModal();
        }
    }
    
    // Close modal with Escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeModal();
        }
    });
    
    // Add CSS for error highlighting
    const style = document.createElement('style');
    style.textContent = `
        .error-highlight {
            border-color: var(--error-color) !important;
            background-color: #fff8f8 !important;
        }
    `;
    document.head.appendChild(style);
    </script>
</body>
</html>