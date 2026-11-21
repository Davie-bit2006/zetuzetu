<?php
require_once 'config.php';

header('Content-Type: application/json');

if (isset($_GET['category_id'])) {
    $category_id = mysqli_real_escape_string($conn, $_GET['category_id']);
    
    $sql = "SELECT * FROM subcategories WHERE category_id = '$category_id' ORDER BY name";
    $result = mysqli_query($conn, $sql);
    
    if ($result) {
        $subcategories = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $subcategories[] = [
                'id' => $row['id'],
                'name' => $row['name']
            ];
        }
        echo json_encode($subcategories);
    } else {
        echo json_encode(['error' => 'Database error: ' . mysqli_error($conn)]);
    }
} else {
    echo json_encode(['error' => 'No category ID provided']);
}
?>