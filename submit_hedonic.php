<?php
include('db.php'); // Include the database connection file

// Get the form data
$name = $_POST['name'];
$institution_name = $_POST['institution_name'];
$rating = isset($_POST['rating']) ? $_POST['rating'] : null;  // Only one rating is allowed
$remarks = $_POST['remarks'] ?? '';
$date_submitted = $_POST['date_submitted'] ?? date('Y-m-d'); // fallback to today if missing
$p_id = $_POST['p_id'];
$request_no = $_POST['request_no'];

// Validate data
if ($name && $institution_name && $rating) {
    // Prepare SQL statement to update data in the hedonic table
    $stmt = $conn->prepare("UPDATE hedonic SET name = ?, institution_name = ?, rating = ?, remarks = ?, date_submitted = ? WHERE p_id = ? AND request_no = ?");
    $stmt->bind_param("sssssii", $name, $institution_name, $rating, $remarks, $date_submitted, $p_id, $request_no);

    if ($stmt->execute()) {
        // Redirect to analyst.php after successful update
        header("Location: public_dashboard.php");
        exit; // Ensure no further code is executed after the redirect
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
} else {
    echo "Please fill out all required fields.";
}

$conn->close();
?>
