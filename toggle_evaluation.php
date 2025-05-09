<?php
include 'db.php';

if (isset($_GET['request_no'])) {
    $request_no = $_GET['request_no'];

    // Get current status
    $stmt = $conn->prepare("SELECT status FROM evaluation_requests WHERE request_no = ?");
    $stmt->bind_param("s", $request_no);
    $stmt->execute();
    $stmt->bind_result($current_status);
    $stmt->fetch();
    $stmt->close();

    $new_status = ($current_status === 'active') ? 'inactive' : 'active';

    $update = $conn->prepare("UPDATE evaluation_requests SET status = ? WHERE request_no = ?");
    $update->bind_param("ss", $new_status, $request_no);
    $update->execute();
    $update->close();
}

header("Location: analyst.php");
exit();
