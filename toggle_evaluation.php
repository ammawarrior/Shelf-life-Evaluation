<?php
include 'db.php';

if (isset($_GET['s_id'])) {
    $s_id = $_GET['s_id'];

    // Get current status
    $stmt = $conn->prepare("SELECT status FROM evaluation_requests WHERE s_id = ?");
    $stmt->bind_param("s", $s_id);
    $stmt->execute();
    $stmt->bind_result($current_status);
    $stmt->fetch();
    $stmt->close();

    $new_status = ($current_status === 'active') ? 'inactive' : 'active';

    $update = $conn->prepare("UPDATE evaluation_requests SET status = ? WHERE s_id = ?");
    $update->bind_param("ss", $new_status, $s_id);
    $update->execute();
    $update->close();
}

header("Location: analyst.php");
exit();
