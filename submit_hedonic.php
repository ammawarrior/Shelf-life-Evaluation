<?php
include('db.php'); // Include the database connection file

// Prevent any output before headers
ob_start();

// Set header to return JSON response
header('Content-Type: application/json');

// Initialize response array
$response = array(
    'success' => false,
    'message' => ''
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $p_id = $_POST['p_id'];
    $request_no = $_POST['request_no'];
    $name = $_POST['name'];
    $institution_name = $_POST['institution_name'];
    $rating = $_POST['rating'];
    $remarks = isset($_POST['remarks']) ? $_POST['remarks'] : '';
    $date_submitted = $_POST['date_submitted'];

    // Validate required fields
    if (empty($p_id) || empty($request_no) || empty($name) || empty($institution_name) || empty($rating)) {
        $response['message'] = 'All required fields must be filled out.';
        echo json_encode($response);
        exit();
    }

    // Check if evaluation is already submitted
    $check_stmt = $conn->prepare("SELECT date_submitted FROM hedonic WHERE BINARY request_no = ? AND p_id = ?");
    $check_stmt->bind_param("si", $request_no, $p_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $check_row = $check_result->fetch_assoc();

    if ($check_row && $check_row['date_submitted'] !== null) {
        $response['message'] = 'You have already submitted your evaluation for this request.';
        echo json_encode($response);
        exit();
    }

    // Update the hedonic table with the evaluation data
    $update_stmt = $conn->prepare("UPDATE hedonic SET 
        institution_name = ?,
        rating = ?,
        remarks = ?,
        date_submitted = ?
        WHERE BINARY request_no = ? AND p_id = ?");
    
    $update_stmt->bind_param("sissis", 
        $institution_name,
        $rating,
        $remarks,
        $date_submitted,
        $request_no,
        $p_id
    );

    if ($update_stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Evaluation submitted successfully!';
    } else {
        $response['message'] = 'Error submitting evaluation: ' . $conn->error;
    }
} else {
    $response['message'] = 'Invalid request method.';
}

// Clear any output buffers
ob_end_clean();

// Send JSON response
echo json_encode($response);
exit();
?>
