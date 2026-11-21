<?php
require_once 'config.php';

if (isset($_GET['category_id'])) {
    $category_id = mysqli_real_escape_string($conn, $_GET['category_id']);
    
    $sql = "SELECT * FROM subcategories WHERE category_id = '$category_id' ORDER BY name";
    $result = mysqli_query($conn, $sql);
    
    $subcategories = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $subcategories[] = array(
            'id' => $row['id'],
            'name' => $row['name']
        );
    }
    
    header('Content-Type: application/json');
    echo json_encode($subcategories);
}
?>